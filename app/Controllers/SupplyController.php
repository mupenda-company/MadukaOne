<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';
require_once dirname(__DIR__) . '/Core/Validator.php';
require_once dirname(__DIR__) . '/Models/Supply.php';

class SupplyController extends AppController
{
    private Supply $supplies;

    public function __construct()
    {
        $this->supplies = new Supply();
    }

    public function index(array $params = []): void
    {
        $supplies = $this->supplies->allByShop($this->currentShopId());

        if (trim((string) file_get_contents(dirname(__DIR__) . '/Views/supplies/index.php')) === '') {
            $this->create($params);
            return;
        }

        $this->render('supplies/index', [
            'pageTitle' => 'Approvisionnements',
            'activeMenu' => 'supplies',
            'supplies' => $supplies,
        ]);
    }

    public function create(array $params = []): void
    {
        $shopId = $this->currentShopId();

        $this->render('supplies/create', [
            'pageTitle' => 'Nouvel arrivage',
            'activeMenu' => 'supplies',
            'suppliers' => $this->suppliers($shopId),
            'products' => $this->products($shopId),
        ]);
    }

    public function store(array $params = []): void
    {
        $data = $this->payload();
        $errors = $this->validateArrival($data);

        if ($errors !== []) {
            $this->flashError($this->firstError($errors));
            $this->redirect('/supplies/create');
        }

        try {
            $supplyId = $this->supplies->createArrival($data, $this->currentShopId(), $this->currentUserId());
            $this->flashSuccess('Arrivage validé avec succès.');
            $this->redirect('/supplies/' . $supplyId);
        } catch (Throwable $exception) {
            $this->flashError('Impossible de valider l’arrivage: ' . $exception->getMessage());
            $this->redirect('/supplies/create');
        }
    }

    public function show(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            $this->abort(404, 'Arrivage introuvable.');
        }

        $supply = $this->supplies->findByShop($id, $this->currentShopId());

        if ($supply === null) {
            $this->abort(404, 'Arrivage introuvable pour cette boutique.');
        }

        if (trim((string) file_get_contents(dirname(__DIR__) . '/Views/supplies/show.php')) === '') {
            $this->flashSuccess('Arrivage validé avec succès.');
            $this->redirect('/supplies/create');
        }

        $this->render('supplies/show', [
            'pageTitle' => 'Détail arrivage',
            'activeMenu' => 'supplies',
            'supply' => $supply,
        ]);
    }

    private function payload(): array
    {
        return [
            'supplier_id' => $_POST['supplier_id'] ?? null,
            'numero_arrivage' => $_POST['numero_arrivage'] ?? '',
            'date_approvisionnement' => $this->dateTimeValue($_POST['date_approvisionnement'] ?? null),
            'items' => $this->itemsPayload($_POST),
        ];
    }

    private function itemsPayload(array $post): array
    {
        if (isset($post['items']) && is_array($post['items'])) {
            return array_values(array_filter($post['items'], 'is_array'));
        }

        $productIds = is_array($post['product_id'] ?? null) ? $post['product_id'] : [];
        $quantities = is_array($post['quantite'] ?? null) ? $post['quantite'] : [];
        $prices = is_array($post['prix_achat_facture'] ?? null) ? $post['prix_achat_facture'] : [];
        $items = [];

        foreach ($productIds as $index => $productId) {
            $items[] = [
                'product_id' => $productId,
                'quantite' => $quantities[$index] ?? null,
                'prix_achat_facture' => $prices[$index] ?? null,
            ];
        }

        return $items;
    }

    private function validateArrival(array $data): array
    {
        $validator = Validator::make($data)
            ->required('supplier_id', 'Fournisseur')
            ->integerPositiveOrZero('supplier_id', 'Fournisseur')
            ->required('numero_arrivage', 'Numéro d’arrivage')
            ->maxLength('numero_arrivage', 50, 'Numéro d’arrivage');

        $errors = $validator->errors();

        if ((int) ($data['supplier_id'] ?? 0) <= 0) {
            $errors['supplier_id'][] = 'Fournisseur invalide.';
        }

        if ($data['items'] === []) {
            $errors['items'][] = 'Ajoutez au moins un produit à l’arrivage.';
            return $errors;
        }

        foreach ($data['items'] as $index => $item) {
            $line = $index + 1;

            if ((int) ($item['product_id'] ?? 0) <= 0) {
                $errors["items.{$index}.product_id"][] = "Produit invalide à la ligne {$line}.";
            }

            if ((int) ($item['quantite'] ?? 0) <= 0) {
                $errors["items.{$index}.quantite"][] = "Quantité invalide à la ligne {$line}.";
            }

            if (!is_numeric($item['prix_achat_facture'] ?? null) || (float) $item['prix_achat_facture'] < 0) {
                $errors["items.{$index}.prix_achat_facture"][] = "Prix d’achat invalide à la ligne {$line}.";
            }
        }

        return $errors;
    }

    private function suppliers(int $shopId): array
    {
        $statement = Database::connection()->prepare('SELECT id, nom FROM suppliers WHERE shop_id = :shop_id ORDER BY nom ASC');
        $statement->execute(['shop_id' => $shopId]);

        return $statement->fetchAll();
    }

    private function products(int $shopId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, nom, ref, prix_achat FROM products WHERE shop_id = :shop_id AND actif = 1 ORDER BY nom ASC'
        );
        $statement->execute(['shop_id' => $shopId]);

        return $statement->fetchAll();
    }

    private function dateTimeValue(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : str_replace('T', ' ', $value);
    }

    private function firstError(array $errors): string
    {
        foreach ($errors as $messages) {
            return (string) ($messages[0] ?? 'Données invalides.');
        }

        return 'Données invalides.';
    }
}
