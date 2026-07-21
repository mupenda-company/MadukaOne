<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';
require_once dirname(__DIR__) . '/Models/Role.php';
require_once dirname(__DIR__) . '/Core/ModuleRegistry.php';
require_once dirname(__DIR__) . '/Core/SubscriptionGate.php';

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
        $shopId = $this->currentShopId();
        $planModules = (new ModuleRegistry())->enabledForShop($shopId);

        $this->render('users/role-create', [
            'pageTitle' => 'Ajouter un role',
            'activeMenu' => 'roles',
            'permissionGroups' => $this->permissionGroups($planModules),
            'planModules' => $planModules,
            'planSubscription' => (new SubscriptionGate())->currentForShop($shopId),
            'roles' => $roles,
            'roleStats' => $this->roleStats($roles),
        ]);
    }

    public function store(array $params = []): void
    {
        $existingPermissions = $this->permissionKeys($role['permissions'] ?? null);
        $isSystemRole = in_array('all', $existingPermissions, true);
        $name = $isSystemRole
            ? (string) ($role['nom'] ?? '')
            : trim((string) ($_POST['nom'] ?? ''));

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

        $planModules = (new ModuleRegistry())->enabledForShop($this->currentShopId());
        $permissions = $this->permissionsPayload($_POST['permissions'] ?? [], $planModules);

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

    public function edit(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $role = $this->roles->findWithUsage($id);

        if ($role === null) {
            $this->flashError('Rôle introuvable ou réservé à l’administration SaaS.');
            $this->redirect('/roles');
        }

        $roles = $this->roles->allWithUsage();
        $shopId = $this->currentShopId();
        $planModules = (new ModuleRegistry())->enabledForShop($shopId);

        $this->render('users/role-create', [
            'pageTitle' => 'Modifier un rôle',
            'activeMenu' => 'roles',
            'permissionGroups' => $this->permissionGroups($planModules),
            'planModules' => $planModules,
            'planSubscription' => (new SubscriptionGate())->currentForShop($shopId),
            'roles' => $roles,
            'roleStats' => $this->roleStats($roles),
            'editingRole' => $role,
        ]);
    }

    public function update(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $role = $this->roles->findWithUsage($id);

        if ($role === null) {
            $this->flashError('Rôle introuvable ou réservé à l’administration SaaS.');
            $this->redirect('/roles');
        }

        $name = trim((string) ($_POST['nom'] ?? ''));
        if ($name === '' || mb_strlen($name) > 50) {
            $this->flashError($name === '' ? 'Le nom du rôle est obligatoire.' : 'Le nom du rôle ne peut pas dépasser 50 caractères.');
            $this->redirect('/roles/' . $id . '/edit');
        }
        if ($this->roles->isSaasRoleName($name)) {
            $this->flashError('Ce rôle est réservé à l’espace de gestion SaaS.');
            $this->redirect('/roles/' . $id . '/edit');
        }

        $planModules = (new ModuleRegistry())->enabledForShop($this->currentShopId());
        $permissions = $this->permissionsPayload($_POST['permissions'] ?? [], $planModules);
        if ($isSystemRole) {
            $permissions = ['all' => true] + $permissions;
        }

        try {
            $payload = json_encode($permissions, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $changed = $this->roles->update($id, ['nom' => $name, 'permissions' => $payload]);

            if (!$changed) {
                $freshRole = $this->roles->findWithUsage($id);
                if ($freshRole === null || (string) $freshRole['nom'] !== $name || (string) $freshRole['permissions'] !== $payload) {
                    throw new RuntimeException('La mise à jour n’a pas été appliquée.');
                }
            }

            $this->flashSuccess('Rôle modifié avec succès.');
            $this->redirect('/roles');
        } catch (Throwable $exception) {
            $message = str_contains(strtolower($exception->getMessage()), 'duplicate')
                ? 'Un rôle portant ce nom existe déjà.'
                : 'Impossible de modifier le rôle : ' . $exception->getMessage();
            $this->flashError($message);
            $this->redirect('/roles/' . $id . '/edit');
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

    private function permissionsPayload(mixed $selectedPermissions, array $planModules): array
    {
        $selectedPermissions = is_array($selectedPermissions) ? $selectedPermissions : [];
        $allowed = [];

        foreach ($this->permissionGroups($planModules) as $group) {
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

    private function permissionGroups(array $planModules): array
    {
        $groups = [
            [
                'label' => 'Pilotage',
                'items' => [
                    'sales_view' => ['label' => 'Voir les ventes', 'modules' => ['pos']],
                    'reports_view' => ['label' => 'Voir les rapports', 'modules' => ['reports']],
                ],
            ],
            [
                'label' => 'Operations',
                'items' => [
                    'pos_access' => ['label' => 'Acceder a la caisse POS', 'modules' => ['pos']],
                    'products_manage' => ['label' => 'Gerer les produits', 'modules' => ['stock']],
                    'stock_adjust' => ['label' => 'Ajuster le stock', 'modules' => ['stock']],
                    'supplies_manage' => ['label' => 'Gerer les approvisionnements', 'modules' => ['supplies']],
                ],
            ],
            [
                'label' => 'Administration',
                'items' => [
                    'expenses_add' => ['label' => 'Ajouter des depenses', 'modules' => ['finance']],
                ],
            ],
        ];

        $enabledCodes = array_fill_keys(array_map(static fn (array $module): string => (string) ($module['code'] ?? ''), $planModules), true);
        $result = [];
        foreach ($groups as $group) {
            $items = [];
            foreach ($group['items'] as $permission => $definition) {
                foreach ($definition['modules'] as $moduleCode) {
                    if (isset($enabledCodes[$moduleCode])) {
                        $items[$permission] = $definition['label'];
                        break;
                    }
                }
            }
            if ($items !== []) {
                $result[] = ['label' => $group['label'], 'items' => $items];
            }
        }

        return $result;
    }
}
