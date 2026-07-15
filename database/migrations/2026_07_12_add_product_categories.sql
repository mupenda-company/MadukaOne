CREATE TABLE IF NOT EXISTS product_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shop_id BIGINT UNSIGNED NOT NULL,
  nom VARCHAR(150) NOT NULL,
  slug VARCHAR(170) NOT NULL,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_product_categories_shop_slug (shop_id, slug),
  UNIQUE KEY uq_product_categories_shop_nom (shop_id, nom),
  KEY idx_product_categories_shop_actif (shop_id, actif),
  CONSTRAINT fk_product_categories_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO product_categories (shop_id, nom, slug, actif)
SELECT shops.id, 'General', 'general', 1
FROM shops;

ALTER TABLE products
  ADD COLUMN category_id BIGINT UNSIGNED NULL AFTER shop_id,
  ADD KEY idx_products_category_id (category_id),
  ADD CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES product_categories(id) ON UPDATE CASCADE ON DELETE SET NULL;

UPDATE products
SET category_id = (
  SELECT product_categories.id
  FROM product_categories
  WHERE product_categories.shop_id = products.shop_id
    AND product_categories.slug = 'general'
  LIMIT 1
)
WHERE category_id IS NULL;
