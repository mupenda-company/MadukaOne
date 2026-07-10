<?php
$features = is_array($features ?? null) ? $features : [];
$categories = is_array($categories ?? null) ? $categories : [];
$plans = is_array($plans ?? null) ? $plans : [];
$assignments = is_array($assignments ?? null) ? $assignments : [];
$categoryPlanAssignments = is_array($assignments['category_plans'] ?? null) ? $assignments['category_plans'] : [];
$planAssignments = is_array($assignments['plans'] ?? null) ? $assignments['plans'] : [];
$safe = static fn ($value, string $fallback = ''): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$featureLabel = static fn (array $feature): string => (string) ($feature['nom'] ?? $feature['code'] ?? 'Fonctionnalite');
$activeFeatures = array_values(array_filter($features, static fn (array $feature): bool => (int) ($feature['actif'] ?? 0) === 1));
$assignedFeatureIds = [];

foreach ($planAssignments as $ids) {
    foreach ((array) $ids as $id) {
        $assignedFeatureIds[(int) $id] = true;
    }
}
?>
<section class="space-y-5">
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Catalogue SaaS</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Fonctionnalites par plan et categorie</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">Attribuez les fonctionnalites aux plans, puis classez les plans dans les categories de boutiques. Une boutique doit avoir une categorie et un plan disponible dans cette categorie.</p>
        </div>
        <a class="btn-secondary" href="<?= $url('/saas-admin/abonnements') ?>">Voir les abonnements</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="stat-card"><p class="text-sm text-slate-500">Fonctionnalites</p><p class="mt-2 text-2xl font-bold"><?= count($features) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Actives</p><p class="mt-2 text-2xl font-bold text-teal-700"><?= count($activeFeatures) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Categories</p><p class="mt-2 text-2xl font-bold text-blue-700"><?= count($categories) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Plans</p><p class="mt-2 text-2xl font-bold text-amber-700"><?= count($plans) ?></p></article>
    </div>

    <section class="surface-panel">
        <div class="grid gap-4 lg:grid-cols-[1fr_1fr_1fr]">
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="font-bold text-slate-950">1. Fonctionnalites dans les plans</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Chaque plan contient les modules inclus dans l'offre. Retirer une fonctionnalite d'un plan la retire pour toutes les boutiques qui utilisent ce plan.</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="font-bold text-slate-950">2. Plans classes par categorie</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Un plan peut etre disponible dans plusieurs categories. Chaque categorie doit proposer au moins un plan utilisable par ses boutiques.</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="font-bold text-slate-950">3. Acces boutique</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Une boutique affiche les fonctionnalites actives de son plan, uniquement si ce plan est autorise dans sa categorie.</p>
            </div>
        </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-[.65fr_1.35fr]">
        <form class="surface-panel space-y-4" method="post" action="<?= $url('/saas-admin/fonctionnalites') ?>">
            <div>
                <h2 class="font-bold text-slate-950">Nouvelle fonctionnalite</h2>
                    <p class="mt-1 text-sm text-slate-500">Creez le module, puis affectez-le aux plans concernes.</p>
            </div>
            <div><label class="mb-2 block text-sm font-semibold" for="code">Code</label><input class="field-control" id="code" name="code" required placeholder="ex: pos_advanced"></div>
            <div><label class="mb-2 block text-sm font-semibold" for="nom">Nom</label><input class="field-control" id="nom" name="nom" required maxlength="120"></div>
            <div><label class="mb-2 block text-sm font-semibold" for="categorie">Groupe catalogue</label><input class="field-control" id="categorie" name="categorie" value="general" maxlength="80"></div>
            <div><label class="mb-2 block text-sm font-semibold" for="description">Description</label><textarea class="field-control min-h-28" id="description" name="description"></textarea></div>
            <label class="inline-flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold"><input class="h-4 w-4" type="checkbox" name="actif" value="1" checked> Active</label>
            <button class="btn-primary" type="submit">Ajouter la fonctionnalite</button>
        </form>

        <section class="surface-panel">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="font-bold text-slate-950">Catalogue des modules</h2>
                    <p class="mt-1 text-sm text-slate-500"><?= count($features) ?> fonctionnalite(s), <?= count($assignedFeatureIds) ?> deja affectee(s).</p>
                </div>
                <input class="field-control lg:w-80" type="search" placeholder="Filtrer le catalogue" data-feature-search>
            </div>

            <div class="mt-5 grid gap-3" data-feature-list>
                <?php foreach ($features as $feature): ?>
                    <form
                        class="rounded-lg border border-slate-200 bg-white p-4"
                        method="post"
                        action="<?= $url('/saas-admin/fonctionnalites/' . (int) $feature['id'] . '/update') ?>"
                        data-feature-row
                        data-search="<?= $safe(strtolower((string) ($feature['code'] ?? '') . ' ' . ($feature['nom'] ?? '') . ' ' . ($feature['categorie'] ?? ''))) ?>"
                    >
                        <div class="grid gap-3 lg:grid-cols-[1fr_1fr_12rem_9rem] lg:items-end">
                            <div><label class="mb-2 block text-xs font-bold uppercase tracking-[.14em] text-slate-400">Code</label><input class="field-control" name="code" value="<?= $safe($feature['code'] ?? '') ?>" required></div>
                            <div><label class="mb-2 block text-xs font-bold uppercase tracking-[.14em] text-slate-400">Nom</label><input class="field-control" name="nom" value="<?= $safe($feature['nom'] ?? '') ?>" required></div>
                            <div><label class="mb-2 block text-xs font-bold uppercase tracking-[.14em] text-slate-400">Groupe</label><input class="field-control" name="categorie" value="<?= $safe($feature['categorie'] ?? 'general') ?>"></div>
                            <label class="inline-flex h-12 items-center gap-3 rounded-lg border border-slate-200 px-4 text-sm font-semibold"><input class="h-4 w-4" type="checkbox" name="actif" value="1" <?= (int) ($feature['actif'] ?? 0) === 1 ? 'checked' : '' ?>> Active</label>
                        </div>
                        <textarea class="field-control mt-3 min-h-20" name="description"><?= $safe($feature['description'] ?? '') ?></textarea>
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700"><?= (int) ($feature['plans_count'] ?? 0) ?> plan(s)</span>
                            </div>
                            <button class="btn-secondary h-10 w-auto px-4" type="submit">Enregistrer</button>
                        </div>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        <section class="surface-panel" id="categories">
            <div>
                <h2 class="font-bold text-slate-950">Plans par categorie</h2>
                <p class="mt-1 text-sm text-slate-500">Classez les plans disponibles dans chaque categorie de boutique.</p>
            </div>
            <div class="mt-5 space-y-4">
                <?php foreach ($categories as $category): ?>
                    <?php $selected = array_map('intval', $categoryPlanAssignments[(int) ($category['id'] ?? 0)] ?? []); ?>
                    <form class="rounded-lg border border-slate-200 bg-white p-4" method="post" action="<?= $url('/saas-admin/fonctionnalites/categories/' . (int) ($category['id'] ?? 0) . '/plans') ?>">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="font-bold text-slate-950"><?= $safe($category['nom'] ?? '') ?></h3>
                                <p class="mt-1 text-xs font-semibold text-slate-500"><?= (int) ($category['shops_count'] ?? 0) ?> boutique(s), <?= count($selected) ?> plan(s)</p>
                            </div>
                            <button class="btn-secondary h-10 w-full sm:w-auto" type="submit">Appliquer</button>
                        </div>
                        <div class="mt-4 grid gap-2 sm:grid-cols-2">
                            <?php foreach ($plans as $plan): ?>
                                <label class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold">
                                    <input class="h-4 w-4" type="checkbox" name="plan_ids[]" value="<?= (int) $plan['id'] ?>" <?= in_array((int) $plan['id'], $selected, true) ? 'checked' : '' ?>>
                                    <span class="min-w-0 truncate"><?= $safe($plan['nom'] ?? '') ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="surface-panel" id="plans">
            <div>
                <h2 class="font-bold text-slate-950">Fonctionnalites par plan</h2>
                <p class="mt-1 text-sm text-slate-500">Selectionnez les modules inclus dans chaque plan d'abonnement.</p>
            </div>
            <div class="mt-5 space-y-4">
                <?php foreach ($plans as $plan): ?>
                    <?php $selected = array_map('intval', $planAssignments[(int) ($plan['id'] ?? 0)] ?? []); ?>
                    <form class="rounded-lg border border-slate-200 bg-white p-4" method="post" action="<?= $url('/saas-admin/fonctionnalites/plans/' . (int) ($plan['id'] ?? 0)) ?>">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="font-bold text-slate-950"><?= $safe($plan['nom'] ?? '') ?></h3>
                                <p class="mt-1 text-xs font-semibold text-slate-500"><?= (int) ($plan['subscriptions_count'] ?? 0) ?> abonnement(s), <?= count($selected) ?> fonctionnalite(s)</p>
                            </div>
                            <button class="btn-secondary h-10 w-full sm:w-auto" type="submit">Appliquer</button>
                        </div>
                        <div class="mt-4 grid gap-2 sm:grid-cols-2">
                            <?php foreach ($activeFeatures as $feature): ?>
                                <label class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold">
                                    <input class="h-4 w-4" type="checkbox" name="feature_ids[]" value="<?= (int) $feature['id'] ?>" <?= in_array((int) $feature['id'], $selected, true) ? 'checked' : '' ?>>
                                    <span class="min-w-0 truncate"><?= $safe($featureLabel($feature)) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</section>

<script>
const featureSearch = document.querySelector('[data-feature-search]');
const featureRows = Array.from(document.querySelectorAll('[data-feature-row]'));

featureSearch?.addEventListener('input', (event) => {
    const query = event.target.value.trim().toLowerCase();
    featureRows.forEach((row) => {
        row.classList.toggle('hidden', query !== '' && !(row.dataset.search || '').includes(query));
    });
});
</script>
