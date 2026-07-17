SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE mobile_supplies
  ADD COLUMN montant_saisi DECIMAL(18,2) NULL AFTER montant,
  ADD COLUMN commission_saisie DECIMAL(18,2) NULL AFTER commission,
  ADD COLUMN devise_saisie ENUM('CDF','USD') NOT NULL DEFAULT 'CDF' AFTER commission_saisie,
  ADD COLUMN taux_change_saisie DECIMAL(18,4) NOT NULL DEFAULT 2800 AFTER devise_saisie;
UPDATE mobile_supplies SET montant_saisi=montant,commission_saisie=commission,devise_saisie='CDF' WHERE montant_saisi IS NULL;
