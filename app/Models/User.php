<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/Model.php';

final class User extends Model
{
    private const OAUTH_COLUMNS = ['google_id', 'apple_id'];
    private const AUTH_PROVIDERS = ['local', 'google', 'apple'];
    private const LEGACY_ROLES = ['admin', 'agent'];

    public function findByEmail(string $email, bool $activeOnly = true): ?array
    {
        $sql = $this->baseSelect() . ' WHERE users.email = :email';

        if ($activeOnly) {
            $sql .= ' AND users.actif = 1';
        }

        $sql .= ' LIMIT 1';

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['email' => strtolower(trim($email))]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    public function findById(int $id, ?int $shopId = null, bool $activeOnly = true): ?array
    {
        $sql = $this->baseSelect() . ' WHERE users.id = :id';
        $params = ['id' => $id];

        if ($shopId !== null) {
            $sql .= ' AND users.shop_id = :shop_id';
            $params['shop_id'] = $shopId;
        }

        if ($activeOnly) {
            $sql .= ' AND users.actif = 1';
        }

        $sql .= ' LIMIT 1';

        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    public function findOAuthUser(string $idColumn, string $providerId, string $email, bool $activeOnly = true): ?array
    {
        $this->assertOAuthColumn($idColumn);

        $sql = $this->baseSelect()
            . " WHERE (users.{$idColumn} = :provider_id OR users.email = :email)";

        if ($activeOnly) {
            $sql .= ' AND users.actif = 1';
        }

        $sql .= ' LIMIT 1';

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'provider_id' => $providerId,
            'email' => strtolower(trim($email)),
        ]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    public function allByShop(int $shopId, bool $activeOnly = true): array
    {
        $sql = $this->baseSelect() . ' WHERE users.shop_id = :shop_id';

        if ($activeOnly) {
            $sql .= ' AND users.actif = 1';
        }

        $sql .= ' ORDER BY users.nom ASC';

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['shop_id' => $shopId]);

        return $statement->fetchAll();
    }

    public function allForSuperAdmin(bool $activeOnly = true): array
    {
        $sql = $this->baseSelect();

        if ($activeOnly) {
            $sql .= ' WHERE users.actif = 1';
        }

        $sql .= ' ORDER BY shops.nom ASC, users.nom ASC';

        return Database::connection()->query($sql)->fetchAll();
    }

    public function create(array $data, ?int $shopId = null): int
    {
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');
        $passwordHash = $data['password_hash'] ?? null;
        $authProvider = $this->normalizeAuthProvider((string) ($data['auth_provider'] ?? 'local'));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Email utilisateur invalide.');
        }

        if ($authProvider === 'local' && $passwordHash === null) {
            if ($password === '') {
                throw new InvalidArgumentException('Mot de passe obligatoire pour un compte local.');
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        }

        $statement = Database::connection()->prepare(
            'INSERT INTO users (
                shop_id, role_id, nom, email, telephone, password_hash, auth_provider,
                google_id, apple_id, email_verified_at, avatar_url, role_legacy, actif
             ) VALUES (
                :shop_id, :role_id, :nom, :email, :telephone, :password_hash, :auth_provider,
                :google_id, :apple_id, :email_verified_at, :avatar_url, :role_legacy, :actif
             )'
        );

        $statement->execute([
            'shop_id' => $shopId ?? $this->nullablePositiveInt($data['shop_id'] ?? null),
            'role_id' => $this->nullablePositiveInt($data['role_id'] ?? null),
            'nom' => trim((string) ($data['nom'] ?? '')),
            'email' => $email,
            'telephone' => $this->nullableString($data['telephone'] ?? null),
            'password_hash' => $passwordHash,
            'auth_provider' => $authProvider,
            'google_id' => $this->nullableString($data['google_id'] ?? null),
            'apple_id' => $this->nullableString($data['apple_id'] ?? null),
            'email_verified_at' => $data['email_verified_at'] ?? null,
            'avatar_url' => $this->nullableString($data['avatar_url'] ?? null),
            'role_legacy' => $this->normalizeLegacyRole((string) ($data['role_legacy'] ?? 'agent')),
            'actif' => isset($data['actif']) ? (int) (bool) $data['actif'] : 1,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function createLocal(
        string $name,
        string $email,
        string $password,
        ?int $shopId,
        ?int $roleId,
        string $legacyRole = 'agent',
        ?string $phone = null
    ): int {
        return $this->create([
            'nom' => $name,
            'email' => $email,
            'password' => $password,
            'shop_id' => $shopId,
            'role_id' => $roleId,
            'role_legacy' => $legacyRole,
            'telephone' => $phone,
            'auth_provider' => 'local',
            'actif' => 1,
        ]);
    }

    public function createOAuth(
        string $provider,
        string $providerId,
        string $name,
        string $email,
        ?int $shopId,
        ?int $roleId,
        string $legacyRole = 'agent',
        ?string $avatarUrl = null
    ): int {
        $provider = $this->normalizeAuthProvider($provider);

        if ($provider === 'local') {
            throw new InvalidArgumentException('Provider OAuth invalide.');
        }

        return $this->create([
            'nom' => $name,
            'email' => $email,
            'password_hash' => null,
            'shop_id' => $shopId,
            'role_id' => $roleId,
            'role_legacy' => $legacyRole,
            'auth_provider' => $provider,
            'google_id' => $provider === 'google' ? $providerId : null,
            'apple_id' => $provider === 'apple' ? $providerId : null,
            'email_verified_at' => date('Y-m-d H:i:s'),
            'avatar_url' => $avatarUrl,
            'actif' => 1,
        ]);
    }

    public function updateByShop(int $id, int $shopId, array $data): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE users
             SET role_id = :role_id,
                 nom = :nom,
                 telephone = :telephone,
                 role_legacy = :role_legacy,
                 actif = :actif
             WHERE id = :id AND shop_id = :shop_id'
        );

        $statement->execute([
            'role_id' => $this->nullablePositiveInt($data['role_id'] ?? null),
            'nom' => trim((string) ($data['nom'] ?? '')),
            'telephone' => $this->nullableString($data['telephone'] ?? null),
            'role_legacy' => $this->normalizeLegacyRole((string) ($data['role_legacy'] ?? 'agent')),
            'actif' => isset($data['actif']) ? (int) (bool) $data['actif'] : 1,
            'id' => $id,
            'shop_id' => $shopId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function updatePassword(int $id, string $password, ?int $shopId = null): bool
    {
        if ($password === '') {
            throw new InvalidArgumentException('Mot de passe vide.');
        }

        $sql = 'UPDATE users
                SET password_hash = :password_hash,
                    auth_provider = CASE WHEN auth_provider IN ("google", "apple") THEN auth_provider ELSE "local" END
                WHERE id = :id';
        $params = [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'id' => $id,
        ];

        if ($shopId !== null) {
            $sql .= ' AND shop_id = :shop_id';
            $params['shop_id'] = $shopId;
        }

        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount() > 0;
    }

    public function linkProviderIfNeeded(
        int $userId,
        string $provider,
        string $idColumn,
        string $providerId,
        ?string $avatarUrl = null,
        ?int $shopId = null
    ): bool {
        $provider = $this->normalizeAuthProvider($provider);
        $this->assertOAuthColumn($idColumn);

        if ($provider === 'local') {
            throw new InvalidArgumentException('Provider OAuth invalide.');
        }

        $sql = "UPDATE users
                SET {$idColumn} = COALESCE({$idColumn}, :provider_id),
                    auth_provider = :provider,
                    email_verified_at = CASE WHEN email_verified_at IS NULL THEN NOW() ELSE email_verified_at END,
                    avatar_url = COALESCE(avatar_url, :avatar_url)
                WHERE id = :id";
        $params = [
            'provider_id' => $providerId,
            'provider' => $provider,
            'avatar_url' => $avatarUrl,
            'id' => $userId,
        ];

        if ($shopId !== null) {
            $sql .= ' AND shop_id = :shop_id';
            $params['shop_id'] = $shopId;
        }

        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount() > 0;
    }

    public function touchLastLogin(int $userId): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE users SET derniere_connexion = NOW() WHERE id = :id AND actif = 1'
        );
        $statement->execute(['id' => $userId]);

        return $statement->rowCount() > 0;
    }

    public function deactivateByShop(int $id, int $shopId): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE users SET actif = 0 WHERE id = :id AND shop_id = :shop_id'
        );
        $statement->execute([
            'id' => $id,
            'shop_id' => $shopId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function sessionPayload(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'nom' => (string) $user['nom'],
            'email' => (string) $user['email'],
            'role' => $this->resolveRole($user),
            'role_id' => isset($user['role_id']) ? (int) $user['role_id'] : null,
            'role_legacy' => (string) ($user['role_legacy'] ?? 'agent'),
            'shop_id' => isset($user['shop_id']) ? (int) $user['shop_id'] : null,
            'auth_provider' => (string) ($user['auth_provider'] ?? 'local'),
        ];
    }

    public function resolveRole(array $user): string
    {
        $roleName = strtolower(trim((string) ($user['role_name'] ?? '')));

        return match ($roleName) {
            'super admin', 'super_admin', 'admin', 'administrateur' => 'admin',
            'gerant', 'gérant', 'manager' => 'gerant',
            'caissier', 'agent', 'vendeur' => 'agent',
            default => strtolower((string) ($user['role_legacy'] ?? 'agent')),
        };
    }

    public function verifyPassword(array $user, string $password): bool
    {
        $hash = $user['password_hash'] ?? null;

        return is_string($hash) && $hash !== '' && password_verify($password, $hash);
    }

    private function baseSelect(): string
    {
        return 'SELECT
                    users.*,
                    roles.nom AS role_name,
                    shops.nom AS shop_name
                FROM users
                LEFT JOIN roles ON roles.id = users.role_id
                LEFT JOIN shops ON shops.id = users.shop_id';
    }

    private function assertOAuthColumn(string $column): void
    {
        if (!in_array($column, self::OAUTH_COLUMNS, true)) {
            throw new InvalidArgumentException('Colonne OAuth non autorisee.');
        }
    }

    private function normalizeAuthProvider(string $provider): string
    {
        $provider = strtolower(trim($provider));

        if (!in_array($provider, self::AUTH_PROVIDERS, true)) {
            throw new InvalidArgumentException('Fournisseur d authentification invalide.');
        }

        return $provider;
    }

    private function normalizeLegacyRole(string $role): string
    {
        $role = strtolower(trim($role));

        return in_array($role, self::LEGACY_ROLES, true) ? $role : 'agent';
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        $value = (int) ($value ?? 0);

        return $value > 0 ? $value : null;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
