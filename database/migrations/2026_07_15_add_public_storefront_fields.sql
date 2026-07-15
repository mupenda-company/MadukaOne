SET @add_shops_slug = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE shops ADD COLUMN slug VARCHAR(160) NULL AFTER nom',
    'DO 0'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'shops'
    AND COLUMN_NAME = 'slug'
);
PREPARE add_shops_slug_statement FROM @add_shops_slug;
EXECUTE add_shops_slug_statement;
DEALLOCATE PREPARE add_shops_slug_statement;

UPDATE shops
SET slug = CONCAT(
  COALESCE(
    NULLIF(TRIM(BOTH '-' FROM REGEXP_REPLACE(LOWER(TRIM(nom)), '[^a-z0-9]+', '-')), ''),
    'boutique'
  ),
  '-',
  id
)
WHERE slug IS NULL OR TRIM(slug) = '';

SET @make_shops_slug_required = (
  SELECT IF(
    IS_NULLABLE = 'YES',
    'ALTER TABLE shops MODIFY COLUMN slug VARCHAR(160) NOT NULL',
    'DO 0'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'shops'
    AND COLUMN_NAME = 'slug'
  LIMIT 1
);
PREPARE make_shops_slug_required_statement FROM @make_shops_slug_required;
EXECUTE make_shops_slug_required_statement;
DEALLOCATE PREPARE make_shops_slug_required_statement;

SET @add_shops_slug_unique = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE shops ADD UNIQUE KEY uq_shops_slug (slug)',
    'DO 0'
  )
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'shops'
    AND INDEX_NAME = 'uq_shops_slug'
);
PREPARE add_shops_slug_unique_statement FROM @add_shops_slug_unique;
EXECUTE add_shops_slug_unique_statement;
DEALLOCATE PREPARE add_shops_slug_unique_statement;

SET @add_shops_owner = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE shops ADD COLUMN owner_user_id BIGINT UNSIGNED NULL AFTER category_id',
    'DO 0'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'shops'
    AND COLUMN_NAME = 'owner_user_id'
);
PREPARE add_shops_owner_statement FROM @add_shops_owner;
EXECUTE add_shops_owner_statement;
DEALLOCATE PREPARE add_shops_owner_statement;

SET @add_shops_logo = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE shops ADD COLUMN logo_url VARCHAR(500) NULL AFTER email',
    'DO 0'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'shops'
    AND COLUMN_NAME = 'logo_url'
);
PREPARE add_shops_logo_statement FROM @add_shops_logo;
EXECUTE add_shops_logo_statement;
DEALLOCATE PREPARE add_shops_logo_statement;

SET @add_shops_owner_index = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE shops ADD KEY idx_shops_owner_user_id (owner_user_id)',
    'DO 0'
  )
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'shops'
    AND INDEX_NAME = 'idx_shops_owner_user_id'
);
PREPARE add_shops_owner_index_statement FROM @add_shops_owner_index;
EXECUTE add_shops_owner_index_statement;
DEALLOCATE PREPARE add_shops_owner_index_statement;

SET @add_shops_owner_fk = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE shops ADD CONSTRAINT fk_shops_owner_user FOREIGN KEY (owner_user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL',
    'DO 0'
  )
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'shops'
    AND CONSTRAINT_NAME = 'fk_shops_owner_user'
);
PREPARE add_shops_owner_fk_statement FROM @add_shops_owner_fk;
EXECUTE add_shops_owner_fk_statement;
DEALLOCATE PREPARE add_shops_owner_fk_statement;
