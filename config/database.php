<?php

$env = strtolower((string) (getenv('APP_ENV') ?: 'local'));
$prefix = $env === 'production' ? 'PRODUCTION' : 'LOCAL';

$envValue = static function (string $key, string $default = '') use ($prefix): string {
    $specific = getenv('DB_' . $prefix . '_' . $key);

    if ($specific !== false && $specific !== '') {
        return (string) $specific;
    }

    $generic = getenv('DB_' . $key);

    if ($generic !== false) {
        return (string) $generic;
    }

    return $default;
};

return [
    'host' => $envValue('HOST', '127.0.0.1'),
    'port' => $envValue('PORT', '3306'),
    'database' => $envValue('NAME', 'shop_logistique'),
    'username' => $envValue('USER', 'root'),
    'password' => $envValue('PASS'),
    'charset' => 'utf8mb4',
];
