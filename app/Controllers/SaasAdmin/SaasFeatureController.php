<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseSaasAdminController.php';

final class SaasFeatureController extends BaseSaasAdminController
{
    public function index(array $params = []): void
    {
        $this->renderSaas('features/index', [
            'pageTitle' => 'Fonctionnalites SaaS',
            'activeMenu' => 'saas-features',
            'features' => $this->repo->features(),
        ]);
    }

    public function store(array $params = []): void
    {
        try {
            $this->repo->createFeature($_POST);
            $this->flashSuccess('Fonctionnalite ajoutee.');
        } catch (Throwable $exception) {
            $this->flashError('Creation impossible: ' . $exception->getMessage());
        }

        $this->redirect('/saas-admin/fonctionnalites');
    }

    public function update(array $params = []): void
    {
        try {
            $this->repo->updateFeature((int) ($params['id'] ?? 0), $_POST);
            $this->flashSuccess('Fonctionnalite mise a jour.');
        } catch (Throwable $exception) {
            $this->flashError('Modification impossible: ' . $exception->getMessage());
        }

        $this->redirect('/saas-admin/fonctionnalites');
    }

    public function toggle(array $params = []): void
    {
        $this->repo->toggleFeature((int) ($params['id'] ?? 0));
        $this->flashSuccess('Statut de la fonctionnalite mis a jour.');
        $this->redirect('/saas-admin/fonctionnalites');
    }
}
