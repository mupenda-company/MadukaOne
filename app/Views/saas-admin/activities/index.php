<?php
$auditLogs = is_array($auditLogs ?? null) ? $auditLogs : [];
$auditFilters = is_array($auditFilters ?? null) ? $auditFilters : [];
$filterOptions = is_array($filterOptions ?? null) ? $filterOptions : [];
$auditStats = is_array($auditStats ?? null) ? $auditStats : [];
$safe = static fn ($value, string $fallback = '—'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$selected = static fn (string $key, string|int $value): string => (string) ($auditFilters[$key] ?? '') === (string) $value ? 'selected' : '';
$moduleLabel = static fn (string $module): string => ucfirst(str_replace('_', ' ', $module));
?>
<section class="space-y-5" data-audit-history>
    <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-slate-950 via-blue-950 to-teal-800 p-6 text-white shadow-xl sm:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-xs font-black uppercase tracking-[.2em] text-teal-300">Traçabilité SaaS</p>
                <h1 class="mt-3 text-3xl font-bold sm:text-4xl">Historique global des activités</h1>
                <p class="mt-3 text-sm leading-7 text-slate-300">Suivez les consultations et opérations effectuées dans toutes les boutiques, avec l’utilisateur, la catégorie et le contexte de chaque action.</p>
            </div>
            <span class="w-fit rounded-xl border border-white/15 bg-white/10 px-4 py-3 text-sm font-bold">300 dernières entrées maximum</span>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="stat-card"><p class="text-sm text-slate-500">Événements enregistrés</p><p class="mt-2 text-2xl font-black"><?= (int) ($auditStats['total'] ?? 0) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Aujourd’hui</p><p class="mt-2 text-2xl font-black text-teal-700"><?= (int) ($auditStats['today'] ?? 0) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Boutiques suivies</p><p class="mt-2 text-2xl font-black text-blue-700"><?= (int) ($auditStats['shops'] ?? 0) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Utilisateurs actifs</p><p class="mt-2 text-2xl font-black text-violet-700"><?= (int) ($auditStats['users'] ?? 0) ?></p></article>
    </div>

    <form class="surface-panel" method="get" action="<?= $url('/saas-admin/activites') ?>">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-lg font-bold">Filtres avancés</h2><p class="mt-1 text-sm text-slate-500">Croisez boutique, catégorie, utilisateur, module et période.</p></div><a class="btn-secondary w-full sm:w-auto" href="<?= $url('/saas-admin/activites') ?>">Réinitialiser</a></div>
        <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <label class="grid gap-2 text-xs font-bold uppercase tracking-wider text-slate-500"><span>Boutique</span><select class="field-control text-sm normal-case" name="shop_id"><option value="0">Toutes les boutiques</option><?php foreach (($filterOptions['shops'] ?? []) as $shop): ?><option value="<?= (int) $shop['id'] ?>" <?= $selected('shop_id', (int) $shop['id']) ?>><?= $safe($shop['nom']) ?></option><?php endforeach; ?></select></label>
            <label class="grid gap-2 text-xs font-bold uppercase tracking-wider text-slate-500"><span>Catégorie</span><select class="field-control text-sm normal-case" name="category_id"><option value="0">Toutes les catégories</option><?php foreach (($filterOptions['categories'] ?? []) as $category): ?><option value="<?= (int) $category['id'] ?>" <?= $selected('category_id', (int) $category['id']) ?>><?= $safe($category['nom']) ?></option><?php endforeach; ?></select></label>
            <label class="grid gap-2 text-xs font-bold uppercase tracking-wider text-slate-500"><span>Utilisateur</span><select class="field-control text-sm normal-case" name="user_id"><option value="0">Tous les utilisateurs</option><?php foreach (($filterOptions['users'] ?? []) as $user): ?><option value="<?= (int) $user['id'] ?>" <?= $selected('user_id', (int) $user['id']) ?>><?= $safe($user['nom'] ?? $user['email']) ?></option><?php endforeach; ?></select></label>
            <label class="grid gap-2 text-xs font-bold uppercase tracking-wider text-slate-500"><span>Module</span><select class="field-control text-sm normal-case" name="module"><option value="">Tous les modules</option><?php foreach (($filterOptions['modules'] ?? []) as $module): ?><option value="<?= $safe($module, '') ?>" <?= $selected('module', $module) ?>><?= $safe($moduleLabel((string) $module)) ?></option><?php endforeach; ?></select></label>
            <label class="grid gap-2 text-xs font-bold uppercase tracking-wider text-slate-500"><span>Date début</span><input class="field-control text-sm normal-case" type="date" name="date_from" value="<?= $safe($auditFilters['date_from'] ?? '', '') ?>"></label>
            <label class="grid gap-2 text-xs font-bold uppercase tracking-wider text-slate-500"><span>Date fin</span><input class="field-control text-sm normal-case" type="date" name="date_to" value="<?= $safe($auditFilters['date_to'] ?? '', '') ?>"></label>
            <label class="grid gap-2 text-xs font-bold uppercase tracking-wider text-slate-500"><span>Type de requête</span><select class="field-control text-sm normal-case" name="method"><option value="">Consultations et actions</option><option value="GET" <?= $selected('method', 'GET') ?>>Consultations (GET)</option><option value="POST" <?= $selected('method', 'POST') ?>>Actions (POST)</option></select></label>
            <label class="grid gap-2 text-xs font-bold uppercase tracking-wider text-slate-500"><span>Recherche</span><input class="field-control text-sm normal-case" name="search" value="<?= $safe($auditFilters['search'] ?? '', '') ?>" placeholder="Action, page, personne..."></label>
        </div>
        <button class="btn-primary mt-4 sm:w-auto sm:px-10" type="submit">Appliquer les filtres</button>
    </form>

    <section class="surface-panel overflow-hidden p-0">
        <div class="flex flex-col gap-2 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-lg font-bold">Journal des mouvements</h2><p class="mt-1 text-sm text-slate-500"><?= count($auditLogs) ?> résultat(s) selon les filtres actifs.</p></div><span class="w-fit rounded-full bg-teal-50 px-3 py-1.5 text-xs font-bold text-teal-700">Journal automatique</span></div>
        <div class="overflow-x-auto">
            <table class="min-w-[1100px] w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-4 py-3">Date et heure</th><th class="px-4 py-3">Boutique / catégorie</th><th class="px-4 py-3">Utilisateur</th><th class="px-4 py-3">Activité</th><th class="px-4 py-3">Module</th><th class="px-4 py-3">Page</th><th class="px-4 py-3">Origine</th></tr></thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <?php foreach ($auditLogs as $log): $isAction = ($log['methode'] ?? 'GET') === 'POST'; ?>
                        <tr class="align-top hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-4 py-4"><p class="font-bold text-slate-900"><?= $safe(date('d/m/Y', strtotime((string) $log['created_at']))) ?></p><p class="mt-1 text-xs text-slate-500"><?= $safe(date('H:i:s', strtotime((string) $log['created_at']))) ?></p></td>
                            <td class="px-4 py-4"><p class="font-bold text-slate-900"><?= $safe($log['shop_name'] ?? null, 'Administration SaaS') ?></p><p class="mt-1 text-xs text-amber-700"><?= $safe($log['category_name'] ?? null, 'Global') ?></p></td>
                            <td class="px-4 py-4"><p class="font-semibold text-slate-900"><?= $safe($log['user_name'] ?? null, 'Utilisateur supprimé') ?></p><p class="mt-1 text-xs text-slate-500"><?= $safe($log['user_email'] ?? '') ?></p></td>
                            <td class="px-4 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-bold <?= $isAction ? 'bg-violet-50 text-violet-700' : 'bg-blue-50 text-blue-700' ?>"><?= $safe($log['methode']) ?></span><p class="mt-2 font-semibold text-slate-800"><?= $safe($log['action']) ?></p></td>
                            <td class="px-4 py-4"><span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700"><?= $safe($moduleLabel((string) $log['module'])) ?></span></td>
                            <td class="max-w-xs px-4 py-4"><code class="break-all text-xs text-slate-600"><?= $safe($log['chemin']) ?></code></td>
                            <td class="px-4 py-4"><p class="font-mono text-xs text-slate-700"><?= $safe($log['ip_address'] ?? null) ?></p><p class="mt-2 max-w-48 truncate text-xs text-slate-400" title="<?= $safe($log['user_agent'] ?? '') ?>"><?= $safe($log['user_agent'] ?? null, 'Non renseigné') ?></p></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($auditLogs === []): ?><tr><td colspan="7" class="px-6 py-16 text-center"><p class="font-bold text-slate-700">Aucune activité ne correspond aux filtres.</p><p class="mt-2 text-sm text-slate-500">Les nouvelles opérations authentifiées apparaîtront automatiquement ici.</p></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
