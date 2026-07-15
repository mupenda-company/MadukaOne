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
            $receivedCurrency = $this->validCurrency((string) ($payload['received_currency'] ?? 'USD'));
            $shopCurrency = $this->shopCurrency($db, $shopId);
            $exchangeRate = $shopCurrency['exchange_rate'];
            $hasEnteredReceived = array_key_exists('amount_received_entered', $payload);
            $rawAmountReceivedEntered = max(0.0, (float) ($payload['amount_received_entered'] ?? 0));
            $amountReceived = $hasEnteredReceived
                ? $this->currencyToUsd($rawAmountReceivedEntered, $receivedCurrency, $exchangeRate)
                : max(0.0, (float) ($payload['montant_recu'] ?? 0));
            $invoiceNumber = $this->generateInvoiceNumber($db);
            $lines = [];
            $totalAmount = 0.0;
            $enteredTotalsByCurrency = [];

            foreach ($items as $item) {
                $product = $this->lockProductForSale($db, (int) $item['product_id'], $shopId);
                $quantity = (int) $item['quantite'];
                $stockBefore = (int) $product['quantite_stock'];
                $expirationDate = trim((string) ($product['date_expiration'] ?? ''));

                if ($stockBefore < $quantity) {
                    throw new RuntimeException('Stock insuffisant pour le produit: ' . $product['nom']);
                }

                if ($expirationDate !== '' && $expirationDate < date('Y-m-d')) {
                    throw new RuntimeException('Impossible de vendre ce produit car il a deja expire: ' . $product['nom']);
                }

                $salePrice = (float) $product['prix_vente'];
                $purchasePrice = (float) $product['prix_achat'];
                $enteredCurrency = $this->validCurrency((string) ($product['prix_vente_devise'] ?? 'USD'));
                $enteredSalePrice = (float) ($product['prix_vente_montant'] ?? $salePrice);
                $enteredSalePrice = $enteredSalePrice > 0 ? $enteredSalePrice : $salePrice;
                $stockAfter = $stockBefore - $quantity;
                $lineTotal = $quantity * $salePrice;
                $enteredLineTotal = $quantity * $enteredSalePrice;
                $totalAmount += $lineTotal;
                $enteredTotalsByCurrency[$enteredCurrency] = ($enteredTotalsByCurrency[$enteredCurrency] ?? 0.0) + $enteredLineTotal;

                $lines[] = [
                    'product_id' => (int) $product['id'],
                    'nom' => (string) $product['nom'],
                    'quantite' => $quantity,
                    'prix_unitaire_vendu' => $salePrice,
                    'prix_unitaire_vendu_saisi' => $enteredSalePrice,
                    'devise_saisie' => $enteredCurrency,
                    'taux_change_saisie' => $exchangeRate,
                    'prix_achat_unitaire' => $purchasePrice,
                    'total_ligne' => $lineTotal,
                    'total_ligne_saisi' => $enteredLineTotal,
                    'stock_avant' => $stockBefore,
                    'stock_apres' => $stockAfter,
                ];
            }

            $amountReceived = min($amountReceived, $totalAmount);
            $saleCurrency = count($enteredTotalsByCurrency) === 1 ? (string) array_key_first($enteredTotalsByCurrency) : $shopCurrency['currency'];
            $totalAmountEntered = count($enteredTotalsByCurrency) === 1
                ? (float) array_values($enteredTotalsByCurrency)[0]
                : $this->usdToCurrency($totalAmount, $saleCurrency, $exchangeRate);
            $maxAmountReceivedEntered = $this->usdToCurrency($totalAmount, $receivedCurrency, $exchangeRate);
            $amountReceivedEntered = $hasEnteredReceived
                ? min($rawAmountReceivedEntered, $maxAmountReceivedEntered)
                : $this->usdToCurrency($amountReceived, $receivedCurrency, $exchangeRate);
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
                totalAmountEntered: $totalAmountEntered,
                saleCurrency: $saleCurrency,
                amountReceived: $amountReceived,
                amountReceivedEntered: $amountReceivedEntered,
                receivedCurrency: $receivedCurrency,
                exchangeRate: $exchangeRate,
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
                    purchasePrice: $line['prix_achat_unitaire'],
                    enteredSalePrice: $line['prix_unitaire_vendu_saisi'],
                    enteredCurrency: $line['devise_saisie'],
                    exchangeRate: $line['taux_change_saisie']
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
                'total_montant_saisi' => $totalAmountEntered,
                'devise_saisie' => $saleCurrency,
                'montant_recu' => $amountReceived,
                'montant_recu_saisi' => $amountReceivedEntered,
                'devise_recu' => $receivedCurrency,
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

    public function allByShop(int $shopId, ?int $limit = 500, array $filters = []): array
    {
        [$where, $params] = $this->salesFilterSql($shopId, $filters);
        $limitSql = $limit === null ? '' : ' LIMIT ' . max(1, min(5000, $limit));
        $statement = Database::connection()->prepare(
            'SELECT
                sales.id,
                sales.numero_facture,
                sales.date_vente,
                sales.total_montant,
                sales.total_montant_saisi,
                sales.devise_saisie,
                sales.montant_recu,
                sales.montant_recu_saisi,
                sales.devise_recu,
                sales.montant_dette,
                sales.taux_change_saisie,
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
             WHERE ' . $where . '
             GROUP BY sales.id
             ORDER BY sales.date_vente DESC, sales.id DESC
             ' . $limitSql
        );
        $statement->execute($params);

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
                sales.total_montant_saisi,
                sales.devise_saisie,
                sales.montant_recu,
                sales.montant_recu_saisi,
                sales.devise_recu,
                sales.montant_dette,
                sales.taux_change_saisie,
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
                sales.total_montant_saisi,
                sales.devise_saisie,
                sales.montant_recu,
                sales.montant_recu_saisi,
                sales.devise_recu,
                sales.montant_dette,
                sales.taux_change_saisie,
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
                sale_details.prix_unitaire_vendu_saisi,
                sale_details.devise_saisie,
                sale_details.taux_change_saisie,
                sale_details.prix_achat_unitaire,
                sale_details.total_ligne,
                sale_details.total_ligne_saisi,
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
            $amountReceived = min($amountReceived, $totalAmount);
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

    public function summaryByShopFiltered(int $shopId, array $filters = []): array
    {
        [$where, $params] = $this->salesFilterSql($shopId, $filters);
        $statement = Database::connection()->prepare(
            'SELECT
                COUNT(*) AS sales_count,
                COALESCE(SUM(CASE WHEN sales.statut = "validee" THEN sales.total_montant ELSE 0 END), 0) AS revenue,
                COALESCE(SUM(CASE WHEN sales.statut = "validee" THEN sales.montant_recu ELSE 0 END), 0) AS received,
                COALESCE(SUM(CASE WHEN sales.statut = "validee" THEN sales.montant_dette ELSE 0 END), 0) AS debt,
                COALESCE(SUM(CASE WHEN sales.statut = "annulee" THEN 1 ELSE 0 END), 0) AS cancelled_count
             FROM sales
             LEFT JOIN customers ON customers.id = sales.customer_id
             INNER JOIN users ON users.id = sales.user_id
             WHERE ' . $where
        );
        $statement->execute($params);
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
            'SELECT id, nom, prix_achat, prix_vente, prix_vente_devise, prix_vente_montant, quantite_stock, date_expiration
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
        float $totalAmountEntered,
        string $saleCurrency,
        float $amountReceived,
        float $amountReceivedEntered,
        string $receivedCurrency,
        float $exchangeRate,
        float $debtAmount,
        string $paymentMethod
    ): int {
        $saleCurrency = $this->validCurrency($saleCurrency);
        $receivedCurrency = $this->validCurrency($receivedCurrency);
        $exchangeRate = $exchangeRate > 0 ? $exchangeRate : 2800.0;
        $statement = $db->prepare(
            'INSERT INTO sales (
                shop_id, customer_id, user_id, numero_facture, total_montant, total_montant_saisi,
                devise_saisie, montant_recu, montant_recu_saisi, devise_recu, taux_change_saisie,
                montant_dette, mode_paiement
             ) VALUES (
                :shop_id, :customer_id, :user_id, :numero_facture, :total_montant, :total_montant_saisi,
                :devise_saisie, :montant_recu, :montant_recu_saisi, :devise_recu, :taux_change_saisie,
                :montant_dette, :mode_paiement
             )'
        );

        $statement->execute([
            'shop_id' => $shopId,
            'customer_id' => $customerId,
            'user_id' => $userId,
            'numero_facture' => $invoiceNumber,
            'total_montant' => $totalAmount,
            'total_montant_saisi' => $totalAmountEntered,
            'devise_saisie' => $saleCurrency,
            'montant_recu' => $amountReceived,
            'montant_recu_saisi' => $amountReceivedEntered,
            'devise_recu' => $receivedCurrency,
            'taux_change_saisie' => $exchangeRate,
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

    private function validCurrency(string $currency): string
    {
        $currency = strtoupper(trim($currency));

        return in_array($currency, ['USD', 'CDF'], true) ? $currency : 'USD';
    }

    private function usdToCurrency(float $amount, string $currency, float $exchangeRate): float
    {
        $currency = $this->validCurrency($currency);
        $exchangeRate = $exchangeRate > 0 ? $exchangeRate : 2800.0;

        return $currency === 'CDF' ? round($amount * $exchangeRate, 2) : round($amount, 2);
    }

    private function currencyToUsd(float $amount, string $currency, float $exchangeRate): float
    {
        $currency = $this->validCurrency($currency);
        $exchangeRate = $exchangeRate > 0 ? $exchangeRate : 2800.0;

        return $currency === 'CDF' ? round($amount / $exchangeRate, 6) : round($amount, 2);
    }

    private function shopCurrency(PDO $db, int $shopId): array
    {
        $statement = $db->prepare(
            'SELECT devise_principale, taux_change_cdf
             FROM shops
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $shopId]);
        $shop = $statement->fetch();
        $exchangeRate = is_array($shop) ? (float) ($shop['taux_change_cdf'] ?? 2800) : 2800.0;

        return [
            'currency' => $this->validCurrency(is_array($shop) ? (string) ($shop['devise_principale'] ?? 'USD') : 'USD'),
            'exchange_rate' => $exchangeRate > 0 ? $exchangeRate : 2800.0,
        ];
    }

    private function salesFilterSql(int $shopId, array $filters): array
    {
        $where = ['sales.shop_id = :shop_id'];
        $params = ['shop_id' => $shopId];

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $where[] = '(sales.numero_facture LIKE :search_invoice OR customers.nom LIKE :search_customer OR users.nom LIKE :search_user)';
            $params['search_invoice'] = '%' . $search . '%';
            $params['search_customer'] = '%' . $search . '%';
            $params['search_user'] = '%' . $search . '%';
        }

        $status = strtolower(trim((string) ($filters['status'] ?? 'all')));
        if (in_array($status, ['validee', 'annulee'], true)) {
            $where[] = 'sales.statut = :status';
            $params['status'] = $status;
        }

        $payment = strtolower(trim((string) ($filters['payment'] ?? 'all')));
        if (in_array($payment, ['cash', 'mobile_money', 'carte', 'virement', 'credit', 'mixte'], true)) {
            $where[] = 'sales.mode_paiement = :payment';
            $params['payment'] = $payment;
        }

        $debt = strtolower(trim((string) ($filters['debt'] ?? 'all')));
        if ($debt === 'paid') {
            $where[] = 'sales.montant_dette <= 0';
        } elseif ($debt === 'debt') {
            $where[] = 'sales.montant_dette > 0';
        }

        $period = strtolower(trim((string) ($filters['period'] ?? 'all')));
        $now = new DateTimeImmutable('now');

        $dateStart = trim((string) ($filters['date_debut'] ?? ''));
        $dateEnd = trim((string) ($filters['date_fin'] ?? ''));
        if ($dateStart !== '') {
            $start = DateTimeImmutable::createFromFormat('!Y-m-d', substr($dateStart, 0, 10));
            if ($start instanceof DateTimeImmutable) {
                $where[] = 'sales.date_vente >= :date_start';
                $params['date_start'] = $start->format('Y-m-d H:i:s');
            }
        }
        if ($dateEnd !== '') {
            $end = DateTimeImmutable::createFromFormat('!Y-m-d', substr($dateEnd, 0, 10));
            if ($end instanceof DateTimeImmutable) {
                $where[] = 'sales.date_vente < :date_end';
                $params['date_end'] = $end->modify('+1 day')->format('Y-m-d H:i:s');
            }
        }

        if ($dateStart !== '' || $dateEnd !== '') {
            return [implode(' AND ', $where), $params];
        }

        if ($period === 'today') {
            $where[] = 'sales.date_vente >= :date_start AND sales.date_vente < :date_end';
            $params['date_start'] = $now->setTime(0, 0)->format('Y-m-d H:i:s');
            $params['date_end'] = $now->setTime(0, 0)->modify('+1 day')->format('Y-m-d H:i:s');
        } elseif ($period === 'week') {
            $where[] = 'sales.date_vente >= :date_start';
            $params['date_start'] = $now->modify('-7 days')->format('Y-m-d H:i:s');
        } elseif ($period === 'month') {
            $where[] = 'sales.date_vente >= :date_start';
            $params['date_start'] = $now->modify('-30 days')->format('Y-m-d H:i:s');
        }

        return [implode(' AND ', $where), $params];
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
