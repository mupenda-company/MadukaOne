INSERT INTO saas_features (code, nom, description, categorie, actif)
VALUES ('finance', 'Depenses et finances', 'Charges, depenses, suivi financier et cycle de validation.', 'finance', 1)
ON DUPLICATE KEY UPDATE
  nom = VALUES(nom),
  description = VALUES(description),
  categorie = VALUES(categorie),
  actif = 1;

INSERT IGNORE INTO saas_plan_features (plan_id, feature_id)
SELECT plans.id, features.id
FROM saas_subscription_plans plans
INNER JOIN saas_features features ON features.code = 'finance'
WHERE plans.code IN ('business', 'pro', 'reseau');
