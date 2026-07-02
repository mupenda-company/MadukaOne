<?php

return [
    'backup_disk' => getenv('BACKUP_DISK') ?: 'local',
    'backup_path' => getenv('BACKUP_PATH') ?: __DIR__ . '/../storage/backups',
    'mysqldump_path' => getenv('MYSQLDUMP_PATH') ?: 'mysqldump',

    'sftp' => [
        'host' => getenv('SFTP_HOST') ?: '',
        'port' => (int) (getenv('SFTP_PORT') ?: 22),
        'username' => getenv('SFTP_USERNAME') ?: '',
        'password' => getenv('SFTP_PASSWORD') ?: '',
        'public_key' => getenv('SFTP_PUBLIC_KEY') ?: '',
        'private_key' => getenv('SFTP_PRIVATE_KEY') ?: '',
        'passphrase' => getenv('SFTP_PASSPHRASE') ?: '',
        'path' => getenv('SFTP_PATH') ?: '/backups',
    ],

    's3' => [
        'bucket' => getenv('S3_BUCKET') ?: '',
        'region' => getenv('S3_REGION') ?: 'us-east-1',
        'access_key' => getenv('S3_ACCESS_KEY') ?: '',
        'secret_key' => getenv('S3_SECRET_KEY') ?: '',
        'prefix' => getenv('S3_PREFIX') ?: 'shop-logistique/backups',
    ],
];
