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

    public function allByShop(int $shopId, int $limit = 500): array
    {
        $statement = Database::connection()->prepare(
            'SELECT
                sales.id,
                sales.numero_facture,
                sales.date_vente,
                sales.total_montant,
                sales.montant_recu,
                sales.montant_dette,
                sales.mode_paiement,
                sales.statut,
                customers.nom AS customer_name,
                users.nom AS user_name,
                COUNT(sale_details.id) AS lignes_count,
                COALESCE(SUM(sale_details.quantite), 0) AS articles_count
             FROM sales
             LEFT JOIN customers ON customers.id = sales.customer_id
             INNER JOIN users ON users.id = sales.user_id
             LEFT JOIN sale_details ON sale_details.sale_id = sales.id
             WHERE sales.shop_id = :shop_id
             GROUP BY sales.id
             ORDER BY sales.date_vente DESC, sales.id DESC
             LIMIT ' . max(1, min(1000, $limit))
        );
        $statement->execute(['shop_id' => $shopId]);

        return $statement->fetchAll();
    }

    public function latestValidatedByShop(int $shopId, int $limit = 10): array
    {
        $statement = Database::connection()->prepare(
            'SELECT
                sales.id,
                sales.numero_facture,
                sales.date_vente,
                sales.total_montant,
                sales.montant_recu,
                sales.montant_dette,
                sales.mode_paiement,
                sales.statut,
                customers.nom AS customer_name,
                users.nom AS user_name,
                COUNT(sale_details.id) AS lignes_count,
                COALESCE(SUM(sale_details.quantite), 0) AS articles_count
             FROM sales
             LEFT JOIN customers ON customers.id = sales.customer_id
             INNER JOIN users ON users.id = sales.user_id
             LEFT JOIN sale_details ON sale_details.sale_id = sales.id
             WHERE sales.shop_id = :shop_id AND sales.statut = "validee"
             GROUP BY sales.id
             ORDER BY sales.date_vente DESC, sales.id DESC
             LIMIT ' . max(1, min(10, $limit))
        );
        $statement->execute(['shop_id' => $shopId]);

        return $statement->fetchAll();
    }

    public function findByShop(int $id, int $shopId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT
                sales.id,
                sales.shop_id,
                sales.customer_id,
                sales.user_id,
                sales.numero_facture,
                sales.date_vente,
                sales.total_montant,
                sales.montant_recu,
                sales.montant_dette,
                sales.mode_paiement,
                sales.statut,
                sales.motif_annulation,
                sales.annulee_at,
                customers.nom AS customer_name,
                customers.telephone AS customer_phone,
                users.nom AS user_name,
                COUNT(sale_details.id) AS lignes_count,
                COALESCE(SUM(sale_details.quantite), 0) AS articles_count
             FROM sales
             LEFT JOIN customers ON customers.id = sales.customer_id
             INNER JOIN users ON users.id = sales.user_id
             LEFT JOIN sale_details ON sale_details.sale_id = sales.id
             WHERE sales.id = :id AND sales.shop_id = :shop_id
             GROUP BY sales.id
             LIMIT 1'
        );
        $statement->execute([
            'id' => $id,
            'shop_id' => $shopId,
        ]);

        $sale = $statement->fetch();

        return is_array($sale) ? $sale : null;
    }

    public function detailsBySale(int $saleId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT
                sale_details.id,
                sale_details.product_id,
                sale_details.quantite,
                sale_details.prix_unitaire_vendu,
                sale_details.prix_achat_unitaire,
                sale_details.total_ligne,
                products.nom AS product_name,
                products.ref AS product_ref
             FROM sale_details
             INNER JOIN products ON products.id = sale_details.product_id
             WHERE sale_details.sale_id = :sale_id
             ORDER BY sale_details.id ASC'
        );
        $statement->execute(['sale_id' => $saleId]);

        return $statement->fetchAll();
    }

    public function updatePaymentByShop(int $id, int $shopId, array $data): bool
    {
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $sale = $this->lockSaleForUpdate($db, $id, $shopId);

            if ($sale === null) {
                $db->rollBack();
                return false;
            }

            if (($sale['statut'] ?? '') === 'annulee') {
                throw new RuntimeException('Une vente annulee ne peut plus etre modifiee.');
            }

            $customerId = $this->nullablePositiveInt($data['customer_id'] ?? null);
            $paymentMethod = $this->validPaymentMethod((string) ($data['mode_paiement'] ?? 'cash'));
            $amountReceived = max(0.0, (float) ($data['montant_recu'] ?? 0));
            $totalAmount = (float) ($sale['total_montant'] ?? 0);
            $debtAmount = $this->calculateDebt($paymentMethod, $amountReceived, $totalAmount);

            if ($debtAmount > 0 && $customerId === null) {
                throw new RuntimeException('Un client est obligatoire si la vente garde un credit.');
            }

            if ($customerId !== null) {
                $this->ensureCustomerBelongsToShop($db, $customerId, $shopId);
            }

            $oldCustomerId = $this->nullablePositiveInt($sale['customer_id'] ?? null);
            $oldDebt = (float) ($sale['montant_dette'] ?? 0);

            if ($oldCustomerId !== null && $oldDebt > 0) {
                $this->adjustCustomerDebt($db, $oldCustomerId, $shopId, -$oldDebt);
            }

            if ($customerId !== null && $debtAmount > 0) {
                $this->adjustCustomerDebt($db, $customerId, $shopId, $debtAmount);
            }

            $statement = $db->prepare(
                'UPDATE sales
                 SET customer_id = :customer_id,
                     montant_recu = :montant_recu,
                     montant_dette = :montant_dette,
                     mode_paiement = :mode_paiement
                 WHERE id = :id AND shop_id = :shop_id'
            );
            $statement->execute([
                'customer_id' => $customerId,
                'montant_recu' => $amountReceived,
                'montant_dette' => $debtAmount,
                'mode_paiement' => $paymentMethod,
                'id' => $id,
                'shop_id' => $shopId,
            ]);

            $db->commit();

            return true;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function cancelByShop(int $id, int $shopId, int $userId, string $reason): bool
    {
        $database = Database::getInstance();
        $db = $database->pdo();
        $db->beginTransaction();

        try {
            $sale = $this->lockSaleForUpdate($db, $id, $shopId);

            if ($sale === null) {
                $db->rollBack();
                return false;
            }

            if (($sale['statut'] ?? '') === 'annulee') {
                $db->commit();
                return true;
            }

            $details = $this->detailsForCancellation($db, $id, $shopId);
            $database->enableStockUpdate();

            foreach ($details as $detail) {
                $stockBefore = (int) $detail['quantite_stock'];
                $quantity = (int) $detail['quantite'];
                $stockAfter = $stockBefore + $quantity;

                $this->updateProductStock($db, (int) $detail['product_id'], $shopId, $stockAfter, $userId);
                $this->insertCancellationStockMovement(
                    db: $db,
                    shopId: $shopId,
                    productId: (int) $detail['product_id'],
                    userId: $userId,
                    saleId: $id,
                    quantity: $quantity,
                    stockBefore: $stockBefore,
                    stockAfter: $stockAfter
                );
            }

            $database->disableStockUpdate();

            $customerId = $this->nullablePositiveInt($sale['customer_id'] ?? null);
            $debt = (float) ($sale['montant_dette'] ?? 0);
            if ($customerId !== null && $debt > 0) {
                $this->adjustCustomerDebt($db, $customerId, $shopId, -$debt);
            }

            $statement = $db->prepare(
                'UPDATE sales
                 SET statut = "annulee",
                     motif_annulation = :motif_annulation,
                     annulee_par = :annulee_par,
                     annulee_at = NOW(),
                     montant_dette = 0
                 WHERE id = :id AND shop_id = :shop_id'
            );
            $statement->execute([
                'motif_annulation' => trim($reason) !== '' ? trim($reason) : 'Vente supprimee depuis l historique',
                'annulee_par' => $userId,
                'id' => $id,
                'shop_id' => $shopId,
            ]);

            $db->commit();

            return true;
        } catch (Throwable $exception) {
            $database->disableStockUpdate();

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function summaryByShop(int $shopId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT
                COUNT(*) AS sales_count,
                COALESCE(SUM(CASE WHEN statut = "validee" THEN total_montant ELSE 0 END), 0) AS revenue,
                COALESCE(SUM(CASE WHEN statut = "validee" THEN montant_recu ELSE 0 END), 0) AS received,
                COALESCE(SUM(CASE WHEN statut = "validee" THEN montant_dette ELSE 0 END), 0) AS debt,
                COALESCE(SUM(CASE WHEN statut = "annulee" THEN 1 ELSE 0 END), 0) AS cancelled_count
             FROM sales
             WHERE shop_id = :shop_id'
        );
        $statement->execute(['shop_id' => $shopId]);
        $summary = $statement->fetch();

        return is_array($summary) ? $summary : [
            'sales_count' => 0,
            'revenue' => 0,
            'received' => 0,
            'debt' => 0,
            'cancelled_count' => 0,
        ];
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

    private function lockSaleForUpdate(PDO $db, int $id, int $shopId): ?array
    {
        $statement = $db->prepare(
            'SELECT id, shop_id, customer_id, total_montant, montant_recu, montant_dette, mode_paiement, statut
             FROM sales
             WHERE id = :id AND shop_id = :shop_id
             LIMIT 1
             FOR UPDATE'
        );
        $statement->execute([
            'id' => $id,
            'shop_id' => $shopId,
        ]);

        $sale = $statement->fetch();

        return is_array($sale) ? $sale : null;
    }

    private function detailsForCancellation(PDO $db, int $saleId, int $shopId): array
    {
        $statement = $db->prepare(
            'SELECT
                sale_details.product_id,
                sale_details.quantite,
                products.quantite_stock
             FROM sale_details
             INNER JOIN products ON products.id = sale_details.product_id
             WHERE sale_details.sale_id = :sale_id AND products.shop_id = :shop_id
             FOR UPDATE'
        );
        $statement->execute([
            'sale_id' => $saleId,
            'shop_id' => $shopId,
        ]);

        return $statement->fetchAll();
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

    private function insertCancellationStockMovement(
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
            'type_mouvement' => 'annulation',
            'quantite' => $quantity,
            'stock_avant' => $stockBefore,
            'stock_apres' => $stockAfter,
            'motif' => 'Annulation vente POS #' . $saleId,
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

    private function adjustCustomerDebt(PDO $db, int $customerId, int $shopId, float $delta): void
    {
        $statement = $db->prepare(
            'UPDATE customers
             SET dette_actuelle = GREATEST(0, dette_actuelle + :delta)
             WHERE id = :id AND shop_id = :shop_id'
        );
        $statement->execute([
            'delta' => $delta,
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
