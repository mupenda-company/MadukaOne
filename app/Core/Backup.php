<?php

declare(strict_types=1);

final class Backup
{
    private array $database;
    private array $storage;

    public function __construct(?array $database = null, ?array $storage = null)
    {
        $this->database = $database ?? require dirname(__DIR__, 2) . '/config/database.php';
        $this->storage = $storage ?? require dirname(__DIR__, 2) . '/config/storage.php';
    }

    public function run(): array
    {
        $backupDir = $this->backupDirectory();
        $baseName = $this->backupBaseName();
        $sqlPath = $backupDir . DIRECTORY_SEPARATOR . $baseName . '.sql';
        $gzipPath = $sqlPath . '.gz';

        $this->dumpDatabase($sqlPath);
        $this->compressSqlFile($sqlPath, $gzipPath);
        $upload = $this->upload($gzipPath, basename($gzipPath));

        return [
            'success' => true,
            'disk' => $this->storage['backup_disk'],
            'local_path' => $gzipPath,
            'size' => filesize($gzipPath) ?: 0,
            'uploaded' => $upload,
        ];
    }

    private function dumpDatabase(string $sqlPath): void
    {
        $command = [
            $this->storage['mysqldump_path'] ?? 'mysqldump',
            '--host=' . $this->database['host'],
            '--port=' . $this->database['port'],
            '--user=' . $this->database['username'],
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--events',
            '--default-character-set=' . ($this->database['charset'] ?? 'utf8mb4'),
            $this->database['database'],
        ];

        $environment = getenv();
        $environment = is_array($environment) ? $environment : [];
        $environment['MYSQL_PWD'] = (string) $this->database['password'];

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['file', $sqlPath, 'wb'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes, dirname(__DIR__, 2), $environment);

        if (!is_resource($process)) {
            throw new RuntimeException('Impossible de lancer mysqldump.');
        }

        fclose($pipes[0]);
        $errorOutput = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            @unlink($sqlPath);
            throw new RuntimeException('mysqldump a echoue: ' . trim((string) $errorOutput));
        }

        if (!is_file($sqlPath) || filesize($sqlPath) === 0) {
            throw new RuntimeException('Le dump SQL genere est vide.');
        }
    }

    private function compressSqlFile(string $sqlPath, string $gzipPath): void
    {
        $input = fopen($sqlPath, 'rb');
        $output = gzopen($gzipPath, 'wb9');

        if ($input === false || $output === false) {
            throw new RuntimeException('Impossible de preparer la compression gzip.');
        }

        try {
            while (!feof($input)) {
                $chunk = fread($input, 1024 * 1024);

                if ($chunk === false) {
                    throw new RuntimeException('Erreur de lecture du dump SQL.');
                }

                gzwrite($output, $chunk);
            }
        } finally {
            fclose($input);
            gzclose($output);
            @unlink($sqlPath);
        }

        if (!is_file($gzipPath) || filesize($gzipPath) === 0) {
            throw new RuntimeException('La compression du dump a echoue.');
        }
    }

    private function upload(string $filePath, string $remoteName): array
    {
        return match ((string) $this->storage['backup_disk']) {
            'local' => ['driver' => 'local', 'status' => 'stored'],
            'sftp' => $this->uploadToSftp($filePath, $remoteName),
            's3' => $this->uploadToS3($filePath, $remoteName),
            default => throw new RuntimeException('Disque de backup non supporte.'),
        };
    }

    private function uploadToSftp(string $filePath, string $remoteName): array
    {
        if (!extension_loaded('ssh2')) {
            throw new RuntimeException('Extension PHP ssh2 requise pour le backup SFTP.');
        }

        $config = $this->storage['sftp'];
        $connection = ssh2_connect((string) $config['host'], (int) $config['port']);

        if ($connection === false) {
            throw new RuntimeException('Connexion SFTP impossible.');
        }

        $authenticated = false;

        if (!empty($config['private_key'])) {
            $authenticated = ssh2_auth_pubkey_file(
                $connection,
                (string) $config['username'],
                (string) $config['public_key'],
                (string) $config['private_key'],
                (string) ($config['passphrase'] ?? '')
            );
        } else {
            $authenticated = ssh2_auth_password($connection, (string) $config['username'], (string) $config['password']);
        }

        if (!$authenticated) {
            throw new RuntimeException('Authentification SFTP refusee.');
        }

        $sftp = ssh2_sftp($connection);

        if ($sftp === false) {
            throw new RuntimeException('Initialisation SFTP impossible.');
        }

        $remotePath = rtrim((string) $config['path'], '/') . '/' . $remoteName;
        $stream = fopen('ssh2.sftp://' . intval($sftp) . $remotePath, 'wb');
        $input = fopen($filePath, 'rb');

        if ($stream === false || $input === false) {
            throw new RuntimeException('Ouverture du flux SFTP impossible.');
        }

        try {
            stream_copy_to_stream($input, $stream);
        } finally {
            fclose($input);
            fclose($stream);
        }

        return ['driver' => 'sftp', 'status' => 'uploaded', 'remote_path' => $remotePath];
    }

    private function uploadToS3(string $filePath, string $remoteName): array
    {
        $config = $this->storage['s3'];
        $bucket = (string) $config['bucket'];
        $region = (string) $config['region'];
        $key = trim((string) $config['prefix'], '/') . '/' . $remoteName;
        $key = ltrim($key, '/');
        $host = $bucket . '.s3.' . $region . '.amazonaws.com';
        $url = 'https://' . $host . '/' . str_replace('%2F', '/', rawurlencode($key));
        $body = file_get_contents($filePath);

        if ($body === false) {
            throw new RuntimeException('Lecture du fichier backup impossible.');
        }

        $now = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');
        $payloadHash = hash('sha256', $body);
        $credentialScope = $date . '/' . $region . '/s3/aws4_request';
        $headers = [
            'host' => $host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date' => $now,
        ];

        $canonicalHeaders = '';

        foreach ($headers as $name => $value) {
            $canonicalHeaders .= $name . ':' . $value . "\n";
        }

        $signedHeaders = implode(';', array_keys($headers));
        $canonicalRequest = implode("\n", [
            'PUT',
            '/' . str_replace('%2F', '/', rawurlencode($key)),
            '',
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $now,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);
        $signature = hash_hmac('sha256', $stringToSign, $this->awsSigningKey((string) $config['secret_key'], $date, $region));
        $authorization = 'AWS4-HMAC-SHA256 Credential=' . $config['access_key'] . '/' . $credentialScope
            . ', SignedHeaders=' . $signedHeaders
            . ', Signature=' . $signature;

        $httpHeaders = [
            'Authorization: ' . $authorization,
            'Host: ' . $host,
            'X-Amz-Content-Sha256: ' . $payloadHash,
            'X-Amz-Date: ' . $now,
            'Content-Type: application/gzip',
        ];

        $context = stream_context_create([
            'http' => [
                'method' => 'PUT',
                'header' => implode("\r\n", $httpHeaders),
                'content' => $body,
                'timeout' => 60,
                'ignore_errors' => true,
            ],
        ]);

        $response = file_get_contents($url, false, $context);
        $status = $this->httpStatus($http_response_header ?? []);

        if ($response === false || $status < 200 || $status >= 300) {
            throw new RuntimeException('Upload S3 echoue avec le statut HTTP ' . $status . '.');
        }

        return ['driver' => 's3', 'status' => 'uploaded', 'bucket' => $bucket, 'key' => $key];
    }

    private function awsSigningKey(string $secretKey, string $date, string $region): string
    {
        $dateKey = hash_hmac('sha256', $date, 'AWS4' . $secretKey, true);
        $dateRegionKey = hash_hmac('sha256', $region, $dateKey, true);
        $dateRegionServiceKey = hash_hmac('sha256', 's3', $dateRegionKey, true);

        return hash_hmac('sha256', 'aws4_request', $dateRegionServiceKey, true);
    }

    private function httpStatus(array $headers): int
    {
        $statusLine = $headers[0] ?? '';

        if (preg_match('#HTTP/\S+\s+(\d{3})#', $statusLine, $matches) !== 1) {
            return 0;
        }

        return (int) $matches[1];
    }

    private function backupDirectory(): string
    {
        $directory = (string) $this->storage['backup_path'];

        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('Impossible de creer le repertoire de backup.');
        }

        if (!is_writable($directory)) {
            throw new RuntimeException('Repertoire de backup non accessible en ecriture.');
        }

        return realpath($directory) ?: $directory;
    }

    private function backupBaseName(): string
    {
        return sprintf(
            '%s_%s',
            preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $this->database['database']),
            date('Ymd_His')
        );
    }
}
