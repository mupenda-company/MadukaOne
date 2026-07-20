<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/Model.php';

final class ShopSubscription extends Model
{
    public function currentForShop(int $shopId): ?array
    {
        $this->ensurePaymentsTable();
        $statement = Database::connection()->prepare(
            'SELECT
                subscriptions.*,
                plans.nom AS plan_name,
                plans.code AS plan_code,
                plans.prix_mensuel_usd,
                plans.limite_boutiques,
                plans.limite_utilisateurs,
                plans.limite_produits,
                plans.description AS plan_description,
                shops.nom AS shop_name,
                shops.devise_principale,
                shops.taux_change_cdf
             FROM saas_subscriptions subscriptions
             INNER JOIN shops ON shops.id = subscriptions.shop_id
             LEFT JOIN saas_subscription_plans plans ON plans.id = subscriptions.plan_id
             WHERE subscriptions.shop_id = :shop_id
             LIMIT 1'
        );
        $statement->execute(['shop_id' => $shopId]);
        $subscription = $statement->fetch();

        return is_array($subscription) ? $subscription : null;
    }

    public function paymentsForShop(int $shopId): array
    {
        $this->ensurePaymentsTable();
        $statement = Database::connection()->prepare(
            'SELECT payments.*, plans.nom AS plan_name
             FROM saas_subscription_payments payments
             LEFT JOIN saas_subscription_plans plans ON plans.id = payments.plan_id
             WHERE payments.shop_id = :shop_id
             ORDER BY payments.created_at DESC, payments.id DESC
             LIMIT 50'
        );
        $statement->execute(['shop_id' => $shopId]);

        return $statement->fetchAll();
    }

    public function featuresForPlan(int $planId): array
    {
        if ($planId < 1) {
            return [];
        }
        $statement = Database::connection()->prepare(
            'SELECT features.id, features.code, features.nom, features.description, features.categorie
             FROM saas_plan_features assignments
             INNER JOIN saas_features features ON features.id = assignments.feature_id
             WHERE assignments.plan_id = :plan_id AND features.actif = 1
             ORDER BY features.categorie, features.nom'
        );
        $statement->execute(['plan_id' => $planId]);
        return $statement->fetchAll();
    }

    public function availablePlansForShop(int $shopId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT plans.*
             FROM shops
             INNER JOIN saas_category_plans category_plans ON category_plans.category_id = shops.category_id
             INNER JOIN saas_subscription_plans plans ON plans.id = category_plans.plan_id AND plans.actif = 1
             WHERE shops.id = :shop_id
             ORDER BY plans.prix_mensuel_usd, plans.nom'
        );
        $statement->execute(['shop_id' => $shopId]);
        return $statement->fetchAll();
    }

    public function changePlan(int $shopId, int $planId): void
    {
        if ($planId < 1) {
            throw new InvalidArgumentException('Selectionnez un plan valide.');
        }
        $allowed = Database::connection()->prepare(
            'SELECT plans.id
             FROM shops
             INNER JOIN saas_category_plans category_plans ON category_plans.category_id = shops.category_id
             INNER JOIN saas_subscription_plans plans ON plans.id = category_plans.plan_id
             WHERE shops.id = :shop_id AND plans.id = :plan_id AND plans.actif = 1
             LIMIT 1'
        );
        $allowed->execute(['shop_id' => $shopId, 'plan_id' => $planId]);
        if (!$allowed->fetchColumn()) {
            throw new RuntimeException('Ce plan n est pas disponible pour la categorie de cette boutique.');
        }

        $statement = Database::connection()->prepare(
            'UPDATE saas_subscriptions SET plan_id = :plan_id WHERE shop_id = :shop_id AND plan_id <> :current_plan_id'
        );
        $statement->execute(['shop_id' => $shopId, 'plan_id' => $planId, 'current_plan_id' => $planId]);
        if ($statement->rowCount() < 1) {
            $current = $this->currentForShop($shopId);
            if ($current === null) {
                throw new RuntimeException('Aucun abonnement n est configure pour cette boutique.');
            }
            throw new RuntimeException('Ce plan est deja le plan actif.');
        }
    }

    public function requestRenewal(int $shopId): int
    {
        $subscription = $this->currentForShop($shopId);

        if ($subscription === null) {
            throw new RuntimeException('Aucun abonnement n est configure pour cette boutique.');
        }

        $periodStart = $this->nextPeriodStart($subscription['date_fin'] ?? null);
        $periodEnd = $periodStart->modify('+1 month')->modify('-1 day');
        $reference = 'REN-' . date('Ymd-His') . '-' . $shopId;
        $statement = Database::connection()->prepare(
            'INSERT INTO saas_subscription_payments (
                shop_id, subscription_id, plan_id, montant_usd, devise, periode_debut, periode_fin,
                statut, reference, notes
             ) VALUES (
                :shop_id, :subscription_id, :plan_id, :montant_usd, :devise, :periode_debut, :periode_fin,
                "pending", :reference, :notes
             )'
        );
        $statement->execute([
            'shop_id' => $shopId,
            'subscription_id' => (int) ($subscription['id'] ?? 0),
            'plan_id' => (int) ($subscription['plan_id'] ?? 0),
            'montant_usd' => (float) ($subscription['prix_mensuel_usd'] ?? 0),
            'devise' => 'USD',
            'periode_debut' => $periodStart->format('Y-m-d'),
            'periode_fin' => $periodEnd->format('Y-m-d'),
            'reference' => $reference,
            'notes' => 'Demande de renouvellement creee depuis l espace boutique.',
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function updateAutoRenew(int $shopId, bool $enabled): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE saas_subscriptions
             SET renouvellement_auto = :enabled
             WHERE shop_id = :shop_id'
        );
        $statement->execute([
            'enabled' => $enabled ? 1 : 0,
            'shop_id' => $shopId,
        ]);

        return $statement->rowCount() > 0;
    }

    private function nextPeriodStart(mixed $dateFin): DateTimeImmutable
    {
        $value = trim((string) ($dateFin ?? ''));

        if ($value !== '') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10));
            if ($date instanceof DateTimeImmutable) {
                return $date->modify('+1 day');
            }
        }

        return new DateTimeImmutable('today');
    }

    private function ensurePaymentsTable(): void
    {
        Database::connection()->exec(
            "CREATE TABLE IF NOT EXISTS saas_subscription_payments (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              shop_id BIGINT UNSIGNED NOT NULL,
              subscription_id BIGINT UNSIGNED NULL,
              plan_id BIGINT UNSIGNED NULL,
              montant_usd DECIMAL(12,2) NOT NULL DEFAULT 0.00,
              devise ENUM('USD', 'CDF') NOT NULL DEFAULT 'USD',
              periode_debut DATE NULL,
              periode_fin DATE NULL,
              statut ENUM('pending', 'paid', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
              mode_paiement VARCHAR(50) NULL,
              reference VARCHAR(100) NULL,
              notes TEXT NULL,
              paid_at DATETIME NULL,
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              KEY idx_saas_subscription_payments_shop (shop_id),
              KEY idx_saas_subscription_payments_subscription (subscription_id),
              KEY idx_saas_subscription_payments_plan (plan_id),
              KEY idx_saas_subscription_payments_statut (statut)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }
}
