<?php
$roles = is_array($roles ?? null) ? $roles : [];
$permissions = is_array($permissions ?? null) ? $permissions : [];
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$permissionKeys = static function ($raw): array {
    $decoded = json_decode((string) ($raw ?? ''), true);

    return is_array($decoded) ? array_keys(array_filter($decoded)) : [];
};
$isSaasRole = static function ($name): bool {
    $normalized = strtolower(trim((string) $name));
    $normalized = str_replace(['-', '_'], ' ', $normalized);
    $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

    return in_array($normalized, ['super admin', 'superadmin', 'super administrateur'], true);
};

$saasRoles = [];
$shopRoles = [];
$permissionUsage = [];
$assignedRoles = 0;

foreach ($roles as $role) {
    $keys = $permissionKeys($role['permissions'] ?? '');

    if ((int) ($role['users_count'] ?? 0) > 0) {
        $assignedRoles++;
    }

    foreach ($keys as $key) {
        $permissionUsage[$key] = true;
    }

    if ($isSaasRole($role['nom'] ?? '')) {
        $saasRoles[] = $role;
    } else {
        $shopRoles[] = $role;
    }
}

$roleGroups = [
    [
        'key' => 'saas',
        'title' => 'Roles SaaS reserves',
        'description' => 'Roles globaux geres uniquement par l administration SaaS.',
        'badge' => 'Global',
        'roles' => $saasRoles,
    ],
    [
        'key' => 'shop',
        'title' => 'Roles boutique attribuables',
        'description' => 'Roles disponibles pour les utilisateurs rattaches aux boutiques.',
        'badge' => 'Boutique',
        'roles' => $shopRoles,
    ],
];
?>
<section class="space-y-5" data-saas-roles>
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Droits</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Roles et permissions</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">Vue separee des roles SaaS reserves et des roles attribuables aux utilisateurs des boutiques.</p>
        </div>
        <a class="btn-primary w-full sm:w-auto" href="<?= $url('/saas-admin/droits/create') ?>">Nouveau role</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <article class="stat-card"><p class="text-sm text-slate-500">Roles</p><p class="mt-2 text-2xl font-bold"><?= count($roles) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">SaaS reserves</p><p class="mt-2 text-2xl font-bold text-amber-700"><?= count($saasRoles) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Boutique</p><p class="mt-2 text-2xl font-bold text-teal-700"><?= count($shopRoles) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Attribues</p><p class="mt-2 text-2xl font-bold text-blue-700"><?= $assignedRoles ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Permissions</p><p class="mt-2 text-2xl font-bold"><?= count($permissionUsage) ?></p></article>
    </div>

    <section class="surface-panel">
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <h2 class="font-bold text-slate-950">Filtres avances</h2>
                    <p class="mt-1 text-sm text-slate-500"><span data-visible-roles-count><?= count($roles) ?></span> role(s) affiche(s), <span data-visible-groups-count>2</span> groupe(s).</p>
                </div>
                <button class="btn-secondary h-10 w-full px-4 sm:w-auto" type="button" data-reset-filters>Reinitialiser</button>
            </div>

            <div class="grid gap-3 lg:grid-cols-4">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700" for="role-search">Recherche</label>
                    <input class="field-control" id="role-search" type="search" placeholder="Nom, permission..." data-filter-search>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700" for="scope-filter">Perimetre</label>
                    <select class="field-control" id="scope-filter" data-filter-scope>
                        <option value="all">Tous les perimetres</option>
                        <option value="saas">Roles SaaS reserves</option>
                        <option value="shop">Roles boutique</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700" for="permission-filter">Permission</label>
                    <select class="field-control" id="permission-filter" data-filter-permission>
                        <option value="all">Toutes les permissions</option>
                        <?php foreach ($permissions as $code => $label): ?>
                            <option value="<?= $safe((string) $code) ?>"><?= $safe($label) ?></option>
                        <?php endforeach; ?>
                        <option value="none">Sans permission</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700" for="usage-filter">Affectation</label>
                    <select class="field-control" id="usage-filter" data-filter-usage>
                        <option value="all">Tous les roles</option>
                        <option value="assigned">Avec utilisateurs</option>
                        <option value="unassigned">Sans utilisateur</option>
                    </select>
                </div>
            </div>
        </div>
    </section>

    <div class="space-y-4" data-role-groups>
        <?php foreach ($roleGroups as $group): ?>
            <section class="surface-panel" data-role-group data-scope="<?= $safe($group['key']) ?>">
                <div class="panel-header">
                    <div class="min-w-0">
                        <h2 class="font-bold text-slate-950"><?= $safe($group['title']) ?></h2>
                        <p class="mt-1 text-sm text-slate-500"><span data-group-visible-count><?= count($group['roles']) ?></span> role(s). <?= $safe($group['description']) ?></p>
                    </div>
                    <span class="rounded-lg <?= $group['key'] === 'saas' ? 'bg-amber-50 text-amber-700' : 'bg-teal-50 text-teal-700' ?> px-3 py-1 text-xs font-bold"><?= $safe($group['badge']) ?></span>
                </div>

                <div class="mt-4 grid gap-3 xl:grid-cols-2">
                    <?php foreach ($group['roles'] as $role): ?>
                        <?php
                            $roleKeys = $permissionKeys($role['permissions'] ?? '');
                            $permissionText = implode(' ', array_map(static fn ($key): string => (string) ($permissions[$key] ?? $key), $roleKeys));
                            $searchText = strtolower((string) ($role['nom'] ?? '') . ' ' . $permissionText);
                            $permissionValues = $roleKeys === [] ? 'none' : implode(' ', $roleKeys);
                            $usersCount = (int) ($role['users_count'] ?? 0);
                        ?>
                        <article
                            class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-teal-200 hover:shadow-md"
                            data-role-card
                            data-scope="<?= $safe($group['key']) ?>"
                            data-permissions="<?= $safe($permissionValues) ?>"
                            data-users-count="<?= $usersCount ?>"
                            data-search="<?= $safe($searchText) ?>"
                        >
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="truncate text-base font-bold text-slate-950"><?= $safe($role['nom'] ?? '') ?></h3>
                                        <span class="rounded-lg <?= $group['key'] === 'saas' ? 'bg-amber-50 text-amber-700' : 'bg-teal-50 text-teal-700' ?> px-2.5 py-1 text-xs font-bold"><?= $safe($group['badge']) ?></span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500"><?= $usersCount ?> utilisateur(s)</p>
                                </div>
                                <span class="rounded-lg bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700"><?= count($roleKeys) ?> droit(s)</span>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <?php foreach ($roleKeys as $key): ?>
                                    <span class="rounded-lg bg-teal-50 px-2.5 py-1 text-xs font-bold text-teal-700"><?= $safe($permissions[$key] ?? $key) ?></span>
                                <?php endforeach; ?>
                                <?php if ($roleKeys === []): ?>
                                    <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-500">Aucune permission specifique</span>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>

                    <?php if ($group['roles'] === []): ?>
                        <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm font-semibold text-slate-500">Aucun role dans ce groupe.</div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>

    <div class="hidden rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center" data-empty-roles>
        <p class="text-sm font-bold text-slate-700">Aucun role ne correspond aux filtres.</p>
        <p class="mt-1 text-sm text-slate-500">Modifiez la recherche, le perimetre, la permission ou l affectation.</p>
    </div>
</section>

<script>
const roleFilters = {
    search: document.querySelector('[data-filter-search]'),
    scope: document.querySelector('[data-filter-scope]'),
    permission: document.querySelector('[data-filter-permission]'),
    usage: document.querySelector('[data-filter-usage]'),
};
const roleCards = Array.from(document.querySelectorAll('[data-role-card]'));
const roleGroups = Array.from(document.querySelectorAll('[data-role-group]'));
const emptyRoles = document.querySelector('[data-empty-roles]');
const visibleRolesCount = document.querySelector('[data-visible-roles-count]');
const visibleGroupsCount = document.querySelector('[data-visible-groups-count]');
const resetRoleFilters = document.querySelector('[data-reset-filters]');

const applyRoleFilters = () => {
    const search = (roleFilters.search?.value || '').trim().toLowerCase();
    const scope = roleFilters.scope?.value || 'all';
    const permission = roleFilters.permission?.value || 'all';
    const usage = roleFilters.usage?.value || 'all';
    let visibleRoles = 0;
    let visibleGroups = 0;

    roleCards.forEach((card) => {
        const usersCount = Number(card.dataset.usersCount || '0');
        const permissions = card.dataset.permissions || 'none';
        const matchesSearch = search === '' || (card.dataset.search || '').includes(search);
        const matchesScope = scope === 'all' || card.dataset.scope === scope;
        const matchesPermission = permission === 'all' || (permission === 'none' ? permissions === 'none' : permissions.split(' ').includes(permission));
        const matchesUsage = usage === 'all' || (usage === 'assigned' && usersCount > 0) || (usage === 'unassigned' && usersCount === 0);
        const visible = matchesSearch && matchesScope && matchesPermission && matchesUsage;
        card.classList.toggle('hidden', !visible);

        if (visible) {
            visibleRoles++;
        }
    });

    roleGroups.forEach((group) => {
        const visibleInGroup = Array.from(group.querySelectorAll('[data-role-card]')).filter((card) => !card.classList.contains('hidden')).length;
        const scopeMatches = scope === 'all' || group.dataset.scope === scope;
        const visible = scopeMatches && visibleInGroup > 0;
        group.classList.toggle('hidden', !visible);
        group.querySelector('[data-group-visible-count]')?.replaceChildren(document.createTextNode(String(visibleInGroup)));

        if (visible) {
            visibleGroups++;
        }
    });

    visibleRolesCount?.replaceChildren(document.createTextNode(String(visibleRoles)));
    visibleGroupsCount?.replaceChildren(document.createTextNode(String(visibleGroups)));
    emptyRoles?.classList.toggle('hidden', visibleRoles > 0);
};

Object.values(roleFilters).forEach((field) => field?.addEventListener('input', applyRoleFilters));
Object.values(roleFilters).forEach((field) => field?.addEventListener('change', applyRoleFilters));
resetRoleFilters?.addEventListener('click', () => {
    if (roleFilters.search) roleFilters.search.value = '';
    if (roleFilters.scope) roleFilters.scope.value = 'all';
    if (roleFilters.permission) roleFilters.permission.value = 'all';
    if (roleFilters.usage) roleFilters.usage.value = 'all';
    applyRoleFilters();
});

applyRoleFilters();
</script>
