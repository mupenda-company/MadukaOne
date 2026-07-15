<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Models/Shop.php';

final class StorefrontController
{
    public function show(array $params = []): void
    {
        $slug = strtolower(trim((string) ($params['slug'] ?? '')));
        $shops = new Shop();
        $shop = $shops->findPublicBySlug($slug);

        if ($shop === null) {
            http_response_code(404);
            echo 'Boutique introuvable.';
            return;
        }

        $products = $shops->publicProducts((int) $shop['id']);
        $basePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
        $basePath = ($basePath === '' || $basePath === '.') ? '' : $basePath;

        require dirname(__DIR__) . '/Views/storefront/show.php';
    }
}
