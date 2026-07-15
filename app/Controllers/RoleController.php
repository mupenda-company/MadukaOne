<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';
require_once dirname(__DIR__) . '/Models/Role.php';

class RoleController extends AppController
{
    private Role $roles;

    public function __construct()
    {
        $this->roles = new Role();
    }

    public function index(array $params = []): void
    {
        $roles = $this->roles->allWithUsage();

        $this->render('users/roles', [
            'pageTitle' => 'Roles et permissions',
            'activeMenu' => 'roles',
            'roles' => $roles,
            'roleStats' => $this->roleStats($roles),
        ]);
    }

    public function create(array $params = []): void
    {
        $roles = $this->roles->allWithUsage();

        $this->render('users/role-create', [
            'pageTitle' => 'Ajouter un role',
            'activeMenu' => 'roles',
            'permissionGroups' => $this->permissionGroups(),
            'roles' => $roles,
            'roleStats' => $this->roleStats($roles),
        ]);
    }

    public function store(array $params = []): void
    {
        $name = trim((string) ($_POST['nom'] ?? ''));

        if ($name === '') {
            $this->flashError('Le nom du role est obligatoire.');
            $this->redirect('/roles/create');
        }

        if (mb_strlen($name) > 50) {
            $this->flashError('Le nom du role ne peut pas depasser 50 caracteres.');
            $this->redirect('/roles/create');
        }

        if ($this->roles->isSaasRoleName($name)) {
            $this->flashError('Ce role est reserve a l espace de gestion SaaS.');
            $this->redirect('/roles/create');
        }

        $permissions = $this->permissionsPayload($_POST['permissions'] ?? []);

        try {
            $this->roles->create([
                'nom' => $name,
                'permissions' => json_encode($permissions, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ]);

            $this->flashSuccess('Role cree avec succes.');
            $this->redirect('/roles');
        } catch (Throwable $exception) {
            $this->flashError('Impossible de creer le role: ' . $exception->getMessage());
            $this->redirect('/roles/create');
        }
    }

    private function roleStats(array $roles): array
    {
        $permissions = [];
        $assignedUsers = 0;

        foreach ($roles as $role) {
            $assignedUsers += (int) ($role['users_count'] ?? 0);
            foreach ($this->permissionKeys($role['permissions'] ?? null) as $permission) {
                $permissions[$permission] = true;
            }
        }

        return [
            'total' => count($roles),
            'permissions' => count($permissions),
            'assigned_users' => $assignedUsers,
        ];
    }

    private function permissionKeys(mixed $rawPermissions): array
    {
        $rawPermissions = trim((string) ($rawPermissions ?? ''));

        if ($rawPermissions === '') {
            return [];
        }

        $decoded = json_decode($rawPermissions, true);

        if (!is_array($decoded)) {
            return [$rawPermissions];
        }

        return array_keys(array_filter($decoded, static fn ($value): bool => (bool) $value));
    }

    private function permissionsPayload(mixed $selectedPermissions): array
    {
        $selectedPermissions = is_array($selectedPermissions) ? $selectedPermissions : [];
        $allowed = [];

        foreach ($this->permissionGroups() as $group) {
            foreach ($group['items'] as $permission => $label) {
                $allowed[$permission] = true;
            }
        }

        $payload = [];

        foreach ($selectedPermissions as $permission) {
            $permission = trim((string) $permission);

            if (isset($allowed[$permission])) {
                $payload[$permission] = true;
            }
        }

        return $payload;
    }

    private function permissionGroups(): array
    {
        return [
            [
                'label' => 'Pilotage',
                'items' => [
                    'all' => 'Acces complet',
                    'sales_view' => 'Voir les ventes',
                    'reports_view' => 'Voir les rapports',
                ],
            ],
            [
                'label' => 'Operations',
                'items' => [
                    'pos_access' => 'Acceder a la caisse POS',
                    'products_manage' => 'Gerer les produits',
                    'stock_adjust' => 'Ajuster le stock',
                    'supplies_manage' => 'Gerer les approvisionnements',
                ],
            ],
            [
                'label' => 'Administration',
                'items' => [
                    'expenses_add' => 'Ajouter des depenses',
                    'users_manage' => 'Gerer les utilisateurs',
                    'roles_manage' => 'Gerer les roles et permissions',
                ],
            ],
        ];
    }
}
