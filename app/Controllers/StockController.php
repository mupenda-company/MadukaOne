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

        $this->render('stock/adjustments', [
            'pageTitle' => 'Ajustement stock',
            'activeMenu' => 'stock',
            'products' => array_values(array_filter($products, static fn (array $product): bool => (int) ($product['actif'] ?? 0) === 1)),
            'recentMovements' => $this->movements->allByShop($shopId, 8),
            'stockStats' => $this->stockStats($products),
        ]);
    }

    public function storeAdjustment(array $params = []): void
    {
        $data = [
            'product_id' => $_POST['product_id'] ?? null,
            'type_mouvement' => $_POST['type_mouvement'] ?? 'ajustement',
            'quantite' => $_POST['quantite'] ?? null,
            'motif' => $_POST['motif'] ?? '',
        ];

        $validator = Validator::make($data)
            ->required('product_id', 'Produit')
            ->required('type_mouvement', 'Type de mouvement')
            ->required('quantite', 'Quantite')
            ->required('motif', 'Motif')
            ->integerPositiveOrZero('product_id', 'Produit')
            ->integerPositiveOrZero('quantite', 'Quantite')
            ->maxLength('motif', 255, 'Motif');

        if ($validator->fails() || (int) $data['product_id'] <= 0 || (int) $data['quantite'] <= 0) {
            $this->flashError($this->firstError($validator->errors()) ?: 'La quantite doit etre superieure a zero.');
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
