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

    public function settleDebtByShop(int $id, int $shopId, float $amount): array
    {
        $amount = round(max(0.0, $amount), 2);

        if ($amount <= 0) {
            throw new InvalidArgumentException('Le montant du règlement doit être supérieur à zéro.');
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $customer = $this->lockByShop($db, $id, $shopId);

            if ($customer === null) {
                $db->rollBack();
                return ['settled' => 0.0, 'remaining_debt' => 0.0, 'updated_sales' => 0];
            }

            $currentDebt = (float) ($customer['dette_actuelle'] ?? 0);
            $settled = min($amount, $currentDebt);

            if ($settled <= 0) {
                $db->commit();
                return ['settled' => 0.0, 'remaining_debt' => 0.0, 'updated_sales' => 0];
            }

            $remainingPayment = $settled;
            $updatedSales = 0;
            $sales = $this->creditSalesForCustomer($db, $id, $shopId);

            foreach ($sales as $sale) {
                if ($remainingPayment <= 0) {
                    break;
                }

                $saleDebt = (float) ($sale['montant_dette'] ?? 0);
                if ($saleDebt <= 0) {
                    continue;
                }

                $applied = min($remainingPayment, $saleDebt);
                $statement = $db->prepare(
                    'UPDATE sales
                     SET montant_recu = montant_recu + :applied_received,
                         montant_dette = GREATEST(0, montant_dette - :applied_debt)
                     WHERE id = :id AND shop_id = :shop_id AND customer_id = :customer_id'
                );
                $statement->execute([
                    'applied_received' => $applied,
                    'applied_debt' => $applied,
                    'id' => (int) $sale['id'],
                    'shop_id' => $shopId,
                    'customer_id' => $id,
                ]);

                $remainingPayment = round($remainingPayment - $applied, 2);
                $updatedSales++;
            }

            $statement = $db->prepare(
                'UPDATE customers
                 SET dette_actuelle = GREATEST(0, dette_actuelle - :settled)
                 WHERE id = :id AND shop_id = :shop_id'
            );
            $statement->execute([
                'settled' => $settled,
                'id' => $id,
                'shop_id' => $shopId,
            ]);

            $db->commit();

            return [
                'settled' => $settled,
                'remaining_debt' => max(0.0, $currentDebt - $settled),
                'updated_sales' => $updatedSales,
            ];
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    private function lockByShop(PDO $db, int $id, int $shopId): ?array
    {
        $statement = $db->prepare(
            'SELECT id, shop_id, dette_actuelle
             FROM customers
             WHERE id = :id AND shop_id = :shop_id
             LIMIT 1
             FOR UPDATE'
        );
        $statement->execute([
            'id' => $id,
            'shop_id' => $shopId,
        ]);

        $customer = $statement->fetch();

        return is_array($customer) ? $customer : null;
    }

    private function creditSalesForCustomer(PDO $db, int $customerId, int $shopId): array
    {
        $statement = $db->prepare(
            'SELECT id, montant_dette
             FROM sales
             WHERE shop_id = :shop_id
               AND customer_id = :customer_id
               AND statut = "validee"
               AND montant_dette > 0
             ORDER BY date_vente ASC, id ASC
             FOR UPDATE'
        );
        $statement->execute([
            'shop_id' => $shopId,
            'customer_id' => $customerId,
        ]);

        return $statement->fetchAll();
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
