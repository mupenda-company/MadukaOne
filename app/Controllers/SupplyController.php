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
        $shopId = $this->currentShopId();
        $perPage = 10;
        $totalSupplies = $this->supplies->countByShop($shopId);
        $totalPages = max(1, (int) ceil($totalSupplies / $perPage));
        $currentPage = max(1, min($totalPages, (int) ($_GET['page'] ?? 1)));
        $offset = ($currentPage - 1) * $perPage;

        $this->render('supplies/index', [
            'pageTitle' => 'Approvisionnements',
            'activeMenu' => 'supplies',
            'supplies' => $this->supplies->allByShop($shopId, $perPage, $offset),
            'pagination' => [
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'total_items' => $totalSupplies,
                'total_pages' => $totalPages,
                'from' => $totalSupplies === 0 ? 0 : $offset + 1,
                'to' => min($offset + $perPage, $totalSupplies),
            ],
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
            'selectedSupplierId' => (int) ($_GET['supplier_id'] ?? 0),
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
        $id = $this->supplyIdFromParams($params);
        $shopId = $this->currentShopId();
        $supply = $this->supplies->findByShop($id, $shopId);

        if ($supply === null) {
            $this->abort(404, 'Approvisionnement introuvable pour cette boutique.');
        }

        $this->render('supplies/show', [
            'pageTitle' => 'Détail approvisionnement',
            'activeMenu' => 'supplies',
            'supply' => $supply,
            'details' => $this->supplies->detailsBySupply($id, $shopId),
        ]);
    }

    public function edit(array $params = []): void
    {
        $id = $this->supplyIdFromParams($params);
        $shopId = $this->currentShopId();
        $supply = $this->supplies->findByShop($id, $shopId);

        if ($supply === null) {
            $this->abort(404, 'Approvisionnement introuvable pour cette boutique.');
        }

        if ((string) ($supply['statut'] ?? '') === 'annule') {
            $this->flashError('Un approvisionnement annulé ne peut pas être modifié.');
            $this->redirect('/supplies/' . $id);
        }

        $this->render('supplies/edit', [
            'pageTitle' => 'Modifier l’approvisionnement',
            'activeMenu' => 'supplies',
            'supply' => $supply,
            'details' => $this->supplies->detailsBySupply($id, $shopId),
            'suppliers' => $this->suppliers($shopId),
            'products' => $this->products($shopId, activeOnly: false),
        ]);
    }

    public function update(array $params = []): void
    {
        $id = $this->supplyIdFromParams($params);
        $data = $this->payload();
        $errors = $this->validateArrival($data);

        if ($errors !== []) {
            $this->flashError($this->firstError($errors));
            $this->redirect('/supplies/' . $id . '/edit');
        }

        try {
            $this->supplies->updateArrival($id, $data, $this->currentShopId(), $this->currentUserId());
            $this->flashSuccess('Approvisionnement mis à jour avec succès.');
            $this->redirect('/supplies/' . $id);
        } catch (Throwable $exception) {
            $this->flashError('Impossible de modifier l’approvisionnement: ' . $exception->getMessage());
            $this->redirect('/supplies/' . $id . '/edit');
        }
    }

    public function cancel(array $params = []): void
    {
        $id = $this->supplyIdFromParams($params);

        try {
            $this->supplies->cancelByShop($id, $this->currentShopId(), $this->currentUserId());
            $this->flashSuccess('Approvisionnement annulé avec succès.');
            $this->redirect('/supplies');
        } catch (Throwable $exception) {
            $this->flashError('Impossible d’annuler l’approvisionnement: ' . $exception->getMessage());
            $this->redirect('/supplies/' . $id);
        }
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

    private function products(int $shopId, bool $activeOnly = true): array
    {
        $activeClause = $activeOnly ? ' AND actif = 1' : '';
        $statement = Database::connection()->prepare(
            'SELECT id, nom, ref, prix_achat FROM products WHERE shop_id = :shop_id' . $activeClause . ' ORDER BY nom ASC'
        );
        $statement->execute(['shop_id' => $shopId]);

        return $statement->fetchAll();
    }

    private function dateTimeValue(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : str_replace('T', ' ', $value);
    }

    private function supplyIdFromParams(array $params): int
    {
        $id = (int) ($params['id'] ?? $_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->abort(404, 'Approvisionnement introuvable.');
        }

        return $id;
    }

    private function firstError(array $errors): string
    {
        foreach ($errors as $messages) {
            return (string) ($messages[0] ?? 'Données invalides.');
        }

        return 'Données invalides.';
    }
}
