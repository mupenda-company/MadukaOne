SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mobile_operators (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shop_id BIGINT UNSIGNED NOT NULL,
  nom VARCHAR(100) NOT NULL,
  logo_url VARCHAR(500) NULL,
  code_ussd VARCHAR(80) NULL,
  numero_principal VARCHAR(80) NULL,
  couleur VARCHAR(20) NOT NULL DEFAULT '#0f766e',
  actif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_mobile_operator_shop_name (shop_id, nom),
  CONSTRAINT fk_mobile_operator_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mobile_products (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shop_id BIGINT UNSIGNED NOT NULL,
  operator_id BIGINT UNSIGNED NOT NULL,
  nom VARCHAR(160) NOT NULL,
  categorie VARCHAR(100) NOT NULL,
  unite ENUM('CDF','GO','MINUTES','SMS','UNITES') NOT NULL DEFAULT 'CDF',
  prix_achat DECIMAL(18,2) NOT NULL DEFAULT 0,
  prix_vente DECIMAL(18,2) NOT NULL DEFAULT 0,
  commission DECIMAL(18,2) NOT NULL DEFAULT 0,
  stock_initial DECIMAL(18,2) NOT NULL DEFAULT 0,
  description TEXT NULL,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_mobile_product_shop (shop_id),
  KEY idx_mobile_product_operator (operator_id),
  CONSTRAINT fk_mobile_product_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
  CONSTRAINT fk_mobile_product_operator FOREIGN KEY (operator_id) REFERENCES mobile_operators(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mobile_supplies (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shop_id BIGINT UNSIGNED NOT NULL,
  operator_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  fournisseur VARCHAR(180) NOT NULL,
  montant DECIMAL(18,2) NOT NULL DEFAULT 0,
  quantite DECIMAL(18,2) NOT NULL,
  commission DECIMAL(18,2) NOT NULL DEFAULT 0,
  date_operation DATE NOT NULL,
  reference VARCHAR(140) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_mobile_supply_shop_date (shop_id, date_operation),
  CONSTRAINT fk_mobile_supply_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
  CONSTRAINT fk_mobile_supply_operator FOREIGN KEY (operator_id) REFERENCES mobile_operators(id) ON DELETE RESTRICT,
  CONSTRAINT fk_mobile_supply_product FOREIGN KEY (product_id) REFERENCES mobile_products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mobile_sales (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shop_id BIGINT UNSIGNED NOT NULL,
  operator_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  numero_client VARCHAR(80) NULL,
  quantite DECIMAL(18,2) NOT NULL,
  montant DECIMAL(18,2) NOT NULL DEFAULT 0,
  commission DECIMAL(18,2) NOT NULL DEFAULT 0,
  date_operation DATETIME NOT NULL,
  reference VARCHAR(140) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_mobile_sale_shop_date (shop_id, date_operation),
  CONSTRAINT fk_mobile_sale_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
  CONSTRAINT fk_mobile_sale_operator FOREIGN KEY (operator_id) REFERENCES mobile_operators(id) ON DELETE RESTRICT,
  CONSTRAINT fk_mobile_sale_product FOREIGN KEY (product_id) REFERENCES mobile_products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO mobile_operators (shop_id, nom, code_ussd, couleur)
SELECT shops.id, defaults.nom, defaults.code_ussd, defaults.couleur
FROM shops
JOIN shop_categories ON shop_categories.id = shops.category_id AND shop_categories.slug = 'vendeur-forfait-mobile-unites'
JOIN (
  SELECT 'Airtel' nom, '*121#' code_ussd, '#e11d48' couleur UNION ALL
  SELECT 'Vodacom', '*111#', '#dc2626' UNION ALL
  SELECT 'Orange', '*123#', '#f97316' UNION ALL
  SELECT 'Africell', '*111#', '#7c3aed'
) defaults
WHERE NOT EXISTS (SELECT 1 FROM mobile_operators existing WHERE existing.shop_id = shops.id AND existing.nom = defaults.nom);
