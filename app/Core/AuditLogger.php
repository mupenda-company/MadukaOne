<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

final class AuditLogger
{
    private static bool $schemaReady = false;

    public static function record(string $method, string $path, string $routeAction = '', array $params = []): void
    {
        $user = $_SESSION['user'] ?? null;
        if (!is_array($user) || empty($user['id'])) {
            return;
        }

        try {
            self::ensureSchema();
            $isSaasAdminRoute = str_starts_with($path, '/saas-admin');
            $shopId = $isSaasAdminRoute ? self::targetShopId($path, $params) : (int) ($_SESSION['current_shop_id'] ?? $user['shop_id'] ?? 0);
            $statement = Database::connection()->prepare(
                'INSERT INTO saas_audit_logs
                    (shop_id, user_id, methode, chemin, action, module, entity_type, entity_id, ip_address, user_agent, metadata)
                 VALUES
                    (:shop_id, :user_id, :methode, :chemin, :action, :module, :entity_type, :entity_id, :ip_address, :user_agent, :metadata)'
            );
            [$entityType, $entityId] = self::entity($path, $params);
            $statement->execute([
                'shop_id' => $shopId > 0 ? $shopId : null,
                'user_id' => (int) $user['id'],
                'methode' => strtoupper($method),
                'chemin' => mb_substr($path, 0, 255),
                'action' => self::actionLabel($method, $path),
                'module' => self::module($path),
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'ip_address' => mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
                'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500) ?: null,
                'metadata' => json_encode(['route' => $routeAction], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        } catch (Throwable) {
            // La journalisation ne doit jamais interrompre une operation metier.
        }
    }

    public static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        Database::connection()->exec(
            "CREATE TABLE IF NOT EXISTS saas_audit_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                shop_id BIGINT UNSIGNED NULL,
                user_id BIGINT UNSIGNED NULL,
                methode VARCHAR(10) NOT NULL,
                chemin VARCHAR(255) NOT NULL,
                action VARCHAR(160) NOT NULL,
                module VARCHAR(80) NOT NULL DEFAULT 'general',
                entity_type VARCHAR(80) NULL,
                entity_id BIGINT UNSIGNED NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(500) NULL,
                metadata JSON NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_saas_audit_created (created_at),
                KEY idx_saas_audit_shop (shop_id, created_at),
                KEY idx_saas_audit_user (user_id, created_at),
                KEY idx_saas_audit_module (module, created_at),
                CONSTRAINT fk_saas_audit_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON UPDATE CASCADE ON DELETE SET NULL,
                CONSTRAINT fk_saas_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        self::$schemaReady = true;
    }

    private static function targetShopId(string $path, array $params): int
    {
        return preg_match('#^/saas-admin/boutiques/[^/]+#', $path) === 1 ? (int) ($params['id'] ?? 0) : 0;
    }

    private static function entity(string $path, array $params): array
    {
        $parts = array_values(array_filter(explode('/', trim($path, '/'))));
        $type = $parts[0] ?? 'page';
        if ($type === 'saas-admin') {
            $type = $parts[1] ?? 'saas';
        }
        return [$type, isset($params['id']) && is_numeric($params['id']) ? (int) $params['id'] : null];
    }

    private static function module(string $path): string
    {
        $map = ['pos' => 'ventes', 'sales' => 'ventes', 'ventes' => 'ventes', 'products' => 'catalogue', 'produits' => 'catalogue', 'stock' => 'stock', 'supplies' => 'approvisionnements', 'approvisionnements' => 'approvisionnements', 'finances' => 'finances', 'users' => 'utilisateurs', 'utilisateurs' => 'utilisateurs', 'saas-admin' => 'administration_saas'];
        $first = explode('/', trim($path, '/'))[0] ?? 'general';
        return $map[$first] ?? ($first !== '' ? $first : 'general');
    }

    private static function actionLabel(string $method, string $path): string
    {
        if (strtoupper($method) === 'GET') {
            return 'Consultation de page';
        }
        foreach (['delete' => 'Suppression', 'toggle' => 'Changement de statut', 'update' => 'Modification', 'create' => 'Creation', 'store' => 'Creation', 'logout' => 'Deconnexion'] as $needle => $label) {
            if (str_contains($path, $needle)) {
                return $label;
            }
        }
        return 'Operation enregistree';
    }
}
