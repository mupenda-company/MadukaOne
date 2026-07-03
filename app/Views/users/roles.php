<?php

$roles = is_array($roles ?? null) ? $roles : [];
$roleStats = is_array($roleStats ?? null) ? $roleStats : [];

$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');

$permissionItems = static function ($rawPermissions): array {
    $rawPermissions = trim((string) ($rawPermissions ?? ''));

    if ($rawPermissions === '') {
        return [];
    }

    $decoded = json_decode($rawPermissions, true);

    if (!is_array($decoded)) {
        return [$rawPermissions => true];
    }

    return $decoded;
};

$formatDate = static function ($value): string {
    $timestamp = strtotime((string) ($value ?? ''));

    return $timestamp !== false ? date('d/m/Y', $timestamp) : '-';
};

$icon = static function (string $name): string {
    $paths = [
        'shield' => '<path d="M12 3 5 6v5c0 4.2 2.7 8 7 10 4.3-2 7-5.8 7-10V6l-7-3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'key' => '<path d="M15 7a4 4 0 1 0-2.7 3.8L15 13.5V16h2.5v2.5H20V16l-5-5m-6-1h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'users' => '<path d="M16 19c0-2.2-1.8-4-4-4H8c-2.2 0-4 1.8-4 4m12-7a3 3 0 1 0 0-6m4 13c0-1.9-1.3-3.5-3-3.9M10 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'edit' => '<path d="M4 20h4l10.5-10.5a2.8 2.8 0 0 0-4-4L4 16v4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="m13.5 6.5 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'plus' => '<path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'search' => '<path d="m21 21-4.3-4.3M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'filter' => '<path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
    ];

    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . ($paths[$name] ?? $paths['shield']) . '</svg>';
};
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Administration</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Roles et permissions</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Consultez les profils d'acces disponibles, leurs permissions et le nombre d'utilisateurs associes.
            </p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-[.16em] text-slate-400">Source</p>
            <p class="mt-1 font-bold text-slate-950">Table roles</p>
        </div>
        <a class="btn-primary w-full gap-2 sm:w-auto" href="<?= $url('/roles/create') ?>">
            <?= $icon('plus') ?>
            <span>Ajouter un role</span>
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <article class="stat-card">
            <p class="text-sm text-slate-500">Roles disponibles</p>
            <p class="mt-2 text-2xl font-bold"><?= (int) ($roleStats['total'] ?? count($roles)) ?></p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Permissions uniques</p>
            <p class="mt-2 text-2xl font-bold text-teal-700"><?= (int) ($roleStats['permissions'] ?? 0) ?></p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Utilisateurs assignes</p>
            <p class="mt-2 text-2xl font-bold"><?= (int) ($roleStats['assigned_users'] ?? 0) ?></p>
        </article>
    </div>

    <section class="surface-panel" data-roles-page>
        <div class="panel-header gap-4">
            <div>
                <h2 class="font-bold text-slate-950">Matrice roles / permissions</h2>
                <p class="mt-1 text-sm text-slate-500">Vue de controle des permissions stockees en JSON dans la base.</p>
            </div>
            <span class="grid h-10 w-10 place-items-center rounded-lg bg-teal-50 text-teal-700"><?= $icon('key') ?></span>
        </div>

        <div class="mt-5 grid gap-3 lg:grid-cols-[1fr_16rem_13rem]">
            <label class="relative">
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"><?= $icon('search') ?></span>
                <input class="field-control pl-11" type="search" data-role-search placeholder="Rechercher role ou permission">
            </label>

            <label class="relative">
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"><?= $icon('filter') ?></span>
                <select class="field-control pl-11" data-role-assignment-filter>
                    <option value="all">Tous les roles</option>
                    <option value="assigned">Avec utilisateurs</option>
                    <option value="unassigned">Sans utilisateur</option>
                </select>
            </label>

            <label class="relative">
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"><?= $icon('key') ?></span>
                <select class="field-control pl-11" data-role-permission-filter>
                    <option value="all">Toutes permissions</option>
                    <option value="with_permissions">Avec permissions</option>
                    <option value="without_permissions">Sans permission</option>
                </select>
            </label>
        </div>

        <div class="mt-5 grid gap-4" data-roles-list>
            <?php if ($roles === []): ?>
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                    Aucun role trouve dans la table roles.
                </div>
            <?php endif; ?>

            <?php foreach ($roles as $role): ?>
                <?php $permissions = $permissionItems($role['permissions'] ?? null); ?>
                <?php
                    $usersCount = (int) ($role['users_count'] ?? 0);
                    $permissionsText = implode(' ', array_map('strval', array_keys($permissions)));
                    $searchText = strtolower((string) ($role['nom'] ?? '') . ' ' . $permissionsText);
                ?>
                <article
                    class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"
                    data-role-card
                    data-search="<?= $safe($searchText) ?>"
                    data-users-count="<?= $usersCount ?>"
                    data-permissions-count="<?= count($permissions) ?>"
                >
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex items-center gap-3">
                                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-slate-950 text-white"><?= $icon('shield') ?></span>
                                <div class="min-w-0">
                                    <h3 class="truncate text-lg font-bold text-slate-950"><?= $safe($role['nom'] ?? 'Role') ?></h3>
                                    <p class="mt-1 text-sm text-slate-500">Cree le <?= $safe($formatDate($role['created_at'] ?? null)) ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                                <?= $icon('users') ?>
                                <?= $usersCount ?> utilisateur(s)
                            </span>
                            <button
                                class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                type="button"
                                data-role-pending-action
                            >
                                <?= $icon('edit') ?>
                                <span>Modifier</span>
                            </button>
                        </div>
                    </div>

                    <div class="mt-4">
                        <p class="mb-2 text-xs font-bold uppercase tracking-[.16em] text-slate-400">Permissions</p>
                        <?php if ($permissions === []): ?>
                            <p class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500">Aucune permission renseignee.</p>
                        <?php else: ?>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($permissions as $permission => $enabled): ?>
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold <?= (bool) $enabled ? 'bg-teal-50 text-teal-700' : 'bg-slate-100 text-slate-500' ?>">
                                        <?= $safe((string) $permission) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>

            <div class="hidden rounded-lg border border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500" data-role-empty-state>
                Aucun role ne correspond aux filtres selectionnes.
            </div>
        </div>
    </section>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var page = document.querySelector('[data-roles-page]');
    var search = page?.querySelector('[data-role-search]');
    var assignmentFilter = page?.querySelector('[data-role-assignment-filter]');
    var permissionFilter = page?.querySelector('[data-role-permission-filter]');
    var cards = Array.prototype.slice.call(page?.querySelectorAll('[data-role-card]') || []);
    var emptyState = page?.querySelector('[data-role-empty-state]');

    var applyFilters = function () {
        var query = (search?.value || '').trim().toLowerCase();
        var assignment = assignmentFilter?.value || 'all';
        var permission = permissionFilter?.value || 'all';
        var visibleCount = 0;

        cards.forEach(function (card) {
            var text = card.getAttribute('data-search') || '';
            var usersCount = Number(card.getAttribute('data-users-count') || '0');
            var permissionsCount = Number(card.getAttribute('data-permissions-count') || '0');
            var matchesSearch = query === '' || text.indexOf(query) !== -1;
            var matchesAssignment = assignment === 'all'
                || (assignment === 'assigned' && usersCount > 0)
                || (assignment === 'unassigned' && usersCount === 0);
            var matchesPermission = permission === 'all'
                || (permission === 'with_permissions' && permissionsCount > 0)
                || (permission === 'without_permissions' && permissionsCount === 0);
            var visible = matchesSearch && matchesAssignment && matchesPermission;

            card.hidden = !visible;
            if (visible) {
                visibleCount++;
            }
        });

        if (emptyState) {
            emptyState.classList.toggle('hidden', visibleCount !== 0);
        }
    };

    search?.addEventListener('input', applyFilters);
    assignmentFilter?.addEventListener('change', applyFilters);
    permissionFilter?.addEventListener('change', applyFilters);

    document.querySelectorAll('[data-role-pending-action]').forEach(function (button) {
        button.addEventListener('click', function () {
            window.alert('Modification des roles : interface prete. Il reste a raccorder les routes backend de gestion.');
        });
    });
});
</script>
