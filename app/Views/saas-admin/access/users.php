<?php
$users = is_array($users ?? null) ? $users : [];
$shops = is_array($shops ?? null) ? $shops : [];
$roles = is_array($roles ?? null) ? $roles : [];
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$userName = static fn (array $user): string => trim((string) (($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''))) ?: 'Utilisateur';
$roleName = static fn (array $user): string => (string) (($user['role_name'] ?? '') !== '' ? $user['role_name'] : ($user['role_legacy'] ?? 'Role non defini'));
$authLabel = static fn (array $user): string => match ((string) ($user['auth_provider'] ?? 'local')) {
    'google' => 'Google',
    'apple' => 'Apple',
    default => (($user['email'] ?? '') !== '' ? 'Local' : 'Invitation'),
};

$activeCount = 0;
$inactiveCount = 0;
$globalUsers = [];
$usersByShop = [];

foreach ($shops as $shop) {
    $usersByShop[(int) ($shop['id'] ?? 0)] = [
        'shop' => $shop,
        'users' => [],
    ];
}

foreach ($users as $user) {
    if ((int) ($user['actif'] ?? 0) === 1) {
        $activeCount++;
    } else {
        $inactiveCount++;
    }

    $shopId = (int) ($user['shop_id'] ?? 0);

    if ($shopId > 0) {
        if (!isset($usersByShop[$shopId])) {
            $usersByShop[$shopId] = [
                'shop' => ['id' => $shopId, 'nom' => $user['shop_name'] ?? 'Boutique inconnue', 'actif' => 1],
                'users' => [],
            ];
        }

        $usersByShop[$shopId]['users'][] = $user;
        continue;
    }

    $globalUsers[] = $user;
}

$roleOptions = [];
foreach ($roles as $role) {
    $name = trim((string) ($role['nom'] ?? ''));
    if ($name !== '') {
        $roleOptions[$name] = true;
    }
}
?>
<section class="space-y-5" data-saas-users>
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Acces</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Utilisateurs par boutique</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">Vue separee par boutique pour controler les comptes, les roles, les statuts et les acces globaux SaaS.</p>
        </div>
        <a class="btn-secondary" href="<?= $url('/saas-admin/droits') ?>">Voir les droits</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="stat-card"><p class="text-sm text-slate-500">Utilisateurs</p><p class="mt-2 text-2xl font-bold"><?= count($users) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Actifs</p><p class="mt-2 text-2xl font-bold text-teal-700"><?= $activeCount ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Suspendus</p><p class="mt-2 text-2xl font-bold text-red-700"><?= $inactiveCount ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Boutiques</p><p class="mt-2 text-2xl font-bold text-blue-700"><?= count($shops) ?></p></article>
    </div>

    <section class="surface-panel">
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <h2 class="font-bold text-slate-950">Filtres avances</h2>
                    <p class="mt-1 text-sm text-slate-500"><span data-visible-users-count><?= count($users) ?></span> utilisateur(s) affiche(s), <span data-visible-groups-count><?= count($usersByShop) + ($globalUsers === [] ? 0 : 1) ?></span> groupe(s).</p>
                </div>
                <button class="btn-secondary h-10 w-full px-4 sm:w-auto" type="button" data-reset-filters>Reinitialiser</button>
            </div>

            <div class="grid gap-3 lg:grid-cols-4">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700" for="user-search">Recherche</label>
                    <input class="field-control" id="user-search" type="search" placeholder="Nom, email, role..." data-filter-search>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700" for="shop-filter">Boutique</label>
                    <select class="field-control" id="shop-filter" data-filter-shop>
                        <option value="all">Toutes les boutiques</option>
                        <option value="global">Comptes globaux SaaS</option>
                        <?php foreach ($shops as $shop): ?>
                            <option value="<?= (int) ($shop['id'] ?? 0) ?>"><?= $safe($shop['nom'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700" for="role-filter">Role</label>
                    <select class="field-control" id="role-filter" data-filter-role>
                        <option value="all">Tous les roles</option>
                        <?php foreach (array_keys($roleOptions) as $role): ?>
                            <option value="<?= $safe(strtolower($role)) ?>"><?= $safe($role) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700" for="status-filter">Statut</label>
                    <select class="field-control" id="status-filter" data-filter-status>
                        <option value="all">Tous les statuts</option>
                        <option value="active">Actifs</option>
                        <option value="inactive">Suspendus</option>
                        <option value="pending">Invitations non activees</option>
                    </select>
                </div>
            </div>
        </div>
    </section>

    <div class="space-y-4" data-user-groups>
        <?php if ($globalUsers !== []): ?>
            <section class="surface-panel" data-user-group data-shop-id="global">
                <div class="panel-header">
                    <div>
                        <h2 class="font-bold text-slate-950">Comptes globaux SaaS</h2>
                        <p class="mt-1 text-sm text-slate-500"><span data-group-visible-count><?= count($globalUsers) ?></span> utilisateur(s) avec acces multi-boutiques.</p>
                    </div>
                    <span class="rounded-lg bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">Global</span>
                </div>
                <div class="mt-4 grid gap-3 xl:grid-cols-2">
                    <?php foreach ($globalUsers as $user): ?>
                        <?php require __DIR__ . '/_user-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php foreach ($usersByShop as $shopId => $group): ?>
            <?php
                $shop = is_array($group['shop'] ?? null) ? $group['shop'] : [];
                $groupUsers = is_array($group['users'] ?? null) ? $group['users'] : [];
                $isShopActive = (int) ($shop['actif'] ?? 0) === 1;
            ?>
            <section class="surface-panel" data-user-group data-shop-id="<?= (int) $shopId ?>">
                <div class="panel-header">
                    <div class="min-w-0">
                        <h2 class="truncate font-bold text-slate-950"><?= $safe($shop['nom'] ?? 'Boutique') ?></h2>
                        <p class="mt-1 text-sm text-slate-500"><span data-group-visible-count><?= count($groupUsers) ?></span> utilisateur(s) dans cette boutique.</p>
                    </div>
                    <span class="rounded-lg px-3 py-1 text-xs font-bold <?= $isShopActive ? 'bg-teal-50 text-teal-700' : 'bg-red-50 text-red-700' ?>"><?= $isShopActive ? 'Boutique active' : 'Boutique suspendue' ?></span>
                </div>

                <div class="mt-4 grid gap-3 xl:grid-cols-2">
                    <?php foreach ($groupUsers as $user): ?>
                        <?php require __DIR__ . '/_user-card.php'; ?>
                    <?php endforeach; ?>
                    <?php if ($groupUsers === []): ?>
                        <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm font-semibold text-slate-500">Aucun utilisateur rattache a cette boutique.</div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>

    <div class="hidden rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center" data-empty-users>
        <p class="text-sm font-bold text-slate-700">Aucun utilisateur ne correspond aux filtres.</p>
        <p class="mt-1 text-sm text-slate-500">Modifiez la recherche, la boutique, le role ou le statut.</p>
    </div>
</section>

<script>
const filters = {
    search: document.querySelector('[data-filter-search]'),
    shop: document.querySelector('[data-filter-shop]'),
    role: document.querySelector('[data-filter-role]'),
    status: document.querySelector('[data-filter-status]'),
};
const userCards = Array.from(document.querySelectorAll('[data-user-card]'));
const groups = Array.from(document.querySelectorAll('[data-user-group]'));
const emptyUsers = document.querySelector('[data-empty-users]');
const visibleUsersCount = document.querySelector('[data-visible-users-count]');
const visibleGroupsCount = document.querySelector('[data-visible-groups-count]');
const resetButton = document.querySelector('[data-reset-filters]');

const applyFilters = () => {
    const search = (filters.search?.value || '').trim().toLowerCase();
    const shop = filters.shop?.value || 'all';
    const role = filters.role?.value || 'all';
    const status = filters.status?.value || 'all';
    let visibleUsers = 0;
    let visibleGroups = 0;

    userCards.forEach((card) => {
        const matchesSearch = search === '' || (card.dataset.search || '').includes(search);
        const matchesShop = shop === 'all' || card.dataset.shopId === shop;
        const matchesRole = role === 'all' || card.dataset.role === role;
        const matchesStatus = status === 'all' || card.dataset.status === status || (status === 'pending' && card.dataset.activation === 'pending');
        const visible = matchesSearch && matchesShop && matchesRole && matchesStatus;
        card.classList.toggle('hidden', !visible);

        if (visible) {
            visibleUsers++;
        }
    });

    groups.forEach((group) => {
        const visibleInGroup = Array.from(group.querySelectorAll('[data-user-card]')).filter((card) => !card.classList.contains('hidden')).length;
        const shopMatches = shop === 'all' || group.dataset.shopId === shop;
        const visible = shopMatches && (visibleInGroup > 0 || (search === '' && role === 'all' && status === 'all'));
        group.classList.toggle('hidden', !visible);
        group.querySelector('[data-group-visible-count]')?.replaceChildren(document.createTextNode(String(visibleInGroup)));

        if (visible) {
            visibleGroups++;
        }
    });

    visibleUsersCount?.replaceChildren(document.createTextNode(String(visibleUsers)));
    visibleGroupsCount?.replaceChildren(document.createTextNode(String(visibleGroups)));
    emptyUsers?.classList.toggle('hidden', visibleUsers > 0 || (shop !== 'all' && groups.some((group) => !group.classList.contains('hidden'))));
};

Object.values(filters).forEach((field) => field?.addEventListener('input', applyFilters));
Object.values(filters).forEach((field) => field?.addEventListener('change', applyFilters));
resetButton?.addEventListener('click', () => {
    if (filters.search) filters.search.value = '';
    if (filters.shop) filters.shop.value = 'all';
    if (filters.role) filters.role.value = 'all';
    if (filters.status) filters.status.value = 'all';
    applyFilters();
});

applyFilters();
</script>
