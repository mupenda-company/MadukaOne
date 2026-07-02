<?php

declare(strict_types=1);

final class Middleware
{
    private const ADMIN_PATTERNS = [
        '#^/products/(create|store|edit|update|delete)#',
        '#^/produits/(create|store|edit|update|delete)#',
        '#^/stock/(adjustments|adjust|store|update|delete)#',
        '#^/reports#',
        '#^/rapports#',
        '#^/finances#',
        '#^/expenses#',
        '#^/depenses#',
        '#^/users#',
        '#^/utilisateurs#',
        '#^/shops#',
        '#^/boutiques#',
        '#^/suppliers#',
        '#^/fournisseurs#',
        '#^/supplies#',
        '#^/approvisionnements#',
    ];

    public static function auth(string $path, string $method, array $params = []): void
    {
        self::startSession();

        if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
            self::redirect('/login');
        }
    }

    public static function guest(string $path, string $method, array $params = []): void
    {
        self::startSession();

        if (!empty($_SESSION['user']) && is_array($_SESSION['user'])) {
            self::redirect(self::isAgent() ? '/pos' : '/');
        }
    }

    public static function adminOnly(string $path, string $method, array $params = []): void
    {
        self::auth($path, $method, $params);

        if (!self::isAdmin()) {
            self::denyAgent();
        }
    }

    public static function blockAgentAdminAccess(string $path, string $method, array $params = []): void
    {
        self::startSession();

        if (!self::isAgent()) {
            return;
        }

        foreach (self::ADMIN_PATTERNS as $pattern) {
            if (preg_match($pattern, $path) === 1) {
                self::denyAgent();
            }
        }
    }

    private static function isAdmin(): bool
    {
        return in_array(self::currentRole(), ['admin', 'super_admin', 'gerant'], true);
    }

    private static function isAgent(): bool
    {
        return self::currentRole() === 'agent';
    }

    private static function currentRole(): ?string
    {
        self::startSession();

        $user = $_SESSION['user'] ?? null;

        if (!is_array($user)) {
            return null;
        }

        $role = $user['role'] ?? $user['role_legacy'] ?? null;

        return is_string($role) ? strtolower($role) : null;
    }

    private static function denyAgent(): never
    {
        $_SESSION['flash_error'] = 'Accès refusé : cette zone est réservée à l administrateur.';
        self::redirect('/pos', 403);
    }

    private static function redirect(string $path, int $statusCode = 302): never
    {
        if (!str_starts_with($path, 'http://') && !str_starts_with($path, 'https://') && !str_starts_with($path, '//')) {
            $basePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
            $basePath = ($basePath === '' || $basePath === '.') ? '' : $basePath;
            $path = $basePath . '/' . ltrim($path, '/');
        }

        http_response_code($statusCode);
        header('Location: ' . $path, true, $statusCode);
        exit;
    }

    private static function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true,
            ]);
        }
    }
}
