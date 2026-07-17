SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE mobile_products
  ADD COLUMN devise_prix ENUM('CDF','USD') NOT NULL DEFAULT 'CDF' AFTER unite,
  ADD COLUMN prix_achat_saisi DECIMAL(18,2) NULL AFTER devise_prix,
  ADD COLUMN prix_vente_saisi DECIMAL(18,2) NULL AFTER prix_achat,
  ADD COLUMN taux_change_prix DECIMAL(18,4) NOT NULL DEFAULT 2800 AFTER prix_vente;
UPDATE mobile_products SET prix_achat_saisi=prix_achat,prix_vente_saisi=prix_vente,devise_prix='CDF' WHERE prix_achat_saisi IS NULL;
