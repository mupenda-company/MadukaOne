ALTER TABLE shops
  ADD COLUMN email VARCHAR(190) NULL AFTER telephone,
  ADD COLUMN devise_principale ENUM('USD', 'CDF') NOT NULL DEFAULT 'USD' AFTER email,
  ADD COLUMN taux_change_cdf DECIMAL(14,4) NOT NULL DEFAULT 2800.0000 AFTER devise_principale,
  ADD CONSTRAINT chk_shops_taux_change_cdf CHECK (taux_change_cdf > 0);

ALTER TABLE products
  ADD COLUMN prix_achat_devise ENUM('USD', 'CDF') NOT NULL DEFAULT 'USD' AFTER prix_vente,
  ADD COLUMN prix_vente_devise ENUM('USD', 'CDF') NOT NULL DEFAULT 'USD' AFTER prix_achat_devise,
  ADD COLUMN prix_achat_montant DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER prix_vente_devise,
  ADD COLUMN prix_vente_montant DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER prix_achat_montant,
  ADD CONSTRAINT chk_products_prix_achat_montant CHECK (prix_achat_montant >= 0),
  ADD CONSTRAINT chk_products_prix_vente_montant CHECK (prix_vente_montant >= 0);

UPDATE products
SET prix_achat_montant = prix_achat,
    prix_vente_montant = prix_vente
WHERE prix_achat_montant = 0.00
  AND prix_vente_montant = 0.00;
