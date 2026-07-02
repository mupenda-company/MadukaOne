<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';

class DashboardController
{
    public function index(array $params = []): void
    {
        $this->startSession();

        $shops = $this->shops();
        $currentUser = $this->currentUser();
        $activeShop = $this->activeShop($shops, $currentUser);

        $stats = [
            [
                'label' => 'Chiffre d affaires',
                'value' => '0,00 USD',
                'detail' => 'Ventes validées de la boutique',
                'tone' => 'teal',
            ],
            [
                'label' => 'Marge brute',
                'value' => '0,00 USD',
                'detail' => 'Ventes moins coût d achat',
                'tone' => 'blue',
            ],
            [
                'label' => 'Dépenses courantes',
                'value' => '0,00 USD',
                'detail' => 'Charges opérationnelles',
                'tone' => 'amber',
            ],
            [
                'label' => 'Bénéfice net',
                'value' => '0,00 USD',
                'detail' => 'Marge brute moins dépenses',
                'tone' => 'slate',
            ],
        ];

        $recentSignals = [
            ['label' => 'Alertes stock', 'value' => '0', 'hint' => 'Produits sous le seuil minimum'],
            ['label' => 'Ventes du jour', 'value' => '0', 'hint' => 'Tickets validés aujourd hui'],
            ['label' => 'Crédits clients', 'value' => '0,00 USD', 'hint' => 'Montant restant à encaisser'],
        ];

        $this->render('dashboard/index', [
            'pageTitle' => 'Tableau de bord admin',
            'pageEyebrow' => 'Pilotage commercial',
            'currentUser' => $currentUser,
            'shops' => $shops,
            'activeShop' => $activeShop,
            'stats' => $stats,
            'recentSignals' => $recentSignals,
            'activeMenu' => 'dashboard',
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

