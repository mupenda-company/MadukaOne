SET @add_supply_detail_entered_price = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE supply_details ADD COLUMN prix_achat_saisi DECIMAL(14,2) NULL AFTER prix_achat_facture',
        'DO 0'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'supply_details'
      AND COLUMN_NAME = 'prix_achat_saisi'
);
PREPARE add_supply_detail_entered_price_statement FROM @add_supply_detail_entered_price;
EXECUTE add_supply_detail_entered_price_statement;
DEALLOCATE PREPARE add_supply_detail_entered_price_statement;

SET @add_supply_detail_entered_currency = (
    SELECT IF(
        COUNT(*) = 0,
        "ALTER TABLE supply_details ADD COLUMN devise_saisie ENUM('USD','CDF') NOT NULL DEFAULT 'USD' AFTER prix_achat_saisi",
        'DO 0'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'supply_details'
      AND COLUMN_NAME = 'devise_saisie'
);
PREPARE add_supply_detail_entered_currency_statement FROM @add_supply_detail_entered_currency;
EXECUTE add_supply_detail_entered_currency_statement;
DEALLOCATE PREPARE add_supply_detail_entered_currency_statement;

SET @add_supply_detail_entered_rate = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE supply_details ADD COLUMN taux_change_saisie DECIMAL(12,4) NOT NULL DEFAULT 2800.0000 AFTER devise_saisie',
        'DO 0'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'supply_details'
      AND COLUMN_NAME = 'taux_change_saisie'
);
PREPARE add_supply_detail_entered_rate_statement FROM @add_supply_detail_entered_rate;
EXECUTE add_supply_detail_entered_rate_statement;
DEALLOCATE PREPARE add_supply_detail_entered_rate_statement;

SET @add_supply_detail_entered_total = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE supply_details ADD COLUMN total_ligne_saisi DECIMAL(14,2) NULL AFTER total_ligne',
        'DO 0'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'supply_details'
      AND COLUMN_NAME = 'total_ligne_saisi'
);
PREPARE add_supply_detail_entered_total_statement FROM @add_supply_detail_entered_total;
EXECUTE add_supply_detail_entered_total_statement;
DEALLOCATE PREPARE add_supply_detail_entered_total_statement;

UPDATE supply_details
SET prix_achat_saisi = prix_achat_facture,
    total_ligne_saisi = total_ligne
WHERE prix_achat_saisi IS NULL
   OR total_ligne_saisi IS NULL;

UPDATE supply_details
INNER JOIN supplies ON supplies.id = supply_details.supply_id
INNER JOIN shops ON shops.id = supplies.shop_id
SET supply_details.taux_change_saisie = shops.taux_change_cdf
WHERE supply_details.devise_saisie = 'USD'
  AND supply_details.taux_change_saisie = 2800.0000;
