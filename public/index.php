<?php

declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');

$root = dirname(__DIR__);

require_once $root . '/app/Core/Middleware.php';
require_once $root . '/app/Core/Router.php';

$router = new Router();
$routes = require $root . '/routes/web.php';

foreach ($routes as $route) {
    [$method, $path, $action] = $route;
    $middlewares = $route[3] ?? [];
    $router->add($method, $path, $action, $middlewares);
}

$router->dispatch();

