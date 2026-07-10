<?php
$categories = is_array($categories ?? null) ? $categories : [];
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$activeCount = count(array_filter($categories, static fn (array $category): bool => (int) ($category['actif'] ?? 0) === 1));
$assignedCount = array_sum(array_map(static fn (array $category): int => (int) ($category['shops_count'] ?? 0), $categories));
?>
<section class="space-y-5" data-saas-categories>
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Categories</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Categories des boutiques</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">Classez les boutiques par secteur pour mieux filtrer le parc SaaS et preparer les offres adaptees.</p>
        </div>
        <a class="btn-secondary" href="<?= $url('/saas-admin/boutiques') ?>">Voir les boutiques</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <article class="stat-card"><p class="text-sm text-slate-500">Categories</p><p class="mt-2 text-2xl font-bold"><?= count($categories) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Actives</p><p class="mt-2 text-2xl font-bold text-teal-700"><?= $activeCount ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Boutiques classees</p><p class="mt-2 text-2xl font-bold text-blue-700"><?= $assignedCount ?></p></article>
    </div>

    <div class="grid gap-5 xl:grid-cols-[24rem_1fr]">
        <form class="surface-panel space-y-4" method="post" action="<?= $url('/saas-admin/categories') ?>" accept-charset="UTF-8">
            <div>
                <h2 class="font-bold text-slate-950">Nouvelle categorie</h2>
                <p class="mt-1 text-sm text-slate-500">Ajoutez un secteur boutique disponible dans le formulaire boutique.</p>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700" for="category_nom">Nom</label>
                <input class="field-control" id="category_nom" name="nom" required maxlength="120" placeholder="Ex: Fleuristes">
            </div>
            <label class="inline-flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold">
                <input class="h-4 w-4" type="checkbox" name="actif" value="1" checked>
                Categorie active
            </label>
            <button class="btn-primary" type="submit">Ajouter</button>
        </form>

        <section class="surface-panel">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <h2 class="font-bold text-slate-950">Catalogue categories</h2>
                    <p class="mt-1 text-sm text-slate-500"><span data-category-visible-count><?= count($categories) ?></span> categorie(s) affichee(s).</p>
                </div>
                <div class="w-full lg:w-80">
                    <label class="sr-only" for="category-search">Filtrer les categories</label>
                    <input class="field-control" id="category-search" type="search" placeholder="Filtrer par nom ou slug" data-category-filter>
                </div>
            </div>

            <div class="mt-5 grid gap-3" data-category-list>
                <?php foreach ($categories as $category): ?>
                    <?php $isActive = (int) ($category['actif'] ?? 0) === 1; ?>
                    <form
                        class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"
                        method="post"
                        action="<?= $url('/saas-admin/categories/' . (int) ($category['id'] ?? 0) . '/update') ?>"
                        data-category-row
                        data-search="<?= $safe(strtolower((string) ($category['nom'] ?? '') . ' ' . ($category['slug'] ?? ''))) ?>"
                    >
                        <div class="grid gap-3 lg:grid-cols-[1fr_1fr_auto] lg:items-end">
                            <div>
                                <label class="mb-2 block text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Nom</label>
                                <input class="field-control" name="nom" required maxlength="120" value="<?= $safe($category['nom'] ?? '') ?>">
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Slug</label>
                                <input class="field-control" name="slug" maxlength="140" value="<?= $safe($category['slug'] ?? '') ?>">
                            </div>
                            <div class="flex flex-wrap gap-2 lg:justify-end">
                                <label class="inline-flex h-11 items-center gap-2 rounded-lg border border-slate-200 px-3 text-xs font-bold text-slate-700">
                                    <input class="h-4 w-4" type="checkbox" name="actif" value="1" <?= $isActive ? 'checked' : '' ?>>
                                    Active
                                </label>
                                <button class="btn-secondary h-11 w-auto px-4" type="submit">Enregistrer</button>
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="rounded-lg px-2.5 py-1 text-xs font-bold <?= $isActive ? 'bg-teal-50 text-teal-700' : 'bg-red-50 text-red-700' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span>
                            <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700"><?= (int) ($category['shops_count'] ?? 0) ?> boutique(s)</span>
                            <button
                                class="rounded-lg px-2.5 py-1 text-xs font-bold <?= $isActive ? 'bg-red-50 text-red-700 hover:bg-red-100' : 'bg-teal-50 text-teal-700 hover:bg-teal-100' ?>"
                                type="submit"
                                form="toggle-category-<?= (int) ($category['id'] ?? 0) ?>"
                            >
                                <?= $isActive ? 'Desactiver' : 'Activer' ?>
                            </button>
                        </div>
                    </form>
                    <form id="toggle-category-<?= (int) ($category['id'] ?? 0) ?>" method="post" action="<?= $url('/saas-admin/categories/' . (int) ($category['id'] ?? 0) . '/toggle') ?>"></form>
                <?php endforeach; ?>
            </div>

            <div class="mt-5 hidden rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center" data-category-empty>
                <p class="text-sm font-bold text-slate-700">Aucune categorie ne correspond au filtre.</p>
            </div>
        </section>
    </div>
</section>

<script>
const categorySearch = document.querySelector('[data-category-filter]');
const categoryRows = Array.from(document.querySelectorAll('[data-category-row]'));
const categoryCount = document.querySelector('[data-category-visible-count]');
const categoryEmpty = document.querySelector('[data-category-empty]');

categorySearch?.addEventListener('input', (event) => {
    const query = event.target.value.trim().toLowerCase();
    let count = 0;

    categoryRows.forEach((row) => {
        const visible = query === '' || (row.dataset.search || '').includes(query);
        row.classList.toggle('hidden', !visible);

        if (visible) {
            count++;
        }
    });

    categoryCount?.replaceChildren(document.createTextNode(String(count)));
    categoryEmpty?.classList.toggle('hidden', count > 0);
});
</script>
