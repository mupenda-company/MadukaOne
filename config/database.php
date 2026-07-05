<?php

$env = strtolower(Env::get('APP_ENV', 'local'));
$prefix = $env === 'production' ? 'PRODUCTION' : 'LOCAL';

$envValue = static function (string $key, string $default = '') use ($prefix): string {
    $specific = Env::get('DB_' . $prefix . '_' . $key, '');

    if ($specific !== '') {
        return $specific;
    }

    return Env::get('DB_' . $key, $default);
};

return [
    'host' => $envValue('HOST', 'localhost'),
    'port' => $envValue('PORT', '3306'),
    'database' => $envValue('NAME', ''),
    'username' => $envValue('USER', ''),
    'password' => $envValue('PASS', ''),
    'charset' => 'utf8mb4',
];
