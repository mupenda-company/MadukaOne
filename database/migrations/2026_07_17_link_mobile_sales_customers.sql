SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE mobile_sales
  ADD COLUMN customer_id BIGINT UNSIGNED NULL AFTER user_id,
  ADD KEY idx_mobile_sales_customer (customer_id),
  ADD CONSTRAINT fk_mobile_sales_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL;
