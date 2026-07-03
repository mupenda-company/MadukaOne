<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/Model.php';

final class Supplier extends Model
{
    public function allByShop(int $shopId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, shop_id, nom, contact_nom, telephone, email, created_at, updated_at
             FROM suppliers
             WHERE shop_id = :shop_id
             ORDER BY nom ASC'
        );
        $statement->execute(['shop_id' => $shopId]);

        return $statement->fetchAll();
    }

    public function create(array $data, int $shopId): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO suppliers (shop_id, nom, contact_nom, telephone, email)
             VALUES (:shop_id, :nom, :contact_nom, :telephone, :email)'
        );
        $statement->execute([
            'shop_id' => $shopId,
            'nom' => trim((string) $data['nom']),
            'contact_nom' => $this->nullableString($data['contact_nom'] ?? null),
            'telephone' => $this->nullableString($data['telephone'] ?? null),
            'email' => $this->nullableString($data['email'] ?? null),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function findByShop(int $id, int $shopId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, shop_id, nom, contact_nom, telephone, email, created_at, updated_at
             FROM suppliers
             WHERE id = :id AND shop_id = :shop_id
             LIMIT 1'
        );
        $statement->execute([
            'id' => $id,
            'shop_id' => $shopId,
        ]);

        $supplier = $statement->fetch();

        return is_array($supplier) ? $supplier : null;
    }

    public function updateByShop(int $id, int $shopId, array $data): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE suppliers
             SET nom = :nom,
                 contact_nom = :contact_nom,
                 telephone = :telephone,
                 email = :email
             WHERE id = :id AND shop_id = :shop_id'
        );
        $statement->execute([
            'nom' => trim((string) $data['nom']),
            'contact_nom' => $this->nullableString($data['contact_nom'] ?? null),
            'telephone' => $this->nullableString($data['telephone'] ?? null),
            'email' => $this->nullableString($data['email'] ?? null),
            'id' => $id,
            'shop_id' => $shopId,
        ]);

        return $statement->rowCount() > 0 || $this->findByShop($id, $shopId) !== null;
    }

    public function deleteByShop(int $id, int $shopId): bool
    {
        $statement = Database::connection()->prepare(
            'DELETE FROM suppliers WHERE id = :id AND shop_id = :shop_id'
        );
        $statement->execute([
            'id' => $id,
            'shop_id' => $shopId,
        ]);

        return $statement->rowCount() > 0;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}

