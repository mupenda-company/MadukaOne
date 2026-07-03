<?php

declare(strict_types=1);

class ShopController
{
    public function create(array $params = []): void
    {
        require dirname(__DIR__) . '/Views/shops/create.php';
    }
}
