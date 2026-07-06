<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';

class ReportController extends AppController
{
    public function sales(array $params = []): void
    {
        $shops = $this->shops();
        $currentUser = $this->currentUser();
        $activeShop = $this->activeShop($shops, $currentUser);
        $shopId = (int) ($activeShop['id'] ?? 1);
        $filter = $this->salesReportFilter();
        $report = $this->salesReportData($shopId, $filter, $activeShop);
        $preview = strtolower((string) ($_GET['export_preview'] ?? ''));
        $export = strtolower((string) ($_GET['export'] ?? ''));
        $confirmed = (string) ($_GET['confirm'] ?? '') === '1';

        if (in_array($preview, ['pdf', 'xlsx'], true)) {
            $this->render('reports/export-preview', [
                'pageTitle' => 'Prévisualisation export',
                'activeMenu' => 'reports',
                'exportFormat' => $preview,
                'currentUser' => $currentUser,
                'shops' => $shops,
                'activeShop' => $activeShop,
            ] + $report);

            return;
        }

        if ($export === 'xlsx') {
            if (!$confirmed) {
                $_GET['export_preview'] = 'xlsx';
                $this->render('reports/export-preview', [
                    'pageTitle' => 'Prévisualisation export',
                    'activeMenu' => 'reports',
                    'exportFormat' => 'xlsx',
                    'currentUser' => $currentUser,
                    'shops' => $shops,
                    'activeShop' => $activeShop,
                ] + $report);

                return;
            }

            $this->exportSalesReportXlsx($report);
        }

        if ($export === 'pdf') {
            if (!$confirmed) {
                $_GET['export_preview'] = 'pdf';
                $this->render('reports/export-preview', [
                    'pageTitle' => 'Prévisualisation export',
                    'activeMenu' => 'reports',
                    'exportFormat' => 'pdf',
                    'currentUser' => $currentUser,
                    'shops' => $shops,
                    'activeShop' => $activeShop,
                ] + $report);

                return;
            }

            $this->exportSalesReportPdf($report);
        }

        $this->render('reports/sales', [
            'pageTitle' => 'Rapport des ventes',
            'activeMenu' => 'reports',
            'reportFilter' => $filter,
        ] + $report);
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

    private function salesReportData(int $shopId, array $filter, array $activeShop = []): array
    {
        $overview = $this->salesOverview($shopId, $filter);

        return [
            'activeShop' => $activeShop,
            'reportFilter' => $filter,
            'periodDisplay' => $this->salesPeriodDisplay($filter),
            'overview' => $overview,
            'cards' => [
                [
                    'label' => 'Chiffre d’affaires validé',
                    'value' => $this->money((float) $overview['validated_revenue']),
                    'detail' => (int) $overview['validated_count'] . ' ticket(s) valide(s)',
                    'tone' => 'teal',
                ],
                [
                    'label' => 'Montant encaissé',
                    'value' => $this->money((float) $overview['received_total']),
                    'detail' => 'Paiements déjà reçus',
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Crédit client ouvert',
                    'value' => $this->money((float) $overview['debt_total']),
                    'detail' => 'Reste à encaisser',
                    'tone' => 'amber',
                ],
                [
                    'label' => 'Articles vendus',
                    'value' => (string) (int) $overview['items_sold'],
                    'detail' => 'Quantités sorties via POS',
                    'tone' => 'slate',
                ],
            ],
            'monthlySales' => $this->monthlySalesReport($shopId, $filter),
            'dailySales' => $this->dailySalesReport($shopId, $filter),
            'paymentBreakdown' => $this->paymentBreakdown($shopId, $filter),
            'topProducts' => $this->topProducts($shopId, $filter),
            'recentSales' => $this->recentSales($shopId, $filter),
        ];
    }

    private function salesReportFilter(): array
    {
        $period = (string) ($_GET['period'] ?? 'current_month');
        $allowed = ['current_month', 'today', 'last_7_days', 'last_30_days', 'current_year', 'custom', 'all'];

        if (!in_array($period, $allowed, true)) {
            $period = 'current_month';
        }

        $today = new DateTimeImmutable('today');

        if ($period === 'custom') {
            $startInput = (string) ($_GET['date_debut'] ?? '');
            $endInput = (string) ($_GET['date_fin'] ?? '');
            $start = DateTimeImmutable::createFromFormat('!Y-m-d', $startInput);
            $end = DateTimeImmutable::createFromFormat('!Y-m-d', $endInput);

            if ($start instanceof DateTimeImmutable && $end instanceof DateTimeImmutable) {
                if ($start > $end) {
                    [$start, $end] = [$end, $start];
                }

                return [
                    'period' => 'custom',
                    'label' => 'Période personnalisée',
                    'start' => $start->format('Y-m-d 00:00:00'),
                    'end' => $end->modify('+1 day')->format('Y-m-d 00:00:00'),
                    'date_debut' => $start->format('Y-m-d'),
                    'date_fin' => $end->format('Y-m-d'),
                ];
            }

            $period = 'current_month';
        }

        return match ($period) {
            'today' => [
                'period' => $period,
                'label' => 'Aujourd’hui',
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
                'label' => 'Année en cours',
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

    private function salesPeriodDisplay(array $filter): string
    {
        if (($filter['start'] ?? null) === null || ($filter['end'] ?? null) === null) {
            return 'Toutes les ventes disponibles';
        }

        $start = new DateTimeImmutable((string) $filter['start']);
        $end = (new DateTimeImmutable((string) $filter['end']))->modify('-1 second');

        return 'Vente du ' . $start->format('d/m/Y') . ' au ' . $end->format('d/m/Y');
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

    private function exportSalesReportXlsx(array $report): never
    {
        if (!class_exists(ZipArchive::class)) {
            $this->abort(500, 'Extension PHP Zip indisponible pour générer le fichier Excel.');
        }

        $activeShop = is_array($report['activeShop'] ?? null) ? $report['activeShop'] : [];
        $filter = is_array($report['reportFilter'] ?? null) ? $report['reportFilter'] : [];
        $overview = is_array($report['overview'] ?? null) ? $report['overview'] : [];
        $periodDisplay = (string) ($report['periodDisplay'] ?? $this->salesPeriodDisplay($filter));

        $sheets = [
            'Synthèse' => [
                ['Rapport des ventes', ''],
                ['Boutique', (string) ($activeShop['nom'] ?? 'Boutique active')],
                ['Adresse', (string) ($activeShop['adresse'] ?? '')],
                ['Téléphone', (string) ($activeShop['telephone'] ?? '')],
                ['Période', $periodDisplay],
                ['Généré le', date('d/m/Y H:i')],
                ['', ''],
                ['Tickets valides', (int) ($overview['validated_count'] ?? 0)],
                ['Chiffre d’affaires', (float) ($overview['validated_revenue'] ?? 0)],
                ['Montant encaissé', (float) ($overview['received_total'] ?? 0)],
                ['Crédit client', (float) ($overview['debt_total'] ?? 0)],
                ['Articles vendus', (int) ($overview['items_sold'] ?? 0)],
                ['Ticket moyen', (float) ($overview['average_ticket'] ?? 0)],
            ],
            'Tickets' => array_merge(
                [['Facture', 'Date', 'Client', 'Caissier', 'Total', 'Reçu', 'Crédit', 'Paiement', 'Statut']],
                array_map(static fn (array $sale): array => [
                    (string) ($sale['numero_facture'] ?? ''),
                    (string) ($sale['date_vente'] ?? ''),
                    (string) ($sale['customer_name'] ?? 'Client comptant'),
                    (string) ($sale['user_name'] ?? ''),
                    (float) ($sale['total_montant'] ?? 0),
                    (float) ($sale['montant_recu'] ?? 0),
                    (float) ($sale['montant_dette'] ?? 0),
                    (string) ($sale['mode_paiement'] ?? ''),
                    (string) ($sale['statut'] ?? ''),
                ], is_array($report['recentSales'] ?? null) ? $report['recentSales'] : [])
            ),
            'Produits' => array_merge(
                [['Produit', 'Référence', 'Quantité', 'Chiffre d’affaires', 'Marge']],
                array_map(static fn (array $product): array => [
                    (string) ($product['product_name'] ?? ''),
                    (string) ($product['product_ref'] ?? ''),
                    (float) ($product['quantity'] ?? 0),
                    (float) ($product['revenue'] ?? 0),
                    (float) ($product['margin'] ?? 0),
                ], is_array($report['topProducts'] ?? null) ? $report['topProducts'] : [])
            ),
            'Paiements' => array_merge(
                [['Mode', 'Tickets', 'Total', 'Reçu', 'Crédit']],
                array_map(static fn (array $payment): array => [
                    (string) ($payment['mode_paiement'] ?? 'cash'),
                    (int) ($payment['tickets'] ?? 0),
                    (float) ($payment['revenue'] ?? 0),
                    (float) ($payment['received'] ?? 0),
                    (float) ($payment['debt'] ?? 0),
                ], is_array($report['paymentBreakdown'] ?? null) ? $report['paymentBreakdown'] : [])
            ),
            'Journalier' => array_merge(
                [['Jour', 'Chiffre d’affaires', 'Tickets']],
                array_map(static fn (array $day): array => [
                    (string) ($day['label'] ?? ''),
                    (float) ($day['revenue'] ?? 0),
                    (int) ($day['tickets'] ?? 0),
                ], is_array($report['dailySales'] ?? null) ? $report['dailySales'] : [])
            ),
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'madukaone_xlsx_');
        if ($tmp === false) {
            $this->abort(500, 'Impossible de préparer le fichier Excel.');
        }

        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            $this->abort(500, 'Impossible de créer le fichier Excel.');
        }

        $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypes(count($sheets)));
        $zip->addFromString('_rels/.rels', $this->xlsxRootRels());
        $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbook(array_keys($sheets)));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRels(count($sheets)));
        $zip->addFromString('xl/styles.xml', $this->xlsxStyles());

        $index = 1;
        foreach ($sheets as $rows) {
            $zip->addFromString('xl/worksheets/sheet' . $index . '.xml', $this->xlsxSheet($rows));
            $index++;
        }

        $zip->close();

        $filename = 'rapport-ventes-' . date('Ymd-His') . '.xlsx';
        $this->downloadFile($tmp, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    private function exportSalesReportPdf(array $report): never
    {
        $activeShop = is_array($report['activeShop'] ?? null) ? $report['activeShop'] : [];
        $filter = is_array($report['reportFilter'] ?? null) ? $report['reportFilter'] : [];
        $overview = is_array($report['overview'] ?? null) ? $report['overview'] : [];
        $periodDisplay = (string) ($report['periodDisplay'] ?? $this->salesPeriodDisplay($filter));
        $lines = [
            'Rapport professionnel des ventes',
            'MadukaOne Commerce ERP',
            '',
            'Boutique active',
            'Nom: ' . (string) ($activeShop['nom'] ?? 'Boutique active'),
            'Adresse: ' . (string) ($activeShop['adresse'] ?? '-'),
            'Téléphone: ' . (string) ($activeShop['telephone'] ?? '-'),
            '',
            'Informations du rapport',
            'Période: ' . $periodDisplay,
            'Généré le: ' . date('d/m/Y H:i'),
            '',
            'Synthèse',
            'Tickets valides: ' . (int) ($overview['validated_count'] ?? 0),
            'Chiffre d’affaires: ' . $this->money((float) ($overview['validated_revenue'] ?? 0)),
            'Montant encaissé: ' . $this->money((float) ($overview['received_total'] ?? 0)),
            'Crédit client: ' . $this->money((float) ($overview['debt_total'] ?? 0)),
            'Articles vendus: ' . (int) ($overview['items_sold'] ?? 0),
            'Ticket moyen: ' . $this->money((float) ($overview['average_ticket'] ?? 0)),
            '',
            'Top produits',
        ];

        foreach (array_slice(is_array($report['topProducts'] ?? null) ? $report['topProducts'] : [], 0, 8) as $product) {
            $lines[] = '- ' . (string) ($product['product_name'] ?? 'Produit') . ' | Qté: ' . (float) ($product['quantity'] ?? 0) . ' | CA: ' . $this->money((float) ($product['revenue'] ?? 0));
        }

        $lines[] = '';
        $lines[] = 'Paiements par mode';
        foreach ((is_array($report['paymentBreakdown'] ?? null) ? $report['paymentBreakdown'] : []) as $payment) {
            $lines[] = '- ' . (string) ($payment['mode_paiement'] ?? 'cash') . ' | Tickets: ' . (int) ($payment['tickets'] ?? 0) . ' | Total: ' . $this->money((float) ($payment['revenue'] ?? 0)) . ' | Crédit: ' . $this->money((float) ($payment['debt'] ?? 0));
        }

        $lines[] = '';
        $lines[] = 'Derniers tickets';
        foreach (array_slice(is_array($report['recentSales'] ?? null) ? $report['recentSales'] : [], 0, 12) as $sale) {
            $lines[] = (string) ($sale['numero_facture'] ?? '-') . ' | ' . (string) ($sale['date_vente'] ?? '') . ' | ' . $this->money((float) ($sale['total_montant'] ?? 0)) . ' | ' . (string) ($sale['statut'] ?? '');
        }

        $pdf = $this->professionalSalesReportPdf($report, $lines);
        $filename = 'rapport-ventes-' . date('Ymd-His') . '.pdf';

        header_remove('Content-Type');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    private function professionalSalesReportPdf(array $report, array $fallbackLines = []): string
    {
        $activeShop = is_array($report['activeShop'] ?? null) ? $report['activeShop'] : [];
        $filter = is_array($report['reportFilter'] ?? null) ? $report['reportFilter'] : [];
        $overview = is_array($report['overview'] ?? null) ? $report['overview'] : [];
        $periodDisplay = (string) ($report['periodDisplay'] ?? $this->salesPeriodDisplay($filter));
        $topProducts = is_array($report['topProducts'] ?? null) ? $report['topProducts'] : [];
        $payments = is_array($report['paymentBreakdown'] ?? null) ? $report['paymentBreakdown'] : [];

        $content = '';
        $pageWidth = 595.0;
        $pageHeight = 842.0;
        $left = 46.0;
        $right = 549.0;
        $contentWidth = $right - $left;
        $primary = [0.06, 0.46, 0.43];
        $primaryDark = [0.05, 0.30, 0.28];
        $primarySoft = [0.94, 0.99, 0.98];
        $dark = [0.07, 0.09, 0.15];
        $muted = [0.36, 0.42, 0.50];
        $light = [0.96, 0.97, 0.98];
        $border = [0.83, 0.86, 0.90];

        $content .= $this->pdfSetFill(1, 1, 1);
        $content .= $this->pdfRect(0, 0, $pageWidth, $pageHeight, 'f');

        $y = 780.0;
        $content .= $this->pdfSetFill($primary[0], $primary[1], $primary[2]);
        $content .= $this->pdfRect($left, $y - 11, 18, 18, 'f');
        $content .= $this->pdfTextAt('M1', $left + 4, $y - 5, 8, 'F2', [1, 1, 1]);
        $content .= $this->pdfTextAt('MadukaOne', $left + 25, $y - 2, 10, 'F2', $dark);
        $content .= $this->pdfTextAt((string) ($activeShop['nom'] ?? 'Boutique active'), $left, $y - 40, 18, 'F2', $dark);
        $content .= $this->pdfTextAt('RAPPORT DES VENTES', $left, $y - 63, 13, 'F2', $primary);
        $content .= $this->pdfTextAt('Rapport commercial consolidé selon les ventes enregistrées dans le système.', $left, $y - 83, 8.5, 'F1', $muted);

        $boxX = 360.0;
        $boxY = $y - 70;
        $content .= $this->pdfSetFill($primarySoft[0], $primarySoft[1], $primarySoft[2]);
        $content .= $this->pdfRect($boxX, $boxY, 150, 68, 'f');
        $content .= $this->pdfSetStroke($border[0], $border[1], $border[2]);
        $content .= $this->pdfRect($boxX, $boxY, 150, 68, 'S');
        $content .= $this->pdfTextAt('IDENTIFIANTS BOUTIQUE', $boxX + 50, $boxY + 52, 7.5, 'F2', $primaryDark);
        $content .= $this->pdfTextAt('ID boutique : ' . (string) ($activeShop['id'] ?? '-'), $boxX + 75, $boxY + 35, 7, 'F1', $dark);
        $content .= $this->pdfTextAt('Téléphone : ' . (string) ($activeShop['telephone'] ?? '-'), $boxX + 48, $boxY + 22, 7, 'F1', $dark);
        $content .= $this->pdfTextAt('Statut : Active', $boxX + 92, $boxY + 9, 7, 'F1', $dark);

        $metaY = 602.0;
        $content .= $this->pdfSetFill($light[0], $light[1], $light[2]);
        $content .= $this->pdfRect($left, $metaY, $contentWidth, 45, 'f');
        $content .= $this->pdfSetStroke($border[0], $border[1], $border[2]);
        $content .= $this->pdfRect($left, $metaY, $contentWidth, 45, 'S');
        $metaCols = [
            ['PÉRIODE', $periodDisplay],
            ['TYPE', 'Rapport des ventes'],
            ['GÉNÉRÉ LE', date('d/m/Y H:i')],
            ['BOUTIQUE', (string) ($activeShop['nom'] ?? '-')],
        ];
        $colW = $contentWidth / 4;
        foreach ($metaCols as $index => $meta) {
            $x = $left + ($index * $colW);
            if ($index > 0) {
                $content .= $this->pdfLine($x, $metaY + 8, $x, $metaY + 37, $border);
            }
            $content .= $this->pdfTextAt($meta[0], $x + 8, $metaY + 27, 6.5, 'F2', $muted);
            $content .= $this->pdfTextAt($meta[1], $x + 8, $metaY + 15, 7, 'F2', $dark);
        }
        $content .= $this->pdfSetFill($primary[0], $primary[1], $primary[2]);
        $content .= $this->pdfRect($left, $metaY - 12, $contentWidth, 2, 'f');

        $y = 556.0;
        $content .= $this->pdfSectionTitle('1. SYNTHÈSE EXÉCUTIVE', $left, $y, $contentWidth);
        $y -= 50;
        $content .= $this->pdfSetFill(1, 1, 1);
        $content .= $this->pdfRect($left, $y, $contentWidth, 40, 'f');
        $content .= $this->pdfSetStroke($border[0], $border[1], $border[2]);
        $content .= $this->pdfRect($left, $y, $contentWidth, 40, 'S');
        $summaryText = 'Ce rapport présente les ventes validées, les encaissements, les crédits clients et les produits performants de la boutique active sur la période sélectionnée.';
        foreach ($this->wrapPdfText($summaryText, 112) as $index => $line) {
            $content .= $this->pdfTextAt($line, $left + 10, $y + 25 - ($index * 11), 7.5, 'F1', $dark);
        }

        $y -= 70;
        $content .= $this->pdfSectionTitle('2. INDICATEURS CLÉS', $left, $y, $contentWidth);
        $y -= 55;
        $indicators = [
            ['VENTES VALIDÉES', $this->money((float) ($overview['validated_revenue'] ?? 0)), (int) ($overview['validated_count'] ?? 0) . ' ticket(s)', [0.02, 0.49, 0.40]],
            ['ENCAISSEMENTS', $this->money((float) ($overview['received_total'] ?? 0)), 'Montant reçu', [0.07, 0.32, 0.70]],
            ['CRÉDIT CLIENT', $this->money((float) ($overview['debt_total'] ?? 0)), 'Reste à encaisser', [0.82, 0.13, 0.25]],
            ['ARTICLES VENDUS', (string) (int) ($overview['items_sold'] ?? 0), 'Quantités POS', [0.08, 0.10, 0.18]],
        ];
        $cardW = $contentWidth / 4;
        foreach ($indicators as $index => $item) {
            $x = $left + ($index * $cardW);
            $content .= $this->pdfSetFill(1, 1, 1);
            $content .= $this->pdfRect($x, $y, $cardW, 45, 'f');
            $content .= $this->pdfSetStroke($border[0], $border[1], $border[2]);
            $content .= $this->pdfRect($x, $y, $cardW, 45, 'S');
            $content .= $this->pdfTextAt($item[0], $x + 8, $y + 30, 6.5, 'F2', $muted);
            $content .= $this->pdfTextAt($item[1], $x + 8, $y + 17, 12, 'F2', $item[3]);
            $content .= $this->pdfTextAt($item[2], $x + 8, $y + 7, 6.5, 'F1', $dark);
        }

        $y -= 70;
        $content .= $this->pdfSectionTitle('3. RÉPARTITION DES PAIEMENTS', $left, $y, $contentWidth);
        $y -= 12;
        $paymentRows = [];
        foreach ($payments as $payment) {
            $paymentRows[] = [
                (string) ($payment['mode_paiement'] ?? 'cash'),
                (string) (int) ($payment['tickets'] ?? 0),
                $this->money((float) ($payment['revenue'] ?? 0)),
                $this->money((float) ($payment['debt'] ?? 0)),
            ];
        }
        if ($paymentRows === []) {
            $paymentRows[] = ['Aucun paiement', '0', '0,00 USD', '0,00 USD'];
        }
        $content .= $this->pdfTable($left, $y, $contentWidth, ['MODE', 'TICKETS', 'TOTAL', 'CRÉDIT'], $paymentRows, [0.34, 0.16, 0.25, 0.25], 5);
        $y -= 28 + (min(5, count($paymentRows)) * 20);

        $content .= $this->pdfSectionTitle('4. TOP PRODUITS VENDUS', $left, $y, $contentWidth);
        $y -= 12;
        $productRows = [];
        foreach (array_slice($topProducts, 0, 5) as $product) {
            $productRows[] = [
                (string) ($product['product_name'] ?? 'Produit'),
                (string) (int) ($product['quantity'] ?? 0),
                $this->money((float) ($product['revenue'] ?? 0)),
                $this->money((float) ($product['margin'] ?? 0)),
            ];
        }
        if ($productRows === []) {
            $productRows[] = ['Aucun produit vendu', '0', '0,00 USD', '0,00 USD'];
        }
        $content .= $this->pdfTable($left, $y, $contentWidth, ['PRODUIT', 'QTÉ', 'VENTES', 'MARGE'], $productRows, [0.46, 0.12, 0.21, 0.21], 5);

        $footerY = 34.0;
        $content .= $this->pdfLine($left, $footerY + 16, $right, $footerY + 16, $border);
        $content .= $this->pdfTextAt('MadukaOne - Rapport généré automatiquement', $left, $footerY, 7, 'F1', $muted);
        $content .= $this->pdfTextAt('Page 1/1', $right - 35, $footerY, 7, 'F1', $muted);

        return $this->pdfFromContentStreams([$content]);
    }

    private function xlsxContentTypes(int $sheetCount): string
    {
        $overrides = '';
        for ($index = 1; $index <= $sheetCount; $index++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . $index . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $overrides
            . '</Types>';
    }

    private function xlsxRootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function xlsxWorkbook(array $sheetNames): string
    {
        $sheets = '';
        foreach ($sheetNames as $index => $name) {
            $sheetId = $index + 1;
            $sheets .= '<sheet name="' . $this->xml($name) . '" sheetId="' . $sheetId . '" r:id="rId' . $sheetId . '"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheets . '</sheets>'
            . '</workbook>';
    }

    private function xlsxWorkbookRels(int $sheetCount): string
    {
        $rels = '';
        for ($index = 1; $index <= $sheetCount; $index++) {
            $rels .= '<Relationship Id="rId' . $index . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $index . '.xml"/>';
        }
        $rels .= '<Relationship Id="rId' . ($sheetCount + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $rels
            . '</Relationships>';
    }

    private function xlsxStyles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            . '<borders count="1"><border/></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            . '</styleSheet>';
    }

    private function xlsxSheet(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        foreach (array_values($rows) as $rowIndex => $row) {
            $xml .= '<row r="' . ($rowIndex + 1) . '">';
            foreach (array_values($row) as $columnIndex => $value) {
                $cell = $this->columnName($columnIndex + 1) . ($rowIndex + 1);
                if (is_int($value) || is_float($value)) {
                    $xml .= '<c r="' . $cell . '"><v>' . $this->xml((string) $value) . '</v></c>';
                } else {
                    $xml .= '<c r="' . $cell . '" t="inlineStr"><is><t>' . $this->xml((string) $value) . '</t></is></c>';
                }
            }
            $xml .= '</row>';
        }

        return $xml . '</sheetData></worksheet>';
    }

    private function columnName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function pdfFromContentStreams(array $streams): string
    {
        $objects = [];
        $catalogId = 1;
        $pagesId = 2;
        $fontId = 3;
        $boldFontId = 4;
        $nextId = 5;
        $pageIds = [];

        $objects[$fontId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[$boldFontId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        foreach ($streams as $stream) {
            $pageId = $nextId++;
            $contentId = $nextId++;
            $pageIds[] = $pageId;
            $objects[$contentId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
            $objects[$pageId] = '<< /Type /Page /Parent ' . $pagesId . ' 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 ' . $fontId . ' 0 R /F2 ' . $boldFontId . ' 0 R >> >> /Contents ' . $contentId . ' 0 R >>';
        }

        $objects[$catalogId] = '<< /Type /Catalog /Pages ' . $pagesId . ' 0 R >>';
        $objects[$pagesId] = '<< /Type /Pages /Kids [' . implode(' ', array_map(static fn (int $id): string => $id . ' 0 R', $pageIds)) . '] /Count ' . count($pageIds) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";

        for ($id = 1; $id <= count($objects); $id++) {
            $pdf .= str_pad((string) ($offsets[$id] ?? 0), 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . ' /Root ' . $catalogId . " 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    private function pdfSectionTitle(string $title, float $x, float $y, float $width): string
    {
        $content = $this->pdfSetFill(0.94, 0.95, 0.97);
        $content .= $this->pdfRect($x, $y, $width, 22, 'f');
        $content .= $this->pdfSetFill(0.06, 0.46, 0.43);
        $content .= $this->pdfRect($x, $y, 3, 22, 'f');
        $content .= $this->pdfTextAt($title, $x + 10, $y + 8, 9, 'F2', [0.04, 0.07, 0.12]);

        return $content;
    }

    private function pdfTable(float $x, float $yTop, float $width, array $headers, array $rows, array $ratios, int $maxRows): string
    {
        $content = '';
        $headerHeight = 20.0;
        $rowHeight = 20.0;
        $y = $yTop - $headerHeight;
        $dark = [0.04, 0.07, 0.12];
        $border = [0.83, 0.86, 0.90];
        $content .= $this->pdfSetFill($dark[0], $dark[1], $dark[2]);
        $content .= $this->pdfRect($x, $y, $width, $headerHeight, 'f');

        $cursorX = $x;
        foreach ($headers as $index => $header) {
            $cellW = $width * (float) ($ratios[$index] ?? (1 / count($headers)));
            $content .= $this->pdfTextAt((string) $header, $cursorX + 6, $y + 7, 6.5, 'F2', [1, 1, 1]);
            $cursorX += $cellW;
        }

        foreach (array_slice($rows, 0, $maxRows) as $rowIndex => $row) {
            $rowY = $y - (($rowIndex + 1) * $rowHeight);
            $fill = $rowIndex % 2 === 0 ? [1, 1, 1] : [0.98, 0.99, 1.0];
            $content .= $this->pdfSetFill($fill[0], $fill[1], $fill[2]);
            $content .= $this->pdfRect($x, $rowY, $width, $rowHeight, 'f');
            $content .= $this->pdfSetStroke($border[0], $border[1], $border[2]);
            $content .= $this->pdfRect($x, $rowY, $width, $rowHeight, 'S');

            $cursorX = $x;
            foreach (array_values($row) as $index => $cell) {
                $cellW = $width * (float) ($ratios[$index] ?? (1 / count($headers)));
                $text = $this->truncatePdfText((string) $cell, $cellW > 180 ? 36 : 20);
                $content .= $this->pdfTextAt($text, $cursorX + 6, $rowY + 7, 7, 'F1', [0.04, 0.07, 0.12]);
                $cursorX += $cellW;
            }
        }

        return $content;
    }

    private function pdfTextAt(string $text, float $x, float $y, float $size, string $font = 'F1', array $rgb = [0, 0, 0]): string
    {
        return sprintf(
            "BT\n%.3F %.3F %.3F rg\n/%s %.2F Tf\n%.2F %.2F Td\n(%s) Tj\nET\n",
            $rgb[0],
            $rgb[1],
            $rgb[2],
            $font,
            $size,
            $x,
            $y,
            $this->pdfText($text)
        );
    }

    private function pdfRect(float $x, float $y, float $width, float $height, string $mode): string
    {
        return sprintf("%.2F %.2F %.2F %.2F re %s\n", $x, $y, $width, $height, $mode);
    }

    private function pdfLine(float $x1, float $y1, float $x2, float $y2, array $rgb = [0, 0, 0]): string
    {
        return $this->pdfSetStroke($rgb[0], $rgb[1], $rgb[2]) . sprintf("%.2F %.2F m %.2F %.2F l S\n", $x1, $y1, $x2, $y2);
    }

    private function pdfSetFill(float $r, float $g, float $b): string
    {
        return sprintf("%.3F %.3F %.3F rg\n", $r, $g, $b);
    }

    private function pdfSetStroke(float $r, float $g, float $b): string
    {
        return sprintf("%.3F %.3F %.3F RG\n", $r, $g, $b);
    }

    private function truncatePdfText(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, max(0, $max - 3)) . '...';
    }

    private function wrapPdfText(string $text, int $max): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $candidate = trim($line . ' ' . $word);
            if (mb_strlen($candidate) > $max && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $candidate;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }

    private function simplePdf(array $lines): string
    {
        $pages = array_chunk($lines, 42);
        $objects = [];
        $catalogId = 1;
        $pagesId = 2;
        $fontId = 3;
        $boldFontId = 4;
        $nextId = 5;
        $pageIds = [];
        $sectionTitles = [
            'Boutique active',
            'Informations du rapport',
            'Synthèse',
            'Top produits',
            'Paiements par mode',
            'Derniers tickets',
        ];

        $objects[$fontId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[$boldFontId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        foreach ($pages as $pageLines) {
            $pageId = $nextId++;
            $contentId = $nextId++;
            $pageIds[] = $pageId;
            $content = "BT\n/F1 11 Tf\n50 790 Td\n";
            foreach ($pageLines as $index => $line) {
                if ($index > 0) {
                    $content .= "0 -17 Td\n";
                }
                if ($line === 'Rapport professionnel des ventes') {
                    $content .= "/F2 16 Tf\n";
                } elseif (in_array($line, $sectionTitles, true)) {
                    $content .= "/F2 12 Tf\n";
                } else {
                    $content .= "/F1 10.5 Tf\n";
                }
                $content .= '(' . $this->pdfText($line) . ") Tj\n";
            }
            $content .= "ET\n";
            $objects[$contentId] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "endstream";
            $objects[$pageId] = '<< /Type /Page /Parent ' . $pagesId . ' 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 ' . $fontId . ' 0 R /F2 ' . $boldFontId . ' 0 R >> >> /Contents ' . $contentId . ' 0 R >>';
        }

        $objects[$catalogId] = '<< /Type /Catalog /Pages ' . $pagesId . ' 0 R >>';
        $objects[$pagesId] = '<< /Type /Pages /Kids [' . implode(' ', array_map(static fn (int $id): string => $id . ' 0 R', $pageIds)) . '] /Count ' . count($pageIds) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($id = 1; $id <= count($objects); $id++) {
            $pdf .= str_pad((string) ($offsets[$id] ?? 0), 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . ' /Root ' . $catalogId . " 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    private function pdfText(string $value): string
    {
        $value = $this->normalizePdfUtf8Text($value);
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', $value);
        $encoded = $encoded === false ? $value : $encoded;

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded);
    }

    private function normalizePdfUtf8Text(string $value): string
    {
        if (!str_contains($value, "\xC3\x83") && !str_contains($value, "\xC3\x82") && !str_contains($value, "\xC3\xA2")) {
            return $value;
        }

        $decoded = iconv('UTF-8', 'Windows-1252//IGNORE', $value);
        if (is_string($decoded) && $decoded !== '' && mb_check_encoding($decoded, 'UTF-8')) {
            return $decoded;
        }

        return str_replace("\xc2\xa0", ' ', $value);
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function downloadFile(string $path, string $filename, string $contentType): never
    {
        header_remove('Content-Type');
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        @unlink($path);
        exit;
    }

    private function money(float $value): string
    {
        return number_format($value, 2, ',', ' ') . ' USD';
    }
}
