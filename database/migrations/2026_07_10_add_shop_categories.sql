CREATE TABLE IF NOT EXISTS shop_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_shop_categories_slug (slug),
  UNIQUE KEY uq_shop_categories_nom (nom),
  KEY idx_shop_categories_actif (actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO shop_categories (nom, slug, actif)
VALUES
  ('Boutiques', 'boutiques', 1),
  ('Pharmacies', 'pharmacies', 1),
  ('Quincailleries', 'quincailleries', 1),
  ('Supermarchés', 'supermarches', 1),
  ('Dépôts', 'depots', 1),
  ('Papeteries', 'papeteries', 1),
  ('Librairies', 'librairies', 1),
  ('Boulangeries', 'boulangeries', 1),
  ('Restaurants', 'restaurants', 1),
  ('Bars', 'bars', 1),
  ('Hôtels', 'hotels', 1),
  ('Magasins de vêtements', 'magasins-de-vetements', 1),
  ('Magasins d''électronique', 'magasins-d-electronique', 1),
  ('Grossistes', 'grossistes', 1),
  ('Distributeurs', 'distributeurs', 1),
  ('Entreprises commerciales', 'entreprises-commerciales', 1)
ON DUPLICATE KEY UPDATE
  nom = VALUES(nom),
  actif = VALUES(actif);

SET @column_exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'shops'
    AND COLUMN_NAME = 'category_id'
);
SET @sql = IF(
  @column_exists = 0,
  'ALTER TABLE shops ADD COLUMN category_id BIGINT UNSIGNED NULL AFTER id',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'shops'
    AND INDEX_NAME = 'idx_shops_category_id'
);
SET @sql = IF(
  @index_exists = 0,
  'ALTER TABLE shops ADD KEY idx_shops_category_id (category_id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE shops
SET category_id = (SELECT id FROM shop_categories WHERE slug = 'boutiques' LIMIT 1)
WHERE category_id IS NULL;
