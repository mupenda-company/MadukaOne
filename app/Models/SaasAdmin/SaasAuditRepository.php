<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Core/AuditLogger.php';

final class SaasAuditRepository
{
    public function __construct()
    {
        AuditLogger::ensureSchema();
    }

    public function filters(): array
    {
        $pdo = Database::connection();
        return [
            'shops' => $pdo->query('SELECT id, nom FROM shops ORDER BY nom')->fetchAll(),
            'categories' => $pdo->query('SELECT id, nom FROM shop_categories ORDER BY nom')->fetchAll(),
            'users' => $pdo->query('SELECT id, nom, email FROM users ORDER BY nom, email')->fetchAll(),
            'modules' => $pdo->query('SELECT DISTINCT module FROM saas_audit_logs ORDER BY module')->fetchAll(PDO::FETCH_COLUMN),
        ];
    }

    public function logs(array $filters, int $limit = 300): array
    {
        $where = ['1 = 1'];
        $params = [];
        foreach (['shop_id' => 'logs.shop_id', 'category_id' => 'categories.id', 'user_id' => 'logs.user_id'] as $key => $column) {
            if ((int) ($filters[$key] ?? 0) > 0) {
                $where[] = $column . ' = :' . $key;
                $params[$key] = (int) $filters[$key];
            }
        }
        if (($filters['module'] ?? '') !== '') {
            $where[] = 'logs.module = :module';
            $params['module'] = (string) $filters['module'];
        }
        if (($filters['method'] ?? '') !== '') {
            $where[] = 'logs.methode = :method';
            $params['method'] = (string) $filters['method'];
        }
        if (($filters['date_from'] ?? '') !== '') {
            $where[] = 'logs.created_at >= :date_from';
            $params['date_from'] = (string) $filters['date_from'] . ' 00:00:00';
        }
        if (($filters['date_to'] ?? '') !== '') {
            $where[] = 'logs.created_at <= :date_to';
            $params['date_to'] = (string) $filters['date_to'] . ' 23:59:59';
        }
        if (($filters['search'] ?? '') !== '') {
            $where[] = '(logs.action LIKE :search OR logs.chemin LIKE :search OR shops.nom LIKE :search OR users.nom LIKE :search OR users.email LIKE :search)';
            $params['search'] = '%' . (string) $filters['search'] . '%';
        }

        $sql = 'SELECT logs.*, shops.nom AS shop_name, categories.nom AS category_name,
                       users.nom AS user_name, users.email AS user_email
                FROM saas_audit_logs logs
                LEFT JOIN shops ON shops.id = logs.shop_id
                LEFT JOIN shop_categories categories ON categories.id = shops.category_id
                LEFT JOIN users ON users.id = logs.user_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY logs.created_at DESC, logs.id DESC LIMIT ' . max(1, min(1000, $limit));
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function stats(): array
    {
        $row = Database::connection()->query(
            "SELECT COUNT(*) AS total,
                    SUM(created_at >= CURDATE()) AS today,
                    COUNT(DISTINCT shop_id) AS shops,
                    COUNT(DISTINCT user_id) AS users
             FROM saas_audit_logs"
        )->fetch();
        return is_array($row) ? $row : [];
    }
}
