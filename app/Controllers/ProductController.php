<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';
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
        ]);
    }

    public function create(array $params = []): void
    {
        $this->render('products/create', [
            'pageTitle' => 'Ajouter un produit',
            'activeMenu' => 'products',
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
        ]);
    }

    public function edit(array $params = []): void
    {
        $this->render('products/edit', [
            'pageTitle' => 'Modifier le produit',
            'activeMenu' => 'products',
            'product' => $this->findProductFromParams($params),
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

    private function validateProduct(array $data, bool $allowInitialStock): Validator
    {
        $validator = Validator::make($data)
            ->required('nom', 'Nom du produit')
            ->maxLength('nom', 190, 'Nom du produit')
            ->maxLength('code_barre', 80, 'Code-barres')
            ->maxLength('ref', 80, 'Référence')
            ->numeric('prix_achat', 'Prix d’achat')
            ->numeric('prix_vente', 'Prix de vente')
            ->positiveOrZero('prix_achat', 'Prix d’achat')
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
            'date_fabrication' => $_POST['date_fabrication'] ?? null,
            'date_expiration' => $_POST['date_expiration'] ?? null,
            'actif' => $_POST['actif'] ?? '1',
        ];

        if ($allowInitialStock) {
            $payload['quantite_stock'] = $_POST['quantite_stock'] ?? 0;
        }

        return $payload;
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
