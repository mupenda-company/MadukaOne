SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE mobile_sales
  ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER shop_id,
  ADD COLUMN client_name VARCHAR(180) NULL AFTER product_id,
  ADD COLUMN benefice DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER commission,
  ADD KEY idx_mobile_sale_user (user_id),
  ADD CONSTRAINT fk_mobile_sale_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
