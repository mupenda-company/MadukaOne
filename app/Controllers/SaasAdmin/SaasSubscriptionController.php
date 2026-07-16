<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseSaasAdminController.php';
require_once dirname(__DIR__, 2) . '/Core/ModuleCatalog.php';

final class SaasSubscriptionController extends BaseSaasAdminController
{
    public function index(array $params = []): void
    {
        $this->renderSaas('subscriptions/index', [
            'pageTitle' => 'Abonnements',
            'activeMenu' => 'saas-subscriptions',
            'shops' => $this->repo->shopsWithMetrics(),
            'plans' => $this->repo->plans(),
            'features' => $this->repo->features(),
            'assignments' => $this->repo->featureAssignmentMaps(),
            'featureIdsByShop' => $this->repo->featureIdsByShop(),
            'moduleCatalog' => ModuleCatalog::all(),
        ]);
    }

    public function storePlan(array $params = []): void
    {
        try {
            $this->repo->createPlan($_POST);
            $this->flashSuccess('Plan ajoute.');
        } catch (Throwable $exception) {
            $this->flashError('Creation du plan impossible: ' . $exception->getMessage());
        }

        $this->redirect('/saas-admin/abonnements');
    }

    public function showPlan(array $params = []): void
    {
        $plan = $this->repo->findPlan((int) ($params['id'] ?? 0));

        if ($plan === null) {
            $this->flashError('Plan introuvable.');
            $this->redirect('/saas-admin/abonnements');
        }

        $this->renderSaas('subscriptions/plan-show', [
            'pageTitle' => 'Detail plan',
            'activeMenu' => 'saas-subscriptions',
            'plan' => $plan,
        ]);
    }

    public function editPlan(array $params = []): void
    {
        $plan = $this->repo->findPlan((int) ($params['id'] ?? 0));

        if ($plan === null) {
            $this->flashError('Plan introuvable.');
            $this->redirect('/saas-admin/abonnements');
        }

        $this->renderSaas('subscriptions/plan-edit', [
            'pageTitle' => 'Modifier plan',
            'activeMenu' => 'saas-subscriptions',
            'plan' => $plan,
        ]);
    }

    public function updatePlan(array $params = []): void
    {
        $planId = (int) ($params['id'] ?? 0);

        try {
            $this->repo->updatePlan($planId, $_POST);
            $this->flashSuccess('Plan mis a jour.');
        } catch (Throwable $exception) {
            $this->flashError('Modification du plan impossible: ' . $exception->getMessage());
        }

        $this->redirect('/saas-admin/abonnements/plans/' . $planId . '/edit');
    }

    public function deletePlan(array $params = []): void
    {
        try {
            $this->repo->deletePlan((int) ($params['id'] ?? 0));
            $this->flashSuccess('Plan supprime ou desactive.');
        } catch (Throwable $exception) {
            $this->flashError('Suppression du plan impossible: ' . $exception->getMessage());
        }

        $this->redirect('/saas-admin/abonnements');
    }

    public function assign(array $params = []): void
    {
        $shopId = (int) ($params['id'] ?? 0);

        try {
            $this->repo->assignSubscription($shopId, $_POST);
            $this->flashSuccess('Abonnement et fonctionnalites mis a jour.');
        } catch (Throwable $exception) {
            $this->flashError('Mise a jour impossible: ' . $exception->getMessage());
        }

        $this->redirect('/saas-admin/abonnements#shop-' . $shopId);
    }
}
