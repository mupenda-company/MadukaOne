<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/Model.php';

class Role extends Model
{
    private const RESERVED_COMPACT_NAMES = [
        'superadmin',
        'superadministrateur',
        'superadministratrice',
        'superuser',
        'superusers',
        'superutilisateur',
        'superutilisateurs',
    ];

    private const RESERVED_EXACT_NAMES = [
        'root',
        'owner',
        'saas admin',
        'admin saas',
        'system admin',
        'admin systeme',
        'administrateur systeme',
    ];

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
             GROUP BY roles.id, roles.nom, roles.permissions, roles.created_at
             ORDER BY roles.nom ASC"
        );

        return array_values(array_filter(
            $statement->fetchAll(),
            fn (array $role): bool => !$this->isSaasRoleName((string) ($role['nom'] ?? ''))
        ));
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
        $normalized = $this->normalizeRoleName($name);

        if ($normalized === '') {
            return false;
        }

        if (in_array($normalized, self::RESERVED_EXACT_NAMES, true)) {
            return true;
        }

        if (preg_match('/\bsuper\s+(admin|administrateur|administratrice|user|users|utilisateur|utilisateurs)\b/', $normalized) === 1) {
            return true;
        }

        $compact = preg_replace('/[^a-z0-9]+/', '', $normalized) ?? '';

        return in_array($compact, self::RESERVED_COMPACT_NAMES, true);
    }

    private function normalizeRoleName(string $name): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        $normalized = strtolower((string) $ascii);
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? $normalized;

        return trim(preg_replace('/\s+/', ' ', $normalized) ?? $normalized);
    }
}
