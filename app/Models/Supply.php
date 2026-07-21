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

    public function allByShop(int $shopId, int $limit = 100, int $offset = 0): array
    {
        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);
        $statement = Database::connection()->prepare(
            'SELECT supplies.*,
                    suppliers.nom AS supplier_name,
                    users.nom AS user_name,
                    COALESCE(detail_totals.lines_count, 0) AS lines_count,
                    COALESCE(detail_totals.total_units, 0) AS total_units,
                    COALESCE(detail_totals.entered_total_usd, 0) AS entered_total_usd,
                    COALESCE(detail_totals.entered_total_cdf, 0) AS entered_total_cdf,
                    COALESCE(detail_totals.entered_currencies_count, 0) AS entered_currencies_count
             FROM supplies
             LEFT JOIN suppliers ON suppliers.id = supplies.supplier_id
             INNER JOIN users ON users.id = supplies.user_id
             LEFT JOIN (
                SELECT supply_id,
                       COUNT(*) AS lines_count,
                       COALESCE(SUM(quantite), 0) AS total_units,
                       COALESCE(SUM(CASE WHEN devise_saisie = "USD" THEN total_ligne_saisi ELSE 0 END), 0) AS entered_total_usd,
                       COALESCE(SUM(CASE WHEN devise_saisie = "CDF" THEN total_ligne_saisi ELSE 0 END), 0) AS entered_total_cdf,
                       COUNT(DISTINCT devise_saisie) AS entered_currencies_count
                FROM supply_details
                GROUP BY supply_id
             ) detail_totals ON detail_totals.supply_id = supplies.id
             WHERE supplies.shop_id = :shop_id
             ORDER BY supplies.date_approvisionnement DESC, supplies.id DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset
        );
        $statement->execute(['shop_id' => $shopId]);

        return $statement->fetchAll();
    }

    public function countByShop(int $shopId): int
    {
        $statement = Database::connection()->prepare('SELECT COUNT(*) FROM supplies WHERE shop_id = :shop_id');
        $statement->execute(['shop_id' => $shopId]);

        return (int) $statement->fetchColumn();
    }

    public function nextArrivalNumber(?DateTimeInterface $date = null): string
    {
        $date ??= new DateTimeImmutable('now');
        $prefix = 'ARR-' . $date->format('Ymd') . '-';
        $statement = Database::connection()->prepare(
            'SELECT numero_arrivage
             FROM supplies
             WHERE numero_arrivage LIKE :prefix
             ORDER BY numero_arrivage DESC
             LIMIT 1'
        );
        $statement->execute(['prefix' => $prefix . '%']);
        $lastNumber = (string) ($statement->fetchColumn() ?: '');
        $nextSequence = 1;

        if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $lastNumber, $matches) === 1) {
            $nextSequence = (int) $matches[1] + 1;
        }

        return $prefix . str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);
    }

    public function arrivalNumberExists(string $number, ?int $excludeId = null): bool
    {
        $number = trim($number);

        if ($number === '') {
            return false;
        }

        $sql = 'SELECT COUNT(*) FROM supplies WHERE numero_arrivage = :numero_arrivage';
        $params = ['numero_arrivage' => $number];

        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn() > 0;
    }

    public function findByShop(int $id, int $shopId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT supplies.*, suppliers.nom AS supplier_name, users.nom AS user_name
             FROM supplies
             LEFT JOIN suppliers ON suppliers.id = supplies.supplier_id
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

    public function detailsBySupply(int $supplyId, int $shopId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT supply_details.*,
                    products.nom AS product_name,
                    products.ref AS product_ref
             FROM supply_details
             INNER JOIN supplies ON supplies.id = supply_details.supply_id
             INNER JOIN products ON products.id = supply_details.product_id
             WHERE supply_details.supply_id = :supply_id
               AND supplies.shop_id = :shop_id
             ORDER BY supply_details.id ASC'
        );
        $statement->execute([
            'supply_id' => $supplyId,
            'shop_id' => $shopId,
        ]);

        return $statement->fetchAll();
    }

    public function createArrival(array $data, int $shopId, int $userId): int
    {
        $database = Database::getInstance();
        $db = $database->pdo();
        $db->beginTransaction();

        try {
            $supplierId = $this->nullablePositiveInt($data['supplier_id'] ?? null);
            if ($supplierId !== null) {
                $this->ensureSupplierBelongsToShop($db, $supplierId, $shopId);
            }
            $total = $this->calculateTotal($data['items']);
            $supplyId = $this->insertSupply($db, $data, $shopId, $userId, $total);

            $database->enableStockUpdate();

            foreach ($data['items'] as $item) {
                $product = $this->lockProductForShop($db, (int) $item['product_id'], $shopId);
                $quantity = (int) $item['quantite'];
                $purchasePrice = (float) $item['prix_achat_facture'];
                $stockBefore = (int) $product['quantite_stock'];
                $stockAfter = $stockBefore + $quantity;

                $this->details->insert(
                    $db,
                    $supplyId,
                    (int) $product['id'],
                    $quantity,
                    $purchasePrice,
                    (float) ($item['prix_achat_saisi'] ?? $purchasePrice),
                    (string) ($item['devise_saisie'] ?? 'USD'),
                    (float) ($item['taux_change_saisie'] ?? 2800)
                );
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
                $this->updateProductStockAndPurchasePrice(
                    $db,
                    (int) $product['id'],
                    $shopId,
                    $stockAfter,
                    $purchasePrice,
                    (float) ($item['prix_achat_saisi'] ?? $purchasePrice),
                    (string) ($item['devise_saisie'] ?? 'USD'),
                    $userId
                );
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

    public function updateArrival(int $id, array $data, int $shopId, int $userId): void
    {
        $database = Database::getInstance();
        $db = $database->pdo();
        $db->beginTransaction();

        try {
            $supply = $this->lockSupplyForShop($db, $id, $shopId);

            if ((string) ($supply['statut'] ?? '') === 'annule') {
                throw new RuntimeException('Un approvisionnement annule ne peut pas etre modifie.');
            }

            $supplierId = $this->nullablePositiveInt($data['supplier_id'] ?? null);
            if ($supplierId !== null) {
                $this->ensureSupplierBelongsToShop($db, $supplierId, $shopId);
            }
            $oldDetails = $this->detailsForUpdate($db, $id);
            $newItems = $this->normalizeItems($data['items']);
            $total = $this->calculateTotal($newItems);

            $this->updateSupplyHeader($db, $id, $data, $shopId, $total);

            $database->enableStockUpdate();
            $this->applyStockDeltaForUpdate($db, $id, $shopId, $userId, $oldDetails, $newItems);
            $this->replaceDetails($db, $id, $newItems);

            $database->disableStockUpdate();
            $db->commit();
        } catch (Throwable $exception) {
            $database->disableStockUpdate();

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function cancelByShop(int $id, int $shopId, int $userId): void
    {
        $database = Database::getInstance();
        $db = $database->pdo();
        $db->beginTransaction();

        try {
            $supply = $this->lockSupplyForShop($db, $id, $shopId);

            if ((string) ($supply['statut'] ?? '') === 'annule') {
                throw new RuntimeException('Cet approvisionnement est deja annule.');
            }

            $details = $this->detailsForUpdate($db, $id);

            if ($details === []) {
                throw new RuntimeException('Aucune ligne a annuler pour cet approvisionnement.');
            }

            $database->enableStockUpdate();

            foreach ($details as $detail) {
                $product = $this->lockProductForShop($db, (int) $detail['product_id'], $shopId);
                $quantity = (int) $detail['quantite'];
                $stockBefore = (int) $product['quantite_stock'];
                $stockAfter = $stockBefore - $quantity;

                if ($stockAfter < 0) {
                    throw new RuntimeException('Stock insuffisant pour annuler cet approvisionnement.');
                }

                $this->insertStockMovement(
                    db: $db,
                    shopId: $shopId,
                    productId: (int) $detail['product_id'],
                    userId: $userId,
                    supplyId: $id,
                    quantity: $quantity,
                    stockBefore: $stockBefore,
                    stockAfter: $stockAfter,
                    type: 'annulation',
                    motif: 'Annulation arrivage fournisseur #' . $id
                );
                $this->updateProductStock($db, (int) $detail['product_id'], $shopId, $stockAfter, $userId);
            }

            $statement = $db->prepare(
                "UPDATE supplies
                 SET statut = 'annule'
                 WHERE id = :id AND shop_id = :shop_id"
            );
            $statement->execute([
                'id' => $id,
                'shop_id' => $shopId,
            ]);

            $database->disableStockUpdate();
            $db->commit();
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
            'supplier_id' => $this->nullablePositiveInt($data['supplier_id'] ?? null),
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
        int $stockAfter,
        string $type = 'entree',
        ?string $motif = null
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
            'type_mouvement' => $type,
            'quantite' => $quantity,
            'stock_avant' => $stockBefore,
            'stock_apres' => $stockAfter,
            'motif' => $motif ?? 'Arrivage fournisseur #' . $supplyId,
        ]);
    }

    private function updateProductStockAndPurchasePrice(
        PDO $db,
        int $productId,
        int $shopId,
        int $stockAfter,
        float $purchasePrice,
        float $enteredPurchasePrice,
        string $enteredCurrency,
        int $userId
    ): void {
        $enteredCurrency = in_array($enteredCurrency, ['USD', 'CDF'], true) ? $enteredCurrency : 'USD';
        $statement = $db->prepare(
            "UPDATE products
             SET quantite_stock = :quantite_stock,
                 prix_achat = :prix_achat,
                 prix_achat_devise = :prix_achat_devise,
                 prix_achat_montant = :prix_achat_montant,
                 updated_by = :updated_by
             WHERE id = :id AND shop_id = :shop_id"
        );

        $statement->execute([
            'quantite_stock' => $stockAfter,
            'prix_achat' => $purchasePrice,
            'prix_achat_devise' => $enteredCurrency,
            'prix_achat_montant' => $enteredPurchasePrice,
            'updated_by' => $userId,
            'id' => $productId,
            'shop_id' => $shopId,
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

    private function lockSupplyForShop(PDO $db, int $id, int $shopId): array
    {
        $statement = $db->prepare(
            'SELECT id, shop_id, statut
             FROM supplies
             WHERE id = :id AND shop_id = :shop_id
             LIMIT 1
             FOR UPDATE'
        );
        $statement->execute([
            'id' => $id,
            'shop_id' => $shopId,
        ]);

        $supply = $statement->fetch();

        if (!is_array($supply)) {
            throw new RuntimeException('Approvisionnement introuvable pour cette boutique.');
        }

        return $supply;
    }

    private function detailsForUpdate(PDO $db, int $supplyId): array
    {
        $statement = $db->prepare(
            'SELECT id, product_id, quantite, prix_achat_facture
             FROM supply_details
             WHERE supply_id = :supply_id
             ORDER BY id ASC'
        );
        $statement->execute(['supply_id' => $supplyId]);

        return $statement->fetchAll();
    }

    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);

            if ($productId <= 0) {
                continue;
            }

            if (!isset($normalized[$productId])) {
                $normalized[$productId] = [
                    'product_id' => $productId,
                    'quantite' => 0,
                    'prix_achat_facture' => (float) ($item['prix_achat_facture'] ?? 0),
                    'prix_achat_saisi' => (float) ($item['prix_achat_saisi'] ?? ($item['prix_achat_facture'] ?? 0)),
                    'devise_saisie' => in_array(($item['devise_saisie'] ?? 'USD'), ['USD', 'CDF'], true) ? (string) $item['devise_saisie'] : 'USD',
                    'taux_change_saisie' => max(0.0001, (float) ($item['taux_change_saisie'] ?? 2800)),
                ];
            }

            $normalized[$productId]['quantite'] += (int) ($item['quantite'] ?? 0);
            $normalized[$productId]['prix_achat_facture'] = (float) ($item['prix_achat_facture'] ?? 0);
            $normalized[$productId]['prix_achat_saisi'] = (float) ($item['prix_achat_saisi'] ?? ($item['prix_achat_facture'] ?? 0));
            $normalized[$productId]['devise_saisie'] = in_array(($item['devise_saisie'] ?? 'USD'), ['USD', 'CDF'], true) ? (string) $item['devise_saisie'] : 'USD';
            $normalized[$productId]['taux_change_saisie'] = max(0.0001, (float) ($item['taux_change_saisie'] ?? 2800));
        }

        return array_values($normalized);
    }

    private function updateSupplyHeader(PDO $db, int $id, array $data, int $shopId, float $total): void
    {
        $statement = $db->prepare(
            'UPDATE supplies
             SET supplier_id = :supplier_id,
                 date_approvisionnement = :date_approvisionnement,
                 total_facture = :total_facture
             WHERE id = :id AND shop_id = :shop_id'
        );

        $statement->execute([
            'supplier_id' => $this->nullablePositiveInt($data['supplier_id'] ?? null),
            'date_approvisionnement' => $data['date_approvisionnement'] ?: date('Y-m-d H:i:s'),
            'total_facture' => $total,
            'id' => $id,
            'shop_id' => $shopId,
        ]);
    }

    private function applyStockDeltaForUpdate(PDO $db, int $supplyId, int $shopId, int $userId, array $oldDetails, array $newItems): void
    {
        $oldQuantities = [];
        $newQuantities = [];

        foreach ($oldDetails as $detail) {
            $productId = (int) $detail['product_id'];
            $oldQuantities[$productId] = ($oldQuantities[$productId] ?? 0) + (int) $detail['quantite'];
        }

        foreach ($newItems as $item) {
            $productId = (int) $item['product_id'];
            $newQuantities[$productId] = ($newQuantities[$productId] ?? 0) + (int) $item['quantite'];
        }

        $productIds = array_unique(array_merge(array_keys($oldQuantities), array_keys($newQuantities)));

        foreach ($productIds as $productId) {
            $delta = (int) ($newQuantities[$productId] ?? 0) - (int) ($oldQuantities[$productId] ?? 0);

            if ($delta === 0) {
                continue;
            }

            $product = $this->lockProductForShop($db, (int) $productId, $shopId);
            $stockBefore = (int) $product['quantite_stock'];
            $stockAfter = $stockBefore + $delta;

            if ($stockAfter < 0) {
                throw new RuntimeException('Stock insuffisant pour modifier cet approvisionnement.');
            }

            $this->insertStockMovement(
                db: $db,
                shopId: $shopId,
                productId: (int) $productId,
                userId: $userId,
                supplyId: $supplyId,
                quantity: abs($delta),
                stockBefore: $stockBefore,
                stockAfter: $stockAfter,
                type: $delta > 0 ? 'entree' : 'sortie',
                motif: 'Modification arrivage fournisseur #' . $supplyId
            );
            $this->updateProductStock($db, (int) $productId, $shopId, $stockAfter, $userId);
        }

        foreach ($newItems as $item) {
            $this->updateProductPurchasePrice(
                $db,
                (int) $item['product_id'],
                $shopId,
                (float) $item['prix_achat_facture'],
                (float) ($item['prix_achat_saisi'] ?? $item['prix_achat_facture']),
                (string) ($item['devise_saisie'] ?? 'USD'),
                $userId
            );
        }
    }

    private function replaceDetails(PDO $db, int $supplyId, array $items): void
    {
        $delete = $db->prepare('DELETE FROM supply_details WHERE supply_id = :supply_id');
        $delete->execute(['supply_id' => $supplyId]);

        foreach ($items as $item) {
            $this->details->insert(
                $db,
                $supplyId,
                (int) $item['product_id'],
                (int) $item['quantite'],
                (float) $item['prix_achat_facture'],
                (float) ($item['prix_achat_saisi'] ?? $item['prix_achat_facture']),
                (string) ($item['devise_saisie'] ?? 'USD'),
                (float) ($item['taux_change_saisie'] ?? 2800)
            );
        }
    }

    private function updateProductPurchasePrice(
        PDO $db,
        int $productId,
        int $shopId,
        float $purchasePrice,
        float $enteredPurchasePrice,
        string $enteredCurrency,
        int $userId
    ): void
    {
        $enteredCurrency = in_array($enteredCurrency, ['USD', 'CDF'], true) ? $enteredCurrency : 'USD';
        $statement = $db->prepare(
            "UPDATE products
             SET prix_achat = :prix_achat,
                 prix_achat_devise = :prix_achat_devise,
                 prix_achat_montant = :prix_achat_montant,
                 updated_by = :updated_by
             WHERE id = :id AND shop_id = :shop_id"
        );

        $statement->execute([
            'prix_achat' => $purchasePrice,
            'prix_achat_devise' => $enteredCurrency,
            'prix_achat_montant' => $enteredPurchasePrice,
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

    private function nullablePositiveInt(mixed $value): ?int
    {
        $value = (int) ($value ?? 0);

        return $value > 0 ? $value : null;
    }
}
