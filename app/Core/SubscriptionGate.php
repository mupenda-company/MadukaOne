<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

final class SubscriptionGate
{
    public function currentForShop(int $shopId): ?array
    {
        if ($shopId < 1) {
            return null;
        }

        try {
            $statement = Database::connection()->prepare(
                'SELECT
                    subscriptions.id,
                    subscriptions.shop_id,
                    subscriptions.plan_id,
                    subscriptions.statut,
                    subscriptions.date_debut,
                    subscriptions.date_fin,
                    plans.nom AS plan_name,
                    plans.code AS plan_code,
                    plans.limite_boutiques,
                    plans.limite_utilisateurs,
                    plans.limite_produits
                 FROM saas_subscriptions subscriptions
                 LEFT JOIN saas_subscription_plans plans ON plans.id = subscriptions.plan_id
                 WHERE subscriptions.shop_id = :shop_id
                 LIMIT 1'
            );
            $statement->execute(['shop_id' => $shopId]);
            $subscription = $statement->fetch();

            return is_array($subscription) ? $subscription : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function usageForShop(int $shopId): array
    {
        return [
            'users' => $this->countRows('users', $shopId, 'actif = 1'),
            'products' => $this->countRows('products', $shopId, 'actif = 1'),
        ];
    }

    public function summaryForShop(int $shopId): array
    {
        $subscription = $this->currentForShop($shopId);
        $usage = $this->usageForShop($shopId);

        return [
            'subscription' => $subscription,
            'usage' => $usage,
            'limits' => [
                'users' => $this->limitValue($subscription['limite_utilisateurs'] ?? null),
                'products' => $this->limitValue($subscription['limite_produits'] ?? null),
                'shops' => $this->limitValue($subscription['limite_boutiques'] ?? null),
            ],
            'active' => $this->subscriptionIsActive($subscription),
        ];
    }

    public function canCreateUser(int $shopId, int $additional = 1): bool
    {
        return $this->canUse($shopId, 'users', 'limite_utilisateurs', $additional);
    }

    public function canCreateProduct(int $shopId, int $additional = 1): bool
    {
        return $this->canUse($shopId, 'products', 'limite_produits', $additional);
    }

    public function shopAllowanceForUser(int $userId, int $activeShopId): array
    {
        $subscription = $this->currentForShop($activeShopId);
        $limit = $this->limitValue($subscription['limite_boutiques'] ?? null);
        $used = $this->ownedShopsCount($userId, $activeShopId);
        $remaining = $limit === null ? null : max(0, $limit - $used);

        return [
            'subscription' => $subscription,
            'active' => $this->subscriptionIsActive($subscription),
            'limit' => $limit,
            'used' => $used,
            'remaining' => $remaining,
            'can_create' => $this->subscriptionIsActive($subscription) && ($limit === null || $remaining > 0),
            'next_plan' => $this->nextPlan($limit),
        ];
    }

    public function shopCreationError(int $userId, int $activeShopId): ?string
    {
        $allowance = $this->shopAllowanceForUser($userId, $activeShopId);

        if (!$allowance['active']) {
            return 'Abonnement inactif ou expire. Renouvelez votre abonnement avant de creer une boutique.';
        }

        if (!$allowance['can_create']) {
            $nextPlan = is_array($allowance['next_plan']) ? (string) ($allowance['next_plan']['nom'] ?? '') : '';
            return 'Limite de boutiques atteinte pour ce plan.' . ($nextPlan !== '' ? ' Passez au plan ' . $nextPlan . '.' : ' Contactez l administration SaaS.');
        }

        return null;
    }

    public function creationError(int $shopId, string $resource): ?string
    {
        $subscription = $this->currentForShop($shopId);

        if (!$this->subscriptionIsActive($subscription)) {
            return 'Abonnement inactif ou expire. Renouvelez l abonnement avant de continuer.';
        }

        $usage = $this->usageForShop($shopId);
        $labels = [
            'users' => ['limit' => 'limite_utilisateurs', 'label' => 'utilisateurs'],
            'products' => ['limit' => 'limite_produits', 'label' => 'produits'],
        ];

        if (!isset($labels[$resource])) {
            return null;
        }

        $limit = $this->limitValue($subscription[$labels[$resource]['limit']] ?? null);

        if ($limit !== null && (($usage[$resource] ?? 0) + 1) > $limit) {
            return 'Limite du plan atteinte pour les ' . $labels[$resource]['label'] . '. Changez de plan ou liberez une place.';
        }

        return null;
    }

    private function canUse(int $shopId, string $usageKey, string $limitKey, int $additional): bool
    {
        $subscription = $this->currentForShop($shopId);

        if (!$this->subscriptionIsActive($subscription)) {
            return false;
        }

        $limit = $this->limitValue($subscription[$limitKey] ?? null);

        if ($limit === null) {
            return true;
        }

        $usage = $this->usageForShop($shopId);

        return (($usage[$usageKey] ?? 0) + max(1, $additional)) <= $limit;
    }

    private function subscriptionIsActive(?array $subscription): bool
    {
        if ($subscription === null) {
            return false;
        }

        if (!in_array((string) ($subscription['statut'] ?? ''), ['trial', 'active'], true)) {
            return false;
        }

        $endDate = trim((string) ($subscription['date_fin'] ?? ''));

        return $endDate === '' || substr($endDate, 0, 10) >= date('Y-m-d');
    }

    private function limitValue(mixed $value): ?int
    {
        $limit = (int) ($value ?? 0);

        return $limit > 0 ? $limit : null;
    }

    private function countRows(string $table, int $shopId, string $extraWhere): int
    {
        try {
            $statement = Database::connection()->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE shop_id = :shop_id AND {$extraWhere}"
            );
            $statement->execute(['shop_id' => $shopId]);

            return (int) $statement->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }

    private function ownedShopsCount(int $userId, int $activeShopId): int
    {
        if ($userId < 1) {
            return 1;
        }

        try {
            $statement = Database::connection()->prepare(
                'SELECT COUNT(*) FROM shops WHERE actif = 1 AND (owner_user_id = :user_id OR id = :active_shop_id)'
            );
            $statement->execute(['user_id' => $userId, 'active_shop_id' => $activeShopId]);

            return max(1, (int) $statement->fetchColumn());
        } catch (Throwable) {
            return 1;
        }
    }

    private function nextPlan(?int $currentLimit): ?array
    {
        try {
            $statement = Database::connection()->prepare(
                'SELECT id, nom, code, limite_boutiques, prix_mensuel_usd
                 FROM saas_subscription_plans
                 WHERE actif = 1
                   AND (:current_limit IS NOT NULL)
                   AND (limite_boutiques > :current_limit_value OR limite_boutiques IS NULL)
                 ORDER BY (limite_boutiques IS NULL) ASC, limite_boutiques ASC, prix_mensuel_usd ASC
                 LIMIT 1'
            );
            $statement->execute([
                'current_limit' => $currentLimit,
                'current_limit_value' => $currentLimit ?? 0,
            ]);
            $plan = $statement->fetch();

            return is_array($plan) ? $plan : null;
        } catch (Throwable) {
            return null;
        }
    }
}
