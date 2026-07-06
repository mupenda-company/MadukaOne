ALTER TABLE products
  ADD COLUMN date_fabrication DATE NULL AFTER alerte_stock_min,
  ADD COLUMN date_expiration DATE NULL AFTER date_fabrication,
  ADD KEY idx_products_date_expiration (date_expiration);
