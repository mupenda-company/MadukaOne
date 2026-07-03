<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';
require_once dirname(__DIR__) . '/Core/Validator.php';
require_once dirname(__DIR__) . '/Models/Product.php';
require_once dirname(__DIR__) . '/Models/StockMovement.php';

class StockController extends AppController
{
    private Product $products;
    private StockMovement $movements;

    public function __construct()
    {
        $this->products = new Product();
        $this->movements = new StockMovement();
    }

    public function movements(array $params = []): void
    {
        $shopId = $this->currentShopId();
        $products = $this->products->allByShop($shopId);

        $this->render('stock/movements', [
            'pageTitle' => 'Stock',
            'activeMenu' => 'stock',
            'products' => $products,
            'movements' => $this->movements->allByShop($shopId),
            'stockStats' => $this->stockStats($products),
        ]);
    }

    public function adjustments(array $params = []): void
    {
        $shopId = $this->currentShopId();
        $products = $this->products->allByShop($shopId);
        $inventoryMode = (string) ($_GET['mode'] ?? 'adjustment');

        $this->render('stock/adjustments', [
            'pageTitle' => $inventoryMode === 'complete' ? 'Inventaire complet' : 'Ajustement stock',
            'activeMenu' => 'stock',
            'products' => array_values(array_filter($products, static fn (array $product): bool => (int) ($product['actif'] ?? 0) === 1)),
            'inventoryMode' => $inventoryMode,
            'recentMovements' => $this->movements->allByShop($shopId, 8),
            'stockStats' => $this->stockStats($products),
        ]);
    }

    public function storeAdjustment(array $params = []): void
    {
        if (($_POST['inventory_mode'] ?? '') === 'complete') {
            $this->storeCompleteInventory();
        }

        $data = [
            'product_id' => $_POST['product_id'] ?? null,
            'type_mouvement' => $_POST['type_mouvement'] ?? 'ajustement',
            'quantite' => $_POST['quantite'] ?? null,
            'motif' => $_POST['motif'] ?? '',
        ];

        $type = (string) $data['type_mouvement'];
        $quantity = (int) ($data['quantite'] ?? -1);

        if (trim((string) $data['motif']) === '') {
            $data['motif'] = match ($type) {
                'entree' => 'Entrée manuelle depuis l’ajustement de stock',
                'sortie' => 'Sortie manuelle depuis l’ajustement de stock',
                default => 'Stock final après inventaire',
            };
        }

        $validator = Validator::make($data)
            ->required('product_id', 'Produit')
            ->required('type_mouvement', 'Type de mouvement')
            ->required('quantite', 'Stock')
            ->integerPositiveOrZero('product_id', 'Produit')
            ->integerPositiveOrZero('quantite', 'Stock')
            ->maxLength('motif', 255, 'Motif');

        $invalidQuantity = $quantity < 0 || ($type !== 'ajustement' && $quantity <= 0);

        if ($validator->fails() || (int) $data['product_id'] <= 0 || $invalidQuantity) {
            $this->flashError($this->firstError($validator->errors()) ?: 'Le stock saisi est invalide.');
            $this->redirect('/stock/adjustments');
        }

        try {
            $this->movements->createManualAdjustment($data, $this->currentShopId(), $this->currentUserId());
            $this->flashSuccess('Mouvement de stock enregistre avec succes.');
            $this->redirect('/stock/movements');
        } catch (Throwable $exception) {
            $this->flashError('Impossible d enregistrer le mouvement: ' . $exception->getMessage());
            $this->redirect('/stock/adjustments');
        }
    }

    private function storeCompleteInventory(): never
    {
        $items = is_array($_POST['items'] ?? null) ? $_POST['items'] : [];

        if ($items === []) {
            $this->flashError('Aucun produit à inventorier.');
            $this->redirect('/stock/adjustments?mode=complete');
        }

        $shopId = $this->currentShopId();
        $userId = $this->currentUserId();
        $products = $this->products->allByShop($shopId);
        $currentStockByProduct = [];

        foreach ($products as $product) {
            if ((int) ($product['actif'] ?? 0) !== 1) {
                continue;
            }

            $currentStockByProduct[(int) $product['id']] = (int) ($product['quantite_stock'] ?? 0);
        }

        $updated = 0;
        $unchanged = 0;

        try {
            foreach ($items as $productId => $stockValue) {
                $productId = (int) $productId;
                $stockValue = trim((string) $stockValue);

                if ($productId <= 0 || !array_key_exists($productId, $currentStockByProduct)) {
                    continue;
                }

                if ($stockValue === '' || filter_var($stockValue, FILTER_VALIDATE_INT) === false || (int) $stockValue < 0) {
                    throw new InvalidArgumentException('Chaque stock inventorié doit être un entier positif ou égal à zéro.');
                }

                $stockAfter = (int) $stockValue;

                if ($stockAfter === $currentStockByProduct[$productId]) {
                    $unchanged++;
                    continue;
                }

                $this->movements->createManualAdjustment([
                    'product_id' => $productId,
                    'type_mouvement' => 'ajustement',
                    'quantite' => $stockAfter,
                    'motif' => 'Inventaire complet',
                ], $shopId, $userId);
                $updated++;
            }

            $message = $updated > 0
                ? "Inventaire complet validé: {$updated} produit(s) mis à jour."
                : 'Inventaire complet validé: aucun écart de stock détecté.';

            if ($unchanged > 0) {
                $message .= " {$unchanged} produit(s) inchangé(s).";
            }

            $this->flashSuccess($message);
            $this->redirect('/stock/movements');
        } catch (Throwable $exception) {
            $this->flashError('Impossible de valider l’inventaire complet: ' . $exception->getMessage());
            $this->redirect('/stock/adjustments?mode=complete');
        }
    }

    private function stockStats(array $products): array
    {
        $active = 0;
        $alerts = 0;
        $ruptures = 0;
        $units = 0;

        foreach ($products as $product) {
            if ((int) ($product['actif'] ?? 0) !== 1) {
                continue;
            }

            $active++;
            $stock = (int) ($product['quantite_stock'] ?? 0);
            $min = (int) ($product['alerte_stock_min'] ?? 0);
            $units += $stock;

            if ($stock === 0) {
                $ruptures++;
            }

            if ($stock <= $min) {
                $alerts++;
            }
        }

        return [
            'active_products' => $active,
            'stock_alerts' => $alerts,
            'ruptures' => $ruptures,
            'units' => $units,
        ];
    }

    private function firstError(array $errors): string
    {
        foreach ($errors as $messages) {
            return (string) ($messages[0] ?? 'Donnees invalides.');
        }

        return '';
    }
}
