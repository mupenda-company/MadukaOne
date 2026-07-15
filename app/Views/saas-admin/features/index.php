<?php

$features = is_array($features ?? null) ? $features : [];
$categories = is_array($categories ?? null) ? $categories : [];
$plans = is_array($plans ?? null) ? $plans : [];
$assignments = is_array($assignments ?? null) ? $assignments : [];
$shopAccessRows = is_array($shopAccessRows ?? null) ? $shopAccessRows : [];
$categoryPlanAssignments = is_array($assignments['category_plans'] ?? null) ? $assignments['category_plans'] : [];
$planAssignments = is_array($assignments['plans'] ?? null) ? $assignments['plans'] : [];
$safe = static fn ($value, string $fallback = ''): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$featureLabel = static fn (array $feature): string => (string) ($feature['nom'] ?? $feature['code'] ?? 'Fonctionnalite');
$activeFeatures = array_values(array_filter($features, static fn (array $feature): bool => (int) ($feature['actif'] ?? 0) === 1));
$featuresByGroup = [];
$assignedFeatureIds = [];
$plansById = [];

foreach ($plans as $plan) {
    $plansById[(int) ($plan['id'] ?? 0)] = $plan;
}

foreach ($features as $feature) {
    $featuresByGroup[(string) ($feature['categorie'] ?? 'general')][] = $feature;
}

foreach ($planAssignments as $ids) {
    foreach ((array) $ids as $id) {
        $assignedFeatureIds[(int) $id] = true;
    }
}

$categoriesWithoutPlan = array_values(array_filter($categories, static function (array $category) use ($categoryPlanAssignments): bool {
    return count((array) ($categoryPlanAssignments[(int) ($category['id'] ?? 0)] ?? [])) === 0;
}));
$shopsWithoutEffectiveFeature = array_values(array_filter($shopAccessRows, static fn (array $row): bool => (int) ($row['features_count'] ?? 0) === 0));
$groupNames = array_keys($featuresByGroup);
sort($groupNames);
$limitText = static function ($value, string $singular, string $plural): string {
    $limit = (int) ($value ?? 0);

    return $limit > 0 ? $limit . ' ' . ($limit > 1 ? $plural : $singular) : 'Illimite';
};
?>

<style>
.features-page { container-type: inline-size; }
.features-hero-actions { display: flex; flex-direction: column; gap: .5rem; width: 100%; }
.features-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
.features-nav { display: flex; gap: .5rem; overflow-x: auto; padding: .25rem; border: 1px solid #e2e8f0; border-radius: .75rem; background: #fff; scrollbar-width: thin; }
.features-nav a { white-space: nowrap; border-radius: .5rem; padding: .65rem .9rem; font-size: .8125rem; font-weight: 800; color: #475569; background: #f8fafc; }
.features-nav a:hover { color: #0f766e; background: #f0fdfa; }
.features-steps { display: grid; gap: .75rem; }
.features-step { display: grid; grid-template-columns: auto 1fr; gap: .75rem; align-items: start; border: 1px solid #e2e8f0; border-radius: .75rem; background: #f8fafc; padding: 1rem; }
.features-step-number { display: grid; width: 2rem; height: 2rem; place-items: center; border-radius: .5rem; background: #fff; color: #475569; font-size: .75rem; font-weight: 900; }
.features-main-grid { display: grid; gap: 1rem; }
.features-toolbar { display: grid; gap: .75rem; }
.feature-row-grid { display: grid; gap: .75rem; }
.feature-badges { display: flex; flex-wrap: wrap; gap: .5rem; min-width: 0; }
.feature-form-card { border: 1px solid #e2e8f0; border-radius: .75rem; background: #fff; padding: 1rem; }
.features-plan-grid { display: grid; gap: 1rem; }
.features-check-grid { display: grid; gap: .5rem; }
.features-lane-grid { display: grid; gap: 1rem; }
.features-scroll-list { max-height: 44rem; overflow: auto; padding-right: .15rem; }
.feature-chip { border-radius: .5rem; padding: .25rem .625rem; font-size: .75rem; font-weight: 800; }
.feature-shop-codes { overflow-wrap: anywhere; }
@media (min-width: 640px) {
    .features-hero-actions { flex-direction: row; width: auto; }
    .features-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
    .features-toolbar { grid-template-columns: 12rem minmax(16rem, 1fr); justify-content: end; }
    .features-check-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (min-width: 1024px) {
    .features-steps { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .feature-row-grid { grid-template-columns: 12rem minmax(12rem, 1fr) 10rem 7.5rem; align-items: end; }
    .features-lane-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
}
@media (min-width: 1280px) {
    .features-stats { grid-template-columns: repeat(5, minmax(0, 1fr)); }
    .features-main-grid { grid-template-columns: 24rem minmax(0, 1fr); }
    .features-plan-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 639px) {
    .features-page .dashboard-hero h1 { font-size: 1.55rem; line-height: 1.15; }
    .features-page .surface-panel { padding: 1rem; }
    .features-page .stat-card { padding: 1rem; }
    .features-page .btn-primary,
    .features-page .btn-secondary { width: 100%; }
}
</style>

<section class="features-page space-y-5">
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Catalogue SaaS</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Gestion professionnelle des fonctionnalites</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                Les fonctionnalites sont incluses dans les plans. Les plans sont ensuite autorises par categorie de boutique. Une boutique ne voit que les modules actifs de son plan si ce plan est disponible dans sa categorie.
            </p>
        </div>
        <div class="features-hero-actions">
            <a class="btn-secondary w-full sm:w-auto" href="<?= $url('/saas-admin/categories') ?>">Categories</a>
            <a class="btn-primary w-full sm:w-auto" href="<?= $url('/saas-admin/abonnements') ?>">Abonnements</a>
        </div>
    </div>

    <nav class="features-nav" aria-label="Navigation fonctionnalites">
        <a href="#catalogue">Catalogue</a>
        <a href="#plans">Plans</a>
        <a href="#categories">Categories</a>
        <a href="#boutiques">Acces boutiques</a>
    </nav>

    <div class="features-stats">
        <article class="stat-card"><p class="text-sm text-slate-500">Fonctionnalites</p><p class="mt-2 text-2xl font-bold"><?= count($features) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Actives</p><p class="mt-2 text-2xl font-bold text-teal-700"><?= count($activeFeatures) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Groupes</p><p class="mt-2 text-2xl font-bold text-slate-950"><?= count($featuresByGroup) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Categories sans plan</p><p class="mt-2 text-2xl font-bold <?= $categoriesWithoutPlan === [] ? 'text-teal-700' : 'text-red-700' ?>"><?= count($categoriesWithoutPlan) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Boutiques sans module</p><p class="mt-2 text-2xl font-bold <?= $shopsWithoutEffectiveFeature === [] ? 'text-teal-700' : 'text-amber-700' ?>"><?= count($shopsWithoutEffectiveFeature) ?></p></article>
    </div>

    <section class="surface-panel">
        <div class="features-steps">
            <div class="features-step">
                <span class="features-step-number">1</span>
                <div>
                    <h2 class="font-bold text-slate-950">Catalogue des fonctionnalites</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Creez les modules commercialisables : caisse, stock, pharmacie, vetements, rapports, multi-devise.</p>
                </div>
            </div>
            <div class="features-step">
                <span class="features-step-number">2</span>
                <div>
                    <h2 class="font-bold text-slate-950">Plans d'abonnement</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Chaque plan contient une selection de fonctionnalites. Modifier un plan modifie toutes les boutiques abonnees a ce plan.</p>
                </div>
            </div>
            <div class="features-step">
                <span class="features-step-number">3</span>
                <div>
                    <h2 class="font-bold text-slate-950">Categories boutiques</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Un plan peut appartenir a plusieurs categories. Les modules metier restent limites aux boutiques de la bonne categorie.</p>
                </div>
            </div>
        </div>
    </section>

    <div class="features-main-grid" id="catalogue">
        <form class="surface-panel space-y-4 xl:sticky xl:top-24 xl:self-start" method="post" action="<?= $url('/saas-admin/fonctionnalites') ?>">
            <div>
                <h2 class="font-bold text-slate-950">Nouvelle fonctionnalite</h2>
                <p class="mt-1 text-sm text-slate-500">Utilisez un code stable. Il servira aux routes, menus et controles serveur.</p>
            </div>
            <div><label class="mb-2 block text-sm font-semibold" for="code">Code technique</label><input class="field-control" id="code" name="code" required placeholder="ex: pharmacy"></div>
            <div><label class="mb-2 block text-sm font-semibold" for="nom">Nom visible</label><input class="field-control" id="nom" name="nom" required maxlength="120" placeholder="Module pharmacie"></div>
            <div>
                <label class="mb-2 block text-sm font-semibold" for="categorie">Groupe</label>
                <select class="field-control" id="categorie" name="categorie">
                    <?php foreach (array_unique(array_merge(['ventes', 'stock', 'achats', 'finance', 'pilotage', 'metier', 'general'], $groupNames)) as $group): ?>
                        <option value="<?= $safe($group) ?>" <?= $group === 'general' ? 'selected' : '' ?>><?= $safe(ucfirst($group)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label class="mb-2 block text-sm font-semibold" for="description">Description</label><textarea class="field-control min-h-28" id="description" name="description" placeholder="Ce que cette fonctionnalite active dans les boutiques."></textarea></div>
            <label class="inline-flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold"><input class="h-4 w-4" type="checkbox" name="actif" value="1" checked> Active</label>
            <button class="btn-primary" type="submit">Ajouter</button>
        </form>

        <section class="surface-panel">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="font-bold text-slate-950">Catalogue des modules</h2>
                    <p class="mt-1 text-sm text-slate-500"><?= count($features) ?> fonctionnalite(s), <?= count($assignedFeatureIds) ?> affectee(s) a au moins un plan.</p>
                </div>
                <div class="features-toolbar">
                    <select class="field-control sm:w-48" data-feature-group>
                        <option value="">Tous les groupes</option>
                        <?php foreach ($groupNames as $group): ?>
                            <option value="<?= $safe(strtolower($group)) ?>"><?= $safe(ucfirst($group)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input class="field-control sm:w-72" type="search" placeholder="Rechercher code ou nom" data-feature-search>
                </div>
            </div>

            <div class="features-scroll-list mt-5 grid gap-3" data-feature-list>
                <?php foreach ($features as $feature): ?>
                    <?php
                    $featureId = (int) ($feature['id'] ?? 0);
                    $isActive = (int) ($feature['actif'] ?? 0) === 1;
                    $group = strtolower((string) ($feature['categorie'] ?? 'general'));
                    ?>
                    <form
                        class="feature-form-card"
                        method="post"
                        action="<?= $url('/saas-admin/fonctionnalites/' . $featureId . '/update') ?>"
                        data-feature-row
                        data-group="<?= $safe($group) ?>"
                        data-search="<?= $safe(strtolower((string) ($feature['code'] ?? '') . ' ' . ($feature['nom'] ?? '') . ' ' . ($feature['categorie'] ?? ''))) ?>"
                    >
                        <div class="feature-row-grid">
                            <div><label class="mb-2 block text-xs font-bold uppercase tracking-[.14em] text-slate-400">Code</label><input class="field-control" name="code" value="<?= $safe($feature['code'] ?? '') ?>" required></div>
                            <div><label class="mb-2 block text-xs font-bold uppercase tracking-[.14em] text-slate-400">Nom</label><input class="field-control" name="nom" value="<?= $safe($feature['nom'] ?? '') ?>" required></div>
                            <div><label class="mb-2 block text-xs font-bold uppercase tracking-[.14em] text-slate-400">Groupe</label><input class="field-control" name="categorie" value="<?= $safe($feature['categorie'] ?? 'general') ?>"></div>
                            <label class="inline-flex h-12 items-center gap-3 rounded-lg border border-slate-200 px-4 text-sm font-semibold"><input class="h-4 w-4" type="checkbox" name="actif" value="1" <?= $isActive ? 'checked' : '' ?>> Active</label>
                        </div>
                        <textarea class="field-control mt-3 min-h-20" name="description"><?= $safe($feature['description'] ?? '') ?></textarea>
                        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="feature-badges">
                                <span class="feature-chip <?= $isActive ? 'bg-teal-50 text-teal-700' : 'bg-slate-100 text-slate-500' ?>"><?= $isActive ? 'Actif' : 'Inactif' ?></span>
                                <span class="feature-chip bg-slate-100 text-slate-700"><?= (int) ($feature['plans_count'] ?? 0) ?> plan(s)</span>
                                <?php if (!isset($assignedFeatureIds[$featureId])): ?><span class="feature-chip bg-amber-50 text-amber-700">Non affectee</span><?php endif; ?>
                            </div>
                            <button class="btn-secondary h-10 w-full px-4 sm:w-auto" type="submit">Enregistrer</button>
                        </div>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <section class="surface-panel" id="plans">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="font-bold text-slate-950">Matrice fonctionnalites par plan</h2>
                <p class="mt-1 text-sm text-slate-500">Cochez les modules inclus dans chaque plan. Les fonctionnalites inactives restent visibles dans le catalogue mais ne sont pas attribuables ici.</p>
            </div>
            <a class="btn-secondary w-full lg:w-auto" href="<?= $url('/saas-admin/abonnements') ?>">Modifier les plans</a>
        </div>

        <div class="features-plan-grid mt-5">
            <?php foreach ($plans as $plan): ?>
                <?php $selected = array_map('intval', $planAssignments[(int) ($plan['id'] ?? 0)] ?? []); ?>
                <form class="feature-form-card" method="post" action="<?= $url('/saas-admin/fonctionnalites/plans/' . (int) ($plan['id'] ?? 0)) ?>">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="font-bold text-slate-950"><?= $safe($plan['nom'] ?? '') ?></h3>
                            <p class="mt-2 flex flex-wrap gap-2 text-xs font-semibold text-slate-500">
                                <span class="feature-chip bg-slate-100 text-slate-600"><?= $safe($limitText($plan['limite_boutiques'] ?? null, 'boutique', 'boutiques')) ?></span>
                                <span class="feature-chip bg-slate-100 text-slate-600"><?= $safe($limitText($plan['limite_utilisateurs'] ?? null, 'utilisateur', 'utilisateurs')) ?></span>
                                <span class="feature-chip bg-teal-50 text-teal-700"><?= count($selected) ?> fonctionnalite(s)</span>
                            </p>
                        </div>
                        <button class="btn-primary h-10 w-full sm:w-auto" type="submit">Appliquer</button>
                    </div>

                    <div class="mt-4 space-y-4">
                        <?php foreach ($featuresByGroup as $group => $groupFeatures): ?>
                            <?php $assignable = array_values(array_filter($groupFeatures, static fn (array $feature): bool => (int) ($feature['actif'] ?? 0) === 1)); ?>
                            <?php if ($assignable === []): ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <div>
                                <p class="mb-2 text-xs font-bold uppercase tracking-[.14em] text-slate-400"><?= $safe($group) ?></p>
                                <div class="features-check-grid">
                                    <?php foreach ($assignable as $feature): ?>
                                        <label class="flex items-start gap-3 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold">
                                            <input class="mt-0.5 h-4 w-4 shrink-0" type="checkbox" name="feature_ids[]" value="<?= (int) $feature['id'] ?>" <?= in_array((int) $feature['id'], $selected, true) ? 'checked' : '' ?>>
                                            <span class="min-w-0">
                                                <span class="block truncate"><?= $safe($featureLabel($feature)) ?></span>
                                                <span class="block truncate text-xs font-medium text-slate-400"><?= $safe($feature['code'] ?? '') ?></span>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </form>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="features-lane-grid">
        <section class="surface-panel" id="categories">
            <div>
                <h2 class="font-bold text-slate-950">Plans autorises par categorie</h2>
                <p class="mt-1 text-sm text-slate-500">Une boutique ne peut utiliser son plan que si ce plan est classe dans sa categorie.</p>
            </div>
            <div class="mt-5 space-y-4">
                <?php foreach ($categories as $category): ?>
                    <?php $selected = array_map('intval', $categoryPlanAssignments[(int) ($category['id'] ?? 0)] ?? []); ?>
                    <form class="feature-form-card" method="post" action="<?= $url('/saas-admin/fonctionnalites/categories/' . (int) ($category['id'] ?? 0) . '/plans') ?>">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="font-bold text-slate-950"><?= $safe($category['nom'] ?? '') ?></h3>
                                <p class="mt-1 text-xs font-semibold text-slate-500"><?= (int) ($category['shops_count'] ?? 0) ?> boutique(s), <?= count($selected) ?> plan(s)</p>
                            </div>
                            <button class="btn-secondary h-10 w-full sm:w-auto" type="submit">Appliquer</button>
                        </div>
                        <div class="features-check-grid mt-4">
                            <?php foreach ($plans as $plan): ?>
                                <label class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold">
                                    <input class="h-4 w-4" type="checkbox" name="plan_ids[]" value="<?= (int) $plan['id'] ?>" <?= in_array((int) $plan['id'], $selected, true) ? 'checked' : '' ?>>
                                    <span class="min-w-0 truncate"><?= $safe($plan['nom'] ?? '') ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($selected === []): ?>
                            <p class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-xs font-bold text-red-700">Aucun plan autorise pour cette categorie.</p>
                        <?php endif; ?>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="surface-panel" id="boutiques">
            <div>
                <h2 class="font-bold text-slate-950">Apercu acces boutiques</h2>
                <p class="mt-1 text-sm text-slate-500">Controle rapide du resultat categorie + plan + fonctionnalites actives.</p>
            </div>
            <div class="mt-5 space-y-3">
                <?php foreach ($shopAccessRows as $row): ?>
                    <?php
                    $featureCount = (int) ($row['features_count'] ?? 0);
                    $status = (string) ($row['subscription_status'] ?? 'non_configure');
                    $ok = $featureCount > 0 && in_array($status, ['trial', 'active'], true);
                    ?>
                    <div class="feature-form-card">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <h3 class="truncate font-bold text-slate-950"><?= $safe($row['shop_name'] ?? '') ?></h3>
                                <p class="mt-1 text-xs font-semibold text-slate-500"><?= $safe($row['category_name'] ?? 'Sans categorie') ?> - <?= $safe($row['plan_name'] ?? 'Sans plan') ?></p>
                            </div>
                            <span class="rounded-lg px-2.5 py-1 text-xs font-bold <?= $ok ? 'bg-teal-50 text-teal-700' : 'bg-amber-50 text-amber-700' ?>"><?= $featureCount ?> module(s)</span>
                        </div>
                        <p class="feature-shop-codes mt-3 text-xs leading-5 text-slate-500"><?= $safe($row['feature_codes'] ?? 'Aucune fonctionnalite effective') ?></p>
                    </div>
                <?php endforeach; ?>
                <?php if ($shopAccessRows === []): ?>
                    <p class="rounded-lg border border-dashed border-slate-200 p-6 text-center text-sm font-semibold text-slate-500">Aucune boutique a analyser.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</section>

<script>
const featureSearch = document.querySelector('[data-feature-search]');
const featureGroup = document.querySelector('[data-feature-group]');
const featureRows = Array.from(document.querySelectorAll('[data-feature-row]'));

const filterFeatures = () => {
    const query = (featureSearch?.value || '').trim().toLowerCase();
    const group = (featureGroup?.value || '').trim().toLowerCase();

    featureRows.forEach((row) => {
        const matchesQuery = query === '' || (row.dataset.search || '').includes(query);
        const matchesGroup = group === '' || (row.dataset.group || '') === group;
        row.classList.toggle('hidden', !matchesQuery || !matchesGroup);
    });
};

featureSearch?.addEventListener('input', filterFeatures);
featureGroup?.addEventListener('change', filterFeatures);
</script>

