<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Validator.php';
require_once dirname(__DIR__) . '/Models/Expense.php';

final class ExpenseController
{
    private Expense $expenses;

    public function __construct()
    {
        $this->expenses = new Expense();
    }

    public function index(array $params = []): void
    {
        $this->requireManager();
        $expenses = $this->expenses->allByShop($this->currentShopId());

        $this->view('expenses/index', compact('expenses'));
    }

    public function create(array $params = []): void
    {
        $this->requireManager();
        $this->view('expenses/create');
    }

    public function store(array $params = []): void
    {
        $this->requireManager();

        $data = $this->payload();
        $errors = $this->validateExpense($data);

        if ($errors !== []) {
            $this->flashErrors($errors);
            $this->redirect('/expenses/create');
        }

        $expenseId = $this->expenses->create($data, $this->currentShopId(), $this->currentUserId());
        $this->flashSuccess('Depense enregistree avec succes.');
        $this->redirect('/expenses/' . $expenseId);
    }

    public function show(array $params = []): void
    {
        $this->requireManager();
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            $this->abort(404, 'Depense introuvable.');
        }

        $expense = $this->expenses->findByShop($id, $this->currentShopId());

        if ($expense === null) {
            $this->abort(404, 'Depense introuvable pour cette boutique.');
        }

        $this->view('expenses/show', compact('expense'));
    }

    private function payload(): array
    {
        return [
            'titre' => $_POST['titre'] ?? '',
            'description' => $_POST['description'] ?? null,
            'montant' => $_POST['montant'] ?? '',
            'categorie' => $_POST['categorie'] ?? 'autre',
            'date_depense' => $_POST['date_depense'] ?? null,
        ];
    }

    private function validateExpense(array $data): array
    {
        $validator = Validator::make($data)
            ->required('titre', 'Titre')
            ->maxLength('titre', 120, 'Titre')
            ->required('montant', 'Montant')
            ->numeric('montant', 'Montant');

        $errors = $validator->errors();

        if (!is_numeric($data['montant'] ?? null) || (float) $data['montant'] <= 0) {
            $errors['montant'][] = 'Le montant doit etre superieur a zero.';
        }

        $allowedCategories = ['transport', 'facture', 'loyer', 'salaire', 'perte_avarie', 'autre'];

        if (!in_array((string) ($data['categorie'] ?? ''), $allowedCategories, true)) {
            $errors['categorie'][] = 'Categorie invalide.';
        }

        return $errors;
    }

    private function requireManager(): void
    {
        $this->startSession();

        if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
            $this->redirect('/login');
        }

        $role = strtolower((string) ($_SESSION['user']['role'] ?? $_SESSION['user']['role_legacy'] ?? 'agent'));
        $roleId = (int) ($_SESSION['user']['role_id'] ?? 0);
        $allowed = in_array($role, ['admin', 'gerant', 'super_admin'], true) || in_array($roleId, [1, 2], true);

        if (!$allowed) {
            http_response_code(403);
            $this->flashError('Acces refuse: les depenses sont reservees a l administrateur ou au gerant.');
            $this->redirect('/pos');
        }
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
