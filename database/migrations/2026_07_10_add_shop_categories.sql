CREATE TABLE IF NOT EXISTS shop_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL,
  description TEXT NULL,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_shop_categories_slug (slug),
  UNIQUE KEY uq_shop_categories_nom (nom),
  KEY idx_shop_categories_actif (actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @shop_category_description_column_exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'shop_categories'
    AND COLUMN_NAME = 'description'
);
SET @shop_category_description_sql = IF(
  @shop_category_description_column_exists = 0,
  'ALTER TABLE shop_categories ADD COLUMN description TEXT NULL AFTER slug',
  'SELECT 1'
);
PREPARE shop_category_description_stmt FROM @shop_category_description_sql;
EXECUTE shop_category_description_stmt;
DEALLOCATE PREPARE shop_category_description_stmt;

SET @shops_category_column_exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'shops'
    AND COLUMN_NAME = 'category_id'
);
SET @shops_category_column_sql = IF(
  @shops_category_column_exists = 0,
  'ALTER TABLE shops ADD COLUMN category_id BIGINT UNSIGNED NULL AFTER id',
  'SELECT 1'
);
PREPARE shops_category_column_stmt FROM @shops_category_column_sql;
EXECUTE shops_category_column_stmt;
DEALLOCATE PREPARE shops_category_column_stmt;

SET @shops_category_index_exists = (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'shops'
    AND INDEX_NAME = 'idx_shops_category_id'
);
SET @shops_category_index_sql = IF(
  @shops_category_index_exists = 0,
  'ALTER TABLE shops ADD KEY idx_shops_category_id (category_id)',
  'SELECT 1'
);
PREPARE shops_category_index_stmt FROM @shops_category_index_sql;
EXECUTE shops_category_index_stmt;
DEALLOCATE PREPARE shops_category_index_stmt;

SET FOREIGN_KEY_CHECKS = 0;

UPDATE shops SET category_id = NULL WHERE category_id IS NOT NULL;

SET @category_plan_table_exists = (
  SELECT COUNT(*)
  FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'saas_category_plans'
);
SET @category_plan_clear_sql = IF(
  @category_plan_table_exists = 1,
  'DELETE FROM saas_category_plans',
  'SELECT 1'
);
PREPARE category_plan_clear_stmt FROM @category_plan_clear_sql;
EXECUTE category_plan_clear_stmt;
DEALLOCATE PREPARE category_plan_clear_stmt;

DELETE FROM shop_categories;
ALTER TABLE shop_categories AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO shop_categories (nom, slug, description, actif)
VALUES
  ('Boutiques', 'boutiques', 'Commerce de detail general avec catalogue produits, caisse, stock, clients et rapports.', 1),
  ('Pharmacies', 'pharmacies', 'Gestion des medicaments, lots, dates d expiration, stock sensible et ventes controlees.', 1),
  ('Quincailleries', 'quincailleries', 'Articles de construction, outillage, quincaillerie, stock par references et approvisionnements.', 1),
  ('Supermarches', 'supermarches', 'Vente rapide multi-rayons avec caisse, inventaire, familles produits et volumes importants.', 1),
  ('Depots', 'depots', 'Gestion de stock entrepose, entrees, sorties, inventaires et suivi des mouvements.', 1),
  ('Papeteries', 'papeteries', 'Articles scolaires, fournitures de bureau, petits accessoires et vente au comptoir.', 1),
  ('Librairies', 'librairies', 'Livres, manuels, auteurs, editions et stock de references culturelles ou scolaires.', 1),
  ('Boulangeries', 'boulangeries', 'Produits frais, production journaliere, ventes rapides, pertes et suivi des invendus.', 1),
  ('Restaurants', 'restaurants', 'Menus, plats, boissons, caisse restaurant, depenses et pilotage journalier.', 1),
  ('Bars', 'bars', 'Boissons, consommations, caisse rapide, stock de bouteilles et suivi des recettes.', 1),
  ('Hotels', 'hotels', 'Services hoteliers, chambres, prestations, facturation client et suivi d activite.', 1),
  ('Magasins de vetements', 'magasins-de-vetements', 'Articles textiles, tailles, collections, stock boutique et ventes au detail.', 1),
  ('Magasins d electronique', 'magasins-d-electronique', 'Appareils, accessoires, references techniques, stock et garanties commerciales.', 1),
  ('Grossistes', 'grossistes', 'Vente en gros, lots, prix de volume, grands stocks et clients professionnels.', 1),
  ('Distributeurs', 'distributeurs', 'Distribution multi-clients, approvisionnement, suivi des sorties et rapports de reseau.', 1),
  ('Entreprises commerciales', 'entreprises-commerciales', 'Activite commerciale polyvalente avec ventes, achats, finances et reporting.', 1),
  ('Vendeur forfait mobile (Unites)', 'vendeur-forfait-mobile-unites', 'Vente d unites, forfaits mobiles, recharges et suivi des operations de telecommunication.', 1)
ON DUPLICATE KEY UPDATE
  nom = VALUES(nom),
  description = VALUES(description),
  actif = VALUES(actif);

UPDATE shops
SET category_id = (SELECT id FROM shop_categories WHERE slug = 'boutiques' LIMIT 1)
WHERE category_id IS NULL;

SET @category_plan_seed_sql = IF(
  @category_plan_table_exists = 1
  AND (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'saas_subscription_plans'
  ) = 1,
  'INSERT IGNORE INTO saas_category_plans (category_id, plan_id)
   SELECT categories.id, plans.id
   FROM shop_categories categories
   CROSS JOIN saas_subscription_plans plans
   WHERE categories.actif = 1 AND plans.actif = 1',
  'SELECT 1'
);
PREPARE category_plan_seed_stmt FROM @category_plan_seed_sql;
EXECUTE category_plan_seed_stmt;
DEALLOCATE PREPARE category_plan_seed_stmt;
