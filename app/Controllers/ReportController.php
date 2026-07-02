<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';

final class ReportController
{
    private const PER_PAGE = 50;
    private const MAX_PER_PAGE = 100;

    public function sales(array $params = []): void
    {
        $this->requireManager();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(self::MAX_PER_PAGE, max(1, (int) ($_GET['per_page'] ?? self::PER_PAGE)));
        $offset = ($page - 1) * $perPage;
        $scope = $this->shopScope();

        $sales = $this->paginatedSales($scope, $perPage, $offset);
        $totalRows = $this->countSales($scope);
        $pagination = [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $totalRows,
            'pages' => (int) ceil($totalRows / $perPage),
            'offset' => $offset,
        ];

        $this->view('reports/sales', compact('sales', 'pagination'));
    }

    public function financials(array $params = []): void
    {
        $this->requireManager();

        $scope = $this->shopScope();
        $financials = $this->financialSummary($scope);

        $this->view('reports/financials', compact('financials'));
    }

    public function stockMovements(array $params = []): void
    {
        $this->requireManager();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(self::MAX_PER_PAGE, max(1, (int) ($_GET['per_page'] ?? self::PER_PAGE)));
        $offset = ($page - 1) * $perPage;
        $scope = $this->shopScope();

        $movements = $this->paginatedStockMovements($scope, $perPage, $offset);
        $pagination = [
            'page' => $page,
            'per_page' => $perPage,
            'offset' => $offset,
        ];

        $this->view('reports/stock-movements', compact('movements', 'pagination'));
    }

    private function financialSummary(array $scope): array
    {
        $db = Database::connection();
        $shopCondition = $scope['all_shops'] ? '' : ' AND sales.shop_id = :shop_id';
        $expenseShopCondition = $scope['all_shops'] ? '' : ' AND expenses.shop_id = :shop_id';

        $salesStatement = $db->prepare(
            "SELECT
                COALESCE(SUM(sales.total_montant), 0) AS chiffre_affaires,
                COUNT(sales.id) AS nombre_ventes
             FROM sales
             WHERE sales.statut = 'validee'{$shopCondition}"
        );
        $this->executeScope($salesStatement, $scope);
        $sales = $salesStatement->fetch() ?: [];

        $costStatement = $db->prepare(
            "SELECT COALESCE(SUM(sale_details.quantite * sale_details.prix_achat_unitaire), 0) AS cout_achat_total
             FROM sale_details
             INNER JOIN sales ON sales.id = sale_details.sale_id
             WHERE sales.statut = 'validee'{$shopCondition}"
        );
        $this->executeScope($costStatement, $scope);
        $costs = $costStatement->fetch() ?: [];

        $expenseStatement = $db->prepare(
            "SELECT COALESCE(SUM(expenses.montant), 0) AS depenses_totales
             FROM expenses
             WHERE 1 = 1{$expenseShopCondition}"
        );
        $this->executeScope($expenseStatement, $scope);
        $expenses = $expenseStatement->fetch() ?: [];

        $revenue = (float) ($sales['chiffre_affaires'] ?? 0);
        $purchaseCost = (float) ($costs['cout_achat_total'] ?? 0);
        $totalExpenses = (float) ($expenses['depenses_totales'] ?? 0);

        return [
            'scope' => $scope['all_shops'] ? 'all_shops' : 'shop',
            'shop_id' => $scope['shop_id'],
            'nombre_ventes' => (int) ($sales['nombre_ventes'] ?? 0),
            'chiffre_affaires' => $revenue,
            'cout_achat_total' => $purchaseCost,
            'depenses_totales' => $totalExpenses,
            'benefice_net' => $revenue - $purchaseCost - $totalExpenses,
        ];
    }

    private function paginatedSales(array $scope, int $limit, int $offset): array
    {
        $shopCondition = $scope['all_shops'] ? '' : ' AND sales.shop_id = :shop_id';
        $statement = Database::connection()->prepare(
            "SELECT
                sales.id,
                sales.shop_id,
                shops.nom AS shop_name,
                sales.numero_facture,
                sales.date_vente,
                sales.total_montant,
                sales.montant_recu,
                sales.montant_dette,
                sales.mode_paiement,
                sales.statut,
                users.nom AS agent_name,
                customers.nom AS customer_name
             FROM sales
             INNER JOIN shops ON shops.id = sales.shop_id
             INNER JOIN users ON users.id = sales.user_id
             LEFT JOIN customers ON customers.id = sales.customer_id
             WHERE sales.statut = 'validee'{$shopCondition}
             ORDER BY sales.date_vente DESC, sales.id DESC
             LIMIT :limit OFFSET :offset"
        );

        $this->bindScope($statement, $scope);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    private function countSales(array $scope): int
    {
        $shopCondition = $scope['all_shops'] ? '' : ' AND shop_id = :shop_id';
        $statement = Database::connection()->prepare(
            "SELECT COUNT(*) AS total
             FROM sales
             WHERE statut = 'validee'{$shopCondition}"
        );
        $this->executeScope($statement, $scope);
        $row = $statement->fetch();

        return (int) ($row['total'] ?? 0);
    }

    private function paginatedStockMovements(array $scope, int $limit, int $offset): array
    {
        $shopCondition = $scope['all_shops'] ? '' : ' AND stock_movements.shop_id = :shop_id';
        $statement = Database::connection()->prepare(
            "SELECT
                stock_movements.*,
                shops.nom AS shop_name,
                products.nom AS product_name,
                users.nom AS user_name
             FROM stock_movements
             INNER JOIN shops ON shops.id = stock_movements.shop_id
             INNER JOIN products ON products.id = stock_movements.product_id
             INNER JOIN users ON users.id = stock_movements.user_id
             WHERE 1 = 1{$shopCondition}
             ORDER BY stock_movements.date_mouvement DESC, stock_movements.id DESC
             LIMIT :limit OFFSET :offset"
        );

        $this->bindScope($statement, $scope);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    private function shopScope(): array
    {
        $this->startSession();
        $user = $_SESSION['user'] ?? [];
        $shopId = isset($user['shop_id']) ? (int) $user['shop_id'] : 0;
        $role = strtolower((string) ($user['role'] ?? $user['role_legacy'] ?? 'agent'));
        $roleId = (int) ($user['role_id'] ?? 0);
        $isSuperAdmin = in_array($role, ['super_admin', 'admin'], true) && $roleId === 1 && $shopId <= 0;

        if ($isSuperAdmin) {
            return ['all_shops' => true, 'shop_id' => null];
        }

        if ($shopId <= 0) {
            $this->abort(403, 'Boutique non definie pour cet utilisateur.');
        }

        return ['all_shops' => false, 'shop_id' => $shopId];
    }

    private function executeScope(PDOStatement $statement, array $scope): void
    {
        $this->bindScope($statement, $scope);
        $statement->execute();
    }

    private function bindScope(PDOStatement $statement, array $scope): void
    {
        if (!$scope['all_shops']) {
            $statement->bindValue('shop_id', (int) $scope['shop_id'], PDO::PARAM_INT);
        }
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
            $_SESSION['flash_error'] = 'Acces refuse: les rapports sont reserves a l administrateur ou au gerant.';
            $this->redirect('/pos');
        }
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
