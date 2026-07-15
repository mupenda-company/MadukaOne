<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Models/User.php';

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
        self::sendNoStoreHeaders();

        if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
            self::redirect('/login');
        }

        self::ensureAuthenticatedUserIsFresh();
    }

    public static function guest(string $path, string $method, array $params = []): void
    {
        self::startSession();
        self::sendNoStoreHeaders();

        if (!empty($_SESSION['user']) && is_array($_SESSION['user'])) {
            if (self::isSaasAdmin()) {
                self::redirect('/saas-admin');
            }

            self::redirect(self::isAgent() ? '/pos' : '/dashboard');
        }
    }

    public static function adminOnly(string $path, string $method, array $params = []): void
    {
        self::auth($path, $method, $params);

        if (!self::isAdmin()) {
            self::denyAgent();
        }
    }

    public static function saasAdmin(string $path, string $method, array $params = []): void
    {
        self::auth($path, $method, $params);

        if (!self::isSaasAdmin()) {
            $_SESSION['flash_error'] = 'Acces refuse : cette administration est reservee aux super administrateurs Maduka One.';
            self::redirect('/dashboard', 403);
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

    private static function isSaasAdmin(): bool
    {
        self::startSession();

        $user = $_SESSION['user'] ?? null;

        if (!is_array($user)) {
            return false;
        }

        return self::isSaasAdminFromDatabase((int) ($user['id'] ?? 0));
    }

    private static function isSaasAdminFromDatabase(int $userId): bool
    {
        if ($userId < 1) {
            return false;
        }

        try {
            require_once __DIR__ . '/Database.php';

            $statement = Database::connection()->prepare(
                'SELECT users.role_id, users.shop_id, users.role_legacy, roles.nom AS role_name
                 FROM users
                 LEFT JOIN roles ON roles.id = users.role_id
                 WHERE users.id = :id AND users.actif = 1
                 LIMIT 1'
            );
            $statement->execute(['id' => $userId]);
            $user = $statement->fetch();

            if (!is_array($user)) {
                return false;
            }

            $roleName = strtolower(trim((string) ($user['role_name'] ?? '')));
            $roleName = str_replace(['-', ' '], '_', $roleName);

            return in_array($roleName, ['super_admin', 'super_administrateur'], true);
        } catch (Throwable) {
            return false;
        }
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

    private static function ensureAuthenticatedUserIsFresh(): void
    {
        $sessionUser = $_SESSION['user'] ?? null;

        if (!is_array($sessionUser)) {
            self::expireSessionAndRedirect('Votre session a expire. Veuillez vous reconnecter.');
        }

        $userId = (int) ($sessionUser['id'] ?? 0);

        if ($userId < 1) {
            self::expireSessionAndRedirect('Votre session est invalide. Veuillez vous reconnecter.');
        }

        try {
            $users = new User();
            $databaseUser = $users->findById($userId, null, false);
        } catch (Throwable) {
            self::expireSessionAndRedirect('Verification de session impossible. Veuillez vous reconnecter.');
        }

        if ($databaseUser === null || (int) ($databaseUser['actif'] ?? 0) !== 1) {
            self::expireSessionAndRedirect('Votre compte a ete desactive. Veuillez contacter votre administrateur.');
        }

        $databasePayload = $users->sessionPayload($databaseUser);

        if (
            self::nullableInt($sessionUser['role_id'] ?? null) !== self::nullableInt($databasePayload['role_id'] ?? null)
            || self::nullableInt($sessionUser['shop_id'] ?? null) !== self::nullableInt($databasePayload['shop_id'] ?? null)
            || strtolower((string) ($sessionUser['role'] ?? '')) !== strtolower((string) ($databasePayload['role'] ?? ''))
            || strtolower((string) ($sessionUser['role_legacy'] ?? '')) !== strtolower((string) ($databasePayload['role_legacy'] ?? ''))
        ) {
            self::expireSessionAndRedirect('Vos droits d acces ont change. Veuillez vous reconnecter.');
        }

        $_SESSION['user'] = $databasePayload;
        $_SESSION['shop_id'] = $databasePayload['shop_id'];
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }

    private static function expireSessionAndRedirect(string $message): never
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION['flash_error'] = $message;
        self::redirect('/login');
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

    private static function sendNoStoreHeaders(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    }
}
