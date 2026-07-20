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
        $categoryId = 0;
        try {
            $categoryId = $this->repo->createCategory($_POST);
            $this->flashSuccess('Categorie creee.');
        } catch (Throwable $exception) {
            $this->flashError('Creation impossible: ' . $exception->getMessage());
        }

        $this->redirect('/saas-admin/categories' . ($categoryId > 0 ? '?category=' . $categoryId : ''));
    }

    public function update(array $params = []): void
    {
        try {
            $id = (int) ($params['id'] ?? 0);
            if (!$this->repo->updateCategory($id, $_POST)) {
                throw new RuntimeException('Categorie introuvable.');
            }
            $this->flashSuccess('Categorie mise a jour.');
        } catch (Throwable $exception) {
            $this->flashError('Modification impossible: ' . $exception->getMessage());
        }

        $this->redirect('/saas-admin/categories?category=' . (int) ($params['id'] ?? 0));
    }

    public function toggle(array $params = []): void
    {
        try {
            if (!$this->repo->toggleCategory((int) ($params['id'] ?? 0))) {
                throw new RuntimeException('Categorie introuvable.');
            }
            $this->flashSuccess('Statut de la categorie mis a jour.');
        } catch (Throwable $exception) {
            $this->flashError('Action impossible: ' . $exception->getMessage());
        }

        $this->redirect('/saas-admin/categories?category=' . (int) ($params['id'] ?? 0));
    }

    public function delete(array $params = []): void
    {
        try {
            if (!$this->repo->deleteCategory((int) ($params['id'] ?? 0))) {
                throw new RuntimeException('Categorie introuvable.');
            }
            $this->flashSuccess('Categorie supprimee.');
        } catch (Throwable $exception) {
            $this->flashError('Suppression impossible: ' . $exception->getMessage());
        }

        $this->redirect('/saas-admin/categories');
    }
}
