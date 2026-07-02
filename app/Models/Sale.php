<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/Model.php';
require_once dirname(__DIR__) . '/Models/SaleDetail.php';

final class Sale extends Model
{
    private SaleDetail $details;

    public function __construct()
    {
        $this->details = new SaleDetail();
    }

    public function createFromPos(array $payload, int $shopId, int $userId): array
    {
        $database = Database::getInstance();
        $db = $database->pdo();
        $items = $this->normalizeItems($payload['items'] ?? []);

        if ($items === []) {
            throw new InvalidArgumentException('Le panier est vide.');
        }

        $db->beginTransaction();

        try {
            $customerId = $this->nullablePositiveInt($payload['customer_id'] ?? null);
            $paymentMethod = $this->validPaymentMethod((string) ($payload['mode_paiement'] ?? 'cash'));
            $amountReceived = max(0.0, (float) ($payload['montant_recu'] ?? 0));
            $invoiceNumber = $this->generateInvoiceNumber($db);
            $lines = [];
            $totalAmount = 0.0;

            foreach ($items as $item) {
                $product = $this->lockProductForSale($db, (int) $item['product_id'], $shopId);
                $quantity = (int) $item['quantite'];
                $stockBefore = (int) $product['quantite_stock'];

                if ($stockBefore < $quantity) {
                    throw new RuntimeException('Stock insuffisant pour le produit: ' . $product['nom']);
                }

                $salePrice = (float) $product['prix_vente'];
                $purchasePrice = (float) $product['prix_achat'];
                $stockAfter = $stockBefore - $quantity;
                $lineTotal = $quantity * $salePrice;
                $totalAmount += $lineTotal;

                $lines[] = [
                    'product_id' => (int) $product['id'],
                    'nom' => (string) $product['nom'],
                    'quantite' => $quantity,
                    'prix_unitaire_vendu' => $salePrice,
                    'prix_achat_unitaire' => $purchasePrice,
                    'total_ligne' => $lineTotal,
                    'stock_avant' => $stockBefore,
                    'stock_apres' => $stockAfter,
                ];
            }

            $debtAmount = $this->calculateDebt($paymentMethod, $amountReceived, $totalAmount);

            if ($debtAmount > 0 && $customerId === null) {
                throw new RuntimeException('Un client est obligatoire pour une vente a credit ou partiellement payee.');
            }

            if ($customerId !== null) {
                $this->ensureCustomerBelongsToShop($db, $customerId, $shopId);
            }

            $saleId = $this->insertSale(
                db: $db,
                shopId: $shopId,
                customerId: $customerId,
                userId: $userId,
                invoiceNumber: $invoiceNumber,
                totalAmount: $totalAmount,
                amountReceived: $amountReceived,
                debtAmount: $debtAmount,
                paymentMethod: $paymentMethod
            );

            $database->enableStockUpdate();

            foreach ($lines as $line) {
                $this->details->insert(
                    db: $db,
                    saleId: $saleId,
                    productId: $line['product_id'],
                    quantity: $line['quantite'],
                    salePrice: $line['prix_unitaire_vendu'],
                    purchasePrice: $line['prix_achat_unitaire']
                );

                $this->insertStockMovement(
                    db: $db,
                    shopId: $shopId,
                    productId: $line['product_id'],
                    userId: $userId,
                    saleId: $saleId,
                    quantity: $line['quantite'],
                    stockBefore: $line['stock_avant'],
                    stockAfter: $line['stock_apres']
                );

                $this->updateProductStock($db, $line['product_id'], $shopId, $line['stock_apres'], $userId);
            }

            if ($debtAmount > 0 && $customerId !== null) {
                $this->incrementCustomerDebt($db, $customerId, $shopId, $debtAmount);
            }

            $database->disableStockUpdate();
            $db->commit();

            return [
                'sale_id' => $saleId,
                'numero_facture' => $invoiceNumber,
                'total_montant' => $totalAmount,
                'montant_recu' => $amountReceived,
                'montant_dette' => $debtAmount,
                'mode_paiement' => $paymentMethod,
                'items' => $lines,
            ];
        } catch (Throwable $exception) {
            $database->disableStockUpdate();

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    private function normalizeItems(mixed $rawItems): array
    {
        if (!is_array($rawItems)) {
            return [];
        }

        $items = [];

        foreach ($rawItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            $productId = (int) ($item['product_id'] ?? $item['id'] ?? 0);
            $quantity = (int) ($item['quantite'] ?? $item['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            if (!isset($items[$productId])) {
                $items[$productId] = [
                    'product_id' => $productId,
                    'quantite' => 0,
                ];
            }

            $items[$productId]['quantite'] += $quantity;
        }

        return array_values($items);
    }

    private function lockProductForSale(PDO $db, int $productId, int $shopId): array
    {
        $statement = $db->prepare(
            'SELECT id, nom, prix_achat, prix_vente, quantite_stock
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

    private function insertSale(
        PDO $db,
        int $shopId,
        ?int $customerId,
        int $userId,
        string $invoiceNumber,
        float $totalAmount,
        float $amountReceived,
        float $debtAmount,
        string $paymentMethod
    ): int {
        $statement = $db->prepare(
            'INSERT INTO sales (
                shop_id, customer_id, user_id, numero_facture, total_montant,
                montant_recu, montant_dette, mode_paiement
             ) VALUES (
                :shop_id, :customer_id, :user_id, :numero_facture, :total_montant,
                :montant_recu, :montant_dette, :mode_paiement
             )'
        );

        $statement->execute([
            'shop_id' => $shopId,
            'customer_id' => $customerId,
            'user_id' => $userId,
            'numero_facture' => $invoiceNumber,
            'total_montant' => $totalAmount,
            'montant_recu' => $amountReceived,
            'montant_dette' => $debtAmount,
            'mode_paiement' => $paymentMethod,
        ]);

        return (int) $db->lastInsertId();
    }

    private function insertStockMovement(
        PDO $db,
        int $shopId,
        int $productId,
        int $userId,
        int $saleId,
        int $quantity,
        int $stockBefore,
        int $stockAfter
    ): void {
        $statement = $db->prepare(
            'INSERT INTO stock_movements (
                shop_id, product_id, user_id, sale_id, type_mouvement,
                quantite, stock_avant, stock_apres, motif
             ) VALUES (
                :shop_id, :product_id, :user_id, :sale_id, :type_mouvement,
                :quantite, :stock_avant, :stock_apres, :motif
             )'
        );

        $statement->execute([
            'shop_id' => $shopId,
            'product_id' => $productId,
            'user_id' => $userId,
            'sale_id' => $saleId,
            'type_mouvement' => 'sortie',
            'quantite' => $quantity,
            'stock_avant' => $stockBefore,
            'stock_apres' => $stockAfter,
            'motif' => 'Vente POS #' . $saleId,
        ]);
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

    private function incrementCustomerDebt(PDO $db, int $customerId, int $shopId, float $debtAmount): void
    {
        $statement = $db->prepare(
            'UPDATE customers
             SET dette_actuelle = dette_actuelle + :dette
             WHERE id = :id AND shop_id = :shop_id'
        );
        $statement->execute([
            'dette' => $debtAmount,
            'id' => $customerId,
            'shop_id' => $shopId,
        ]);
    }

    private function ensureCustomerBelongsToShop(PDO $db, int $customerId, int $shopId): void
    {
        $statement = $db->prepare(
            'SELECT id
             FROM customers
             WHERE id = :id AND shop_id = :shop_id
             LIMIT 1'
        );
        $statement->execute([
            'id' => $customerId,
            'shop_id' => $shopId,
        ]);

        if ($statement->fetch() === false) {
            throw new RuntimeException('Client introuvable pour cette boutique.');
        }
    }

    private function calculateDebt(string $paymentMethod, float $amountReceived, float $totalAmount): float
    {
        if ($paymentMethod === 'credit') {
            return max(0.0, $totalAmount - $amountReceived);
        }

        return max(0.0, $totalAmount - $amountReceived);
    }

    private function validPaymentMethod(string $paymentMethod): string
    {
        $paymentMethod = strtolower(trim($paymentMethod));
        $allowed = ['cash', 'mobile_money', 'carte', 'virement', 'credit', 'mixte'];

        return in_array($paymentMethod, $allowed, true) ? $paymentMethod : 'cash';
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        $value = (int) ($value ?? 0);

        return $value > 0 ? $value : null;
    }

    private function generateInvoiceNumber(PDO $db): string
    {
        $year = date('Y');

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $number = 'FAC-' . $year . '-' . strtoupper(bin2hex(random_bytes(3)));
            $statement = $db->prepare('SELECT id FROM sales WHERE numero_facture = :numero_facture LIMIT 1');
            $statement->execute(['numero_facture' => $number]);

            if ($statement->fetch() === false) {
                return $number;
            }
        }

        throw new RuntimeException('Impossible de generer un numero de facture unique.');
    }
}
