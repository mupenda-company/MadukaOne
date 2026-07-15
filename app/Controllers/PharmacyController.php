<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';
require_once dirname(__DIR__) . '/Models/Product.php';
require_once dirname(__DIR__) . '/Models/ProductSpecialization.php';

final class PharmacyController extends AppController
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
        $this->requirePharmacyShop();

        $products = $this->specialization->pharmacyProducts($this->currentShopId());

        $this->render('pharmacy/index', [
            'pageTitle' => 'Module pharmacie',
            'activeMenu' => 'pharmacy',
            'products' => $products,
            'stats' => $this->specialization->pharmacyStats($products),
        ]);
    }

    public function edit(array $params = []): void
    {
        $this->requirePharmacyShop();
        $product = $this->productFromParams($params);

        $this->render('pharmacy/edit', [
            'pageTitle' => 'Fiche medicament',
            'activeMenu' => 'pharmacy',
            'product' => $product,
            'details' => $this->specialization->pharmacyDetails((int) $product['id'], $this->currentShopId()),
        ]);
    }

    public function update(array $params = []): void
    {
        $this->requirePharmacyShop();
        $product = $this->productFromParams($params);

        $this->specialization->savePharmacy((int) $product['id'], $this->currentShopId(), $_POST);
        $this->flashSuccess('Fiche medicament mise a jour.');
        $this->redirect('/pharmacie');
    }

    private function requirePharmacyShop(): void
    {
        $shop = $this->activeShop($this->shops(), $this->currentUser());
        $slug = (string) ($shop['category_slug'] ?? '');

        if ($slug !== 'pharmacies') {
            $this->flashError('Le module pharmacie est disponible uniquement pour les boutiques de categorie Pharmacies.');
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
