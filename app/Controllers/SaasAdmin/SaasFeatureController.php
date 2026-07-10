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
            'categories' => $this->repo->categories(),
            'plans' => $this->repo->plans(),
            'assignments' => $this->repo->featureAssignmentMaps(),
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

    public function syncCategoryPlans(array $params = []): void
    {
        try {
            $planIds = is_array($_POST['plan_ids'] ?? null) ? $_POST['plan_ids'] : [];
            $this->repo->syncCategoryPlans((int) ($params['id'] ?? 0), $planIds);
            $this->flashSuccess('Plans de la categorie mis a jour.');
        } catch (Throwable $exception) {
            $this->flashError('Affectation des plans impossible: ' . $exception->getMessage());
        }

        $this->redirect('/saas-admin/fonctionnalites#categories');
    }

    public function syncPlan(array $params = []): void
    {
        try {
            $featureIds = is_array($_POST['feature_ids'] ?? null) ? $_POST['feature_ids'] : [];
            $this->repo->syncPlanFeatures((int) ($params['id'] ?? 0), $featureIds);
            $this->flashSuccess('Fonctionnalites du plan mises a jour.');
        } catch (Throwable $exception) {
            $this->flashError('Affectation plan impossible: ' . $exception->getMessage());
        }

        $this->redirect('/saas-admin/fonctionnalites#plans');
    }
}
