<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Models/User.php';

class UserController
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function index(array $params = []): void
    {
        $this->startSession();

        $currentUser = $this->currentUser();
        $shops = $this->shops();
        $activeShop = $this->activeShop($shops, $currentUser);
        $users = $this->users->allForSuperAdmin(false);

        $this->render('users/index', [
            'pageTitle' => 'Utilisateurs',
            'currentUser' => $currentUser,
            'shops' => $shops,
            'activeShop' => $activeShop,
            'activeMenu' => 'users',
            'users' => $users,
            'userStats' => $this->userStats($users),
        ]);
    }

    public function profile(array $params = []): void
    {
        $this->startSession();

        $currentUser = $this->currentUser();
        $shops = $this->shops();
        $activeShop = $this->activeShop($shops, $currentUser);

        $this->render('users/profile', [
            'pageTitle' => 'Paramètres du profil',
            'currentUser' => $currentUser,
            'shops' => $shops,
            'activeShop' => $activeShop,
            'activeMenu' => 'profile',
        ]);
    }

    private function render(string $view, array $data = []): void
    {
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

    private function currentUser(): array
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

    private function shops(): array
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

    private function activeShop(array $shops, array $currentUser): array
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

    private function userStats(array $users): array
    {
        $stats = [
            'total' => count($users),
            'active' => 0,
            'inactive' => 0,
            'oauth' => 0,
            'shops' => [],
        ];

        foreach ($users as $user) {
            if ((int) ($user['actif'] ?? 0) === 1) {
                $stats['active']++;
            } else {
                $stats['inactive']++;
            }

            if (in_array((string) ($user['auth_provider'] ?? 'local'), ['google', 'apple'], true)) {
                $stats['oauth']++;
            }

            $shopName = trim((string) ($user['shop_name'] ?? ''));
            if ($shopName !== '') {
                $stats['shops'][$shopName] = true;
            }
        }

        $stats['shops'] = count($stats['shops']);

        return $stats;
    }

    private function startSession(): void
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

