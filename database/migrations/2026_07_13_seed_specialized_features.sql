INSERT INTO saas_features (code, nom, description, categorie, actif)
VALUES
  ('pharmacy', 'Module pharmacie', 'Dosages, formes pharmaceutiques, lots, ordonnances et alertes d expiration.', 'metier', 1),
  ('fashion', 'Module vetements', 'Tailles, couleurs, marques, collections et variantes textiles.', 'metier', 1)
ON DUPLICATE KEY UPDATE
  nom = VALUES(nom),
  description = VALUES(description),
  categorie = VALUES(categorie),
  actif = 1;
