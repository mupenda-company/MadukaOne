<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/Model.php';

final class Customer extends Model
{
    public function allByShop(int $shopId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, shop_id, nom, telephone, email, dette_actuelle, created_at, updated_at
             FROM customers
             WHERE shop_id = :shop_id
             ORDER BY nom ASC'
        );
        $statement->execute(['shop_id' => $shopId]);

        return $statement->fetchAll();
    }

    public function findByShop(int $id, int $shopId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, shop_id, nom, telephone, email, dette_actuelle, created_at, updated_at
             FROM customers
             WHERE id = :id AND shop_id = :shop_id
             LIMIT 1'
        );
        $statement->execute([
            'id' => $id,
            'shop_id' => $shopId,
        ]);

        $customer = $statement->fetch();

        return is_array($customer) ? $customer : null;
    }

    public function create(array $data, int $shopId): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO customers (shop_id, nom, telephone, email, dette_actuelle)
             VALUES (:shop_id, :nom, :telephone, :email, :dette_actuelle)'
        );
        $statement->execute([
            'shop_id' => $shopId,
            'nom' => trim((string) $data['nom']),
            'telephone' => $this->nullableString($data['telephone'] ?? null),
            'email' => $this->nullableString($data['email'] ?? null),
            'dette_actuelle' => max(0, (float) ($data['dette_actuelle'] ?? 0)),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function updateByShop(int $id, int $shopId, array $data): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE customers
             SET nom = :nom,
                 telephone = :telephone,
                 email = :email,
                 dette_actuelle = :dette_actuelle
             WHERE id = :id AND shop_id = :shop_id'
        );
        $statement->execute([
            'nom' => trim((string) $data['nom']),
            'telephone' => $this->nullableString($data['telephone'] ?? null),
            'email' => $this->nullableString($data['email'] ?? null),
            'dette_actuelle' => max(0, (float) ($data['dette_actuelle'] ?? 0)),
            'id' => $id,
            'shop_id' => $shopId,
        ]);

        return $statement->rowCount() > 0 || $this->findByShop($id, $shopId) !== null;
    }

    public function deleteByShop(int $id, int $shopId): bool
    {
        $statement = Database::connection()->prepare(
            'DELETE FROM customers WHERE id = :id AND shop_id = :shop_id'
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
