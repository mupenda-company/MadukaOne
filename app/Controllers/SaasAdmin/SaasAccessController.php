<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseSaasAdminController.php';

final class SaasAccessController extends BaseSaasAdminController
{
    public function users(array $params = []): void
    {
        $this->renderSaas('access/users', [
            'pageTitle' => 'Acces utilisateurs',
            'activeMenu' => 'saas-users',
            'users' => $this->repo->allUsers(),
            'shops' => $this->repo->shopsWithMetrics(),
            'roles' => $this->repo->roles(),
        ]);
    }

    public function editUser(array $params = []): void
    {
        $user = $this->repo->findUser((int) ($params['id'] ?? 0));

        if ($user === null) {
            $this->flashError('Utilisateur introuvable.');
            $this->redirect('/saas-admin/utilisateurs');
        }

        $this->renderSaas('access/user-edit', [
            'pageTitle' => 'Modifier les acces',
            'activeMenu' => 'saas-users',
            'user' => $user,
            'shops' => $this->repo->shopsWithMetrics(),
            'roles' => $this->repo->roles(),
        ]);
    }

    public function updateUser(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        try {
            $this->repo->updateUserAccess($id, $_POST);
            $this->flashSuccess('Acces utilisateur mis a jour.');
        } catch (InvalidArgumentException $exception) {
            $this->flashError($exception->getMessage());
        }
        $this->redirect('/saas-admin/utilisateurs');
    }

    public function roles(array $params = []): void
    {
        $this->renderSaas('access/roles', [
            'pageTitle' => 'Roles et droits',
            'activeMenu' => 'saas-rights',
            'roles' => $this->repo->roles(),
            'permissions' => $this->repo->permissionsCatalog(),
        ]);
    }

    public function createRole(array $params = []): void
    {
        $this->renderSaas('access/role-create', [
            'pageTitle' => 'Nouveau role',
            'activeMenu' => 'saas-rights',
            'permissions' => $this->repo->permissionsCatalog(),
        ]);
    }

    public function storeRole(array $params = []): void
    {
        $name = trim((string) ($_POST['nom'] ?? ''));

        if ($name === '') {
            $this->flashError('Le nom du role est obligatoire.');
            $this->redirect('/saas-admin/droits/create');
        }

        try {
            $allowed = $this->repo->permissionsCatalog();
            $selected = is_array($_POST['permissions'] ?? null) ? $_POST['permissions'] : [];
            $payload = [];

            foreach ($selected as $permission) {
                $permission = (string) $permission;
                if (isset($allowed[$permission])) {
                    $payload[$permission] = true;
                }
            }

            $this->repo->createRole(['nom' => $name, 'permissions' => $payload]);
            $this->flashSuccess('Role cree avec ses permissions.');
            $this->redirect('/saas-admin/droits');
        } catch (Throwable $exception) {
            $this->flashError('Creation du role impossible: ' . $exception->getMessage());
            $this->redirect('/saas-admin/droits/create');
        }
    }
}
