CREATE DATABASE IF NOT EXISTS shop_logistique
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE shop_logistique;

SET FOREIGN_KEY_CHECKS = 0;

-- Suppression des tables existantes dans le bon ordre
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS supply_details;
DROP TABLE IF EXISTS supplies;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS sale_details;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS shops;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================================
-- 1. CONFIGURATION MULTI-BOUTIQUES & RÔLES
-- =========================================================================

CREATE TABLE shops (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(120) NOT NULL,
  adresse VARCHAR(255) NULL,
  telephone VARCHAR(50) NULL,
  email VARCHAR(190) NULL,
  devise_principale ENUM('USD', 'CDF') NOT NULL DEFAULT 'USD',
  taux_change_cdf DECIMAL(14,4) NOT NULL DEFAULT 2800.0000,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_shops_actif (actif),
  CONSTRAINT chk_shops_taux_change_cdf CHECK (taux_change_cdf > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE roles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(50) NOT NULL,
  permissions TEXT NULL, -- Stockage en format JSON ou texte des droits d'accès
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_roles_nom (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- 2. UTILISATEURS, TIERS (CLIENTS & FOURNISSEURS)
-- =========================================================================

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shop_id BIGINT UNSIGNED NULL, -- Si NULL, super-admin ayant accès à toutes les boutiques
  role_id BIGINT UNSIGNED NULL,
  prenom VARCHAR(120) NULL,
  nom VARCHAR(120) NOT NULL,
  email VARCHAR(190) NULL,
  telephone VARCHAR(30) NULL,
  password_hash VARCHAR(255) NULL,
  auth_provider ENUM('local', 'google', 'apple') NOT NULL DEFAULT 'local',
  google_id VARCHAR(191) NULL,
  apple_id VARCHAR(191) NULL,
  invitation_code VARCHAR(64) NULL,
  email_verified_at DATETIME NULL,
  avatar_url VARCHAR(500) NULL,
  role_legacy ENUM('admin', 'agent') NOT NULL DEFAULT 'agent', -- Rétrocompatibilité
  actif TINYINT(1) NOT NULL DEFAULT 1,
  derniere_connexion DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_google_id (google_id),
  UNIQUE KEY uq_users_apple_id (apple_id),
  UNIQUE KEY uq_users_invitation_code (invitation_code),
  KEY idx_users_actif (actif),
  KEY idx_users_auth_provider (auth_provider),
  CONSTRAINT fk_users_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shop_id BIGINT UNSIGNED NOT NULL,
  nom VARCHAR(120) NOT NULL,
  telephone VARCHAR(30) NULL,
  email VARCHAR(190) NULL,
  dette_actuelle DECIMAL(12,2) NOT NULL DEFAULT 0.00, -- Suivi dynamique de ce que le client doit
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_customers_shop_id (shop_id),
  CONSTRAINT fk_customers_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_customers_dette CHECK (dette_actuelle >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE suppliers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shop_id BIGINT UNSIGNED NOT NULL,
  nom VARCHAR(120) NOT NULL,
  contact_nom VARCHAR(120) NULL,
  telephone VARCHAR(30) NULL,
  email VARCHAR(190) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_suppliers_shop_id (shop_id),
  CONSTRAINT fk_suppliers_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- 3. PRODUITS (SÉPARÉS PAR BOUTIQUE)
-- =========================================================================

CREATE TABLE products (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shop_id BIGINT UNSIGNED NOT NULL,
  code_barre VARCHAR(80) NULL,
  ref VARCHAR(80) NULL,
  nom VARCHAR(190) NOT NULL,
  description TEXT NULL,
  prix_achat DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  prix_vente DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  prix_achat_devise ENUM('USD', 'CDF') NOT NULL DEFAULT 'USD',
  prix_vente_devise ENUM('USD', 'CDF') NOT NULL DEFAULT 'USD',
  prix_achat_montant DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  prix_vente_montant DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  quantite_stock INT NOT NULL DEFAULT 0,
  alerte_stock_min INT NOT NULL DEFAULT 0,
  date_fabrication DATE NULL,
  date_expiration DATE NULL,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_products_shop_barcode (shop_id, code_barre),
  UNIQUE KEY uq_products_shop_ref (shop_id, ref),
  KEY idx_products_nom (nom),
  KEY idx_products_actif (actif),
  KEY idx_products_quantite_stock (quantite_stock),
  KEY idx_products_date_expiration (date_expiration),
  CONSTRAINT fk_products_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_products_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_products_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_products_prix_achat CHECK (prix_achat >= 0),
  CONSTRAINT chk_products_prix_vente CHECK (prix_vente >= 0),
  CONSTRAINT chk_products_prix_achat_montant CHECK (prix_achat_montant >= 0),
  CONSTRAINT chk_products_prix_vente_montant CHECK (prix_vente_montant >= 0),
  CONSTRAINT chk_products_quantite_stock CHECK (quantite_stock >= 0),
  CONSTRAINT chk_products_alerte_stock_min CHECK (alerte_stock_min >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- 4. LOGISTIQUE : APPROVISIONNEMENTS (SUPPLIES)
-- =========================================================================

CREATE TABLE supplies (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shop_id BIGINT UNSIGNED NOT NULL,
  supplier_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL, -- L'administrateur ou acheteur ayant validé l'entrée
  numero_arrivage VARCHAR(50) NOT NULL,
  date_approvisionnement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  total_facture DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  statut ENUM('en_attente', 'reçu', 'annule') NOT NULL DEFAULT 'reçu',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_supplies_numero (numero_arrivage),
  KEY idx_supplies_shop (shop_id),
  CONSTRAINT fk_supplies_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_supplies_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_supplies_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE supply_details (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  supply_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  quantite INT NOT NULL,
  prix_achat_facture DECIMAL(12,2) NOT NULL, -- Prix d'achat spécifique à cet arrivage
  total_ligne DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_supply_details_parent FOREIGN KEY (supply_id) REFERENCES supplies(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_supply_details_product FOREIGN KEY (product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_supply_details_qte CHECK (quantite > 0),
  CONSTRAINT chk_supply_details_prix CHECK (prix_achat_facture >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- 5. TRANSACTIONS : VENTES & SUPPORTS DE FACTURATION (POS)
-- =========================================================================

CREATE TABLE sales (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shop_id BIGINT UNSIGNED NOT NULL,
  customer_id BIGINT UNSIGNED NULL, -- Peut être anonyme (Client Comptant)
  user_id BIGINT UNSIGNED NOT NULL,  -- L'agent qui réalise la vente
  numero_facture VARCHAR(50) NOT NULL,
  date_vente DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  total_montant DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  montant_recu DECIMAL(12,2) NOT NULL DEFAULT 0.00,   -- Argent donné par le client
  montant_dette DECIMAL(12,2) NOT NULL DEFAULT 0.00,  -- Reste à payer si crédit/facture partielle
  mode_paiement ENUM('cash', 'mobile_money', 'carte', 'virement', 'credit', 'mixte') NOT NULL DEFAULT 'cash',
  statut ENUM('validee', 'annulee') NOT NULL DEFAULT 'validee',
  motif_annulation VARCHAR(255) NULL,
  annulee_par BIGINT UNSIGNED NULL,
  annulee_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_sales_numero_facture (numero_facture),
  KEY idx_sales_shop_id (shop_id),
  KEY idx_sales_user_id (user_id),
  KEY idx_sales_date_vente (date_vente),
  KEY idx_sales_statut (statut),
  CONSTRAINT fk_sales_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_sales_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_sales_annulee_par FOREIGN KEY (annulee_par) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_sales_total_montant CHECK (total_montant >= 0),
  CONSTRAINT chk_sales_montant_recu CHECK (montant_recu >= 0),
  CONSTRAINT chk_sales_montant_dette CHECK (montant_dette >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sale_details (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sale_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  quantite INT NOT NULL,
  prix_unitaire_vendu DECIMAL(12,2) NOT NULL,
  prix_achat_unitaire DECIMAL(12,2) NOT NULL DEFAULT 0.00, -- Figé au moment de la vente pour marge nette exacte
  total_ligne DECIMAL(12,2) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_sale_details_sale_id (sale_id),
  KEY idx_sale_details_product_id (product_id),
  CONSTRAINT fk_sale_details_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_sale_details_product FOREIGN KEY (product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_sale_details_quantite CHECK (quantite > 0),
  CONSTRAINT chk_sale_details_prix_unitaire_vendu CHECK (prix_unitaire_vendu >= 0),
  CONSTRAINT chk_sale_details_prix_achat_unitaire CHECK (prix_achat_unitaire >= 0),
  CONSTRAINT chk_sale_details_total_ligne CHECK (total_ligne >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- 6. TRAÇABILITÉ : MOUVEMENTS DE STOCK IMMUABLES
-- =========================================================================

CREATE TABLE stock_movements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shop_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL, -- Qui a fait ou déclenché l'opération
  sale_id BIGINT UNSIGNED NULL,
  supply_id BIGINT UNSIGNED NULL,  -- Si lié à un arrivage de marchandise
  type_mouvement ENUM('entree', 'sortie', 'ajustement', 'annulation') NOT NULL,
  quantite INT NOT NULL,
  stock_avant INT NOT NULL,
  stock_apres INT NOT NULL,
  motif VARCHAR(255) NOT NULL,
  date_mouvement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_stock_movements_shop (shop_id),
  KEY idx_stock_movements_product_id (product_id),
  KEY idx_stock_movements_user_id (user_id),
  KEY idx_stock_movements_type_mouvement (type_mouvement),
  CONSTRAINT fk_stock_movements_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_stock_movements_product FOREIGN KEY (product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_stock_movements_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_stock_movements_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_stock_movements_supply FOREIGN KEY (supply_id) REFERENCES supplies(id) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_stock_movements_quantite CHECK (quantite > 0),
  CONSTRAINT chk_stock_movements_stock_avant CHECK (stock_avant >= 0),
  CONSTRAINT chk_stock_movements_stock_apres CHECK (stock_apres >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- 7. FINANCES : GESTION DES DÉPENSES OPÉRATIONNELLES (CHARGES)
-- =========================================================================

CREATE TABLE expenses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shop_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL, -- Qui a enregistré la dépense
  titre VARCHAR(120) NOT NULL,      -- Ex: Facture SNEL, Carburant groupe, Transport
  description TEXT NULL,
  montant DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  categorie ENUM('transport', 'facture', 'loyer', 'salaire', 'perte_avarie', 'autre') NOT NULL DEFAULT 'autre',
  date_depense DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_expenses_shop (shop_id),
  KEY idx_expenses_category (categorie),
  CONSTRAINT fk_expenses_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_expenses_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_expenses_montant CHECK (montant > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- 8. TRIGGERS SÉCURITÉ - CONTRE LE VOL ET LA MODIFICATION RETROACTIVE
-- =========================================================================

DELIMITER $$

-- 1. Protection du stock global : Obligation de passer par un mouvement
CREATE TRIGGER trg_products_before_update_stock_guard
BEFORE UPDATE ON products
FOR EACH ROW
BEGIN
  IF NEW.quantite_stock <> OLD.quantite_stock AND COALESCE(@allow_stock_update, 0) <> 1 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Modification directe du stock interdite: creer un mouvement de stock.';
  END IF;
END$$

-- 2. Calcul automatique de la ligne de vente (quantité * PU)
CREATE TRIGGER trg_sale_details_before_insert_calculate_total
BEFORE INSERT ON sale_details
FOR EACH ROW
BEGIN
  SET NEW.total_ligne = NEW.quantite * NEW.prix_unitaire_vendu;
END$$

-- 3. Verrouillage strict des lignes de facturation (Empêche l'agent de modifier après encaissement)
CREATE TRIGGER trg_sale_details_before_update_block
BEFORE UPDATE ON sale_details
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Modification interdite: les details de vente sont non modifiables.';
END$$

CREATE TRIGGER trg_sale_details_before_delete_block
BEFORE DELETE ON sale_details
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Suppression interdite: les details de vente sont non supprimables.';
END$$

-- 4. Verrouillage de l'en-tête de vente : Pas d'effacement physique
CREATE TRIGGER trg_sales_before_delete_block
BEFORE DELETE ON sales
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Suppression interdite: une vente doit rester historisee pour audit.';
END$$

-- 5. Immuabilité absolue de l'historique des flux de stock (Audit-trail intègre)
CREATE TRIGGER trg_stock_movements_before_update_block
BEFORE UPDATE ON stock_movements
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Modification interdite: le journal des mouvements de stock est immuable.';
END$$

CREATE TRIGGER trg_stock_movements_before_delete_block
BEFORE DELETE ON stock_movements
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Suppression interdite: le journal des mouvements de stock est immuable.';
END$$

-- 6. Verrouillage de l'historique des dépenses après insertion (Évite le blanchiment ou masquage de vol)
CREATE TRIGGER trg_expenses_before_update_block
BEFORE UPDATE ON expenses
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Modification interdite: Une depense enregistree ne peut plus etre alteree.';
END$$

CREATE TRIGGER trg_expenses_before_delete_block
BEFORE DELETE ON expenses
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Suppression interdite: Une depense validee ne peut pas etre supprimee.';
END$$

DELIMITER ;

-- =========================================================================
-- 9. DONNÉES D'INITIALISATION SÉCURISÉES
-- =========================================================================

-- Création de la boutique témoin principale
INSERT INTO shops (id, nom, adresse, telephone, email, devise_principale, taux_change_cdf, actif)
VALUES (1, 'Boutique Pilote - Centre Ville', 'Av. Principale No 10', '+243000000000', NULL, 'USD', 2400.0000, 1);

-- Création des rôles par défaut
INSERT INTO roles (id, nom, permissions) 
VALUES 
(1, 'Super Admin', '{"all": true}'),
(2, 'Gerant', '{"sales_view":true,"stock_adjust":true,"expenses_add":true}'),
(3, 'Caissier', '{"pos_access":true}');

-- Insertion du compte Administrateur Principal (Rattaché à la boutique 1 par défaut)
INSERT INTO users (nom, email, password_hash, role_legacy, role_id, shop_id, actif)
VALUES (
  'Administrateur',
  'admin@example.com',
  '$2y$10$P6GArcijgFX6rQVQQTxxg.TusYWUJObGMjjfuMtJOB1B.dHskS2JC', -- Défaut: admin123
  'admin',
  1,
  1,
  1
);
