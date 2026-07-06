<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';

class ReportController extends AppController
{
    public function sales(array $params = []): void
    {
        $shopId = $this->currentShopId();
        $filter = $this->salesReportFilter();
        $overview = $this->salesOverview($shopId, $filter);

        $this->render('reports/sales', [
            'pageTitle' => 'Rapport des ventes',
            'activeMenu' => 'reports',
            'reportFilter' => $filter,
            'overview' => $overview,
            'cards' => [
                [
                    'label' => 'Chiffre d affaires valide',
                    'value' => $this->money((float) $overview['validated_revenue']),
                    'detail' => (int) $overview['validated_count'] . ' ticket(s) valide(s)',
                    'tone' => 'teal',
                ],
                [
                    'label' => 'Montant encaisse',
                    'value' => $this->money((float) $overview['received_total']),
                    'detail' => 'Paiements deja recus',
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Credit client ouvert',
                    'value' => $this->money((float) $overview['debt_total']),
                    'detail' => 'Reste a encaisser',
                    'tone' => 'amber',
                ],
                [
                    'label' => 'Articles vendus',
                    'value' => (string) (int) $overview['items_sold'],
                    'detail' => 'Quantites sorties via POS',
                    'tone' => 'slate',
                ],
            ],
            'monthlySales' => $this->monthlySalesReport($shopId, $filter),
            'dailySales' => $this->dailySalesReport($shopId, $filter),
            'paymentBreakdown' => $this->paymentBreakdown($shopId, $filter),
            'topProducts' => $this->topProducts($shopId, $filter),
            'recentSales' => $this->recentSales($shopId, $filter),
        ]);
    }

    public function stockMovements(array $params = []): void
    {
        $shopId = $this->currentShopId();
        $summary = $this->stockSummary($shopId);

        $this->renderReport('reports/stock-movements', 'Rapport de stock', [
            ['label' => 'Entrees', 'value' => (string) $summary['entries'], 'tone' => 'teal'],
            ['label' => 'Sorties', 'value' => (string) $summary['outputs'], 'tone' => 'blue'],
            ['label' => 'Alertes', 'value' => (string) $summary['alerts'], 'tone' => 'amber'],
        ], $this->monthlyMovementBars($shopId));
    }

    public function financials(array $params = []): void
    {
        $shopId = $this->currentShopId();
        $summary = $this->financialSummary($shopId);

        $this->renderReport('reports/financials', 'Rapport financier', [
            ['label' => 'Chiffre d affaires', 'value' => $this->money((float) $summary['revenue']), 'tone' => 'teal'],
            ['label' => 'Depenses', 'value' => $this->money((float) $summary['expenses']), 'tone' => 'amber'],
            ['label' => 'Benefice net', 'value' => $this->money((float) $summary['net_profit']), 'tone' => 'blue'],
        ], $this->monthlySalesBars($shopId));
    }

    public function backup(array $params = []): void
    {
        $this->json([
            'ok' => true,
            'success' => true,
            'message' => 'Declencheur de sauvegarde pret. Brancher ici le service de backup.',
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

    private function salesReportFilter(): array
    {
        $period = (string) ($_GET['period'] ?? 'current_month');
        $allowed = ['current_month', 'today', 'last_7_days', 'last_30_days', 'current_year', 'all'];

        if (!in_array($period, $allowed, true)) {
            $period = 'current_month';
        }

        $today = new DateTimeImmutable('today');

        return match ($period) {
            'today' => [
                'period' => $period,
                'label' => 'Aujourd hui',
                'start' => $today->format('Y-m-d 00:00:00'),
                'end' => $today->modify('+1 day')->format('Y-m-d 00:00:00'),
            ],
            'last_7_days' => [
                'period' => $period,
                'label' => '7 derniers jours',
                'start' => $today->modify('-6 days')->format('Y-m-d 00:00:00'),
                'end' => $today->modify('+1 day')->format('Y-m-d 00:00:00'),
            ],
            'last_30_days' => [
                'period' => $period,
                'label' => '30 derniers jours',
                'start' => $today->modify('-29 days')->format('Y-m-d 00:00:00'),
                'end' => $today->modify('+1 day')->format('Y-m-d 00:00:00'),
            ],
            'current_year' => [
                'period' => $period,
                'label' => 'Annee en cours',
                'start' => $today->format('Y-01-01 00:00:00'),
                'end' => $today->modify('+1 year')->format('Y-01-01 00:00:00'),
            ],
            'all' => [
                'period' => $period,
                'label' => 'Toutes les ventes',
                'start' => null,
                'end' => null,
            ],
            default => [
                'period' => 'current_month',
                'label' => 'Mois en cours',
                'start' => $today->format('Y-m-01 00:00:00'),
                'end' => $today->modify('first day of next month')->format('Y-m-d 00:00:00'),
            ],
        };
    }

    private function salesDateCondition(array $filter, string $column = 'sales.date_vente'): string
    {
        if (($filter['start'] ?? null) === null || ($filter['end'] ?? null) === null) {
            return '';
        }

        return " AND {$column} >= :date_start AND {$column} < :date_end";
    }

    private function salesDateParams(array $filter): array
    {
        if (($filter['start'] ?? null) === null || ($filter['end'] ?? null) === null) {
            return [];
        }

        return [
            'date_start' => (string) $filter['start'],
            'date_end' => (string) $filter['end'],
        ];
    }

    private function salesOverview(int $shopId, array $filter): array
    {
        $dateCondition = $this->salesDateCondition($filter, 'date_vente');
        $statement = Database::connection()->prepare(
            "SELECT
                COUNT(*) AS total_count,
                COALESCE(SUM(CASE WHEN statut = 'validee' THEN 1 ELSE 0 END), 0) AS validated_count,
                COALESCE(SUM(CASE WHEN statut = 'annulee' THEN 1 ELSE 0 END), 0) AS cancelled_count,
                COALESCE(SUM(CASE WHEN statut = 'validee' THEN total_montant ELSE 0 END), 0) AS validated_revenue,
                COALESCE(SUM(CASE WHEN statut = 'validee' THEN montant_recu ELSE 0 END), 0) AS received_total,
                COALESCE(SUM(CASE WHEN statut = 'validee' THEN montant_dette ELSE 0 END), 0) AS debt_total,
                COALESCE(AVG(CASE WHEN statut = 'validee' THEN total_montant ELSE NULL END), 0) AS average_ticket,
                COUNT(DISTINCT CASE WHEN statut = 'validee' THEN customer_id ELSE NULL END) AS customers_count,
                MIN(CASE WHEN statut = 'validee' THEN date_vente ELSE NULL END) AS first_sale_at,
                MAX(CASE WHEN statut = 'validee' THEN date_vente ELSE NULL END) AS last_sale_at
             FROM sales
             WHERE shop_id = :shop_id{$dateCondition}"
        );
        $statement->execute(array_merge(['shop_id' => $shopId], $this->salesDateParams($filter)));
        $overview = $statement->fetch() ?: [];

        $itemDateCondition = $this->salesDateCondition($filter);
        $items = Database::connection()->prepare(
            "SELECT COALESCE(SUM(sale_details.quantite), 0)
             FROM sale_details
             INNER JOIN sales ON sales.id = sale_details.sale_id
             WHERE sales.shop_id = :shop_id AND sales.statut = 'validee'{$itemDateCondition}"
        );
        $items->execute(array_merge(['shop_id' => $shopId], $this->salesDateParams($filter)));
        $overview['items_sold'] = (int) $items->fetchColumn();

        $period = $this->currentAndPreviousMonthSales($shopId);

        return array_merge([
            'total_count' => 0,
            'validated_count' => 0,
            'cancelled_count' => 0,
            'validated_revenue' => 0,
            'received_total' => 0,
            'debt_total' => 0,
            'average_ticket' => 0,
            'customers_count' => 0,
            'first_sale_at' => null,
            'last_sale_at' => null,
            'items_sold' => 0,
            'current_month_revenue' => 0,
            'previous_month_revenue' => 0,
            'month_delta_percent' => 0,
        ], $overview, $period);
    }

    private function currentAndPreviousMonthSales(int $shopId): array
    {
        $statement = Database::connection()->prepare(
            "SELECT
                COALESCE(SUM(CASE
                    WHEN date_vente >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                    THEN total_montant ELSE 0 END), 0) AS current_month_revenue,
                COALESCE(SUM(CASE
                    WHEN date_vente >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
                     AND date_vente < DATE_FORMAT(CURDATE(), '%Y-%m-01')
                    THEN total_montant ELSE 0 END), 0) AS previous_month_revenue
             FROM sales
             WHERE shop_id = :shop_id AND statut = 'validee'"
        );
        $statement->execute(['shop_id' => $shopId]);
        $row = $statement->fetch() ?: ['current_month_revenue' => 0, 'previous_month_revenue' => 0];

        $current = (float) $row['current_month_revenue'];
        $previous = (float) $row['previous_month_revenue'];
        $delta = $previous > 0 ? (($current - $previous) / $previous) * 100 : ($current > 0 ? 100 : 0);

        return [
            'current_month_revenue' => $current,
            'previous_month_revenue' => $previous,
            'month_delta_percent' => $delta,
        ];
    }

    private function monthlySalesReport(int $shopId, array $filter): array
    {
        $dateCondition = $this->salesDateCondition($filter);
        $statement = Database::connection()->prepare(
            "SELECT
                MONTH(date_vente) AS month_number,
                COALESCE(SUM(total_montant), 0) AS revenue,
                COALESCE(SUM(montant_recu), 0) AS received,
                COALESCE(SUM(montant_dette), 0) AS debt,
                COUNT(*) AS tickets
             FROM sales
             WHERE shop_id = :shop_id
               AND statut = 'validee'
               {$dateCondition}
             GROUP BY MONTH(date_vente)
             ORDER BY month_number"
        );
        $statement->execute(array_merge(['shop_id' => $shopId], $this->salesDateParams($filter)));
        $rowsByMonth = [];

        foreach ($statement->fetchAll() as $row) {
            $rowsByMonth[(int) $row['month_number']] = $row;
        }

        $months = ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aout', 'Sep', 'Oct', 'Nov', 'Dec'];
        $max = 0.0;
        $report = [];

        for ($month = 1; $month <= 12; $month++) {
            $row = $rowsByMonth[$month] ?? ['revenue' => 0, 'received' => 0, 'debt' => 0, 'tickets' => 0];
            $revenue = (float) $row['revenue'];
            $max = max($max, $revenue);
            $report[] = [
                'label' => $months[$month - 1],
                'revenue' => $revenue,
                'received' => (float) $row['received'],
                'debt' => (float) $row['debt'],
                'tickets' => (int) $row['tickets'],
                'height' => 8,
            ];
        }

        foreach ($report as &$row) {
            $row['height'] = $max > 0 ? max(8, (int) round(((float) $row['revenue'] / $max) * 100)) : 8;
        }
        unset($row);

        return $report;
    }

    private function dailySalesReport(int $shopId, array $filter): array
    {
        $start = ($filter['start'] ?? null) !== null ? new DateTimeImmutable((string) $filter['start']) : new DateTimeImmutable('-13 days');
        $end = ($filter['end'] ?? null) !== null ? new DateTimeImmutable((string) $filter['end']) : new DateTimeImmutable('tomorrow');
        $diffDays = max(1, (int) $start->diff($end)->format('%a'));

        if ($diffDays > 31) {
            $start = $end->modify('-30 days');
        }

        $statement = Database::connection()->prepare(
            "SELECT DATE(date_vente) AS sale_day,
                    COALESCE(SUM(total_montant), 0) AS revenue,
                    COUNT(*) AS tickets
             FROM sales
             WHERE shop_id = :shop_id
               AND statut = 'validee'
               AND date_vente >= :date_start
               AND date_vente < :date_end
             GROUP BY DATE(date_vente)
             ORDER BY sale_day"
        );
        $statement->execute([
            'shop_id' => $shopId,
            'date_start' => $start->format('Y-m-d 00:00:00'),
            'date_end' => $end->format('Y-m-d 00:00:00'),
        ]);
        $rowsByDay = [];

        foreach ($statement->fetchAll() as $row) {
            $rowsByDay[(string) $row['sale_day']] = $row;
        }

        $report = [];
        $max = 0.0;

        $cursor = $start;

        while ($cursor < $end) {
            $day = $cursor->format('Y-m-d');
            $row = $rowsByDay[$day] ?? ['revenue' => 0, 'tickets' => 0];
            $revenue = (float) $row['revenue'];
            $max = max($max, $revenue);
            $report[] = [
                'label' => date('d/m', strtotime($day)),
                'revenue' => $revenue,
                'tickets' => (int) $row['tickets'],
                'height' => 8,
            ];
            $cursor = $cursor->modify('+1 day');
        }

        foreach ($report as &$row) {
            $row['height'] = $max > 0 ? max(8, (int) round(((float) $row['revenue'] / $max) * 100)) : 8;
        }
        unset($row);

        return $report;
    }

    private function paymentBreakdown(int $shopId, array $filter): array
    {
        $dateCondition = $this->salesDateCondition($filter);
        $statement = Database::connection()->prepare(
            "SELECT mode_paiement,
                    COUNT(*) AS tickets,
                    COALESCE(SUM(total_montant), 0) AS revenue,
                    COALESCE(SUM(montant_recu), 0) AS received,
                    COALESCE(SUM(montant_dette), 0) AS debt
             FROM sales
             WHERE shop_id = :shop_id AND statut = 'validee'{$dateCondition}
             GROUP BY mode_paiement
             ORDER BY revenue DESC"
        );
        $statement->execute(array_merge(['shop_id' => $shopId], $this->salesDateParams($filter)));

        return $statement->fetchAll();
    }

    private function topProducts(int $shopId, array $filter): array
    {
        $dateCondition = $this->salesDateCondition($filter);
        $statement = Database::connection()->prepare(
            "SELECT
                products.nom AS product_name,
                products.ref AS product_ref,
                COALESCE(SUM(sale_details.quantite), 0) AS quantity,
                COALESCE(SUM(sale_details.total_ligne), 0) AS revenue,
                COALESCE(SUM(sale_details.quantite * (sale_details.prix_unitaire_vendu - sale_details.prix_achat_unitaire)), 0) AS margin
             FROM sale_details
             INNER JOIN sales ON sales.id = sale_details.sale_id
             INNER JOIN products ON products.id = sale_details.product_id
             WHERE sales.shop_id = :shop_id AND sales.statut = 'validee'{$dateCondition}
             GROUP BY products.id, products.nom, products.ref
             ORDER BY revenue DESC, quantity DESC
             LIMIT 8"
        );
        $statement->execute(array_merge(['shop_id' => $shopId], $this->salesDateParams($filter)));

        return $statement->fetchAll();
    }

    private function recentSales(int $shopId, array $filter): array
    {
        $dateCondition = $this->salesDateCondition($filter);
        $statement = Database::connection()->prepare(
            "SELECT
                sales.id,
                sales.numero_facture,
                sales.date_vente,
                sales.total_montant,
                sales.montant_recu,
                sales.montant_dette,
                sales.mode_paiement,
                sales.statut,
                customers.nom AS customer_name,
                users.nom AS user_name,
                COUNT(sale_details.id) AS lines_count,
                COALESCE(SUM(sale_details.quantite), 0) AS items_count
             FROM sales
             LEFT JOIN customers ON customers.id = sales.customer_id
             INNER JOIN users ON users.id = sales.user_id
             LEFT JOIN sale_details ON sale_details.sale_id = sales.id
             WHERE sales.shop_id = :shop_id{$dateCondition}
             GROUP BY sales.id
             ORDER BY sales.date_vente DESC, sales.id DESC
             LIMIT 12"
        );
        $statement->execute(array_merge(['shop_id' => $shopId], $this->salesDateParams($filter)));

        return $statement->fetchAll();
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
