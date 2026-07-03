<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/Model.php';

final class StockMovement extends Model
{
    public function allByShop(int $shopId, int $limit = 100): array
    {
        $statement = Database::connection()->prepare(
            'SELECT
                stock_movements.*,
                products.nom AS product_name,
                products.ref AS product_ref,
                users.nom AS user_name
             FROM stock_movements
             INNER JOIN products ON products.id = stock_movements.product_id
             INNER JOIN users ON users.id = stock_movements.user_id
             WHERE stock_movements.shop_id = :shop_id
             ORDER BY stock_movements.date_mouvement DESC, stock_movements.id DESC
             LIMIT ' . max(1, min(500, $limit))
        );
        $statement->execute(['shop_id' => $shopId]);

        return $statement->fetchAll();
    }

    public function createManualAdjustment(array $data, int $shopId, int $userId): int
    {
        $database = Database::getInstance();
        $db = $database->pdo();
        $productId = (int) ($data['product_id'] ?? 0);
        $type = $this->movementType((string) ($data['type_mouvement'] ?? 'ajustement'));
        $quantityInput = (int) ($data['quantite'] ?? 0);
        $motif = trim((string) ($data['motif'] ?? ''));

        if ($productId <= 0 || $quantityInput < 0 || $motif === '') {
            throw new InvalidArgumentException('Produit, stock et motif sont obligatoires.');
        }

        $db->beginTransaction();

        try {
            $product = $this->lockProduct($db, $productId, $shopId);
            $stockBefore = (int) $product['quantite_stock'];
            $stockAfter = match ($type) {
                'entree' => $stockBefore + $quantityInput,
                'sortie' => $stockBefore - $quantityInput,
                default => $quantityInput,
            };

            if ($type !== 'ajustement' && $quantityInput <= 0) {
                throw new RuntimeException('La quantite doit etre superieure a zero pour une entree ou une sortie.');
            }

            if ($stockAfter < 0) {
                throw new RuntimeException('Stock insuffisant pour cette sortie.');
            }

            $movementQuantity = $type === 'ajustement' ? abs($stockAfter - $stockBefore) : $quantityInput;

            if ($movementQuantity <= 0) {
                throw new RuntimeException('Aucun ecart de stock a enregistrer.');
            }

            $database->enableStockUpdate();

            $movementId = $this->insertMovement(
                db: $db,
                shopId: $shopId,
                productId: $productId,
                userId: $userId,
                type: $type,
                quantity: $movementQuantity,
                stockBefore: $stockBefore,
                stockAfter: $stockAfter,
                motif: $motif
            );

            $this->updateProductStock($db, $productId, $shopId, $stockAfter, $userId);

            $database->disableStockUpdate();
            $db->commit();

            return $movementId;
        } catch (Throwable $exception) {
            $database->disableStockUpdate();

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    private function lockProduct(PDO $db, int $productId, int $shopId): array
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
            throw new RuntimeException('Produit introuvable ou inactif pour cette boutique.');
        }

        return $product;
    }

    private function insertMovement(
        PDO $db,
        int $shopId,
        int $productId,
        int $userId,
        string $type,
        int $quantity,
        int $stockBefore,
        int $stockAfter,
        string $motif
    ): int {
        $statement = $db->prepare(
            'INSERT INTO stock_movements (
                shop_id, product_id, user_id, type_mouvement,
                quantite, stock_avant, stock_apres, motif
             ) VALUES (
                :shop_id, :product_id, :user_id, :type_mouvement,
                :quantite, :stock_avant, :stock_apres, :motif
             )'
        );

        $statement->execute([
            'shop_id' => $shopId,
            'product_id' => $productId,
            'user_id' => $userId,
            'type_mouvement' => $type,
            'quantite' => $quantity,
            'stock_avant' => $stockBefore,
            'stock_apres' => $stockAfter,
            'motif' => $motif,
        ]);

        return (int) $db->lastInsertId();
    }

    private function updateProductStock(PDO $db, int $productId, int $shopId, int $stockAfter, int $userId): void
    {
        $statement = $db->prepare(
            'UPDATE products
             SET quantite_stock = :quantite_stock,
                 updated_by = :updated_by
             WHERE id = :id AND shop_id = :shop_id'
        );

        $statement->execute([
            'quantite_stock' => $stockAfter,
            'updated_by' => $userId,
            'id' => $productId,
            'shop_id' => $shopId,
        ]);
    }

    private function movementType(string $type): string
    {
        $type = strtolower(trim($type));

        return in_array($type, ['entree', 'sortie', 'ajustement'], true) ? $type : 'ajustement';
    }
}
