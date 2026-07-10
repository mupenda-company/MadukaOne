INSERT INTO roles (nom, permissions)
VALUES ('Super Admin', '{"all": true}')
ON DUPLICATE KEY UPDATE
  permissions = CASE
    WHEN permissions IS NULL OR permissions = '' THEN VALUES(permissions)
    ELSE permissions
  END;

INSERT INTO users (
  shop_id,
  role_id,
  prenom,
  nom,
  email,
  password_hash,
  auth_provider,
  role_legacy,
  actif
)
SELECT
  NULL,
  roles.id,
  'Super',
  'Administrateur 2',
  'superadmin2@example.com',
  '$2y$10$librUjaQOSXAkoqVm5GBz.H/eFMXyL7noC99lbNC/309Szk4aYCEi',
  'local',
  'admin',
  1
FROM roles
WHERE roles.nom = 'Super Admin'
ON DUPLICATE KEY UPDATE
  shop_id = NULL,
  role_id = VALUES(role_id),
  auth_provider = 'local',
  role_legacy = 'admin',
  actif = 1,
  password_hash = COALESCE(users.password_hash, VALUES(password_hash));