<?php

declare(strict_types=1);

final class Router
{
    /**
     * @var array<string, array<int, array{path:string, pattern:string, action:string|callable, middlewares:array<int, string|callable>}>>
     */
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, string|callable $action, array $middlewares = []): self
    {
        return $this->add('GET', $path, $action, $middlewares);
    }

    public function post(string $path, string|callable $action, array $middlewares = []): self
    {
        return $this->add('POST', $path, $action, $middlewares);
    }

    public function add(string $method, string $path, string|callable $action, array $middlewares = []): self
    {
        $method = strtoupper($method);
        $path = $this->normalizePath($path);

        if (!isset($this->routes[$method])) {
            $this->routes[$method] = [];
        }

        $this->routes[$method][] = [
            'path' => $path,
            'pattern' => $this->compilePattern($path),
            'action' => $action,
            'middlewares' => $middlewares,
        ];

        return $this;
    }

    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = $this->pathWithoutBase((string) parse_url($uri, PHP_URL_PATH));

        if (!isset($this->routes[$method])) {
            $this->abort(405, 'Method Not Allowed');
        }

        foreach ($this->routes[$method] as $route) {
            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            $params = array_filter(
                $matches,
                static fn (string|int $key): bool => is_string($key),
                ARRAY_FILTER_USE_KEY
            );

            $this->runMiddlewares($route['middlewares'], $path, $method, $params);
            $this->callAction($route['action'], $params);

            return;
        }

        $this->abort(404, 'Page Not Found');
    }

    private function runMiddlewares(array $middlewares, string $path, string $method, array $params): void
    {
        foreach ($middlewares as $middleware) {
            if (is_callable($middleware)) {
                $middleware($path, $method, $params);
                continue;
            }

            if (!is_string($middleware)) {
                throw new InvalidArgumentException('Invalid middleware definition.');
            }

            if (!method_exists(Middleware::class, $middleware)) {
                throw new RuntimeException("Middleware method [{$middleware}] not found.");
            }

            Middleware::{$middleware}($path, $method, $params);
        }
    }

    private function callAction(string|callable $action, array $params): void
    {
        if (is_callable($action)) {
            $action($params);
            return;
        }

        [$controllerName, $method] = array_pad(explode('@', $action, 2), 2, null);

        if ($controllerName === null || $method === null) {
            throw new InvalidArgumentException('Controller action must use Controller@method format.');
        }

        $controllerPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $controllerName);
        $controllerFile = dirname(__DIR__) . '/Controllers/' . $controllerPath . '.php';
        $className = basename($controllerPath);

        if (is_file($controllerFile)) {
            require_once $controllerFile;
        }

        if (!class_exists($className)) {
            throw new RuntimeException("Controller [{$controllerName}] not found.");
        }

        $controller = new $className();

        if (!method_exists($controller, $method)) {
            throw new RuntimeException("Action [{$controllerName}@{$method}] not found.");
        }

        $controller->{$method}($params);
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private function pathWithoutBase(string $path): string
    {
        $path = $this->normalizePath($path);
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $basePath = $this->normalizePath(dirname($scriptName));
        $scriptDirectory = trim($basePath, '/');

        if ($basePath !== '/' && $path === $basePath) {
            return '/';
        }

        if ($basePath !== '/' && str_starts_with($path, $basePath . '/')) {
            return $this->normalizePath(substr($path, strlen($basePath)));
        }

        if (str_contains($path, '/index.php/')) {
            return $this->normalizePath(substr($path, strpos($path, '/index.php/') + strlen('/index.php/')));
        }

        if ($scriptDirectory !== '' && str_contains($path, '/' . $scriptDirectory . '/')) {
            return $this->normalizePath(substr($path, strpos($path, '/' . $scriptDirectory . '/') + strlen('/' . $scriptDirectory . '/')));
        }

        if (str_contains($path, '/public/')) {
            return $this->normalizePath(substr($path, strpos($path, '/public/') + strlen('/public/')));
        }

        return $path;
    }

    private function compilePattern(string $path): string
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);

        if ($pattern === null) {
            throw new RuntimeException('Unable to compile route pattern.');
        }

        return '#^' . $pattern . '$#';
    }

    private function abort(int $statusCode, string $message): never
    {
        http_response_code($statusCode);
        echo $message;
        exit;
    }
}
