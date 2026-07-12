DROP TRIGGER IF EXISTS trg_expenses_before_update_block;
DROP TRIGGER IF EXISTS trg_expenses_before_delete_block;

SET @add_expense_status = (
    SELECT IF(
        COUNT(*) = 0,
        "ALTER TABLE expenses ADD COLUMN statut ENUM('active','cancelled') NOT NULL DEFAULT 'active' AFTER date_depense",
        'DO 0'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'expenses'
      AND COLUMN_NAME = 'statut'
);
PREPARE add_expense_status_statement FROM @add_expense_status;
EXECUTE add_expense_status_statement;
DEALLOCATE PREPARE add_expense_status_statement;

SET @add_expense_cancellation_reason = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE expenses ADD COLUMN cancellation_reason TEXT NULL AFTER statut',
        'DO 0'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'expenses'
      AND COLUMN_NAME = 'cancellation_reason'
);
PREPARE add_expense_cancellation_reason_statement FROM @add_expense_cancellation_reason;
EXECUTE add_expense_cancellation_reason_statement;
DEALLOCATE PREPARE add_expense_cancellation_reason_statement;

SET @add_expense_cancelled_by = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE expenses ADD COLUMN cancelled_by BIGINT UNSIGNED NULL AFTER cancellation_reason',
        'DO 0'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'expenses'
      AND COLUMN_NAME = 'cancelled_by'
);
PREPARE add_expense_cancelled_by_statement FROM @add_expense_cancelled_by;
EXECUTE add_expense_cancelled_by_statement;
DEALLOCATE PREPARE add_expense_cancelled_by_statement;

SET @add_expense_cancelled_at = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE expenses ADD COLUMN cancelled_at DATETIME NULL AFTER cancelled_by',
        'DO 0'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'expenses'
      AND COLUMN_NAME = 'cancelled_at'
);
PREPARE add_expense_cancelled_at_statement FROM @add_expense_cancelled_at;
EXECUTE add_expense_cancelled_at_statement;
DEALLOCATE PREPARE add_expense_cancelled_at_statement;

SET @add_expense_updated_at = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE expenses ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at',
        'DO 0'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'expenses'
      AND COLUMN_NAME = 'updated_at'
);
PREPARE add_expense_updated_at_statement FROM @add_expense_updated_at;
EXECUTE add_expense_updated_at_statement;
DEALLOCATE PREPARE add_expense_updated_at_statement;

SET @add_expense_status_index = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE expenses ADD KEY idx_expenses_status (statut)',
        'DO 0'
    )
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'expenses'
      AND INDEX_NAME = 'idx_expenses_status'
);
PREPARE add_expense_status_index_statement FROM @add_expense_status_index;
EXECUTE add_expense_status_index_statement;
DEALLOCATE PREPARE add_expense_status_index_statement;

SET @add_expense_cancelled_by_index = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE expenses ADD KEY idx_expenses_cancelled_by (cancelled_by)',
        'DO 0'
    )
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'expenses'
      AND INDEX_NAME = 'idx_expenses_cancelled_by'
);
PREPARE add_expense_cancelled_by_index_statement FROM @add_expense_cancelled_by_index;
EXECUTE add_expense_cancelled_by_index_statement;
DEALLOCATE PREPARE add_expense_cancelled_by_index_statement;

SET @add_expense_cancelled_by_fk = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE expenses ADD CONSTRAINT fk_expenses_cancelled_by FOREIGN KEY (cancelled_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL',
        'DO 0'
    )
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'expenses'
      AND CONSTRAINT_NAME = 'fk_expenses_cancelled_by'
);
PREPARE add_expense_cancelled_by_fk_statement FROM @add_expense_cancelled_by_fk;
EXECUTE add_expense_cancelled_by_fk_statement;
DEALLOCATE PREPARE add_expense_cancelled_by_fk_statement;
