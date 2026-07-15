<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/ModuleRegistry.php';
require_once __DIR__ . '/ShopContext.php';
require_once __DIR__ . '/ShopSettings.php';
require_once __DIR__ . '/SubscriptionGate.php';

abstract class AppController
{
    protected function render(string $view, array $data = []): void
    {
        $this->startSession();

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }

        $currentUser = $data['currentUser'] ?? $this->currentUser();
        $shops = $data['shops'] ?? $this->shops();
        $activeShop = $data['activeShop'] ?? $this->activeShop($shops, $currentUser);
        $shopCategoryProfile = $data['shopCategoryProfile'] ?? $this->shopCategoryProfile($activeShop);
        $businessSettings = $data['businessSettings'] ?? $this->businessSettings((int) ($activeShop['id'] ?? 0));
        $subscriptionSummary = $data['subscriptionSummary'] ?? $this->subscriptionSummary((int) ($activeShop['id'] ?? 0));
        $shopAllowance = $data['shopAllowance'] ?? (new SubscriptionGate())->shopAllowanceForUser(
            (int) ($currentUser['id'] ?? 0),
            (int) ($activeShop['id'] ?? 0)
        );
        $enabledModules = $data['enabledModules'] ?? $this->enabledModules((int) ($activeShop['id'] ?? 0));
        $enabledModuleCodes = $data['enabledModuleCodes'] ?? array_values(array_map(
            static fn (array $module): string => (string) ($module['code'] ?? ''),
            is_array($enabledModules) ? $enabledModules : []
        ));

        $data = array_merge($data, [
            'currentUser' => $currentUser,
            'shops' => $shops,
            'activeShop' => $activeShop,
            'shopCategoryProfile' => $shopCategoryProfile,
            'businessSettings' => $businessSettings,
            'subscriptionSummary' => $subscriptionSummary,
            'shopAllowance' => $shopAllowance,
            'enabledModules' => $enabledModules,
            'enabledModuleCodes' => $enabledModuleCodes,
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
        return $this->shopContext($this->currentUser())->id();
    }

    protected function shops(): array
    {
        return $this->shopContext($this->currentUser())->shops();
    }

    protected function enabledModules(int $shopId): array
    {
        return (new ModuleRegistry())->enabledForShop($shopId);
    }

    protected function moduleEnabled(string $code, ?int $shopId = null): bool
    {
        return (new ModuleRegistry())->allows($shopId ?? $this->currentShopId(), $code);
    }

    protected function businessSettings(?int $shopId = null): array
    {
        return (new ShopSettings())->allForShop($shopId ?? $this->currentShopId());
    }

    protected function subscriptionSummary(?int $shopId = null): array
    {
        return (new SubscriptionGate())->summaryForShop($shopId ?? $this->currentShopId());
    }

    protected function shopCategoryProfile(array $shop): array
    {
        $slug = (string) ($shop['category_slug'] ?? 'boutiques');
        $base = [
            'slug' => $slug !== '' ? $slug : 'boutiques',
            'name' => (string) ($shop['category_name'] ?? 'Boutiques'),
            'description' => (string) ($shop['category_description'] ?? 'Commerce de detail general avec catalogue produits, caisse, stock, clients et rapports.'),
            'catalog_label' => 'Catalogue produits',
            'pos_label' => 'Caisse POS',
            'stock_label' => 'Stock et inventaire',
            'supply_label' => 'Approvisionnements',
            'customer_label' => 'Clients et credits',
            'activity_label' => 'Administration activite',
            'primary_unit' => 'Article',
            'spaces' => [
                ['label' => 'Catalogue', 'description' => 'Produits, categories, prix et references.', 'href' => '/products'],
                ['label' => 'Caisse', 'description' => 'Ventes, paiement, monnaie et historique.', 'href' => '/pos'],
                ['label' => 'Stock', 'description' => 'Quantites disponibles, seuils et mouvements.', 'href' => '/stock/movements'],
                ['label' => 'Approvisionnement', 'description' => 'Arrivages, fournisseurs et couts d achat.', 'href' => '/supplies'],
                ['label' => 'Finances', 'description' => 'Depenses, benefices disponibles et charges.', 'href' => '/finances'],
                ['label' => 'Rapports', 'description' => 'Ventes, stock, finances et exports.', 'href' => '/rapports/ventes'],
            ],
            'focus' => ['Catalogue fiable', 'Caisse rapide', 'Stock exact', 'Rapports exploitables'],
        ];

        $profiles = [
            'pharmacies' => [
                'catalog_label' => 'Medicaments',
                'activity_label' => 'Gestion pharmacie',
                'primary_unit' => 'Medicament',
                'spaces' => [
                    ['label' => 'Gestion pharmacie', 'description' => 'Dosages, formes, lots, ordonnances et alertes d expiration.', 'href' => '/pharmacie'],
                    ['label' => 'Medicaments', 'description' => 'Catalogue, prix, references et dates d expiration.', 'href' => '/products'],
                    ['label' => 'Caisse pharmacie', 'description' => 'Ventes controlees et historique des tickets.', 'href' => '/pos'],
                    ['label' => 'Stock sensible', 'description' => 'Niveaux, mouvements et seuils critiques.', 'href' => '/stock/movements'],
                    ['label' => 'Approvisionnements', 'description' => 'Arrivages, fournisseurs et lots entrants.', 'href' => '/supplies'],
                    ['label' => 'Rapports', 'description' => 'Ventes, stock et alertes d expiration.', 'href' => '/rapports/ventes'],
                ],
                'focus' => ['Lots et expirations', 'Stock sensible', 'Ventes controlees', 'Alertes de seuil'],
            ],
            'quincailleries' => [
                'catalog_label' => 'Articles quincaillerie',
                'primary_unit' => 'Article technique',
                'focus' => ['References solides', 'Stock par familles', 'Prix d achat', 'Fournisseurs'],
            ],
            'supermarches' => [
                'catalog_label' => 'Rayons et produits',
                'pos_label' => 'Caisse rapide',
                'primary_unit' => 'Produit rayon',
                'focus' => ['Rayons', 'Vente rapide', 'Inventaires frequents', 'Volumes'],
            ],
            'depots' => [
                'catalog_label' => 'Stocks depot',
                'stock_label' => 'Mouvements depot',
                'primary_unit' => 'Stock',
                'focus' => ['Entrees', 'Sorties', 'Inventaires', 'Tracabilite'],
            ],
            'papeteries' => [
                'catalog_label' => 'Articles papeterie',
                'primary_unit' => 'Fourniture',
                'focus' => ['Fournitures', 'Kits scolaires', 'Prix unitaires', 'Stock rapide'],
            ],
            'librairies' => [
                'catalog_label' => 'Livres et editions',
                'primary_unit' => 'Livre',
                'focus' => ['Titres', 'Auteurs', 'Editions', 'Stock scolaire'],
            ],
            'boulangeries' => [
                'catalog_label' => 'Produits boulangerie',
                'primary_unit' => 'Produit frais',
                'focus' => ['Production du jour', 'Invendus', 'Pertes', 'Caisse rapide'],
            ],
            'restaurants' => [
                'catalog_label' => 'Menu restaurant',
                'pos_label' => 'Caisse restaurant',
                'primary_unit' => 'Plat',
                'focus' => ['Menu', 'Boissons', 'Depenses cuisine', 'Recette journaliere'],
            ],
            'bars' => [
                'catalog_label' => 'Boissons et articles',
                'pos_label' => 'Caisse bar',
                'primary_unit' => 'Boisson',
                'focus' => ['Stock bouteilles', 'Consommations', 'Recettes', 'Pertes'],
            ],
            'hotels' => [
                'catalog_label' => 'Chambres et services',
                'pos_label' => 'Facturation hotel',
                'primary_unit' => 'Service',
                'focus' => ['Chambres', 'Services', 'Facturation', 'Suivi client'],
            ],
            'magasins-de-vetements' => [
                'catalog_label' => 'Articles vetements',
                'activity_label' => 'Gestion vetements',
                'primary_unit' => 'Article textile',
                'spaces' => [
                    ['label' => 'Gestion vetements', 'description' => 'Tailles, couleurs, marques, collections et variantes.', 'href' => '/vetements'],
                    ['label' => 'Articles textiles', 'description' => 'Catalogue, prix, references et categories.', 'href' => '/products'],
                    ['label' => 'Caisse boutique', 'description' => 'Ventes, retours de paiement et historique.', 'href' => '/pos'],
                    ['label' => 'Stock boutique', 'description' => 'Disponibilites par article et mouvements.', 'href' => '/stock/movements'],
                    ['label' => 'Approvisionnements', 'description' => 'Arrivages par fournisseurs et collections.', 'href' => '/supplies'],
                    ['label' => 'Rapports', 'description' => 'Suivi des ventes et performance catalogue.', 'href' => '/rapports/ventes'],
                ],
                'focus' => ['Tailles', 'Collections', 'Variantes', 'Stock boutique'],
            ],
            'magasins-d-electronique' => [
                'catalog_label' => 'Articles electroniques',
                'primary_unit' => 'Appareil',
                'focus' => ['References techniques', 'Accessoires', 'Garanties', 'Stock valeur'],
            ],
            'grossistes' => [
                'catalog_label' => 'Catalogue gros',
                'primary_unit' => 'Lot',
                'focus' => ['Lots', 'Prix volume', 'Clients pros', 'Grands stocks'],
            ],
            'distributeurs' => [
                'catalog_label' => 'Catalogue distribution',
                'stock_label' => 'Flux distribution',
                'primary_unit' => 'Article distribue',
                'focus' => ['Sorties', 'Clients', 'Reseau', 'Rapports'],
            ],
            'entreprises-commerciales' => [
                'catalog_label' => 'Offre commerciale',
                'primary_unit' => 'Article commercial',
                'focus' => ['Ventes', 'Achats', 'Finances', 'Pilotage'],
            ],
            'vendeur-forfait-mobile-unites' => [
                'catalog_label' => 'Forfaits et unites',
                'pos_label' => 'Vente unites',
                'stock_label' => 'Solde unites',
                'supply_label' => 'Recharge stock unites',
                'customer_label' => 'Clients mobile',
                'primary_unit' => 'Unite',
                'focus' => ['Forfaits', 'Unites', 'Recharges', 'Operations rapides'],
            ],
        ];

        return array_replace_recursive($base, $profiles[$base['slug']] ?? []);
    }

    protected function activeShop(array $shops, array $currentUser): array
    {
        return $this->shopContext($currentUser)->activeShop();
    }

    protected function shopContext(array $currentUser): ShopContext
    {
        return new ShopContext($currentUser);
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
