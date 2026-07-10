SET @add_limit_column = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE saas_subscription_plans ADD COLUMN limite_boutiques INT NULL AFTER code',
        'DO 0'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'saas_subscription_plans'
      AND COLUMN_NAME = 'limite_boutiques'
);
PREPARE add_limit_column_statement FROM @add_limit_column;
EXECUTE add_limit_column_statement;
DEALLOCATE PREPARE add_limit_column_statement;

INSERT INTO saas_subscription_plans
    (nom, code, limite_boutiques, prix_mensuel_usd, limite_utilisateurs, limite_produits, description, actif)
VALUES
    (
        'Starter',
        'starter',
        1,
        3.00,
        2,
        NULL,
        '1 boutique\n2 utilisateurs\nCaisse et tickets\nGestion des produits\nStock initial et seuils\nClients simples\nCreances clients\nRapports essentiels\nDevise boutique\nAssistance standard',
        1
    ),
    (
        'Business',
        'business',
        3,
        7.00,
        8,
        NULL,
        '3 boutiques\n8 utilisateurs\nToutes les fonctions Starter\nApprovisionnements fournisseurs\nAjustements de stock\nInventaire complet\nCharges et depenses\nHistorique des ventes\nRapports complets\nExports et impressions',
        1
    ),
    (
        'Pro',
        'pro',
        8,
        12.00,
        20,
        NULL,
        '8 boutiques\n20 utilisateurs\nToutes les fonctions Business\nGestion avancee des roles\nSuivi multi-boutiques\nAlertes stock renforcees\nAnalyse marges et charges\nSuivi avance des creances\nRapports financiers detailles\nAssistance prioritaire',
        1
    ),
    (
        'Reseau',
        'reseau',
        NULL,
        20.00,
        NULL,
        NULL,
        'Boutiques illimitees\nUtilisateurs illimites\nToutes les fonctions Pro\nPilotage reseau\nCentralisation des rapports\nGestion etendue des equipes\nSuivi global stock et ventes\nParametrage personnalise\nAccompagnement de deploiement\nAssistance prioritaire renforcee',
        1
    )
ON DUPLICATE KEY UPDATE
    nom = VALUES(nom),
    limite_boutiques = VALUES(limite_boutiques),
    prix_mensuel_usd = VALUES(prix_mensuel_usd),
    limite_utilisateurs = VALUES(limite_utilisateurs),
    limite_produits = VALUES(limite_produits),
    description = VALUES(description),
    actif = VALUES(actif);

UPDATE saas_subscription_plans
SET actif = 0
WHERE code = 'enterprise';
