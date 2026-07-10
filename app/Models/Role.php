<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/Model.php';

class Role extends Model
{
    private const SAAS_ROLE_NAMES = ['super admin', 'super_admin', 'super-administrateur', 'super administrateur'];

    public function allWithUsage(): array
    {
        $statement = Database::connection()->query(
            "SELECT
                roles.id,
                roles.nom,
                roles.permissions,
                roles.created_at,
                COUNT(users.id) AS users_count
             FROM roles
             LEFT JOIN users ON users.role_id = roles.id
             WHERE LOWER(REPLACE(REPLACE(roles.nom, '-', ' '), '_', ' ')) NOT IN ('super admin', 'super administrateur')
             GROUP BY roles.id, roles.nom, roles.permissions, roles.created_at
             ORDER BY roles.nom ASC"
        );

        return $statement->fetchAll();
    }

    public function create(array $data): int
    {
        $name = trim((string) ($data['nom'] ?? ''));

        if ($this->isSaasRoleName($name)) {
            throw new InvalidArgumentException('Ce role est reserve a l espace SaaS.');
        }

        $statement = Database::connection()->prepare(
            'INSERT INTO roles (nom, permissions)
             VALUES (:nom, :permissions)'
        );

        $statement->execute([
            'nom' => $name,
            'permissions' => $data['permissions'] ?? null,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function isAssignableInShop(int $roleId): bool
    {
        if ($roleId < 1) {
            return false;
        }

        $statement = Database::connection()->prepare('SELECT nom FROM roles WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $roleId]);
        $name = $statement->fetchColumn();

        return is_string($name) && !$this->isSaasRoleName($name);
    }

    public function isSaasRoleName(string $name): bool
    {
        $normalized = strtolower(trim(str_replace(['-', '_'], ' ', $name)));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return in_array($normalized, self::SAAS_ROLE_NAMES, true);
    }
}
