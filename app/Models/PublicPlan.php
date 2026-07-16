<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';

final class PublicPlan
{
    public function activeWithFeatures(): array
    {
        $plans = Database::connection()->query(
            'SELECT id, nom, code, prix_mensuel_usd, limite_boutiques,
                    limite_utilisateurs, limite_produits, description
             FROM saas_subscription_plans
             WHERE actif = 1
             ORDER BY prix_mensuel_usd ASC, nom ASC'
        )->fetchAll();

        $features = Database::connection()->query(
            'SELECT plan_features.plan_id, features.id, features.nom, features.code,
                    features.description, features.categorie
             FROM saas_plan_features plan_features
             INNER JOIN saas_features features ON features.id = plan_features.feature_id
             INNER JOIN saas_subscription_plans plans ON plans.id = plan_features.plan_id
             WHERE plans.actif = 1 AND features.actif = 1
             ORDER BY plan_features.plan_id ASC, features.categorie ASC, features.nom ASC'
        )->fetchAll();

        $featuresByPlan = [];
        foreach ((array) $features as $feature) {
            $featuresByPlan[(int) ($feature['plan_id'] ?? 0)][] = $feature;
        }

        foreach ($plans as &$plan) {
            $plan['features'] = $featuresByPlan[(int) ($plan['id'] ?? 0)] ?? [];
        }
        unset($plan);

        return $plans;
    }
}
