CREATE TABLE IF NOT EXISTS saas_category_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_saas_category_plans (category_id, plan_id),
    CONSTRAINT fk_saas_category_plans_category FOREIGN KEY (category_id) REFERENCES shop_categories(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_saas_category_plans_plan FOREIGN KEY (plan_id) REFERENCES saas_subscription_plans(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO saas_category_plans (category_id, plan_id)
SELECT categories.id, plans.id
FROM shop_categories categories
CROSS JOIN saas_subscription_plans plans
WHERE categories.actif = 1
  AND plans.actif = 1;

INSERT INTO saas_subscriptions (shop_id, plan_id, statut, date_debut, renouvellement_auto)
SELECT shops.id, MIN(category_plans.plan_id), 'trial', CURRENT_DATE, 1
FROM shops
INNER JOIN saas_category_plans category_plans ON category_plans.category_id = shops.category_id
LEFT JOIN saas_subscriptions subscriptions ON subscriptions.shop_id = shops.id
WHERE subscriptions.id IS NULL
GROUP BY shops.id;

DROP TABLE IF EXISTS saas_category_features;
