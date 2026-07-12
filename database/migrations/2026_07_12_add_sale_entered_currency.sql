ALTER TABLE sales
  ADD COLUMN total_montant_saisi DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER total_montant,
  ADD COLUMN devise_saisie ENUM('USD','CDF') NOT NULL DEFAULT 'USD' AFTER total_montant_saisi,
  ADD COLUMN montant_recu_saisi DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER montant_recu,
  ADD COLUMN devise_recu ENUM('USD','CDF') NOT NULL DEFAULT 'USD' AFTER montant_recu_saisi,
  ADD COLUMN taux_change_saisie DECIMAL(12,4) NOT NULL DEFAULT 2800.0000 AFTER devise_recu;

ALTER TABLE sale_details
  ADD COLUMN prix_unitaire_vendu_saisi DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER prix_unitaire_vendu,
  ADD COLUMN devise_saisie ENUM('USD','CDF') NOT NULL DEFAULT 'USD' AFTER prix_unitaire_vendu_saisi,
  ADD COLUMN taux_change_saisie DECIMAL(12,4) NOT NULL DEFAULT 2800.0000 AFTER devise_saisie,
  ADD COLUMN total_ligne_saisi DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER total_ligne;

UPDATE sales
SET total_montant_saisi = total_montant,
    devise_saisie = 'USD',
    montant_recu_saisi = montant_recu,
    devise_recu = 'USD'
WHERE total_montant_saisi = 0.00
  AND total_montant > 0;
