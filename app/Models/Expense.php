<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/Model.php';

final class Expense extends Model
{
    public function allByShop(int $shopId, array $filters = []): array
    {
        $conditions = ['expenses.shop_id = :shop_id'];
        $params = ['shop_id' => $shopId];
        $status = (string) ($filters['status'] ?? '');

        if (in_array($status, ['active', 'cancelled'], true)) {
            $conditions[] = 'expenses.statut = :status';
            $params['status'] = $status;
        }

        $category = (string) ($filters['category'] ?? '');

        if (in_array($category, $this->categories(), true)) {
            $conditions[] = 'expenses.categorie = :category';
            $params['category'] = $category;
        }

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $conditions[] = '(expenses.titre LIKE :search OR expenses.description LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $dateStart = $this->dateFilter($filters['date_debut'] ?? null, false);

        if ($dateStart !== null) {
            $conditions[] = 'expenses.date_depense >= :date_start';
            $params['date_start'] = $dateStart;
        }

        $dateEnd = $this->dateFilter($filters['date_fin'] ?? null, true);

        if ($dateEnd !== null) {
            $conditions[] = 'expenses.date_depense <= :date_end';
            $params['date_end'] = $dateEnd;
        }

        $statement = Database::connection()->prepare(
            'SELECT expenses.*, users.nom AS user_name
             FROM expenses
             INNER JOIN users ON users.id = expenses.user_id
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY expenses.date_depense DESC, expenses.id DESC'
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function findByShop(int $id, int $shopId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT expenses.*, users.nom AS user_name
             FROM expenses
             INNER JOIN users ON users.id = expenses.user_id
             WHERE expenses.id = :id AND expenses.shop_id = :shop_id
             LIMIT 1'
        );
        $statement->execute([
            'id' => $id,
            'shop_id' => $shopId,
        ]);

        $expense = $statement->fetch();

        return is_array($expense) ? $expense : null;
    }

    public function availableProfitByShop(int $shopId): float
    {
        $summary = $this->profitSummaryByShop($shopId);

        return max(0.0, round((float) $summary['available_profit'], 2));
    }

    public function availableProfitForUpdate(int $shopId, int $expenseId): float
    {
        $summary = $this->profitSummaryByShop($shopId, $expenseId);

        return max(0.0, round((float) $summary['available_profit'], 2));
    }

    public function create(array $data, int $shopId, int $userId): int
    {
        $amount = round((float) $data['montant'], 2);
        $availableProfit = $this->availableProfitByShop($shopId);

        if ($amount <= 0) {
            throw new InvalidArgumentException('Le montant doit etre superieur a zero.');
        }

        if ($amount > $availableProfit) {
            throw new InvalidArgumentException(
                'Le montant de la depense ne peut pas depasser le solde du benefice disponible: '
                . number_format($availableProfit, 2, ',', ' ')
                . ' USD.'
            );
        }

        $statement = Database::connection()->prepare(
            'INSERT INTO expenses (
                shop_id, user_id, titre, description, montant, montant_saisi, devise_saisie, taux_change_saisie, categorie, date_depense
             ) VALUES (
                :shop_id, :user_id, :titre, :description, :montant, :montant_saisi, :devise_saisie, :taux_change_saisie, :categorie, :date_depense
             )'
        );

        $statement->execute([
            'shop_id' => $shopId,
            'user_id' => $userId,
            'titre' => trim((string) $data['titre']),
            'description' => $this->nullableString($data['description'] ?? null),
            'montant' => $amount,
            'montant_saisi' => round((float) ($data['montant_saisi'] ?? $amount), 2),
            'devise_saisie' => $this->validCurrency((string) ($data['devise_saisie'] ?? 'USD')),
            'taux_change_saisie' => max(0.0001, (float) ($data['taux_change_saisie'] ?? 2800)),
            'categorie' => $this->validCategory((string) ($data['categorie'] ?? 'autre')),
            'date_depense' => $data['date_depense'] ?: date('Y-m-d H:i:s'),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $id, int $shopId, array $data): bool
    {
        $expense = $this->findByShop($id, $shopId);

        if ($expense === null) {
            throw new InvalidArgumentException('Depense introuvable.');
        }

        if (($expense['statut'] ?? 'active') !== 'active') {
            throw new InvalidArgumentException('Une depense annulee ne peut pas etre modifiee.');
        }

        $amount = round((float) $data['montant'], 2);
        $availableProfit = $this->availableProfitForUpdate($shopId, $id);

        if ($amount <= 0) {
            throw new InvalidArgumentException('Le montant doit etre superieur a zero.');
        }

        if ($amount > $availableProfit) {
            throw new InvalidArgumentException(
                'Le montant de la depense ne peut pas depasser le solde du benefice disponible: '
                . number_format($availableProfit, 2, ',', ' ')
                . ' USD.'
            );
        }

        $statement = Database::connection()->prepare(
            'UPDATE expenses
             SET titre = :titre,
                 description = :description,
                 montant = :montant,
                 montant_saisi = :montant_saisi,
                 devise_saisie = :devise_saisie,
                 taux_change_saisie = :taux_change_saisie,
                 categorie = :categorie
             WHERE id = :id
               AND shop_id = :shop_id
               AND statut = "active"'
        );
        $statement->execute([
            'id' => $id,
            'shop_id' => $shopId,
            'titre' => trim((string) $data['titre']),
            'description' => $this->nullableString($data['description'] ?? null),
            'montant' => $amount,
            'montant_saisi' => round((float) ($data['montant_saisi'] ?? $amount), 2),
            'devise_saisie' => $this->validCurrency((string) ($data['devise_saisie'] ?? 'USD')),
            'taux_change_saisie' => max(0.0001, (float) ($data['taux_change_saisie'] ?? 2800)),
            'categorie' => $this->validCategory((string) ($data['categorie'] ?? 'autre')),
        ]);

        return $statement->rowCount() > 0;
    }

    public function cancel(int $id, int $shopId, int $userId, ?string $reason = null): bool
    {
        $expense = $this->findByShop($id, $shopId);

        if ($expense === null) {
            throw new InvalidArgumentException('Depense introuvable.');
        }

        if (($expense['statut'] ?? 'active') !== 'active') {
            throw new InvalidArgumentException('Cette depense est deja annulee.');
        }

        $statement = Database::connection()->prepare(
            'UPDATE expenses
             SET statut = "cancelled",
                 cancellation_reason = :reason,
                 cancelled_by = :cancelled_by,
                 cancelled_at = NOW()
             WHERE id = :id
               AND shop_id = :shop_id
               AND statut = "active"'
        );
        $statement->execute([
            'id' => $id,
            'shop_id' => $shopId,
            'cancelled_by' => $userId,
            'reason' => $this->nullableString($reason),
        ]);

        return $statement->rowCount() > 0;
    }

    public function categories(): array
    {
        return ['transport', 'facture', 'loyer', 'salaire', 'perte_avarie', 'frais_operateur', 'connexion_internet', 'communication', 'maintenance_terminal', 'electricite', 'autre'];
    }

    private function profitSummaryByShop(int $shopId, ?int $excludedExpenseId = null): array
    {
        $shopStatement = Database::connection()->prepare(
            'SELECT categories.slug AS category_slug, shops.taux_change_cdf
             FROM shops
             LEFT JOIN shop_categories categories ON categories.id = shops.category_id
             WHERE shops.id = :shop_id LIMIT 1'
        );
        $shopStatement->execute(['shop_id' => $shopId]);
        $shop = $shopStatement->fetch() ?: [];

        if (($shop['category_slug'] ?? '') === 'vendeur-forfait-mobile-unites') {
            $mobileSales = Database::connection()->prepare(
                'SELECT COALESCE(SUM(benefice), 0) FROM mobile_sales WHERE shop_id = :shop_id'
            );
            $mobileSales->execute(['shop_id' => $shopId]);
            $rate = max((float) (($shop['taux_change_cdf'] ?? 2800) ?: 2800), 0.0001);
            $grossMargin = (float) $mobileSales->fetchColumn() / $rate;
        } else {
        $sales = Database::connection()->prepare(
            "SELECT
                COALESCE(SUM(sales.total_montant), 0) AS revenue,
                COALESCE(SUM(sale_costs.cost), 0) AS cost
             FROM sales
             LEFT JOIN (
                SELECT sale_id, SUM(quantite * prix_achat_unitaire) AS cost
                FROM sale_details
                GROUP BY sale_id
             ) sale_costs ON sale_costs.sale_id = sales.id
             WHERE sales.shop_id = :shop_id AND sales.statut = 'validee'"
        );
        $sales->execute(['shop_id' => $shopId]);
        $salesSummary = $sales->fetch() ?: ['revenue' => 0, 'cost' => 0];
        $grossMargin = (float) $salesSummary['revenue'] - (float) $salesSummary['cost'];
        }

        $expenseSql = 'SELECT COALESCE(SUM(montant), 0) FROM expenses WHERE shop_id = :shop_id AND statut = "active"';
        $expenseParams = ['shop_id' => $shopId];

        if ($excludedExpenseId !== null && $excludedExpenseId > 0) {
            $expenseSql .= ' AND id <> :excluded_expense_id';
            $expenseParams['excluded_expense_id'] = $excludedExpenseId;
        }

        $expenses = Database::connection()->prepare($expenseSql);
        $expenses->execute($expenseParams);
        $expenseTotal = (float) $expenses->fetchColumn();
        return [
            'gross_margin' => $grossMargin,
            'expenses' => $expenseTotal,
            'available_profit' => $grossMargin - $expenseTotal,
        ];
    }

    private function validCategory(string $category): string
    {
        $category = strtolower(trim($category));
        $allowed = $this->categories();

        return in_array($category, $allowed, true) ? $category : 'autre';
    }

    private function validCurrency(string $currency): string
    {
        return in_array($currency, ['USD', 'CDF'], true) ? $currency : 'USD';
    }

    private function dateFilter(mixed $value, bool $endOfDay): ?string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);

        if (!$date instanceof DateTimeImmutable) {
            return null;
        }

        return $date->format('Y-m-d') . ($endOfDay ? ' 23:59:59' : ' 00:00:00');
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
