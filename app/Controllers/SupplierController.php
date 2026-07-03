<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';
require_once dirname(__DIR__) . '/Core/Validator.php';
require_once dirname(__DIR__) . '/Models/Supplier.php';

class SupplierController extends AppController
{
    private Supplier $suppliers;

    public function __construct()
    {
        $this->suppliers = new Supplier();
    }

    public function index(array $params = []): void
    {
        $this->render('suppliers/index', [
            'pageTitle' => 'Fournisseurs',
            'activeMenu' => 'suppliers',
            'suppliers' => $this->suppliers->allByShop($this->currentShopId()),
        ]);
    }

    public function store(array $params = []): void
    {
        $data = $this->payload();
        $validator = $this->validateSupplier($data);

        if ($validator->fails()) {
            $this->flashError($this->firstError($validator->errors()));
            $this->redirect('/suppliers');
        }

        if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->flashError('Adresse email du fournisseur invalide.');
            $this->redirect('/suppliers');
        }

        try {
            $this->suppliers->create($data, $this->currentShopId());
            $this->flashSuccess('Fournisseur ajouté avec succès.');
        } catch (Throwable $exception) {
            $this->flashError('Impossible d’enregistrer le fournisseur: ' . $exception->getMessage());
        }

        $this->redirect('/suppliers');
    }

    public function show(array $params = []): void
    {
        $this->render('suppliers/show', [
            'pageTitle' => 'Détail fournisseur',
            'activeMenu' => 'suppliers',
            'supplier' => $this->findSupplierFromParams($params),
        ]);
    }

    public function edit(array $params = []): void
    {
        $this->render('suppliers/edit', [
            'pageTitle' => 'Modifier le fournisseur',
            'activeMenu' => 'suppliers',
            'supplier' => $this->findSupplierFromParams($params),
        ]);
    }

    public function update(array $params = []): void
    {
        $id = $this->supplierIdFromParams($params);
        $data = $this->payload();
        $validator = $this->validateSupplier($data);

        if ($validator->fails()) {
            $this->flashError($this->firstError($validator->errors()));
            $this->redirect('/suppliers/' . $id . '/edit');
        }

        if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->flashError('Adresse email du fournisseur invalide.');
            $this->redirect('/suppliers/' . $id . '/edit');
        }

        if (!$this->suppliers->updateByShop($id, $this->currentShopId(), $data)) {
            $this->abort(404, 'Fournisseur introuvable pour cette boutique.');
        }

        $this->flashSuccess('Fournisseur mis à jour avec succès.');
        $this->redirect('/suppliers');
    }

    public function destroy(array $params = []): void
    {
        try {
            if (!$this->suppliers->deleteByShop($this->supplierIdFromParams($params), $this->currentShopId())) {
                $this->abort(404, 'Fournisseur introuvable pour cette boutique.');
            }

            $this->flashSuccess('Fournisseur supprimé avec succès.');
        } catch (Throwable) {
            $this->flashError('Impossible de supprimer ce fournisseur: il est peut-être lié à un arrivage.');
        }

        $this->redirect('/suppliers');
    }

    private function validateSupplier(array $data): Validator
    {
        return Validator::make($data)
            ->required('nom', 'Nom du fournisseur')
            ->maxLength('nom', 120, 'Nom du fournisseur')
            ->maxLength('contact_nom', 120, 'Contact principal')
            ->maxLength('telephone', 30, 'Téléphone')
            ->maxLength('email', 190, 'Email');
    }

    private function payload(): array
    {
        return [
            'nom' => $_POST['nom'] ?? '',
            'contact_nom' => $this->nullableValue($_POST['contact_nom'] ?? null),
            'telephone' => $this->nullableValue($_POST['telephone'] ?? null),
            'email' => $this->nullableValue($_POST['email'] ?? null),
        ];
    }

    private function nullableValue(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function findSupplierFromParams(array $params): array
    {
        $supplier = $this->suppliers->findByShop($this->supplierIdFromParams($params), $this->currentShopId());

        if ($supplier === null) {
            $this->abort(404, 'Fournisseur introuvable pour cette boutique.');
        }

        return $supplier;
    }

    private function supplierIdFromParams(array $params): int
    {
        $id = (int) ($params['id'] ?? $_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->abort(404, 'Fournisseur introuvable.');
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
