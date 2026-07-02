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
        $expenses = array_map(
            static fn (array $expense): array => array_merge($expense, ['user' => $expense['user_name'] ?? '']),
            $this->expenses->allByShop($this->currentShopId())
        );

        $this->render('expenses/index', [
            'pageTitle' => 'Charges de la boutique',
            'activeMenu' => 'finances',
            'expenses' => $expenses,
        ]);
    }

    public function create(array $params = []): void
    {
        $this->render('expenses/create', [
            'pageTitle' => 'Nouvelle dépense',
            'activeMenu' => 'finances',
        ]);
    }

    public function store(array $params = []): void
    {
        $data = $this->payload();
        $validator = Validator::make($data)
            ->required('titre', 'Titre')
            ->maxLength('titre', 120, 'Titre')
            ->required('montant', 'Montant')
            ->numeric('montant', 'Montant')
            ->positiveOrZero('montant', 'Montant');

        if ($validator->fails() || (float) $data['montant'] <= 0) {
            $this->flashError($this->firstError($validator->errors()) ?: 'Le montant doit être supérieur à zéro.');
            $this->redirect('/expenses');
        }

        try {
            $this->expenses->create($data, $this->currentShopId(), $this->currentUserId());
            $this->flashSuccess('Dépense enregistrée avec succès.');
        } catch (Throwable $exception) {
            $this->flashError('Impossible d’enregistrer la dépense: ' . $exception->getMessage());
        }

        $this->redirect('/expenses');
    }

    public function show(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            $this->abort(404, 'Dépense introuvable.');
        }

        $expense = $this->expenses->findByShop($id, $this->currentShopId());

        if ($expense === null) {
            $this->abort(404, 'Dépense introuvable pour cette boutique.');
        }

        $this->render('expenses/show', [
            'pageTitle' => 'Détail dépense',
            'activeMenu' => 'finances',
            'expense' => $expense,
        ]);
    }

    private function payload(): array
    {
        return [
            'titre' => $_POST['titre'] ?? '',
            'description' => $_POST['description'] ?? null,
            'montant' => $_POST['montant'] ?? 0,
            'categorie' => $_POST['categorie'] ?? 'autre',
            'date_depense' => $this->dateValue($_POST['date_depense'] ?? null),
        ];
    }

    private function dateValue(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value . ' 00:00:00';
    }

    private function firstError(array $errors): string
    {
        foreach ($errors as $messages) {
            return (string) ($messages[0] ?? '');
        }

        return '';
    }
}
