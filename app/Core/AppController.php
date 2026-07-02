<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

abstract class AppController
{
    protected function render(string $view, array $data = []): void
    {
        $this->startSession();

        $currentUser = $data['currentUser'] ?? $this->currentUser();
        $shops = $data['shops'] ?? $this->shops();
        $activeShop = $data['activeShop'] ?? $this->activeShop($shops, $currentUser);

        $data = array_merge($data, [
            'currentUser' => $currentUser,
            'shops' => $shops,
            'activeShop' => $activeShop,
        ]);

        $basePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
        $basePath = ($basePath === '' || $basePath === '.') ? '' : $basePath;

        $asset = static function (string $path) use ($basePath): string {
            return htmlspecialchars($basePath . '/' . ltrim($path, '/'), ENT_QUOTES, 'UTF-8');
        };

        $url = static function (string $path, array $query = []) use ($basePath): string {
            $href = $basePath . '/' . ltrim($path, '/');

            if ($path === '/') {
                $href = $basePath === '' ? '/' : $basePath . '/';
            }

            if ($query !== []) {
                $href .= (str_contains($href, '?') ? '&' : '?') . http_build_query($query);
            }

            return htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
        };

        extract($data, EXTR_SKIP);

        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';
        $content = (string) ob_get_clean();

        require dirname(__DIR__) . '/Views/layouts/app.php';
    }

    protected function json(array $payload, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    protected function abort(int $statusCode, string $message): never
    {
        http_response_code($statusCode);
        echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        exit;
    }

    protected function redirect(string $path): never
    {
        $basePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
        $basePath = ($basePath === '' || $basePath === '.') ? '' : $basePath;

        header('Location: ' . $basePath . '/' . ltrim($path, '/'), true, 302);
        exit;
    }

    protected function flashSuccess(string $message): void
    {
        $this->startSession();
        $_SESSION['flash_success'] = $message;
    }

    protected function flashError(string $message): void
    {
        $this->startSession();
        $_SESSION['flash_error'] = $message;
    }

    protected function currentUser(): array
    {
        $user = $_SESSION['user'] ?? null;

        if (is_array($user)) {
            return $user;
        }

        return [
            'id' => null,
            'nom' => 'Administrateur',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'shop_id' => 1,
            'auth_provider' => 'local',
        ];
    }

    protected function currentUserId(): int
    {
        $userId = (int) ($this->currentUser()['id'] ?? 0);

        if ($userId > 0) {
            return $userId;
        }

        try {
            $statement = Database::connection()->query('SELECT id FROM users WHERE actif = 1 ORDER BY id ASC LIMIT 1');
            $id = (int) $statement->fetchColumn();

            if ($id > 0) {
                return $id;
            }
        } catch (Throwable) {
        }

        return 1;
    }

    protected function currentShopId(): int
    {
        $shops = $this->shops();
        $activeShop = $this->activeShop($shops, $this->currentUser());

        return (int) ($activeShop['id'] ?? 1);
    }

    protected function shops(): array
    {
        try {
            $statement = Database::connection()->query(
                'SELECT id, nom, adresse, telephone, actif FROM shops WHERE actif = 1 ORDER BY nom ASC'
            );
            $shops = $statement->fetchAll();

            if (is_array($shops) && $shops !== []) {
                return $shops;
            }
        } catch (Throwable) {
        }

        return [
            [
                'id' => 1,
                'nom' => 'Boutique Pilote - Centre Ville',
                'adresse' => 'Av. Principale No 10',
                'telephone' => '+243000000000',
                'actif' => 1,
            ],
        ];
    }

    protected function activeShop(array $shops, array $currentUser): array
    {
        $requestedShopId = filter_input(INPUT_GET, 'shop_id', FILTER_VALIDATE_INT);
        $sessionShopId = isset($_SESSION['current_shop_id']) ? (int) $_SESSION['current_shop_id'] : null;
        $preferredShopId = $requestedShopId ?: $sessionShopId ?: (int) ($currentUser['shop_id'] ?? 0);

        foreach ($shops as $shop) {
            if ((int) $shop['id'] === $preferredShopId) {
                $_SESSION['current_shop_id'] = (int) $shop['id'];
                return $shop;
            }
        }

        $_SESSION['current_shop_id'] = (int) $shops[0]['id'];

        return $shops[0];
    }

    protected function startSession(): void
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
