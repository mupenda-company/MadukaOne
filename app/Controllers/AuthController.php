<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';

final class AuthController
{
    public function login(array $params = []): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $view = dirname(__DIR__) . '/Views/auth/login.php';

            if (is_file($view)) {
                require $view;
                return;
            }

            echo 'Login';
            return;
        }

        $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        $password = (string) ($_POST['password'] ?? '');

        if ($email === false || $password === '') {
            $this->flash('Email ou mot de passe invalide.');
            $this->redirect('/login');
        }

        $user = $this->findUserByEmail($email);

        if (
            $user === null
            || empty($user['password_hash'])
            || !password_verify($password, (string) $user['password_hash'])
        ) {
            $this->flash('Identifiants incorrects.');
            $this->redirect('/login');
        }

        $this->startAuthenticatedSession($user);
        $this->touchLastLogin((int) $user['id']);
        $this->redirectAfterLogin($user);
    }

    public function authenticate(array $params = []): void
    {
        $this->login($params);
    }

    public function googleCallback(array $params = []): void
    {
        $this->handleOAuthCallback(
            provider: 'google',
            idColumn: 'google_id',
            tokenEndpoint: 'https://oauth2.googleapis.com/token',
            jwksUrl: 'https://www.googleapis.com/oauth2/v3/certs',
            issuers: ['accounts.google.com', 'https://accounts.google.com'],
            clientIdEnv: 'GOOGLE_CLIENT_ID',
            clientSecretEnv: 'GOOGLE_CLIENT_SECRET',
            redirectUriEnv: 'GOOGLE_REDIRECT_URI'
        );
    }

    public function appleCallback(array $params = []): void
    {
        $this->handleOAuthCallback(
            provider: 'apple',
            idColumn: 'apple_id',
            tokenEndpoint: 'https://appleid.apple.com/auth/token',
            jwksUrl: 'https://appleid.apple.com/auth/keys',
            issuers: ['https://appleid.apple.com'],
            clientIdEnv: 'APPLE_CLIENT_ID',
            clientSecretEnv: 'APPLE_CLIENT_SECRET',
            redirectUriEnv: 'APPLE_REDIRECT_URI'
        );
    }

    public function logout(array $params = []): void
    {
        $this->startSession();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }

        session_destroy();
        $this->redirect('/login');
    }

    public function redirectToGoogle(array $params = []): void
    {
        $this->redirectToOAuthProvider(
            authorizationUrl: 'https://accounts.google.com/o/oauth2/v2/auth',
            clientIdEnv: 'GOOGLE_CLIENT_ID',
            redirectUriEnv: 'GOOGLE_REDIRECT_URI',
            scope: 'openid email profile'
        );
    }

    public function redirectToApple(array $params = []): void
    {
        $this->redirectToOAuthProvider(
            authorizationUrl: 'https://appleid.apple.com/auth/authorize',
            clientIdEnv: 'APPLE_CLIENT_ID',
            redirectUriEnv: 'APPLE_REDIRECT_URI',
            scope: 'openid email name',
            extra: ['response_mode' => 'form_post']
        );
    }

    private function handleOAuthCallback(
        string $provider,
        string $idColumn,
        string $tokenEndpoint,
        string $jwksUrl,
        array $issuers,
        string $clientIdEnv,
        string $clientSecretEnv,
        string $redirectUriEnv
    ): void {
        try {
            $this->validateOAuthState();
            $idToken = (string) ($_POST['id_token'] ?? $_GET['id_token'] ?? '');
            $code = (string) ($_POST['code'] ?? $_GET['code'] ?? '');

            if ($idToken === '' && $code !== '') {
                $idToken = $this->exchangeCodeForIdToken(
                    tokenEndpoint: $tokenEndpoint,
                    code: $code,
                    clientId: $this->requiredEnv($clientIdEnv),
                    clientSecret: $this->requiredEnv($clientSecretEnv),
                    redirectUri: $this->requiredEnv($redirectUriEnv)
                );
            }

            if ($idToken === '') {
                throw new RuntimeException('Aucun jeton OAuth reçu.');
            }

            $claims = $this->verifyIdToken(
                jwt: $idToken,
                jwksUrl: $jwksUrl,
                issuers: $issuers,
                audience: $this->requiredEnv($clientIdEnv)
            );

            $providerId = (string) ($claims['sub'] ?? '');
            $email = filter_var((string) ($claims['email'] ?? ''), FILTER_VALIDATE_EMAIL);

            if ($providerId === '' || $email === false) {
                throw new RuntimeException('Profil OAuth incomplet.');
            }

            $user = $this->findOAuthUser($idColumn, $providerId, $email);

            if ($user === null) {
                $this->flash('Compte introuvable. Demandez à l administrateur de lier votre compte ' . ucfirst($provider) . '.');
                $this->redirect('/login');
            }

            $this->linkProviderIfNeeded((int) $user['id'], $provider, $idColumn, $providerId, $claims);
            $user[$idColumn] = $providerId;
            $user['auth_provider'] = $provider;

            $this->startAuthenticatedSession($user);
            $this->touchLastLogin((int) $user['id']);
            $this->redirectAfterLogin($user);
        } catch (Throwable $exception) {
            $this->flash('Connexion ' . ucfirst($provider) . ' impossible.');
            $this->redirect('/login');
        }
    }

    private function findUserByEmail(string $email): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT users.*, roles.nom AS role_name
             FROM users
             LEFT JOIN roles ON roles.id = users.role_id
             WHERE users.email = :email AND users.actif = 1
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    private function findOAuthUser(string $idColumn, string $providerId, string $email): ?array
    {
        if (!in_array($idColumn, ['google_id', 'apple_id'], true)) {
            throw new InvalidArgumentException('OAuth column not allowed.');
        }

        $statement = Database::connection()->prepare(
            "SELECT users.*, roles.nom AS role_name
             FROM users
             LEFT JOIN roles ON roles.id = users.role_id
             WHERE users.actif = 1 AND (users.{$idColumn} = :provider_id OR users.email = :email)
             LIMIT 1"
        );
        $statement->execute([
            'provider_id' => $providerId,
            'email' => $email,
        ]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    private function startAuthenticatedSession(array $user): void
    {
        $this->startSession();
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'nom' => (string) $user['nom'],
            'email' => (string) $user['email'],
            'role' => $this->resolveRole($user),
            'role_id' => isset($user['role_id']) ? (int) $user['role_id'] : null,
            'role_legacy' => (string) ($user['role_legacy'] ?? 'agent'),
            'shop_id' => isset($user['shop_id']) ? (int) $user['shop_id'] : null,
            'auth_provider' => (string) ($user['auth_provider'] ?? 'local'),
        ];
    }

    private function resolveRole(array $user): string
    {
        $roleName = strtolower(trim((string) ($user['role_name'] ?? '')));

        return match ($roleName) {
            'super admin', 'super_admin', 'admin', 'administrateur' => 'admin',
            'gerant', 'gérant', 'manager' => 'gerant',
            'caissier', 'agent', 'vendeur' => 'agent',
            default => strtolower((string) ($user['role_legacy'] ?? 'agent')),
        };
    }

    private function redirectAfterLogin(array $user): never
    {
        $role = $this->resolveRole($user);
        $this->redirect($role === 'agent' ? '/pos' : '/dashboard');
    }

    private function touchLastLogin(int $userId): void
    {
        $statement = Database::connection()->prepare('UPDATE users SET derniere_connexion = NOW() WHERE id = :id');
        $statement->execute(['id' => $userId]);
    }

    private function linkProviderIfNeeded(int $userId, string $provider, string $idColumn, string $providerId, array $claims): void
    {
        $statement = Database::connection()->prepare(
            "UPDATE users
             SET {$idColumn} = COALESCE({$idColumn}, :provider_id),
                 auth_provider = :provider,
                 email_verified_at = CASE WHEN email_verified_at IS NULL THEN NOW() ELSE email_verified_at END,
                 avatar_url = COALESCE(avatar_url, :avatar_url)
             WHERE id = :id"
        );
        $statement->execute([
            'provider_id' => $providerId,
            'provider' => $provider,
            'avatar_url' => $claims['picture'] ?? null,
            'id' => $userId,
        ]);
    }

    private function exchangeCodeForIdToken(
        string $tokenEndpoint,
        string $code,
        string $clientId,
        string $clientSecret,
        string $redirectUri
    ): string {
        $payload = http_build_query([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 10,
            ],
        ]);

        $response = file_get_contents($tokenEndpoint, false, $context);

        if ($response === false) {
            throw new RuntimeException('OAuth token exchange failed.');
        }

        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data) || empty($data['id_token']) || !is_string($data['id_token'])) {
            throw new RuntimeException('OAuth id_token missing.');
        }

        return $data['id_token'];
    }

    private function verifyIdToken(string $jwt, string $jwksUrl, array $issuers, string $audience): array
    {
        [$header64, $payload64, $signature64] = array_pad(explode('.', $jwt), 3, '');

        if ($header64 === '' || $payload64 === '' || $signature64 === '') {
            throw new RuntimeException('Invalid JWT format.');
        }

        $header = json_decode($this->base64UrlDecode($header64), true, 512, JSON_THROW_ON_ERROR);
        $claims = json_decode($this->base64UrlDecode($payload64), true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($header) || !is_array($claims) || ($header['alg'] ?? '') !== 'RS256') {
            throw new RuntimeException('Unsupported OAuth token.');
        }

        if (!in_array((string) ($claims['iss'] ?? ''), $issuers, true)) {
            throw new RuntimeException('Invalid token issuer.');
        }

        if ((string) ($claims['aud'] ?? '') !== $audience) {
            throw new RuntimeException('Invalid token audience.');
        }

        if ((int) ($claims['exp'] ?? 0) < time()) {
            throw new RuntimeException('Expired token.');
        }

        $pem = $this->publicKeyForKid($jwksUrl, (string) ($header['kid'] ?? ''));
        $verified = openssl_verify(
            $header64 . '.' . $payload64,
            $this->base64UrlDecode($signature64),
            $pem,
            OPENSSL_ALGO_SHA256
        );

        if ($verified !== 1) {
            throw new RuntimeException('Invalid token signature.');
        }

        return $claims;
    }

    private function publicKeyForKid(string $jwksUrl, string $kid): string
    {
        if ($kid === '') {
            throw new RuntimeException('Missing token key id.');
        }

        $response = file_get_contents($jwksUrl);

        if ($response === false) {
            throw new RuntimeException('Unable to fetch OAuth public keys.');
        }

        $jwks = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        foreach (($jwks['keys'] ?? []) as $key) {
            if (($key['kid'] ?? '') === $kid && ($key['kty'] ?? '') === 'RSA') {
                return $this->jwkToPem($key);
            }
        }

        throw new RuntimeException('OAuth public key not found.');
    }

    private function jwkToPem(array $jwk): string
    {
        $modulus = $this->base64UrlDecode((string) $jwk['n']);
        $exponent = $this->base64UrlDecode((string) $jwk['e']);
        $rsaPublicKey = $this->asn1Sequence($this->asn1Integer($modulus) . $this->asn1Integer($exponent));
        $algorithm = $this->asn1Sequence($this->asn1ObjectIdentifier('1.2.840.113549.1.1.1') . "\x05\x00");
        $publicKey = $this->asn1Sequence($algorithm . "\x03" . $this->asn1Length(strlen($rsaPublicKey) + 1) . "\x00" . $rsaPublicKey);

        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($publicKey), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    private function asn1Integer(string $value): string
    {
        if (ord($value[0]) > 0x7f) {
            $value = "\x00" . $value;
        }

        return "\x02" . $this->asn1Length(strlen($value)) . $value;
    }

    private function asn1Sequence(string $value): string
    {
        return "\x30" . $this->asn1Length(strlen($value)) . $value;
    }

    private function asn1ObjectIdentifier(string $oid): string
    {
        $parts = array_map('intval', explode('.', $oid));
        $body = chr($parts[0] * 40 + $parts[1]);

        for ($i = 2, $count = count($parts); $i < $count; $i++) {
            $body .= $this->encodeOidPart($parts[$i]);
        }

        return "\x06" . $this->asn1Length(strlen($body)) . $body;
    }

    private function encodeOidPart(int $value): string
    {
        $result = chr($value & 0x7f);
        $value >>= 7;

        while ($value > 0) {
            $result = chr(($value & 0x7f) | 0x80) . $result;
            $value >>= 7;
        }

        return $result;
    }

    private function asn1Length(int $length): string
    {
        if ($length < 128) {
            return chr($length);
        }

        $bytes = '';

        while ($length > 0) {
            $bytes = chr($length & 0xff) . $bytes;
            $length >>= 8;
        }

        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    private function base64UrlDecode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4), true);

        if ($decoded === false) {
            throw new RuntimeException('Invalid base64url value.');
        }

        return $decoded;
    }

    private function redirectToOAuthProvider(
        string $authorizationUrl,
        string $clientIdEnv,
        string $redirectUriEnv,
        string $scope,
        array $extra = []
    ): void {
        $this->startSession();
        $state = bin2hex(random_bytes(32));
        $_SESSION['oauth_state'] = $state;

        $query = http_build_query(array_merge([
            'client_id' => $this->requiredEnv($clientIdEnv),
            'redirect_uri' => $this->requiredEnv($redirectUriEnv),
            'response_type' => 'code',
            'scope' => $scope,
            'state' => $state,
        ], $extra));

        $this->redirect($authorizationUrl . '?' . $query);
    }

    private function validateOAuthState(): void
    {
        $this->startSession();

        $expected = $_SESSION['oauth_state'] ?? null;
        $received = $_POST['state'] ?? $_GET['state'] ?? null;
        unset($_SESSION['oauth_state']);

        if (!is_string($expected) || !is_string($received) || !hash_equals($expected, $received)) {
            throw new RuntimeException('Invalid OAuth state.');
        }
    }

    private function requiredEnv(string $key): string
    {
        $value = getenv($key);

        if (!is_string($value) || trim($value) === '') {
            throw new RuntimeException("Missing environment variable {$key}.");
        }

        return trim($value);
    }

    private function flash(string $message): void
    {
        $this->startSession();
        $_SESSION['flash_error'] = $message;
    }

    private function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true,
            ]);
        }
    }

    private function redirect(string $path): never
    {
        if (!str_starts_with($path, 'http://') && !str_starts_with($path, 'https://') && !str_starts_with($path, '//')) {
            $basePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
            $basePath = ($basePath === '' || $basePath === '.') ? '' : $basePath;
            $path = $basePath . '/' . ltrim($path, '/');
        }

        header('Location: ' . $path, true, 302);
        exit;
    }
}
