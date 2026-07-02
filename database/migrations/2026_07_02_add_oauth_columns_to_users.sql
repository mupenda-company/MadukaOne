ALTER TABLE users
  MODIFY password_hash VARCHAR(255) NULL,
  ADD COLUMN auth_provider ENUM('local', 'google', 'apple') NOT NULL DEFAULT 'local' AFTER password_hash,
  ADD COLUMN google_id VARCHAR(191) NULL AFTER auth_provider,
  ADD COLUMN apple_id VARCHAR(191) NULL AFTER google_id,
  ADD COLUMN email_verified_at DATETIME NULL AFTER apple_id,
  ADD COLUMN avatar_url VARCHAR(500) NULL AFTER email_verified_at,
  ADD UNIQUE KEY uq_users_google_id (google_id),
  ADD UNIQUE KEY uq_users_apple_id (apple_id),
  ADD KEY idx_users_auth_provider (auth_provider);
