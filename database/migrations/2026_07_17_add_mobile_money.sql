SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mobile_money_accounts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shop_id BIGINT UNSIGNED NOT NULL,
  operator_id BIGINT UNSIGNED NOT NULL,
  nom VARCHAR(120) NOT NULL,
  numero VARCHAR(80) NOT NULL,
  proprietaire VARCHAR(160) NOT NULL,
  solde DECIMAL(18,2) NOT NULL DEFAULT 0,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_mobile_money_account_number (shop_id, numero),
  KEY idx_mobile_money_account_operator (operator_id),
  CONSTRAINT fk_mobile_money_account_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
  CONSTRAINT fk_mobile_money_account_operator FOREIGN KEY (operator_id) REFERENCES mobile_operators(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mobile_money_commissions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shop_id BIGINT UNSIGNED NOT NULL,
  operator_id BIGINT UNSIGNED NOT NULL,
  type_transaction VARCHAR(40) NOT NULL,
  taux DECIMAL(8,4) NOT NULL DEFAULT 0,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_mobile_money_commission (shop_id, operator_id, type_transaction),
  CONSTRAINT fk_mobile_money_commission_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
  CONSTRAINT fk_mobile_money_commission_operator FOREIGN KEY (operator_id) REFERENCES mobile_operators(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mobile_money_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shop_id BIGINT UNSIGNED NOT NULL,
  account_id BIGINT UNSIGNED NOT NULL,
  operator_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  type_transaction VARCHAR(40) NOT NULL,
  numero VARCHAR(80) NOT NULL,
  client VARCHAR(180) NULL,
  montant DECIMAL(18,2) NOT NULL,
  frais DECIMAL(18,2) NOT NULL DEFAULT 0,
  taux_commission DECIMAL(8,4) NOT NULL DEFAULT 0,
  commission DECIMAL(18,2) NOT NULL DEFAULT 0,
  sens_caisse ENUM('entree','sortie') NOT NULL,
  solde_avant DECIMAL(18,2) NOT NULL,
  solde_apres DECIMAL(18,2) NOT NULL,
  reference VARCHAR(140) NOT NULL,
  date_operation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_mobile_money_reference (shop_id, reference),
  KEY idx_mobile_money_tx_shop_date (shop_id, date_operation),
  CONSTRAINT fk_mobile_money_tx_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
  CONSTRAINT fk_mobile_money_tx_account FOREIGN KEY (account_id) REFERENCES mobile_money_accounts(id) ON DELETE RESTRICT,
  CONSTRAINT fk_mobile_money_tx_operator FOREIGN KEY (operator_id) REFERENCES mobile_operators(id) ON DELETE RESTRICT,
  CONSTRAINT fk_mobile_money_tx_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mobile_cash_sessions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shop_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  solde_ouverture DECIMAL(18,2) NOT NULL DEFAULT 0,
  solde_fermeture_theorique DECIMAL(18,2) NULL,
  solde_fermeture_reel DECIMAL(18,2) NULL,
  opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  closed_at DATETIME NULL,
  statut ENUM('ouverte','fermee') NOT NULL DEFAULT 'ouverte',
  KEY idx_mobile_cash_shop_status (shop_id, statut),
  CONSTRAINT fk_mobile_cash_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
  CONSTRAINT fk_mobile_cash_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO mobile_money_accounts (shop_id, operator_id, nom, numero, proprietaire, solde)
SELECT o.shop_id, o.id,
  CASE o.nom WHEN 'Airtel' THEN 'Airtel Money' WHEN 'Orange' THEN 'Orange Money' WHEN 'Vodacom' THEN 'M-Pesa' WHEN 'Africell' THEN 'AfriMoney' ELSE CONCAT(o.nom, ' Money') END,
  COALESCE(NULLIF(o.numero_principal, ''), CONCAT('A-configurer-', o.id)), 'Boutique', 0
FROM mobile_operators o;

INSERT IGNORE INTO mobile_money_commissions (shop_id, operator_id, type_transaction, taux)
SELECT o.shop_id, o.id, t.type_transaction,
  CASE WHEN t.type_transaction IN ('retrait','cash_out') THEN 0.8 WHEN t.type_transaction IN ('depot','cash_in') THEN 0.3 ELSE 0 END
FROM mobile_operators o
CROSS JOIN (
  SELECT 'depot' type_transaction UNION ALL SELECT 'retrait' UNION ALL SELECT 'envoi' UNION ALL SELECT 'reception'
  UNION ALL SELECT 'paiement_facture' UNION ALL SELECT 'achat_credit' UNION ALL SELECT 'achat_forfait'
  UNION ALL SELECT 'cash_in' UNION ALL SELECT 'cash_out'
) t;
