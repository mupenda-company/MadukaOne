-- Les rôles Admin et Super Admin restent exclusivement dans l'espace SaaS.
-- Le créateur/propriétaire d'une boutique reçoit le rôle Propriétaire.

INSERT INTO roles (nom, permissions)
SELECT 'Propriétaire', '{"all":true}'
WHERE NOT EXISTS (
    SELECT 1 FROM roles WHERE LOWER(nom) IN ('propriétaire', 'proprietaire')
);

SET @owner_role_id := (
    SELECT id FROM roles WHERE LOWER(nom) IN ('propriétaire', 'proprietaire') ORDER BY id LIMIT 1
);

SET @manager_role_id := (
    SELECT id FROM roles WHERE LOWER(nom) IN ('gérant', 'gerant') ORDER BY id LIMIT 1
);

-- Complète les anciennes boutiques qui ne possédaient pas encore owner_user_id.
UPDATE shops AS s
SET s.owner_user_id = (
    SELECT MIN(u.id)
    FROM users AS u
    INNER JOIN roles AS r ON r.id = u.role_id
    WHERE u.shop_id = s.id
      AND LOWER(REPLACE(REPLACE(r.nom, '-', ' '), '_', ' ')) IN ('gérant', 'gerant', 'manager')
)
WHERE s.owner_user_id IS NULL;

-- Les propriétaires déjà identifiés deviennent Propriétaires, sauf les comptes SaaS réservés.
UPDATE users AS u
INNER JOIN shops AS s ON s.owner_user_id = u.id
LEFT JOIN roles AS current_role ON current_role.id = u.role_id
SET u.role_id = @owner_role_id,
    u.role_legacy = 'admin'
WHERE u.shop_id IS NOT NULL
  AND LOWER(REPLACE(REPLACE(current_role.nom, '-', ' '), '_', ' ')) NOT IN (
      'admin', 'administrateur', 'administratrice', 'super admin', 'super administrateur', 'super administratrice'
  );

-- Aucun utilisateur rattaché à une boutique ne peut conserver un rôle SaaS réservé.
UPDATE users AS u
INNER JOIN roles AS current_role ON current_role.id = u.role_id
SET u.role_id = @manager_role_id,
    u.role_legacy = 'admin'
WHERE u.shop_id IS NOT NULL
  AND @manager_role_id IS NOT NULL
  AND LOWER(REPLACE(REPLACE(current_role.nom, '-', ' '), '_', ' ')) IN (
      'admin', 'administrateur', 'administratrice', 'super admin', 'super administrateur', 'super administratrice'
  );
