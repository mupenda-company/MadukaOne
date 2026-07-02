<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Validator.php';
require_once dirname(__DIR__) . '/Models/Product.php';

final class ProductController
{
    private Product $products;

    public function __construct()
    {
        $this->products = new Product();
    }

    public function index(array $params = []): void
    {
        $this->requireAdmin();
        $shopId = $this->currentShopId();
        $products = $this->products->allByShop($shopId);

        $this->view('products/index', compact('products'));
    }

    public function create(array $params = []): void
    {
        $this->requireAdmin();
        $this->view('products/create');
    }

    public function store(array $params = []): void
    {
        $this->requireAdmin();

        $data = $this->productPayload(allowInitialStock: true);
        $validator = $this->validateProduct($data, allowInitialStock: true);

        if ($validator->fails()) {
            $this->flashErrors($validator->errors());
            $this->redirect('/products/create');
        }

        $this->products->create($data, $this->currentShopId(), $this->currentUserId());
        $this->flashSuccess('Produit cree avec succes.');
        $this->redirect('/products');
    }

    public function show(array $params = []): void
    {
        $this->requireAdmin();
        $product = $this->findProductFromParams($params);

        $this->view('products/show', compact('product'));
    }

    public function edit(array $params = []): void
    {
        $this->requireAdmin();
        $product = $this->findProductFromParams($params);

        $this->view('products/edit', compact('product'));
    }

    public function update(array $params = []): void
    {
        $this->requireAdmin();
        $id = $this->productIdFromParams($params);
        $data = $this->productPayload(allowInitialStock: false);
        $validator = $this->validateProduct($data, allowInitialStock: false);

        if ($validator->fails()) {
            $this->flashErrors($validator->errors());
            $this->redirect('/products/' . $id . '/edit');
        }

        $updated = $this->products->updateByShop($id, $this->currentShopId(), $data, $this->currentUserId());

        if (!$updated) {
            $this->abort(404, 'Produit introuvable pour cette boutique.');
        }

        $this->flashSuccess('Produit mis a jour avec succes.');
        $this->redirect('/products');
    }

    public function destroy(array $params = []): void
    {
        $this->requireAdmin();
        $deleted = $this->products->deleteByShop($this->productIdFromParams($params), $this->currentShopId());

        if (!$deleted) {
            $this->abort(404, 'Produit introuvable pour cette boutique.');
        }

        $this->flashSuccess('Produit desactive avec succes.');
        $this->redirect('/products');
    }

    private function validateProduct(array $data, bool $allowInitialStock): Validator
    {
        $validator = Validator::make($data)
            ->required('nom', 'Nom du produit')
            ->maxLength('nom', 190, 'Nom du produit')
            ->maxLength('code_barre', 80, 'Code-barres')
            ->maxLength('ref', 80, 'Reference')
            ->numeric('prix_achat', 'Prix d achat')
            ->numeric('prix_vente', 'Prix de vente')
            ->positiveOrZero('prix_achat', 'Prix d achat')
            ->positiveOrZero('prix_vente', 'Prix de vente')
            ->integerPositiveOrZero('alerte_stock_min', 'Alerte stock minimum');

        if ($allowInitialStock) {
            $validator->integerPositiveOrZero('quantite_stock', 'Stock initial');
        }

        return $validator;
    }

    private function productPayload(bool $allowInitialStock): array
    {
        $payload = [
            'code_barre' => $_POST['code_barre'] ?? null,
            'ref' => $_POST['ref'] ?? null,
            'nom' => $_POST['nom'] ?? '',
            'description' => $_POST['description'] ?? null,
            'prix_achat' => $_POST['prix_achat'] ?? 0,
            'prix_vente' => $_POST['prix_vente'] ?? 0,
            'alerte_stock_min' => $_POST['alerte_stock_min'] ?? 0,
            'actif' => $_POST['actif'] ?? '1',
        ];

        if ($allowInitialStock) {
            $payload['quantite_stock'] = $_POST['quantite_stock'] ?? 0;
        }

        return $payload;
    }

    private function findProductFromParams(array $params): array
    {
        $product = $this->products->findByShop($this->productIdFromParams($params), $this->currentShopId());

        if ($product === null) {
            $this->abort(404, 'Produit introuvable pour cette boutique.');
        }

        return $product;
    }

    private function productIdFromParams(array $params): int
    {
        $id = (int) ($params['id'] ?? $_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->abort(404, 'Produit introuvable.');
        }

        return $id;
    }

    private function currentShopId(): int
    {
        $this->startSession();
        $shopId = (int) ($_SESSION['user']['shop_id'] ?? 0);

        if ($shopId <= 0) {
            $this->abort(403, 'Boutique non definie pour cet utilisateur.');
        }

        return $shopId;
    }

    private function currentUserId(): int
    {
        $this->startSession();
        $userId = (int) ($_SESSION['user']['id'] ?? 0);

        if ($userId <= 0) {
            $this->redirect('/login');
        }

        return $userId;
    }

    private function requireAdmin(): void
    {
        $this->startSession();

        if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
            $this->redirect('/login');
        }

        $role = strtolower((string) ($_SESSION['user']['role'] ?? $_SESSION['user']['role_legacy'] ?? 'agent'));

        if ($role === 'agent') {
            http_response_code(403);
            $this->flashError('Acces refuse: la gestion des articles est reservee a l administrateur.');
            $this->redirect('/pos');
        }
    }

    private function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $file = dirname(__DIR__) . '/Views/' . $view . '.php';

        if (!is_file($file)) {
            $this->abort(500, 'Vue introuvable.');
        }

        require $file;
    }

    private function flashErrors(array $errors): void
    {
        $this->startSession();
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = $_POST;
    }

    private function flashSuccess(string $message): void
    {
        $this->startSession();
        $_SESSION['flash_success'] = $message;
    }

    private function flashError(string $message): void
    {
        $this->startSession();
        $_SESSION['flash_error'] = $message;
    }

    private function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true,
            ]);
        }
    }

    private function redirect(string $path): never
    {
        header('Location: ' . $path, true, 302);
        exit;
    }

    private function abort(int $statusCode, string $message): never
    {
        http_response_code($statusCode);
        echo $message;
        exit;
    }
}
