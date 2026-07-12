<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';

class DashboardController extends AppController
{
    public function index(array $params = []): void
    {
        $this->sendNoStoreHeaders();

        $shops = $this->shops();
        $currentUser = $this->currentUser();
        $activeShop = $this->activeShop($shops, $currentUser);
        $shopId = (int) $activeShop['id'];
        $summary = $this->summary($shopId);
        $monthlyTrend = $this->monthlyTrend($shopId);

        $stats = [
            [
                'label' => 'Vente du jour',
                'value' => $this->money((float) $summary['today_revenue']),
                'detail' => (int) $summary['today_sales'] . ' ticket(s) validé(s) aujourd’hui',
                'tone' => 'teal',
            ],
            [
                'label' => 'Chiffre d’affaires',
                'value' => $this->money((float) $summary['revenue']),
                'detail' => 'Ventes validées de la boutique',
                'tone' => 'blue',
            ],
            [
                'label' => 'Marge brute',
                'value' => $this->money((float) $summary['gross_margin']),
                'detail' => 'Ventes moins coût d’achat',
                'tone' => 'emerald',
            ],
            [
                'label' => 'Dépenses courantes',
                'value' => $this->money((float) $summary['expenses']),
                'detail' => 'Charges opérationnelles',
                'tone' => 'amber',
            ],
            [
                'label' => 'Bénéfice net',
                'value' => $this->money((float) $summary['net_profit']),
                'detail' => 'Marge brute moins dépenses',
                'tone' => 'slate',
            ],
            [
                'label' => 'Alertes expiration',
                'value' => (string) $summary['expiration_alerts'],
                'detail' => 'Produits expires ou a moins de 30 jours',
                'tone' => 'amber',
            ],
        ];

        $recentSignals = [
            ['label' => 'Alertes stock', 'value' => (string) $summary['stock_alerts'], 'hint' => 'Produits sous le seuil minimum'],
            ['label' => 'Alertes expiration', 'value' => (string) $summary['expiration_alerts'], 'hint' => 'Produits expires ou proches de la date limite'],
            ['label' => 'Crédits clients', 'value' => $this->money((float) $summary['customer_debt']), 'hint' => 'Montant restant à encaisser'],
            ['label' => 'Produits actifs', 'value' => (string) $summary['active_products'], 'hint' => 'Catalogue disponible dans la boutique'],
        ];

        $this->render('dashboard/index', [
            'pageTitle' => 'Tableau de bord admin',
            'pageEyebrow' => 'Pilotage commercial',
            'currentUser' => $currentUser,
            'shops' => $shops,
            'activeShop' => $activeShop,
            'summary' => $summary,
            'stats' => $stats,
            'recentSignals' => $recentSignals,
            'monthlyTrend' => $monthlyTrend,
            'activeMenu' => 'dashboard',
        ]);
    }

    private function summary(int $shopId): array
    {
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

        $todaySales = Database::connection()->prepare(
            "SELECT COUNT(*) AS tickets, COALESCE(SUM(total_montant), 0) AS revenue
             FROM sales
             WHERE shop_id = :shop_id
               AND statut = 'validee'
               AND date_vente >= CURDATE()
               AND date_vente < DATE_ADD(CURDATE(), INTERVAL 1 DAY)"
        );
        $todaySales->execute(['shop_id' => $shopId]);
        $todaySummary = $todaySales->fetch() ?: ['tickets' => 0, 'revenue' => 0];

        $expenses = Database::connection()->prepare('SELECT COALESCE(SUM(montant), 0) FROM expenses WHERE shop_id = :shop_id AND statut = "active"');
        $expenses->execute(['shop_id' => $shopId]);
        $expenseTotal = (float) $expenses->fetchColumn();

        $signals = Database::connection()->prepare(
            "SELECT
                (SELECT COUNT(*) FROM products WHERE shop_id = :shop_products AND actif = 1 AND quantite_stock <= alerte_stock_min) AS stock_alerts,
                (SELECT COUNT(*) FROM products WHERE shop_id = :shop_expiration AND actif = 1 AND date_expiration IS NOT NULL AND date_expiration <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)) AS expiration_alerts,
                (SELECT COUNT(*) FROM products WHERE shop_id = :shop_products_active AND actif = 1) AS active_products,
                (SELECT COALESCE(SUM(montant_dette), 0) FROM sales WHERE shop_id = :shop_sales_debt AND statut = 'validee') AS customer_debt"
        );
        $signals->execute([
            'shop_products' => $shopId,
            'shop_expiration' => $shopId,
            'shop_products_active' => $shopId,
            'shop_sales_debt' => $shopId,
        ]);
        $signalSummary = $signals->fetch() ?: ['stock_alerts' => 0, 'expiration_alerts' => 0, 'active_products' => 0, 'customer_debt' => 0];
        $grossMargin = (float) $salesSummary['revenue'] - (float) $salesSummary['cost'];

        return [
            'revenue' => (float) $salesSummary['revenue'],
            'gross_margin' => $grossMargin,
            'expenses' => $expenseTotal,
            'net_profit' => $grossMargin - $expenseTotal,
            'today_revenue' => (float) $todaySummary['revenue'],
            'today_sales' => (int) $todaySummary['tickets'],
            'stock_alerts' => (int) $signalSummary['stock_alerts'],
            'expiration_alerts' => (int) $signalSummary['expiration_alerts'],
            'active_products' => (int) $signalSummary['active_products'],
            'customer_debt' => (float) $signalSummary['customer_debt'],
        ];
    }

    private function monthlyTrend(int $shopId): array
    {
        $months = [];
        $start = new DateTimeImmutable('first day of this month');
        $start = $start->modify('-5 months');

        for ($index = 0; $index < 6; $index++) {
            $month = $start->modify('+' . $index . ' months');
            $key = $month->format('Y-m-01');
            $months[$key] = [
                'key' => $key,
                'label' => $this->monthLabel((int) $month->format('n')),
                'revenue' => 0.0,
                'gross_margin' => 0.0,
                'expenses' => 0.0,
                'net_profit' => 0.0,
                'revenue_height' => 8,
                'margin_height' => 8,
                'profit_height' => 8,
            ];
        }

        $sales = Database::connection()->prepare(
            "SELECT
                DATE_FORMAT(sales.date_vente, '%Y-%m-01') AS period_key,
                COALESCE(SUM(sales.total_montant), 0) AS revenue,
                COALESCE(SUM(sale_costs.cost), 0) AS cost
             FROM sales
             LEFT JOIN (
                SELECT sale_id, SUM(quantite * prix_achat_unitaire) AS cost
                FROM sale_details
                GROUP BY sale_id
             ) sale_costs ON sale_costs.sale_id = sales.id
             WHERE sales.shop_id = :shop_id
               AND sales.statut = 'validee'
               AND sales.date_vente >= :date_start
             GROUP BY period_key"
        );
        $sales->execute([
            'shop_id' => $shopId,
            'date_start' => $start->format('Y-m-01 00:00:00'),
        ]);

        foreach ($sales->fetchAll() as $row) {
            $key = (string) $row['period_key'];

            if (!isset($months[$key])) {
                continue;
            }

            $revenue = (float) $row['revenue'];
            $grossMargin = $revenue - (float) $row['cost'];
            $months[$key]['revenue'] = $revenue;
            $months[$key]['gross_margin'] = $grossMargin;
        }

        $expenses = Database::connection()->prepare(
            "SELECT DATE_FORMAT(date_depense, '%Y-%m-01') AS period_key, COALESCE(SUM(montant), 0) AS expenses
             FROM expenses
             WHERE shop_id = :shop_id AND statut = 'active' AND date_depense >= :date_start
             GROUP BY period_key"
        );
        $expenses->execute([
            'shop_id' => $shopId,
            'date_start' => $start->format('Y-m-01 00:00:00'),
        ]);

        foreach ($expenses->fetchAll() as $row) {
            $key = (string) $row['period_key'];

            if (!isset($months[$key])) {
                continue;
            }

            $months[$key]['expenses'] = (float) $row['expenses'];
        }

        $max = 0.0;

        foreach ($months as &$month) {
            $month['net_profit'] = (float) $month['gross_margin'] - (float) $month['expenses'];
            $max = max($max, (float) $month['revenue'], (float) $month['gross_margin'], abs((float) $month['net_profit']));
        }
        unset($month);

        foreach ($months as &$month) {
            $month['revenue_height'] = $this->barHeight((float) $month['revenue'], $max);
            $month['margin_height'] = $this->barHeight((float) $month['gross_margin'], $max);
            $month['profit_height'] = $this->barHeight(abs((float) $month['net_profit']), $max);
        }
        unset($month);

        return array_values($months);
    }

    private function barHeight(float $value, float $max): int
    {
        return $max > 0 ? max(8, (int) round(($value / $max) * 100)) : 8;
    }

    private function monthLabel(int $month): string
    {
        $labels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];

        return $labels[$month - 1] ?? '';
    }

    private function sendNoStoreHeaders(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    }

    private function money(float $value): string
    {
        $shop = $this->activeShop($this->shops(), $this->currentUser());
        $currency = in_array(($shop['devise_principale'] ?? 'USD'), ['USD', 'CDF'], true) ? (string) $shop['devise_principale'] : 'USD';
        $rate = (float) (($shop['taux_change_cdf'] ?? 2800) ?: 2800);
        $usd = number_format($value, 2, ',', ' ') . ' USD';
        $cdf = number_format($value * $rate, 2, ',', ' ') . ' CDF';

        return $currency === 'CDF' ? $cdf . ' (' . $usd . ')' : $usd . ' (' . $cdf . ')';
    }
}
