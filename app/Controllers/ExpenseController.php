<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';

class ExpenseController extends AppController
{
    public function index(array $params = []): void
    {
        $statement = Database::connection()->prepare(
            'SELECT expenses.titre, expenses.categorie, expenses.date_depense, expenses.montant, users.nom AS user
             FROM expenses
             INNER JOIN users ON users.id = expenses.user_id
             WHERE expenses.shop_id = :shop_id
             ORDER BY expenses.date_depense DESC, expenses.id DESC
             LIMIT 50'
        );
        $statement->execute(['shop_id' => $this->currentShopId()]);
        $expenses = $statement->fetchAll();

        $this->render('expenses/index', [
            'pageTitle' => 'Charges de la boutique',
            'activeMenu' => 'finances',
            'expenses' => $expenses,
        ]);
    }

    public function store(array $params = []): void
    {
        $title = trim((string) ($_POST['titre'] ?? ''));
        $amount = (float) ($_POST['montant'] ?? 0);
        $category = (string) ($_POST['categorie'] ?? 'autre');
        $date = trim((string) ($_POST['date_depense'] ?? ''));

        if ($title === '' || $amount <= 0) {
            $this->flashError('Le titre et un montant positif sont obligatoires.');
            $this->redirect('/expenses');
        }

        if (!in_array($category, ['transport', 'facture', 'loyer', 'salaire', 'perte_avarie', 'autre'], true)) {
            $category = 'autre';
        }

        try {
            $statement = Database::connection()->prepare(
                'INSERT INTO expenses (shop_id, user_id, titre, description, montant, categorie, date_depense)
                 VALUES (:shop_id, :user_id, :titre, :description, :montant, :categorie, :date_depense)'
            );
            $statement->execute([
                'shop_id' => $this->currentShopId(),
                'user_id' => $this->currentUserId(),
                'titre' => $title,
                'description' => $this->nullableString($_POST['description'] ?? null),
                'montant' => $amount,
                'categorie' => $category,
                'date_depense' => $date !== '' ? $date . ' 00:00:00' : date('Y-m-d H:i:s'),
            ]);

            $this->flashSuccess('Dépense enregistrée avec succès.');
        } catch (Throwable $exception) {
            $this->flashError('Impossible d’enregistrer la dépense: ' . $exception->getMessage());
        }

        $this->redirect('/expenses');
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

