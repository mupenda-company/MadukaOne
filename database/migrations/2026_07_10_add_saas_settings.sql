CREATE TABLE IF NOT EXISTS saas_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL,
    setting_value TEXT NULL,
    value_type ENUM('string','integer','decimal','boolean','json') NOT NULL DEFAULT 'string',
    label VARCHAR(160) NOT NULL,
    description TEXT NULL,
    group_name VARCHAR(80) NOT NULL DEFAULT 'general',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_saas_settings_key (setting_key),
    KEY idx_saas_settings_group (group_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO saas_settings (setting_key, setting_value, value_type, label, description, group_name) VALUES
('platform_name', 'MadukaOne SaaS', 'string', 'Nom de la plateforme', 'Nom affiche dans l espace SaaS et les communications.', 'platform'),
('support_email', 'support@madukaone.local', 'string', 'Email support', 'Adresse de contact pour les boutiques clientes.', 'platform'),
('support_phone', '', 'string', 'Telephone support', 'Numero de support commercial ou technique.', 'platform'),
('default_currency', 'USD', 'string', 'Devise par defaut', 'Devise appliquee aux nouvelles offres SaaS.', 'billing'),
('default_trial_days', '14', 'integer', 'Jours d essai par defaut', 'Duree initiale accordee aux nouvelles boutiques.', 'billing'),
('billing_grace_days', '7', 'integer', 'Delai de grace paiement', 'Nombre de jours avant suspension apres echeance.', 'billing'),
('allow_new_shops', '1', 'boolean', 'Autoriser les nouvelles boutiques', 'Controle la creation de boutiques depuis l espace SaaS.', 'access'),
('maintenance_mode', '0', 'boolean', 'Mode maintenance SaaS', 'Indique une maintenance globale de la plateforme.', 'access')
ON DUPLICATE KEY UPDATE
    value_type = VALUES(value_type),
    label = VALUES(label),
    description = VALUES(description),
    group_name = VALUES(group_name);
