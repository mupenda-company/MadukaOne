<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseSaasAdminController.php';

final class SaasShopController extends BaseSaasAdminController
{
    public function index(array $params = []): void
    {
        $this->renderSaas('shops/index', [
            'pageTitle' => 'Toutes les boutiques',
            'activeMenu' => 'saas-shops',
            'shops' => $this->repo->shopsWithMetrics(),
        ]);
    }

    public function select(array $params = []): void
    {
        $this->renderSaas('shops/select', [
            'pageTitle' => 'Choisir une boutique',
            'activeMenu' => 'saas-shop-space',
            'shops' => $this->repo->shopsWithMetrics(),
        ]);
    }

    public function create(array $params = []): void
    {
        $this->renderSaas('shops/form', [
            'pageTitle' => 'Nouvelle boutique',
            'activeMenu' => 'saas-shops',
            'shop' => null,
            'categories' => $this->repo->categories(true),
        ]);
    }

    public function store(array $params = []): void
    {
        $error = $this->validateShopPayload($_POST);

        if ($error !== null) {
            $this->flashError($error);
            $this->redirect('/saas-admin/boutiques/create');
        }

        try {
            $shopId = $this->repo->createShop($_POST);
            $this->flashSuccess('Boutique creee. Configurez maintenant son abonnement.');
            $this->redirect('/saas-admin/abonnements#shop-' . $shopId);
        } catch (Throwable $exception) {
            $this->flashError('Creation impossible: ' . $exception->getMessage());
            $this->redirect('/saas-admin/boutiques/create');
        }
    }

    public function edit(array $params = []): void
    {
        $shop = $this->repo->findShop((int) ($params['id'] ?? 0));

        if ($shop === null) {
            $this->flashError('Boutique introuvable.');
            $this->redirect('/saas-admin/boutiques');
        }

        $this->renderSaas('shops/form', [
            'pageTitle' => 'Modifier la boutique',
            'activeMenu' => 'saas-shops',
            'shop' => $shop,
            'categories' => $this->repo->categories(true),
        ]);
    }

    public function accessShop(array $params = []): void
    {
        $shopId = (int) ($params['id'] ?? 0);
        $shop = $this->repo->findShop($shopId);

        if ($shop === null) {
            $this->flashError('Boutique introuvable.');
            $this->redirect('/saas-admin/boutiques');
        }

        $_SESSION['current_shop_id'] = $shopId;
        $_SESSION['saas_anonymous_shop_access'] = [
            'shop_id' => $shopId,
            'shop_name' => (string) ($shop['nom'] ?? 'Boutique'),
            'started_at' => date('Y-m-d H:i:s'),
        ];

        $this->flashSuccess('Acces anonyme a la boutique active: ' . (string) ($shop['nom'] ?? 'Boutique') . '.');
        $this->redirect('/dashboard?shop_id=' . $shopId);
    }

    public function update(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $error = $this->validateShopPayload($_POST);

        if ($error !== null) {
            $this->flashError($error);
            $this->redirect('/saas-admin/boutiques/' . $id . '/edit');
        }

        try {
            $this->repo->updateShop($id, $_POST);
            $this->flashSuccess('Boutique mise a jour.');
            $this->redirect('/saas-admin/boutiques');
        } catch (Throwable $exception) {
            $this->flashError('Modification impossible: ' . $exception->getMessage());
            $this->redirect('/saas-admin/boutiques/' . $id . '/edit');
        }
    }

    public function toggle(array $params = []): void
    {
        $this->repo->toggleShop((int) ($params['id'] ?? 0));
        $this->flashSuccess('Statut de la boutique mis a jour.');
        $this->redirect('/saas-admin/boutiques');
    }
}
