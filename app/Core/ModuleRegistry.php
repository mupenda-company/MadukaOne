<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

final class ModuleRegistry
{
    public function allActive(): array
    {
        try {
            $statement = Database::connection()->query(
                'SELECT id, code, nom, description, categorie, actif
                 FROM saas_features
                 WHERE actif = 1
                 ORDER BY categorie ASC, nom ASC'
            );

            return $statement->fetchAll();
        } catch (Throwable) {
            return [];
        }
    }

    public function enabledForShop(int $shopId): array
    {
        if ($shopId < 1) {
            return [];
        }

        $features = [];

        foreach ($this->featuresFromSubscription($shopId) as $feature) {
            $features[(string) $feature['code']] = $feature + ['source' => 'plan'];
        }

        ksort($features);

        return array_values($features);
    }

    public function enabledCodesForShop(int $shopId): array
    {
        return array_values(array_map(
            static fn (array $feature): string => (string) ($feature['code'] ?? ''),
            $this->enabledForShop($shopId)
        ));
    }

    public function allows(int $shopId, string $code): bool
    {
        $code = strtolower(trim($code));

        if ($code === '') {
            return false;
        }

        return in_array($code, $this->enabledCodesForShop($shopId), true);
    }

    private function featuresFromSubscription(int $shopId): array
    {
        try {
            $statement = Database::connection()->prepare(
                'SELECT DISTINCT
                        features.id,
                        features.code,
                        features.nom,
                        features.description,
                        features.categorie
                 FROM shops
                 INNER JOIN saas_subscriptions subscriptions ON subscriptions.shop_id = shops.id
                 INNER JOIN saas_category_plans category_plans ON category_plans.category_id = shops.category_id
                    AND category_plans.plan_id = subscriptions.plan_id
                 INNER JOIN saas_plan_features plan_features ON plan_features.plan_id = subscriptions.plan_id
                 INNER JOIN saas_features features ON features.id = plan_features.feature_id
                    AND features.actif = 1
                 WHERE shops.id = :shop_id
                   AND shops.actif = 1
                   AND subscriptions.plan_id IS NOT NULL
                   AND subscriptions.statut IN ("trial", "active")
                   AND (subscriptions.date_fin IS NULL OR subscriptions.date_fin >= CURRENT_DATE)
                 ORDER BY features.categorie ASC, features.nom ASC'
            );
            $statement->execute(['shop_id' => $shopId]);

            return $statement->fetchAll();
        } catch (Throwable) {
            return [];
        }
    }

}
