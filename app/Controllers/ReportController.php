<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';

class ReportController extends AppController
{
    public function sales(array $params = []): void
    {
        $shopId = $this->currentShopId();
        $summary = $this->salesSummary($shopId);

        $this->renderReport('reports/sales', 'Rapport des ventes', [
            ['label' => 'Ventes du jour', 'value' => $this->money((float) $summary['today_total']), 'tone' => 'teal'],
            ['label' => 'Tickets validés', 'value' => (string) $summary['today_count'], 'tone' => 'blue'],
            ['label' => 'Crédit client', 'value' => $this->money((float) $summary['debt_total']), 'tone' => 'amber'],
        ], $this->monthlySalesBars($shopId));
    }

    public function stockMovements(array $params = []): void
    {
        $shopId = $this->currentShopId();
        $summary = $this->stockSummary($shopId);

        $this->renderReport('reports/stock-movements', 'Rapport de stock', [
            ['label' => 'Entrées', 'value' => (string) $summary['entries'], 'tone' => 'teal'],
            ['label' => 'Sorties', 'value' => (string) $summary['outputs'], 'tone' => 'blue'],
            ['label' => 'Alertes', 'value' => (string) $summary['alerts'], 'tone' => 'amber'],
        ], $this->monthlyMovementBars($shopId));
    }

    public function financials(array $params = []): void
    {
        $shopId = $this->currentShopId();
        $summary = $this->financialSummary($shopId);

        $this->renderReport('reports/financials', 'Rapport financier', [
            ['label' => 'Chiffre d’affaires', 'value' => $this->money((float) $summary['revenue']), 'tone' => 'teal'],
            ['label' => 'Dépenses', 'value' => $this->money((float) $summary['expenses']), 'tone' => 'amber'],
            ['label' => 'Bénéfice net', 'value' => $this->money((float) $summary['net_profit']), 'tone' => 'blue'],
        ], $this->monthlySalesBars($shopId));
    }

    public function backup(array $params = []): void
    {
        $this->json([
            'ok' => true,
            'message' => 'Déclencheur de sauvegarde prêt. Brancher ici le service de backup.',
            'requested_at' => date(DATE_ATOM),
        ]);
    }

    private function renderReport(string $view, string $title, array $cards, array $chartBars): void
    {
        $this->render($view, [
            'pageTitle' => $title,
            'activeMenu' => 'reports',
            'cards' => $cards,
            'chartBars' => $chartBars,
        ]);
    }

    private function salesSummary(int $shopId): array
    {
        $statement = Database::connection()->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN DATE(date_vente) = CURDATE() AND statut = 'validee' THEN total_montant ELSE 0 END), 0) AS today_total,
                COALESCE(SUM(CASE WHEN DATE(date_vente) = CURDATE() AND statut = 'validee' THEN 1 ELSE 0 END), 0) AS today_count,
                COALESCE(SUM(CASE WHEN statut = 'validee' THEN montant_dette ELSE 0 END), 0) AS debt_total
             FROM sales
             WHERE shop_id = :shop_id"
        );
        $statement->execute(['shop_id' => $shopId]);

        return $statement->fetch() ?: ['today_total' => 0, 'today_count' => 0, 'debt_total' => 0];
    }

    private function stockSummary(int $shopId): array
    {
        $movements = Database::connection()->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN type_mouvement = 'entree' THEN quantite ELSE 0 END), 0) AS entries,
                COALESCE(SUM(CASE WHEN type_mouvement = 'sortie' THEN quantite ELSE 0 END), 0) AS outputs
             FROM stock_movements
             WHERE shop_id = :shop_id"
        );
        $movements->execute(['shop_id' => $shopId]);
        $summary = $movements->fetch() ?: ['entries' => 0, 'outputs' => 0];

        $alerts = Database::connection()->prepare(
            'SELECT COUNT(*) FROM products WHERE shop_id = :shop_id AND actif = 1 AND quantite_stock <= alerte_stock_min'
        );
        $alerts->execute(['shop_id' => $shopId]);
        $summary['alerts'] = (int) $alerts->fetchColumn();

        return $summary;
    }

    private function financialSummary(int $shopId): array
    {
        $statement = Database::connection()->prepare(
            "SELECT
                COALESCE(SUM(sale_details.total_ligne), 0) AS revenue,
                COALESCE(SUM(sale_details.quantite * sale_details.prix_achat_unitaire), 0) AS cost
             FROM sale_details
             INNER JOIN sales ON sales.id = sale_details.sale_id
             WHERE sales.shop_id = :shop_id AND sales.statut = 'validee'"
        );
        $statement->execute(['shop_id' => $shopId]);
        $sales = $statement->fetch() ?: ['revenue' => 0, 'cost' => 0];

        $expenses = Database::connection()->prepare('SELECT COALESCE(SUM(montant), 0) FROM expenses WHERE shop_id = :shop_id');
        $expenses->execute(['shop_id' => $shopId]);
        $expenseTotal = (float) $expenses->fetchColumn();
        $grossMargin = (float) $sales['revenue'] - (float) $sales['cost'];

        return [
            'revenue' => (float) $sales['revenue'],
            'expenses' => $expenseTotal,
            'net_profit' => $grossMargin - $expenseTotal,
        ];
    }

    private function monthlySalesBars(int $shopId): array
    {
        $statement = Database::connection()->prepare(
            "SELECT MONTH(date_vente) AS month_number, COALESCE(SUM(total_montant), 0) AS total
             FROM sales
             WHERE shop_id = :shop_id AND statut = 'validee' AND YEAR(date_vente) = YEAR(CURDATE())
             GROUP BY MONTH(date_vente)"
        );
        $statement->execute(['shop_id' => $shopId]);

        return $this->barsFromRows($statement->fetchAll(), 'month_number', 'total');
    }

    private function monthlyMovementBars(int $shopId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT MONTH(date_mouvement) AS month_number, COALESCE(SUM(quantite), 0) AS total
             FROM stock_movements
             WHERE shop_id = :shop_id AND YEAR(date_mouvement) = YEAR(CURDATE())
             GROUP BY MONTH(date_mouvement)'
        );
        $statement->execute(['shop_id' => $shopId]);

        return $this->barsFromRows($statement->fetchAll(), 'month_number', 'total');
    }

    private function barsFromRows(array $rows, string $indexKey, string $valueKey): array
    {
        $values = array_fill(1, 12, 0.0);

        foreach ($rows as $row) {
            $values[(int) $row[$indexKey]] = (float) $row[$valueKey];
        }

        $max = max($values);

        if ($max <= 0) {
            return array_fill(0, 12, 8);
        }

        return array_map(static fn (float $value): int => max(8, (int) round(($value / $max) * 100)), array_values($values));
    }

    private function money(float $value): string
    {
        return number_format($value, 2, ',', ' ') . ' USD';
    }
}

