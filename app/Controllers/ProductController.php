<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';
require_once dirname(__DIR__) . '/Core/SubscriptionGate.php';
require_once dirname(__DIR__) . '/Core/Validator.php';
require_once dirname(__DIR__) . '/Models/Product.php';

class ProductController extends AppController
{
    private Product $products;

    public function __construct()
    {
        $this->products = new Product();
    }

    public function index(array $params = []): void
    {
        $this->render('products/index', [
            'pageTitle' => 'Produits',
            'activeMenu' => 'products',
            'products' => $this->products->allByShop($this->currentShopId()),
            'productCategories' => $this->productCategories(),
        ]);
    }

    public function create(array $params = []): void
    {
        $this->render('products/create', [
            'pageTitle' => 'Ajouter un produit',
            'activeMenu' => 'products',
            'productCategories' => $this->productCategories(),
            'nextReference' => $this->products->nextReference($this->currentShopId()),
        ]);
    }

    public function store(array $params = []): void
    {
        $data = $this->productPayload(allowInitialStock: true);
        $validator = $this->validateProduct($data, allowInitialStock: true);

        if ($validator->fails()) {
            $this->flashError($this->firstError($validator->errors()));
            $this->redirect('/products/create');
        }

        $dateError = $this->dateValidationError($data);
        if ($dateError !== null) {
            $this->flashError($dateError);
            $this->redirect('/products/create');
        }

        if (!$this->products->categoryBelongsToShop($this->nullableCategoryId($data['category_id'] ?? null), $this->currentShopId())) {
            $this->flashError('La categorie selectionnee est invalide pour cette boutique.');
            $this->redirect('/products/create');
        }

        $limitError = (new SubscriptionGate())->creationError($this->currentShopId(), 'products');
        if ($limitError !== null) {
            $this->flashError($limitError);
            $this->redirect('/products/create');
        }

        try {
            $productId = $this->products->create($data, $this->currentShopId(), $this->currentUserId());
            $stock = (int) ($data['quantite_stock'] ?? 0);

            if ($stock > 0) {
                $this->insertInitialStockMovement($productId, $stock);
            }

            $this->flashSuccess('Produit créé avec succès.');
            $this->redirect('/products');
        } catch (Throwable $exception) {
            $this->flashError('Impossible d’enregistrer le produit: ' . $exception->getMessage());
            $this->redirect('/products/create');
        }
    }

    public function show(array $params = []): void
    {
        $this->render('products/show', [
            'pageTitle' => 'Détail produit',
            'activeMenu' => 'products',
            'product' => $this->findProductFromParams($params),
            'productCategories' => $this->productCategories(),
        ]);
    }

    public function edit(array $params = []): void
    {
        $this->render('products/edit', [
            'pageTitle' => 'Modifier le produit',
            'activeMenu' => 'products',
            'product' => $this->findProductFromParams($params),
            'productCategories' => $this->productCategories(),
        ]);
    }

    public function update(array $params = []): void
    {
        $id = $this->productIdFromParams($params);
        $data = $this->productPayload(allowInitialStock: false);
        $validator = $this->validateProduct($data, allowInitialStock: false);

        if ($validator->fails()) {
            $this->flashError($this->firstError($validator->errors()));
            $this->redirect('/products/' . $id . '/edit');
        }

        $dateError = $this->dateValidationError($data);
        if ($dateError !== null) {
            $this->flashError($dateError);
            $this->redirect('/products/' . $id . '/edit');
        }

        if (!$this->products->categoryBelongsToShop($this->nullableCategoryId($data['category_id'] ?? null), $this->currentShopId())) {
            $this->flashError('La categorie selectionnee est invalide pour cette boutique.');
            $this->redirect('/products/' . $id . '/edit');
        }

        if (!$this->products->updateByShop($id, $this->currentShopId(), $data, $this->currentUserId())) {
            $this->abort(404, 'Produit introuvable pour cette boutique.');
        }

        $this->flashSuccess('Produit mis à jour avec succès.');
        $this->redirect('/products');
    }

    public function destroy(array $params = []): void
    {
        if (!$this->products->deleteByShop($this->productIdFromParams($params), $this->currentShopId())) {
            $this->abort(404, 'Produit introuvable pour cette boutique.');
        }

        $this->flashSuccess('Produit désactivé avec succès.');
        $this->redirect('/products');
    }

    public function storeCategory(array $params = []): void
    {
        try {
            $payload = $this->isJsonRequest() ? $this->jsonPayload() : $_POST;
            $category = $this->products->createCategory($this->currentShopId(), (string) ($payload['nom'] ?? $payload['name'] ?? ''));

            $this->json([
                'ok' => true,
                'success' => true,
                'message' => 'Categorie ajoutee avec succes.',
                'category' => $category,
                'categories' => $this->productCategories(),
            ], 201);
        } catch (Throwable $exception) {
            $this->json(['ok' => false, 'success' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    private function validateProduct(array $data, bool $allowInitialStock): Validator
    {
        $validator = Validator::make($data)
            ->required('nom', 'Nom du produit')
            ->maxLength('nom', 190, 'Nom du produit')
            ->maxLength('code_barre', 80, 'Code-barres')
            ->maxLength('ref', 80, 'Référence')
            ->numeric('prix_achat', 'Prix d’achat')
            ->numeric('prix_vente', 'Prix de vente')
            ->numeric('prix_achat_montant', 'Montant du prix d achat')
            ->numeric('prix_vente_montant', 'Montant du prix de vente')
            ->positiveOrZero('prix_achat', 'Prix d’achat')
            ->positiveOrZero('prix_vente', 'Prix de vente')
            ->positiveOrZero('prix_achat_montant', 'Montant du prix d achat')
            ->positiveOrZero('prix_vente_montant', 'Montant du prix de vente')
            ->integerPositiveOrZero('alerte_stock_min', 'Alerte stock minimum');

        if ($allowInitialStock) {
            $validator->integerPositiveOrZero('quantite_stock', 'Stock initial');
        }

        return $validator;
    }

    private function productPayload(bool $allowInitialStock): array
    {
        $purchaseCurrency = $this->currencyFromInput($_POST['prix_achat_devise'] ?? null);
        $saleCurrency = $this->currencyFromInput($_POST['prix_vente_devise'] ?? null);
        $purchaseAmount = $_POST['prix_achat_montant'] ?? $_POST['prix_achat'] ?? 0;
        $saleAmount = $_POST['prix_vente_montant'] ?? $_POST['prix_vente'] ?? 0;
        $exchangeRate = $this->currentExchangeRate();

        $payload = [
            'code_barre' => $_POST['code_barre'] ?? null,
            'ref' => $_POST['ref'] ?? null,
            'category_id' => $_POST['category_id'] ?? null,
            'nom' => $_POST['nom'] ?? '',
            'description' => $_POST['description'] ?? null,
            'prix_achat' => $this->amountToUsd($purchaseAmount, $purchaseCurrency, $exchangeRate),
            'prix_vente' => $this->amountToUsd($saleAmount, $saleCurrency, $exchangeRate),
            'prix_achat_devise' => $purchaseCurrency,
            'prix_vente_devise' => $saleCurrency,
            'prix_achat_montant' => $purchaseAmount,
            'prix_vente_montant' => $saleAmount,
            'alerte_stock_min' => $_POST['alerte_stock_min'] ?? 0,
            'date_fabrication' => $_POST['date_fabrication'] ?? null,
            'date_expiration' => $_POST['date_expiration'] ?? null,
            'actif' => $_POST['actif'] ?? '1',
        ];

        if ($allowInitialStock) {
            $payload['quantite_stock'] = $_POST['quantite_stock'] ?? 0;
        }

        return $payload;
    }

    private function currencyFromInput(mixed $value): string
    {
        $currency = strtoupper(trim((string) ($value ?? 'USD')));

        return in_array($currency, ['USD', 'CDF'], true) ? $currency : 'USD';
    }

    private function amountToUsd(mixed $amount, string $currency, float $exchangeRate): float
    {
        $value = is_numeric($amount) ? (float) $amount : 0.0;

        if ($currency === 'CDF') {
            return round($value / max($exchangeRate, 0.0001), 2);
        }

        return round($value, 2);
    }

    private function currentExchangeRate(): float
    {
        $shops = $this->shops();
        $activeShop = $this->activeShop($shops, $this->currentUser());
        $rate = (float) ($activeShop['taux_change_cdf'] ?? 2800);

        return $rate > 0 ? $rate : 2800;
    }

    private function dateValidationError(array $data): ?string
    {
        $manufacturedValue = trim((string) ($data['date_fabrication'] ?? ''));
        $expirationValue = trim((string) ($data['date_expiration'] ?? ''));
        $manufacturedAt = $this->dateFromInput($data['date_fabrication'] ?? null);
        $expiresAt = $this->dateFromInput($data['date_expiration'] ?? null);

        if ($manufacturedValue !== '' && $manufacturedAt === null) {
            return 'La date de fabrication est invalide.';
        }

        if ($expirationValue !== '' && $expiresAt === null) {
            return 'La date d expiration est invalide.';
        }

        if ($manufacturedAt !== null && $expiresAt !== null && $manufacturedAt > $expiresAt) {
            return 'La date de fabrication ne peut pas etre apres la date d expiration.';
        }

        return null;
    }

    private function dateFromInput(mixed $value): ?DateTimeImmutable
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();

        if (!$date instanceof DateTimeImmutable || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        return $date;
    }

    private function productCategories(): array
    {
        $categories = $this->products->categoriesByShop($this->currentShopId());

        if ($categories === []) {
            $this->products->createCategory($this->currentShopId(), 'General');
            $categories = $this->products->categoriesByShop($this->currentShopId());
        }

        return $categories;
    }

    private function nullableCategoryId(mixed $value): ?int
    {
        $id = (int) ($value ?? 0);

        return $id > 0 ? $id : null;
    }

    private function jsonPayload(): array
    {
        $raw = file_get_contents('php://input');

        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $payload = json_decode($raw, true);

        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('JSON invalide.');
        }

        return $payload;
    }

    private function isJsonRequest(): bool
    {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));

        return str_contains($contentType, 'application/json') || str_contains($accept, 'application/json');
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

    private function insertInitialStockMovement(int $productId, int $stock): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO stock_movements (shop_id, product_id, user_id, type_mouvement, quantite, stock_avant, stock_apres, motif)
             VALUES (:shop_id, :product_id, :user_id, :type_mouvement, :quantite, 0, :stock_apres, :motif)'
        );
        $statement->execute([
            'shop_id' => $this->currentShopId(),
            'product_id' => $productId,
            'user_id' => $this->currentUserId(),
            'type_mouvement' => 'entree',
            'quantite' => $stock,
            'stock_apres' => $stock,
            'motif' => 'Stock initial à la création du produit',
        ]);
    }

    private function firstError(array $errors): string
    {
        foreach ($errors as $messages) {
            return (string) ($messages[0] ?? 'Données invalides.');
        }

        return 'Données invalides.';
    }
}
