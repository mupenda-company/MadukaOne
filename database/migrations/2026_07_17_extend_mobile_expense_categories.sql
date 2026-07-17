SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE expenses
  MODIFY categorie ENUM(
    'transport', 'facture', 'loyer', 'salaire', 'perte_avarie',
    'frais_operateur', 'connexion_internet', 'communication',
    'maintenance_terminal', 'electricite', 'autre'
  ) NOT NULL DEFAULT 'autre';
