<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/Model.php';
require_once dirname(__DIR__) . '/Models/SupplyDetail.php';

final class Supply extends Model
{
    private SupplyDetail $details;

    public function __construct()
    {
        $this->details = new SupplyDetail();
    }

    public function allByShop(int $shopId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT supplies.*, suppliers.nom AS supplier_name, users.nom AS user_name
             FROM supplies
             INNER JOIN suppliers ON suppliers.id = supplies.supplier_id
             INNER JOIN users ON users.id = supplies.user_id
             WHERE supplies.shop_id = :shop_id
             ORDER BY supplies.date_approvisionnement DESC'
        );
        $statement->execute(['shop_id' => $shopId]);

        return $statement->fetchAll();
    }

    public function findByShop(int $id, int $shopId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT supplies.*, suppliers.nom AS supplier_name, users.nom AS user_name
             FROM supplies
             INNER JOIN suppliers ON suppliers.id = supplies.supplier_id
             INNER JOIN users ON users.id = supplies.user_id
             WHERE supplies.id = :id AND supplies.shop_id = :shop_id
             LIMIT 1'
        );
        $statement->execute([
            'id' => $id,
            'shop_id' => $shopId,
        ]);

        $supply = $statement->fetch();

        return is_array($supply) ? $supply : null;
    }

    public function createArrival(array $data, int $shopId, int $userId): int
    {
        $database = Database::getInstance();
        $db = $database->pdo();
        $db->beginTransaction();

        try {
            $this->ensureSupplierBelongsToShop($db, (int) $data['supplier_id'], $shopId);
            $total = $this->calculateTotal($data['items']);
            $supplyId = $this->insertSupply($db, $data, $shopId, $userId, $total);

            $database->enableStockUpdate();

            foreach ($data['items'] as $item) {
                $product = $this->lockProductForShop($db, (int) $item['product_id'], $shopId);
                $quantity = (int) $item['quantite'];
                $purchasePrice = (float) $item['prix_achat_facture'];
                $stockBefore = (int) $product['quantite_stock'];
                $stockAfter = $stockBefore + $quantity;

                $this->details->insert($db, $supplyId, (int) $product['id'], $quantity, $purchasePrice);
                $this->insertStockMovement(
                    db: $db,
                    shopId: $shopId,
                    productId: (int) $product['id'],
                    userId: $userId,
                    supplyId: $supplyId,
                    quantity: $quantity,
                    stockBefore: $stockBefore,
                    stockAfter: $stockAfter
                );
                $this->updateProductStockAndPurchasePrice($db, (int) $product['id'], $shopId, $stockAfter, $purchasePrice, $userId);
            }

            $database->disableStockUpdate();
            $db->commit();

            return $supplyId;
        } catch (Throwable $exception) {
            $database->disableStockUpdate();

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    private function insertSupply(PDO $db, array $data, int $shopId, int $userId, float $total): int
    {
        $statement = $db->prepare(
            'INSERT INTO supplies (
                shop_id, supplier_id, user_id, numero_arrivage, date_approvisionnement, total_facture
             ) VALUES (
                :shop_id, :supplier_id, :user_id, :numero_arrivage, :date_approvisionnement, :total_facture
             )'
        );

        $statement->execute([
            'shop_id' => $shopId,
            'supplier_id' => (int) $data['supplier_id'],
            'user_id' => $userId,
            'numero_arrivage' => trim((string) $data['numero_arrivage']),
            'date_approvisionnement' => $data['date_approvisionnement'] ?: date('Y-m-d H:i:s'),
            'total_facture' => $total,
        ]);

        return (int) $db->lastInsertId();
    }

    private function ensureSupplierBelongsToShop(PDO $db, int $supplierId, int $shopId): void
    {
        $statement = $db->prepare(
            'SELECT id
             FROM suppliers
             WHERE id = :id AND shop_id = :shop_id
             LIMIT 1'
        );
        $statement->execute([
            'id' => $supplierId,
            'shop_id' => $shopId,
        ]);

        if ($statement->fetch() === false) {
            throw new RuntimeException('Fournisseur introuvable pour cette boutique.');
        }
    }

    private function lockProductForShop(PDO $db, int $productId, int $shopId): array
    {
        $statement = $db->prepare(
            'SELECT id, quantite_stock
             FROM products
             WHERE id = :id AND shop_id = :shop_id AND actif = 1
             LIMIT 1
             FOR UPDATE'
        );
        $statement->execute([
            'id' => $productId,
            'shop_id' => $shopId,
        ]);

        $product = $statement->fetch();

        if (!is_array($product)) {
            throw new RuntimeException('Produit introuvable pour cette boutique.');
        }

        return $product;
    }

    private function insertStockMovement(
        PDO $db,
        int $shopId,
        int $productId,
        int $userId,
        int $supplyId,
        int $quantity,
        int $stockBefore,
        int $stockAfter
    ): void {
        $statement = $db->prepare(
            'INSERT INTO stock_movements (
                shop_id, product_id, user_id, supply_id, type_mouvement,
                quantite, stock_avant, stock_apres, motif
             ) VALUES (
                :shop_id, :product_id, :user_id, :supply_id, :type_mouvement,
                :quantite, :stock_avant, :stock_apres, :motif
             )'
        );

        $statement->execute([
            'shop_id' => $shopId,
            'product_id' => $productId,
            'user_id' => $userId,
            'supply_id' => $supplyId,
            'type_mouvement' => 'entree',
            'quantite' => $quantity,
            'stock_avant' => $stockBefore,
            'stock_apres' => $stockAfter,
            'motif' => 'Arrivage fournisseur #' . $supplyId,
        ]);
    }

    private function updateProductStockAndPurchasePrice(
        PDO $db,
        int $productId,
        int $shopId,
        int $stockAfter,
        float $purchasePrice,
        int $userId
    ): void {
        $statement = $db->prepare(
            'UPDATE products
             SET quantite_stock = :quantite_stock,
                 prix_achat = :prix_achat,
                 updated_by = :updated_by
             WHERE id = :id AND shop_id = :shop_id'
        );

        $statement->execute([
            'quantite_stock' => $stockAfter,
            'prix_achat' => $purchasePrice,
            'updated_by' => $userId,
            'id' => $productId,
            'shop_id' => $shopId,
        ]);
    }

    private function calculateTotal(array $items): float
    {
        $total = 0.0;

        foreach ($items as $item) {
            $total += (int) $item['quantite'] * (float) $item['prix_achat_facture'];
        }

        return $total;
    }
}
