<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';
require_once dirname(__DIR__) . '/Models/Product.php';
require_once dirname(__DIR__) . '/Models/ProductSpecialization.php';

final class FashionController extends AppController
{
    private Product $products;
    private ProductSpecialization $specialization;

    public function __construct()
    {
        $this->products = new Product();
        $this->specialization = new ProductSpecialization();
    }

    public function index(array $params = []): void
    {
        $this->requireFashionShop();

        $products = $this->specialization->fashionProducts($this->currentShopId());

        $this->render('fashion/index', [
            'pageTitle' => 'Module vetements',
            'activeMenu' => 'fashion',
            'products' => $products,
            'stats' => $this->specialization->fashionStats($products),
        ]);
    }

    public function edit(array $params = []): void
    {
        $this->requireFashionShop();
        $product = $this->productFromParams($params);

        $this->render('fashion/edit', [
            'pageTitle' => 'Fiche article textile',
            'activeMenu' => 'fashion',
            'product' => $product,
            'details' => $this->specialization->fashionDetails((int) $product['id'], $this->currentShopId()),
        ]);
    }

    public function update(array $params = []): void
    {
        $this->requireFashionShop();
        $product = $this->productFromParams($params);

        $this->specialization->saveFashion((int) $product['id'], $this->currentShopId(), $_POST);
        $this->flashSuccess('Fiche vetement mise a jour.');
        $this->redirect('/vetements');
    }

    private function requireFashionShop(): void
    {
        $shop = $this->activeShop($this->shops(), $this->currentUser());
        $slug = (string) ($shop['category_slug'] ?? '');

        if ($slug !== 'magasins-de-vetements') {
            $this->flashError('Le module vetements est disponible uniquement pour les boutiques de categorie Magasins de vetements.');
            $this->redirect('/shops/activity');
        }
    }

    private function productFromParams(array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $product = $this->products->findByShop($id, $this->currentShopId());

        if ($product === null) {
            $this->abort(404, 'Produit introuvable pour cette boutique.');
        }

        return $product;
    }
}
