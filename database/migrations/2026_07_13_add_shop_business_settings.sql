CREATE TABLE IF NOT EXISTS shop_business_settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shop_id BIGINT UNSIGNED NOT NULL,
  setting_key VARCHAR(100) NOT NULL,
  setting_value TEXT NULL,
  value_type ENUM('boolean', 'string', 'integer', 'decimal', 'json') NOT NULL DEFAULT 'string',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_shop_business_settings_key (shop_id, setting_key),
  KEY idx_shop_business_settings_shop (shop_id),
  CONSTRAINT fk_shop_business_settings_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO shop_business_settings (shop_id, setting_key, setting_value, value_type)
SELECT shops.id, defaults.setting_key, defaults.setting_value, 'boolean'
FROM shops
CROSS JOIN (
  SELECT 'sales_credit_enabled' AS setting_key, '1' AS setting_value
  UNION ALL SELECT 'partial_payments_enabled', '1'
  UNION ALL SELECT 'discounts_enabled', '1'
  UNION ALL SELECT 'taxes_enabled', '0'
  UNION ALL SELECT 'expiration_dates_enabled', '1'
  UNION ALL SELECT 'variants_enabled', '0'
  UNION ALL SELECT 'tables_enabled', '0'
  UNION ALL SELECT 'reservations_enabled', '0'
  UNION ALL SELECT 'multi_warehouse_enabled', '0'
  UNION ALL SELECT 'multi_shop_enabled', '0'
) defaults;
