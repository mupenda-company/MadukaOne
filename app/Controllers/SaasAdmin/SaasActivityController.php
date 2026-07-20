<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseSaasAdminController.php';
require_once dirname(__DIR__, 2) . '/Models/SaasAdmin/SaasAuditRepository.php';

final class SaasActivityController extends BaseSaasAdminController
{
    public function index(array $params = []): void
    {
        $audit = new SaasAuditRepository();
        $filters = [
            'shop_id' => (int) ($_GET['shop_id'] ?? 0),
            'category_id' => (int) ($_GET['category_id'] ?? 0),
            'user_id' => (int) ($_GET['user_id'] ?? 0),
            'module' => trim((string) ($_GET['module'] ?? '')),
            'method' => in_array(($_GET['method'] ?? ''), ['GET', 'POST'], true) ? (string) $_GET['method'] : '',
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to' => trim((string) ($_GET['date_to'] ?? '')),
            'search' => trim((string) ($_GET['search'] ?? '')),
        ];
        $this->renderSaas('activities/index', [
            'pageTitle' => 'Historique global des activités',
            'activeMenu' => 'saas-activities',
            'auditLogs' => $audit->logs($filters),
            'auditFilters' => $filters,
            'filterOptions' => $audit->filters(),
            'auditStats' => $audit->stats(),
        ]);
    }
}
