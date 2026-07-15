<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseSaasAdminController.php';

final class SaasCategoryController extends BaseSaasAdminController
{
    public function index(array $params = []): void
    {
        $this->renderSaas('categories/index', [
            'pageTitle' => 'Categories boutiques',
            'activeMenu' => 'saas-categories',
            'categories' => $this->repo->categories(),
        ]);
    }

    public function store(array $params = []): void
    {
        try {
            $this->repo->createCategory($_POST);
            $this->flashSuccess('Categorie creee.');
        } catch (Throwable $exception) {
            $this->flashError('Creation impossible: ' . $exception->getMessage());
        }

        $this->redirect('/saas-admin/categories');
    }

    public function update(array $params = []): void
    {
        try {
            $this->repo->updateCategory((int) ($params['id'] ?? 0), $_POST);
            $this->flashSuccess('Categorie mise a jour.');
        } catch (Throwable $exception) {
            $this->flashError('Modification impossible: ' . $exception->getMessage());
        }

        $this->redirect('/saas-admin/categories');
    }

    public function toggle(array $params = []): void
    {
        try {
            $this->repo->toggleCategory((int) ($params['id'] ?? 0));
            $this->flashSuccess('Statut de la categorie mis a jour.');
        } catch (Throwable $exception) {
            $this->flashError('Action impossible: ' . $exception->getMessage());
        }

        $this->redirect('/saas-admin/categories');
    }
}
