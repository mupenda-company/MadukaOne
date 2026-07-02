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
        $summary = $this->summary((int) $activeShop['id']);

        $stats = [
            [
                'label' => 'Chiffre d’affaires',
                'value' => $this->money((float) $summary['revenue']),
                'detail' => 'Ventes validées de la boutique',
                'tone' => 'teal',
            ],
            [
                'label' => 'Marge brute',
                'value' => $this->money((float) $summary['gross_margin']),
                'detail' => 'Ventes moins coût d’achat',
                'tone' => 'blue',
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
        ];

        $recentSignals = [
            ['label' => 'Alertes stock', 'value' => (string) $summary['stock_alerts'], 'hint' => 'Produits sous le seuil minimum'],
            ['label' => 'Ventes du jour', 'value' => (string) $summary['today_sales'], 'hint' => 'Tickets validés aujourd’hui'],
            ['label' => 'Crédits clients', 'value' => $this->money((float) $summary['customer_debt']), 'hint' => 'Montant restant à encaisser'],
        ];

        $this->render('dashboard/index', [
            'pageTitle' => 'Tableau de bord admin',
            'pageEyebrow' => 'Pilotage commercial',
            'currentUser' => $currentUser,
            'shops' => $shops,
            'activeShop' => $activeShop,
            'stats' => $stats,
            'recentSignals' => $recentSignals,
            'activeMenu' => 'dashboard',
        ]);
    }

    private function summary(int $shopId): array
    {
        $sales = Database::connection()->prepare(
            "SELECT
                COALESCE(SUM(sale_details.total_ligne), 0) AS revenue,
                COALESCE(SUM(sale_details.quantite * sale_details.prix_achat_unitaire), 0) AS cost
             FROM sale_details
             INNER JOIN sales ON sales.id = sale_details.sale_id
             WHERE sales.shop_id = :shop_id AND sales.statut = 'validee'"
        );
        $sales->execute(['shop_id' => $shopId]);
        $salesSummary = $sales->fetch() ?: ['revenue' => 0, 'cost' => 0];

        $expenses = Database::connection()->prepare('SELECT COALESCE(SUM(montant), 0) FROM expenses WHERE shop_id = :shop_id');
        $expenses->execute(['shop_id' => $shopId]);
        $expenseTotal = (float) $expenses->fetchColumn();

        $signals = Database::connection()->prepare(
            "SELECT
                (SELECT COUNT(*) FROM products WHERE shop_id = :shop_products AND actif = 1 AND quantite_stock <= alerte_stock_min) AS stock_alerts,
                (SELECT COUNT(*) FROM sales WHERE shop_id = :shop_sales_today AND statut = 'validee' AND DATE(date_vente) = CURDATE()) AS today_sales,
                (SELECT COALESCE(SUM(montant_dette), 0) FROM sales WHERE shop_id = :shop_sales_debt AND statut = 'validee') AS customer_debt"
        );
        $signals->execute([
            'shop_products' => $shopId,
            'shop_sales_today' => $shopId,
            'shop_sales_debt' => $shopId,
        ]);
        $signalSummary = $signals->fetch() ?: ['stock_alerts' => 0, 'today_sales' => 0, 'customer_debt' => 0];
        $grossMargin = (float) $salesSummary['revenue'] - (float) $salesSummary['cost'];

        return [
            'revenue' => (float) $salesSummary['revenue'],
            'gross_margin' => $grossMargin,
            'expenses' => $expenseTotal,
            'net_profit' => $grossMargin - $expenseTotal,
            'stock_alerts' => (int) $signalSummary['stock_alerts'],
            'today_sales' => (int) $signalSummary['today_sales'],
            'customer_debt' => (float) $signalSummary['customer_debt'],
        ];
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
        return number_format($value, 2, ',', ' ') . ' USD';
    }
}
