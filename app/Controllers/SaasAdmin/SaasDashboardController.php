<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseSaasAdminController.php';

final class SaasDashboardController extends BaseSaasAdminController
{
    public function index(array $params = []): void
    {
        $this->renderSaas('dashboard/index', [
            'pageTitle' => 'Pilotage SaaS',
            'activeMenu' => 'saas-dashboard',
            'stats' => $this->repo->dashboardStats(),
            'distributions' => $this->repo->dashboardDistributions(),
            'shops' => array_slice($this->repo->shopsWithMetrics(), 0, 8),
            'attentionShops' => $this->repo->shopsNeedingAttention(),
            'subscriptions' => array_slice($this->repo->subscriptions(), 0, 8),
        ]);
    }
}
