ALTER TABLE users
  MODIFY email VARCHAR(190) NULL,
  ADD COLUMN prenom VARCHAR(120) NULL AFTER role_id,
  ADD COLUMN invitation_code VARCHAR(64) NULL AFTER apple_id,
  ADD UNIQUE KEY uq_users_invitation_code (invitation_code);
