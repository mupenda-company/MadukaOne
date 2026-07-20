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
