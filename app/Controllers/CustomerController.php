<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';
require_once dirname(__DIR__) . '/Core/Validator.php';
require_once dirname(__DIR__) . '/Models/Customer.php';

class CustomerController extends AppController
{
    private Customer $customers;

    public function __construct()
    {
        $this->customers = new Customer();
    }

    public function index(array $params = []): void
    {
        $this->render('customers/index', [
            'pageTitle' => 'Clients',
            'activeMenu' => 'customers',
            'customers' => $this->customers->allByShop($this->currentShopId()),
        ]);
    }

    public function store(array $params = []): void
    {
        $data = $this->payload();
        $validator = $this->validateCustomer($data);

        if ($validator->fails()) {
            $this->flashError($this->firstError($validator->errors()));
            $this->redirect('/customers');
        }

        if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->flashError('Adresse email du client invalide.');
            $this->redirect('/customers');
        }

        try {
            $this->customers->create($data, $this->currentShopId());
            $this->flashSuccess('Client ajouté avec succès.');
        } catch (Throwable $exception) {
            $this->flashError('Impossible d’enregistrer le client: ' . $exception->getMessage());
        }

        $this->redirect('/customers');
    }

    public function show(array $params = []): void
    {
        $this->render('customers/show', [
            'pageTitle' => 'Détail client',
            'activeMenu' => 'customers',
            'customer' => $this->findCustomerFromParams($params),
        ]);
    }

    public function edit(array $params = []): void
    {
        $this->render('customers/edit', [
            'pageTitle' => 'Modifier le client',
            'activeMenu' => 'customers',
            'customer' => $this->findCustomerFromParams($params),
        ]);
    }

    public function update(array $params = []): void
    {
        $id = $this->customerIdFromParams($params);
        $data = $this->payload();
        $validator = $this->validateCustomer($data);

        if ($validator->fails()) {
            $this->flashError($this->firstError($validator->errors()));
            $this->redirect('/customers/' . $id . '/edit');
        }

        if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->flashError('Adresse email du client invalide.');
            $this->redirect('/customers/' . $id . '/edit');
        }

        if (!$this->customers->updateByShop($id, $this->currentShopId(), $data)) {
            $this->abort(404, 'Client introuvable pour cette boutique.');
        }

        $this->flashSuccess('Client mis à jour avec succès.');
        $this->redirect('/customers');
    }

    public function destroy(array $params = []): void
    {
        try {
            if (!$this->customers->deleteByShop($this->customerIdFromParams($params), $this->currentShopId())) {
                $this->abort(404, 'Client introuvable pour cette boutique.');
            }

            $this->flashSuccess('Client supprimé avec succès.');
        } catch (Throwable) {
            $this->flashError('Impossible de supprimer ce client: il est peut-être lié à une vente.');
        }

        $this->redirect('/customers');
    }

    public function settleDebt(array $params = []): void
    {
        $id = $this->customerIdFromParams($params);
        $amount = (float) ($_POST['amount'] ?? 0);

        try {
            $result = $this->customers->settleDebtByShop($id, $this->currentShopId(), $amount);

            if (($result['settled'] ?? 0) <= 0) {
                $this->flashError('Aucune dette à régler pour ce client.');
                $this->redirect('/customers');
            }

            $this->flashSuccess(
                'Dette réglée: ' . number_format((float) $result['settled'], 2, ',', ' ')
                . ' USD. Facture(s) actualisée(s): ' . (int) ($result['updated_sales'] ?? 0) . '.'
            );
        } catch (Throwable $exception) {
            $this->flashError('Impossible de régler la dette: ' . $exception->getMessage());
        }

        $this->redirect('/customers');
    }

    private function validateCustomer(array $data): Validator
    {
        return Validator::make($data)
            ->required('nom', 'Nom du client')
            ->maxLength('nom', 120, 'Nom du client')
            ->maxLength('telephone', 30, 'Téléphone')
            ->maxLength('email', 190, 'Email')
            ->numeric('dette_actuelle', 'Dette actuelle')
            ->positiveOrZero('dette_actuelle', 'Dette actuelle');
    }

    private function payload(): array
    {
        return [
            'nom' => $_POST['nom'] ?? '',
            'telephone' => $this->nullableValue($_POST['telephone'] ?? null),
            'email' => $this->nullableValue($_POST['email'] ?? null),
            'dette_actuelle' => $_POST['dette_actuelle'] ?? 0,
        ];
    }

    private function nullableValue(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function findCustomerFromParams(array $params): array
    {
        $customer = $this->customers->findByShop($this->customerIdFromParams($params), $this->currentShopId());

        if ($customer === null) {
            $this->abort(404, 'Client introuvable pour cette boutique.');
        }

        return $customer;
    }

    private function customerIdFromParams(array $params): int
    {
        $id = (int) ($params['id'] ?? $_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->abort(404, 'Client introuvable.');
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
