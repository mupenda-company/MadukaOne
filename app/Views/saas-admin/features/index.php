<?php
$features = is_array($features ?? null) ? $features : [];
$categories = is_array($categories ?? null) ? $categories : [];
$plans = is_array($plans ?? null) ? $plans : [];
$assignments = is_array($assignments ?? null) ? $assignments : [];
$shopAccessRows = is_array($shopAccessRows ?? null) ? $shopAccessRows : [];
$moduleCatalog = is_array($moduleCatalog ?? null) ? $moduleCatalog : [];
$planAssignments = is_array($assignments['plans'] ?? null) ? $assignments['plans'] : [];
$categoryPlanAssignments = is_array($assignments['category_plans'] ?? null) ? $assignments['category_plans'] : [];
$safe = static fn ($value, string $fallback = ''): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$activeCount = count(array_filter($features, static fn (array $feature): bool => (int) ($feature['actif'] ?? 0) === 1));
$assignedCount = count(array_filter($features, static fn (array $feature): bool => (int) ($feature['plans_count'] ?? 0) > 0));
$featuresByCode = [];
foreach ($features as $feature) { $featuresByCode[(string) ($feature['code'] ?? '')] = $feature; }
$groupLabels = ['ventes' => 'Ventes et clients', 'stock' => 'Stock et catalogue', 'achats' => 'Achats', 'pilotage' => 'Pilotage', 'finance' => 'Finance', 'metier' => 'Modules specialises'];
$toneByGroup = ['ventes' => 'from-teal-500 to-cyan-500', 'stock' => 'from-blue-500 to-indigo-500', 'achats' => 'from-amber-500 to-orange-500', 'pilotage' => 'from-violet-500 to-purple-500', 'finance' => 'from-emerald-500 to-teal-500', 'metier' => 'from-rose-500 to-pink-500'];
?>

<section class="space-y-5" data-features-page>
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-950 via-slate-900 to-amber-950 p-6 text-white shadow-xl sm:p-8">
        <div class="absolute -right-20 -top-24 h-64 w-64 rounded-full bg-amber-400/20 blur-3xl"></div>
        <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-xs font-bold uppercase tracking-[.18em] text-amber-300">Catalogue fonctionnel SaaS</p>
                <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Fonctionnalites exactes des modules</h1>
                <p class="mt-3 text-sm leading-6 text-slate-300">Inventaire des modules controles par les abonnements, de leurs capacites metier et de leurs affectations aux plans et categories.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <a class="btn-secondary w-full sm:w-auto" href="#plans">Configurer les plans</a>
                <a class="btn-primary w-full sm:w-auto" href="#catalogue">Voir les modules</a>
            </div>
        </div>
    </div>

    <nav class="flex gap-2 overflow-x-auto rounded-xl border border-slate-200 bg-white p-2 shadow-sm" aria-label="Sections fonctionnalites">
        <?php foreach ([['catalogue','Modules'],['new-feature','Ajouter'],['plans','Plans'],['categories','Categories'],['access','Acces boutiques']] as [$anchor,$label]): ?>
            <a class="whitespace-nowrap rounded-lg bg-slate-50 px-4 py-2 text-sm font-bold text-slate-600 transition hover:bg-amber-50 hover:text-amber-700" href="#<?= $anchor ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </nav>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="stat-card"><p class="text-sm text-slate-500">Modules controles</p><p class="mt-2 text-3xl font-black"><?= count($features) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Modules actifs</p><p class="mt-2 text-3xl font-black text-teal-700"><?= $activeCount ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Affectes a un plan</p><p class="mt-2 text-3xl font-black text-blue-700"><?= $assignedCount ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Capacites documentees</p><p class="mt-2 text-3xl font-black text-amber-700"><?= array_sum(array_map(static fn (array $module): int => count($module['capabilities'] ?? []), $moduleCatalog)) ?></p></article>
    </div>

    <section id="catalogue" class="scroll-mt-24 space-y-4">
        <div class="surface-panel">
            <div class="panel-header">
                <div><h2 class="text-xl font-black text-slate-950">Catalogue complet des modules</h2><p class="mt-1 text-sm text-slate-500">Chaque carte decrit les operations effectivement prises en charge par le module.</p></div>
                <input class="field-control w-full sm:max-w-xs" type="search" placeholder="Rechercher un module ou une capacite" data-module-search>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-2" data-module-list>
            <?php foreach ($features as $feature): ?>
                <?php
                $code = (string) ($feature['code'] ?? '');
                $catalog = is_array($moduleCatalog[$code] ?? null) ? $moduleCatalog[$code] : [];
                $capabilities = is_array($catalog['capabilities'] ?? null) ? $catalog['capabilities'] : [];
                $group = (string) ($feature['categorie'] ?? $catalog['group'] ?? 'general');
                $search = strtolower($code . ' ' . ($feature['nom'] ?? '') . ' ' . ($feature['description'] ?? '') . ' ' . implode(' ', $capabilities));
                $active = (int) ($feature['actif'] ?? 0) === 1;
                ?>
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg" data-module-card data-search="<?= $safe($search) ?>">
                    <div class="h-1.5 bg-gradient-to-r <?= $toneByGroup[$group] ?? 'from-slate-500 to-slate-700' ?>"></div>
                    <div class="p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2"><code class="rounded-lg bg-slate-950 px-2.5 py-1 text-xs font-bold text-white"><?= $safe($code) ?></code><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600"><?= $safe($groupLabels[$group] ?? ucfirst($group)) ?></span></div>
                                <h3 class="mt-3 text-lg font-black text-slate-950"><?= $safe($catalog['label'] ?? $feature['nom'] ?? $code) ?></h3>
                                <p class="mt-1 text-sm leading-6 text-slate-500"><?= $safe($feature['description'] ?? 'Description non configuree') ?></p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-bold <?= $active ? 'bg-teal-50 text-teal-700' : 'bg-red-50 text-red-700' ?>"><?= $active ? 'Actif' : 'Inactif' ?></span>
                        </div>

                        <div class="mt-5">
                            <p class="text-xs font-bold uppercase tracking-[.14em] text-slate-400"><?= count($capabilities) ?> capacite(s) metier</p>
                            <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                                <?php foreach ($capabilities as $capability): ?><li class="flex items-start gap-2 rounded-lg bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700"><span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-teal-500"></span><span><?= $safe($capability) ?></span></li><?php endforeach; ?>
                                <?php if ($capabilities === []): ?><li class="rounded-lg bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-700">Module personnalise : capacites a documenter.</li><?php endif; ?>
                            </ul>
                        </div>

                        <details class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <summary class="cursor-pointer text-sm font-bold text-slate-700">Modifier le module</summary>
                            <form class="mt-4 grid gap-3 sm:grid-cols-2" method="post" action="<?= $url('/saas-admin/fonctionnalites/' . (int) ($feature['id'] ?? 0) . '/update') ?>">
                                <label class="text-sm font-semibold">Code<input class="field-control mt-2" name="code" value="<?= $safe($code) ?>" required></label>
                                <label class="text-sm font-semibold">Nom<input class="field-control mt-2" name="nom" value="<?= $safe($feature['nom'] ?? '') ?>" required></label>
                                <label class="text-sm font-semibold">Groupe<input class="field-control mt-2" name="categorie" value="<?= $safe($group) ?>" required></label>
                                <label class="flex items-center gap-2 self-end rounded-lg border border-slate-200 bg-white px-3 py-3 text-sm font-bold"><input type="checkbox" name="actif" value="1" <?= $active ? 'checked' : '' ?>> Module actif</label>
                                <label class="text-sm font-semibold sm:col-span-2">Description<textarea class="field-control mt-2 min-h-20" name="description"><?= $safe($feature['description'] ?? '') ?></textarea></label>
                                <button class="btn-primary sm:col-span-2" type="submit">Enregistrer les informations</button>
                            </form>
                        </details>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="new-feature" class="surface-panel scroll-mt-24">
        <div class="panel-header"><div><h2 class="text-xl font-black text-slate-950">Ajouter un module controle</h2><p class="mt-1 text-sm text-slate-500">Le code doit correspondre au code utilise par les routes et le menu boutique.</p></div></div>
        <form class="mt-5 grid gap-4 lg:grid-cols-[12rem_1fr_12rem_auto]" method="post" action="<?= $url('/saas-admin/fonctionnalites') ?>">
            <input class="field-control" name="code" placeholder="code_module" required>
            <input class="field-control" name="nom" placeholder="Nom visible" required>
            <input class="field-control" name="categorie" placeholder="Groupe" value="general" required>
            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-4 text-sm font-bold"><input type="checkbox" name="actif" value="1" checked> Actif</label>
            <textarea class="field-control min-h-20 lg:col-span-4" name="description" placeholder="Description fonctionnelle exacte"></textarea>
            <button class="btn-primary lg:col-span-4 lg:justify-self-end" type="submit">Ajouter le module</button>
        </form>
    </section>

    <section id="plans" class="surface-panel scroll-mt-24">
        <div class="panel-header"><div><h2 class="text-xl font-black text-slate-950">Modules inclus par plan</h2><p class="mt-1 text-sm text-slate-500">Ces cases pilotent les modules disponibles pour les boutiques abonnees.</p></div></div>
        <div class="mt-5 grid gap-4 xl:grid-cols-2">
            <?php foreach ($plans as $plan): ?>
                <?php $selected = array_map('intval', (array) ($planAssignments[(int) ($plan['id'] ?? 0)] ?? [])); ?>
                <form class="rounded-xl border border-slate-200 bg-slate-50 p-4" method="post" action="<?= $url('/saas-admin/fonctionnalites/plans/' . (int) ($plan['id'] ?? 0)) ?>">
                    <div class="flex items-center justify-between gap-3"><div><h3 class="font-black text-slate-950"><?= $safe($plan['nom'] ?? '') ?></h3><p class="text-xs font-bold uppercase tracking-[.12em] text-slate-400"><?= $safe($plan['code'] ?? '') ?></p></div><span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-600"><?= count($selected) ?> module(s)</span></div>
                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        <?php foreach ($features as $feature): ?><label class="flex items-start gap-2 rounded-lg border border-slate-200 bg-white p-3 text-sm font-semibold"><input class="mt-1" type="checkbox" name="feature_ids[]" value="<?= (int) ($feature['id'] ?? 0) ?>" <?= in_array((int) ($feature['id'] ?? 0), $selected, true) ? 'checked' : '' ?>><span><strong class="block text-slate-800"><?= $safe($feature['nom'] ?? '') ?></strong><code class="text-xs text-slate-400"><?= $safe($feature['code'] ?? '') ?></code></span></label><?php endforeach; ?>
                    </div>
                    <button class="btn-primary mt-4 w-full" type="submit">Enregistrer le plan</button>
                </form>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="categories" class="surface-panel scroll-mt-24">
        <div class="panel-header"><div><h2 class="text-xl font-black text-slate-950">Plans autorises par categorie</h2><p class="mt-1 text-sm text-slate-500">Une boutique doit avoir un plan autorise pour sa categorie afin de recevoir ses modules.</p></div></div>
        <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <?php foreach ($categories as $category): ?>
                <?php $selectedPlans = array_map('intval', (array) ($categoryPlanAssignments[(int) ($category['id'] ?? 0)] ?? [])); ?>
                <form class="rounded-xl border border-slate-200 p-4" method="post" action="<?= $url('/saas-admin/fonctionnalites/categories/' . (int) ($category['id'] ?? 0) . '/plans') ?>">
                    <h3 class="font-black text-slate-950"><?= $safe($category['nom'] ?? '') ?></h3><p class="mt-1 text-xs text-slate-400"><?= $safe($category['slug'] ?? '') ?></p>
                    <div class="mt-4 space-y-2"><?php foreach ($plans as $plan): ?><label class="flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 text-sm font-semibold"><input type="checkbox" name="plan_ids[]" value="<?= (int) ($plan['id'] ?? 0) ?>" <?= in_array((int) ($plan['id'] ?? 0), $selectedPlans, true) ? 'checked' : '' ?>> <?= $safe($plan['nom'] ?? '') ?></label><?php endforeach; ?></div>
                    <button class="btn-secondary mt-4 w-full" type="submit">Enregistrer</button>
                </form>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="access" class="surface-panel scroll-mt-24">
        <div class="panel-header"><div><h2 class="text-xl font-black text-slate-950">Acces effectifs des boutiques</h2><p class="mt-1 text-sm text-slate-500">Lecture croisee de la categorie, du plan et des modules obtenus.</p></div></div>
        <div class="responsive-table mt-5 overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm"><thead><tr class="text-xs uppercase tracking-[.12em] text-slate-400"><th class="px-4 py-3">Boutique</th><th class="px-4 py-3">Categorie</th><th class="px-4 py-3">Plan</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3">Modules</th></tr></thead><tbody class="divide-y divide-slate-100"><?php foreach ($shopAccessRows as $row): ?><tr><td class="px-4 py-4 font-bold"><?= $safe($row['shop_name'] ?? '') ?></td><td class="px-4 py-4"><?= $safe($row['category_name'] ?? 'Sans categorie') ?></td><td class="px-4 py-4"><?= $safe($row['plan_name'] ?? 'Sans plan') ?></td><td class="px-4 py-4"><?= $safe($row['subscription_status'] ?? '-') ?></td><td class="px-4 py-4"><p class="font-bold"><?= (int) ($row['features_count'] ?? 0) ?> module(s)</p><p class="mt-1 max-w-xl text-xs text-slate-500"><?= $safe($row['feature_codes'] ?? 'Aucun module') ?></p></td></tr><?php endforeach; ?></tbody></table></div>
    </section>
</section>

<script>
const moduleSearch = document.querySelector('[data-module-search]');
const moduleCards = Array.from(document.querySelectorAll('[data-module-card]'));
moduleSearch?.addEventListener('input', () => {
    const query = moduleSearch.value.trim().toLowerCase();
    moduleCards.forEach((card) => card.classList.toggle('hidden', query !== '' && !(card.dataset.search || '').includes(query)));
});
</script>
