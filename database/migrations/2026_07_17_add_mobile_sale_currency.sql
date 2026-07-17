SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE mobile_sales
  ADD COLUMN montant_saisi DECIMAL(18,2) NULL AFTER montant,
  ADD COLUMN devise_saisie ENUM('CDF','USD') NOT NULL DEFAULT 'CDF' AFTER montant_saisi,
  ADD COLUMN taux_change_saisie DECIMAL(18,4) NOT NULL DEFAULT 2800 AFTER devise_saisie;
UPDATE mobile_sales SET montant_saisi=montant,devise_saisie='CDF' WHERE montant_saisi IS NULL;
