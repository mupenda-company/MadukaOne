SET @add_expense_entered_amount = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE expenses ADD COLUMN montant_saisi DECIMAL(14,2) NULL AFTER montant',
        'DO 0'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'expenses'
      AND COLUMN_NAME = 'montant_saisi'
);
PREPARE add_expense_entered_amount_statement FROM @add_expense_entered_amount;
EXECUTE add_expense_entered_amount_statement;
DEALLOCATE PREPARE add_expense_entered_amount_statement;

SET @add_expense_entered_currency = (
    SELECT IF(
        COUNT(*) = 0,
        "ALTER TABLE expenses ADD COLUMN devise_saisie ENUM('USD','CDF') NOT NULL DEFAULT 'USD' AFTER montant_saisi",
        'DO 0'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'expenses'
      AND COLUMN_NAME = 'devise_saisie'
);
PREPARE add_expense_entered_currency_statement FROM @add_expense_entered_currency;
EXECUTE add_expense_entered_currency_statement;
DEALLOCATE PREPARE add_expense_entered_currency_statement;

SET @add_expense_entered_rate = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE expenses ADD COLUMN taux_change_saisie DECIMAL(12,4) NOT NULL DEFAULT 2800.0000 AFTER devise_saisie',
        'DO 0'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'expenses'
      AND COLUMN_NAME = 'taux_change_saisie'
);
PREPARE add_expense_entered_rate_statement FROM @add_expense_entered_rate;
EXECUTE add_expense_entered_rate_statement;
DEALLOCATE PREPARE add_expense_entered_rate_statement;

UPDATE expenses
SET montant_saisi = montant
WHERE montant_saisi IS NULL;
