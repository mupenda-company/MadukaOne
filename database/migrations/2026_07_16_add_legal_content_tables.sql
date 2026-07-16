SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS saas_privacy_sections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(180) NOT NULL,
    contenu TEXT NOT NULL,
    ordre INT UNSIGNED NOT NULL DEFAULT 0,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_privacy_publication (actif, ordre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS saas_terms_sections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(180) NOT NULL,
    contenu TEXT NOT NULL,
    ordre INT UNSIGNED NOT NULL DEFAULT 0,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_terms_publication (actif, ordre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO saas_privacy_sections (titre, contenu, ordre)
SELECT 'Données collectées', 'Informations de compte : nom, email, rôle, boutique rattachée et état du compte.\nDonnées commerciales saisies dans l’application : ventes, produits, stock, clients, fournisseurs, dépenses et rapports.\nDonnées techniques utiles à la sécurité : dernière connexion, fournisseur d’authentification et journal applicatif.', 10
WHERE NOT EXISTS (SELECT 1 FROM saas_privacy_sections);

INSERT INTO saas_privacy_sections (titre, contenu, ordre)
SELECT 'Utilisation des données', 'Permettre l’accès aux modules autorisés selon le profil utilisateur.\nAssurer la traçabilité des ventes, mouvements de stock et opérations sensibles.\nProduire des rapports de gestion pour la boutique active.', 20
WHERE (SELECT COUNT(*) FROM saas_privacy_sections) = 1;

INSERT INTO saas_privacy_sections (titre, contenu, ordre)
SELECT 'Protection et conservation', 'Les mots de passe sont stockés sous forme de hash et ne sont pas affichés par l’application.\nLes données opérationnelles sont conservées pour les besoins d’audit, de comptabilité et de suivi commercial.\nLes accès doivent rester personnels ; chaque utilisateur est responsable de la confidentialité de son compte.', 30
WHERE (SELECT COUNT(*) FROM saas_privacy_sections) = 2;

INSERT INTO saas_privacy_sections (titre, contenu, ordre)
SELECT 'Contact et demandes', 'Toute demande relative aux données doit être adressée à l’administrateur de la boutique ou au responsable technique de l’installation MadukaOne concernée.', 40
WHERE (SELECT COUNT(*) FROM saas_privacy_sections) = 3;

INSERT INTO saas_terms_sections (titre, contenu, ordre)
SELECT 'Accès à l’application', 'MadukaOne est réservé aux utilisateurs autorisés par l’administrateur de la boutique. Les identifiants sont personnels et ne doivent pas être partagés.', 10
WHERE NOT EXISTS (SELECT 1 FROM saas_terms_sections);

INSERT INTO saas_terms_sections (titre, contenu, ordre)
SELECT 'Utilisation professionnelle', 'Les modules de caisse, stock, clients, fournisseurs, dépenses et rapports doivent être utilisés pour des opérations réelles, vérifiables et conformes aux règles internes de la boutique.', 20
WHERE (SELECT COUNT(*) FROM saas_terms_sections) = 1;

INSERT INTO saas_terms_sections (titre, contenu, ordre)
SELECT 'Exactitude des données', 'Chaque utilisateur doit saisir des informations exactes. Les ventes, mouvements de stock, règlements et dépenses peuvent avoir un impact direct sur les rapports financiers.', 30
WHERE (SELECT COUNT(*) FROM saas_terms_sections) = 2;

INSERT INTO saas_terms_sections (titre, contenu, ordre)
SELECT 'Opérations sensibles', 'Certaines actions sont limitées par rôle et peuvent demander une confirmation. Les journaux de vente et de stock doivent rester exploitables pour l’audit.', 40
WHERE (SELECT COUNT(*) FROM saas_terms_sections) = 3;

INSERT INTO saas_terms_sections (titre, contenu, ordre)
SELECT 'Disponibilité et maintenance', 'Des interruptions peuvent intervenir pendant les opérations de maintenance, de sauvegarde ou de correction technique. Les responsables doivent organiser les contrôles nécessaires.', 50
WHERE (SELECT COUNT(*) FROM saas_terms_sections) = 4;
