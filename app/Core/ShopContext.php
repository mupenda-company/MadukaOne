<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

final class ShopContext
{
    private array $currentUser;
    private ?array $shops = null;
    private ?array $activeShop = null;

    public function __construct(array $currentUser)
    {
        $this->currentUser = $currentUser;
    }

    public function id(): int
    {
        return (int) ($this->activeShop()['id'] ?? 1);
    }

    public function user(): array
    {
        return $this->currentUser;
    }

    public function shops(): array
    {
        if ($this->shops !== null) {
            return $this->shops;
        }

        try {
            $params = [];
            $where = 'shops.actif = 1';

            if (!$this->canAccessAllShops()) {
                $shopId = (int) ($this->currentUser['shop_id'] ?? 0);
                $userId = (int) ($this->currentUser['id'] ?? 0);

                if ($shopId > 0 && $userId > 0) {
                    $where .= ' AND (shops.id = :shop_id OR shops.owner_user_id = :owner_user_id)';
                    $params['shop_id'] = $shopId;
                    $params['owner_user_id'] = $userId;
                } elseif ($shopId > 0) {
                    $where .= ' AND shops.id = :shop_id';
                    $params['shop_id'] = $shopId;
                } else {
                    $where .= ' AND 1 = 0';
                }
            }

            $statement = Database::connection()->prepare(
                'SELECT shops.id,
                        shops.nom,
                        shops.slug,
                        shops.adresse,
                        shops.telephone,
                        shops.email,
                        shops.logo_url,
                        shops.devise_principale,
                        shops.taux_change_cdf,
                        shops.actif,
                        shops.category_id,
                        categories.nom AS category_name,
                        categories.slug AS category_slug,
                        categories.description AS category_description
                 FROM shops
                 LEFT JOIN shop_categories categories ON categories.id = shops.category_id
                 WHERE ' . $where . '
                 ORDER BY shops.nom ASC'
            );
            $statement->execute($params);
            $shops = $statement->fetchAll();

            if (is_array($shops) && $shops !== []) {
                $this->shops = $shops;
                return $this->shops;
            }
        } catch (Throwable) {
        }

        $this->shops = [$this->fallbackShop()];

        return $this->shops;
    }

    public function activeShop(): array
    {
        if ($this->activeShop !== null) {
            return $this->activeShop;
        }

        $shops = $this->shops();
        $userShopId = (int) ($this->currentUser['shop_id'] ?? 0);
        $requestedShopId = filter_input(INPUT_GET, 'shop_id', FILTER_VALIDATE_INT) ?: null;
        $sessionShopId = isset($_SESSION['current_shop_id']) ? (int) $_SESSION['current_shop_id'] : null;
        $preferredShopId = $this->canManageShops()
            ? ($requestedShopId ?: $sessionShopId ?: $userShopId)
            : $userShopId;

        foreach ($shops as $shop) {
            if ((int) ($shop['id'] ?? 0) === $preferredShopId) {
                $_SESSION['current_shop_id'] = (int) $shop['id'];
                $this->activeShop = $shop;
                return $this->activeShop;
            }
        }

        $_SESSION['current_shop_id'] = (int) ($shops[0]['id'] ?? 1);
        $this->activeShop = $shops[0];

        return $this->activeShop;
    }

    public function canAccessShop(int $shopId): bool
    {
        if ($shopId < 1) {
            return false;
        }

        foreach ($this->shops() as $shop) {
            if ((int) ($shop['id'] ?? 0) === $shopId) {
                return true;
            }
        }

        return false;
    }

    public function canManageShops(): bool
    {
        if (!empty($this->currentUser['is_saas_admin'])) {
            return true;
        }

        $role = strtolower(trim((string) ($this->currentUser['role'] ?? '')));
        $roleName = strtolower(trim((string) ($this->currentUser['role_name'] ?? '')));
        $roleName = str_replace(['-', ' '], '_', $roleName);

        return in_array($role, ['owner', 'gerant'], true)
            || in_array($roleName, ['proprietaire', 'propriétaire', 'owner', 'gerant', 'gérant', 'manager'], true);
    }

    private function canAccessAllShops(): bool
    {
        if (!empty($this->currentUser['is_saas_admin'])) {
            return true;
        }

        $roleName = strtolower(trim((string) ($this->currentUser['role_name'] ?? '')));
        $roleName = str_replace(['-', ' '], '_', $roleName);

        return in_array($roleName, ['super_admin', 'super_administrateur'], true);
    }

    private function fallbackShop(): array
    {
        $shopId = (int) ($this->currentUser['shop_id'] ?? 1);

        if ($shopId < 1 && !$this->canAccessAllShops()) {
            $shopId = 0;
        }

        return [
            'id' => $shopId > 0 ? $shopId : 0,
            'nom' => 'Boutique active',
            'slug' => null,
            'adresse' => null,
            'telephone' => null,
            'email' => null,
            'logo_url' => null,
            'devise_principale' => 'USD',
            'taux_change_cdf' => 2800,
            'actif' => 1,
            'category_id' => null,
            'category_name' => 'Boutiques',
            'category_slug' => 'boutiques',
            'category_description' => 'Commerce de detail general avec catalogue produits, caisse, stock, clients et rapports.',
        ];
    }
}
