<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';
require_once dirname(__DIR__) . '/Core/Validator.php';
require_once dirname(__DIR__) . '/Models/Expense.php';

class ExpenseController extends AppController
{
    private Expense $expenses;

    public function __construct()
    {
        $this->expenses = new Expense();
    }

    public function index(array $params = []): void
    {
        $filters = $this->filtersFromQuery();
        $expenses = array_map(
            static fn (array $expense): array => array_merge($expense, ['user' => $expense['user_name'] ?? '']),
            $this->expenses->allByShop($this->currentShopId(), $filters)
        );

        $this->render('expenses/index', [
            'pageTitle' => 'Charges de la boutique',
            'activeMenu' => 'finances',
            'expenses' => $expenses,
            'availableProfit' => $this->expenses->availableProfitByShop($this->currentShopId()),
            'expenseCategories' => $this->expenses->categories(),
            'filters' => $filters,
        ]);
    }

    public function create(array $params = []): void
    {
        $this->render('expenses/create', [
            'pageTitle' => 'Nouvelle depense',
            'activeMenu' => 'finances',
        ]);
    }

    public function store(array $params = []): void
    {
        $data = $this->payload();

        if (!$this->validatePayload($data)) {
            $this->redirect('/expenses');
        }

        try {
            $this->expenses->create($data, $this->currentShopId(), $this->currentUserId());
            $this->flashSuccess('Depense enregistree avec succes.');
        } catch (Throwable $exception) {
            $this->flashError('Impossible d enregistrer la depense: ' . $exception->getMessage());
        }

        $this->redirect('/expenses');
    }

    public function show(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            $this->abort(404, 'Depense introuvable.');
        }

        $expense = $this->expenses->findByShop($id, $this->currentShopId());

        if ($expense === null) {
            $this->abort(404, 'Depense introuvable pour cette boutique.');
        }

        $this->render('expenses/show', [
            'pageTitle' => 'Detail depense',
            'activeMenu' => 'finances',
            'expense' => $expense,
            'availableProfit' => $this->expenses->availableProfitForUpdate($this->currentShopId(), $id),
        ]);
    }

    public function update(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $data = $this->payload();

        if (!$this->validatePayload($data)) {
            $this->redirect($this->returnTo());
        }

        try {
            $this->expenses->update($id, $this->currentShopId(), $data);
            $this->flashSuccess('Depense modifiee avec succes.');
        } catch (Throwable $exception) {
            $this->flashError('Modification impossible: ' . $exception->getMessage());
        }

        $this->redirect($this->returnTo());
    }

    public function cancel(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);

        try {
            $this->expenses->cancel($id, $this->currentShopId(), $this->currentUserId(), $_POST['cancellation_reason'] ?? null);
            $this->flashSuccess('Depense annulee avec succes.');
        } catch (Throwable $exception) {
            $this->flashError('Annulation impossible: ' . $exception->getMessage());
        }

        $this->redirect($this->returnTo());
    }

    private function payload(): array
    {
        $enteredAmount = is_numeric($_POST['montant'] ?? null) ? round((float) $_POST['montant'], 2) : 0.0;
        $shop = $this->activeShop($this->shops(), $this->currentUser());
        $currency = in_array(($_POST['devise'] ?? null), ['USD', 'CDF'], true) ? (string) $_POST['devise'] : (string) ($shop['devise_principale'] ?? 'USD');
        $rate = (float) (($shop['taux_change_cdf'] ?? 2800) ?: 2800);

        return [
            'titre' => $_POST['titre'] ?? '',
            'description' => $_POST['description'] ?? null,
            'montant' => $this->amountToUsd($enteredAmount, $currency, $rate),
            'montant_saisi' => $enteredAmount,
            'devise_saisie' => $currency,
            'taux_change_saisie' => $rate,
            'categorie' => $_POST['categorie'] ?? 'autre',
            'date_depense' => null,
        ];
    }

    private function amountToUsd(float $amount, string $currency, float $rate): float
    {
        if ($currency === 'CDF') {
            return round($amount / max($rate, 0.0001), 2);
        }

        return round($amount, 2);
    }

    private function validatePayload(array $data): bool
    {
        $validator = Validator::make($data)
            ->required('titre', 'Titre')
            ->maxLength('titre', 120, 'Titre')
            ->required('montant_saisi', 'Montant')
            ->numeric('montant_saisi', 'Montant')
            ->positiveOrZero('montant_saisi', 'Montant');

        if ($validator->fails() || (float) $data['montant_saisi'] <= 0 || (float) $data['montant'] <= 0) {
            $this->flashError($this->firstError($validator->errors()) ?: 'Le montant doit etre superieur a zero.');

            return false;
        }

        return true;
    }

    private function filtersFromQuery(): array
    {
        $filters = [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'category' => trim((string) ($_GET['category'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'date_debut' => trim((string) ($_GET['date_debut'] ?? '')),
            'date_fin' => trim((string) ($_GET['date_fin'] ?? '')),
        ];

        if (!in_array($filters['category'], $this->expenses->categories(), true)) {
            $filters['category'] = '';
        }

        if (!in_array($filters['status'], ['active', 'cancelled'], true)) {
            $filters['status'] = '';
        }

        return $filters;
    }

    private function returnTo(): string
    {
        $returnTo = trim((string) ($_POST['return_to'] ?? ''));

        if ($returnTo !== '' && str_starts_with($returnTo, '/')) {
            return $returnTo;
        }

        return '/expenses';
    }

    private function firstError(array $errors): string
    {
        foreach ($errors as $messages) {
            return (string) ($messages[0] ?? '');
        }

        return '';
    }
}
