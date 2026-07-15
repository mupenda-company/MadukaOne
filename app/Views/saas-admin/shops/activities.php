<?php
$shops = is_array($shops ?? null) ? $shops : [];
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$categories = [];
$activeCount = 0;

foreach ($shops as $shop) {
    $category = (string) (($shop['category_name'] ?? '') !== '' ? $shop['category_name'] : 'Sans categorie');
    $categories[$category] = true;
    $activeCount += (int) ($shop['actif'] ?? 0) === 1 ? 1 : 0;
}
?>
<section class="space-y-5" data-saas-activities>
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Pilotage des activites</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Administration activite</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                Controlez les categories, les volumes et l acces aux espaces metier de toutes les boutiques depuis l administration SaaS.
            </p>
        </div>
        <a class="btn-secondary w-full sm:w-auto" href="<?= $url('/saas-admin/categories') ?>">Gerer les categories</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <article class="stat-card"><p class="text-sm text-slate-500">Boutiques</p><p class="mt-2 text-2xl font-bold"><?= count($shops) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Activites</p><p class="mt-2 text-2xl font-bold text-amber-700"><?= count($categories) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Boutiques actives</p><p class="mt-2 text-2xl font-bold text-teal-700"><?= $activeCount ?></p></article>
    </div>

    <section class="surface-panel">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-bold text-slate-950">Activites par boutique</h2>
                <p class="mt-1 text-sm text-slate-500"><span data-activity-visible-count><?= count($shops) ?></span> boutique(s) affichee(s).</p>
            </div>
            <div class="w-full lg:w-80">
                <label class="sr-only" for="activity-search">Filtrer les activites</label>
                <input class="field-control" id="activity-search" type="search" placeholder="Boutique, categorie ou contact" data-activity-filter>
            </div>
        </div>

        <div class="mt-5 grid gap-4 xl:grid-cols-2" data-activity-list>
            <?php foreach ($shops as $shop): ?>
                <?php
                $shopId = (int) ($shop['id'] ?? 0);
                $isActive = (int) ($shop['actif'] ?? 0) === 1;
                $category = (string) (($shop['category_name'] ?? '') !== '' ? $shop['category_name'] : 'Sans categorie');
                $search = strtolower((string) ($shop['nom'] ?? '') . ' ' . $category . ' ' . ($shop['email'] ?? '') . ' ' . ($shop['telephone'] ?? ''));
                ?>
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm" data-activity-row data-search="<?= $safe($search) ?>">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="truncate font-bold text-slate-950"><?= $safe($shop['nom'] ?? '') ?></h3>
                                <span class="rounded-lg px-2.5 py-1 text-xs font-bold <?= $isActive ? 'bg-teal-50 text-teal-700' : 'bg-red-50 text-red-700' ?>"><?= $isActive ? 'Active' : 'Suspendue' ?></span>
                            </div>
                            <p class="mt-2 text-sm font-bold text-amber-700"><?= $safe($category) ?></p>
                            <p class="mt-1 truncate text-sm text-slate-500"><?= $safe($shop['email'] ?? $shop['telephone'] ?? 'Contact non defini') ?></p>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Produits</p><p class="mt-1 font-bold"><?= (int) ($shop['products_count'] ?? 0) ?></p></div>
                        <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Ventes</p><p class="mt-1 font-bold"><?= (int) ($shop['sales_count'] ?? 0) ?></p></div>
                        <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Utilisateurs</p><p class="mt-1 font-bold"><?= (int) ($shop['users_count'] ?? 0) ?></p></div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a class="btn-primary h-10 w-auto px-4" href="<?= $url('/saas-admin/boutiques/' . $shopId . '/activity') ?>">Ouvrir l activite</a>
                        <a class="btn-secondary h-10 w-auto px-4" href="<?= $url('/saas-admin/boutiques/' . $shopId . '/edit') ?>">Configurer</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="mt-5 <?= $shops === [] ? '' : 'hidden' ?> rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center" data-activity-empty>
            <p class="text-sm font-bold text-slate-700">Aucune activite trouvee.</p>
        </div>
    </section>
</section>
<script>
const activitySearch = document.querySelector('[data-activity-filter]');
const activityRows = Array.from(document.querySelectorAll('[data-activity-row]'));
const activityCount = document.querySelector('[data-activity-visible-count]');
const activityEmpty = document.querySelector('[data-activity-empty]');

activitySearch?.addEventListener('input', (event) => {
    const query = event.target.value.trim().toLowerCase();
    let visible = 0;
    activityRows.forEach((row) => {
        const show = query === '' || (row.dataset.search || '').includes(query);
        row.classList.toggle('hidden', !show);
        if (show) visible++;
    });
    if (activityCount) activityCount.textContent = String(visible);
    activityEmpty?.classList.toggle('hidden', visible > 0);
});
</script>
