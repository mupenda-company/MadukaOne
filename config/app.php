<?php

$env = strtolower((string) (getenv('APP_ENV') ?: 'local'));
$prefix = $env === 'production' ? 'PRODUCTION' : 'LOCAL';

$envValue = static function (string $key, string $default = '') use ($prefix): string {
    $specific = getenv('APP_' . $prefix . '_' . $key);

    if ($specific !== false && $specific !== '') {
        return (string) $specific;
    }

    $generic = getenv('APP_' . $key);

    if ($generic !== false && $generic !== '') {
        return (string) $generic;
    }

    return $default;
};

return [
    'name' => getenv('APP_NAME') ?: 'Shop Logistique',
    'env' => $env,
    'url' => rtrim($envValue('URL', 'http://localhost/Shop_logistique/public'), '/'),
];
