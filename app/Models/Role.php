<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/Model.php';

class Role extends Model
{
    public function allWithUsage(): array
    {
        $statement = Database::connection()->query(
            'SELECT
                roles.id,
                roles.nom,
                roles.permissions,
                roles.created_at,
                COUNT(users.id) AS users_count
             FROM roles
             LEFT JOIN users ON users.role_id = roles.id
             GROUP BY roles.id, roles.nom, roles.permissions, roles.created_at
             ORDER BY roles.nom ASC'
        );

        return $statement->fetchAll();
    }

    public function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO roles (nom, permissions)
             VALUES (:nom, :permissions)'
        );

        $statement->execute([
            'nom' => trim((string) ($data['nom'] ?? '')),
            'permissions' => $data['permissions'] ?? null,
        ]);

        return (int) Database::connection()->lastInsertId();
    }
}

