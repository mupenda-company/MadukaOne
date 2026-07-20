<?php
$categories = is_array($categories ?? null) ? $categories : [];
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$activeCount = count(array_filter($categories, static fn (array $category): bool => (int) ($category['actif'] ?? 0) === 1));
$assignedCount = array_sum(array_map(static fn (array $category): int => (int) ($category['shops_count'] ?? 0), $categories));
$inactiveShopsCount = array_sum(array_map(static fn (array $category): int => (int) ($category['inactive_shops_count'] ?? 0), $categories));
$requestedCategoryId = max(0, (int) ($_GET['category'] ?? 0));
$categoryIds = array_map(static fn (array $category): int => (int) ($category['id'] ?? 0), $categories);
$selectedCategoryId = in_array($requestedCategoryId, $categoryIds, true) ? $requestedCategoryId : (int) ($categoryIds[0] ?? 0);
?>
<section class="space-y-5" data-saas-categories>
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Catégories</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Catégories des boutiques</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">Classez les boutiques par secteur et gérez complètement leur catégorie depuis un espace unique.</p>
        </div>
        <a class="btn-secondary" href="<?= $url('/saas-admin/boutiques') ?>">Voir les boutiques</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="stat-card"><p class="text-sm text-slate-500">Catégories</p><p class="mt-2 text-2xl font-bold"><?= count($categories) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Actives</p><p class="mt-2 text-2xl font-bold text-teal-700"><?= $activeCount ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Boutiques classées</p><p class="mt-2 text-2xl font-bold text-blue-700"><?= $assignedCount ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Boutiques désactivées</p><p class="mt-2 text-2xl font-bold text-red-700"><?= $inactiveShopsCount ?></p><p class="mt-1 text-xs text-slate-500">Toutes catégories confondues</p></article>
    </div>

    <div class="grid gap-5 xl:grid-cols-[24rem_1fr]">
        <form class="surface-panel space-y-4" method="post" action="<?= $url('/saas-admin/categories') ?>" accept-charset="UTF-8">
            <div><h2 class="font-bold text-slate-950">Nouvelle catégorie</h2><p class="mt-1 text-sm text-slate-500">Ajoutez un secteur disponible lors de la création d’une boutique.</p></div>
            <div><label class="mb-2 block text-sm font-semibold text-slate-700" for="category_nom">Nom</label><input class="field-control" id="category_nom" name="nom" required maxlength="120" placeholder="Ex. Fleuristes"></div>
            <div><label class="mb-2 block text-sm font-semibold text-slate-700" for="category_description">Description</label><textarea class="field-control min-h-28" id="category_description" name="description" placeholder="Activité, modules prioritaires et contexte d’utilisation."></textarea></div>
            <label class="inline-flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold"><input class="h-4 w-4" type="checkbox" name="actif" value="1" checked>Catégorie active</label>
            <button class="btn-primary" type="submit">Ajouter la catégorie</button>
        </form>

        <section class="surface-panel" id="category-editor">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div><h2 class="font-bold text-slate-950">Fiche de la catégorie</h2><p class="mt-1 text-sm text-slate-500">Sélectionnez une catégorie, puis modifiez ses informations ou son statut.</p></div>
                <div class="w-full lg:w-80">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-[.14em] text-slate-400" for="category-selector">Catégorie</label>
                    <select class="field-control" id="category-selector" data-category-selector>
                        <?php foreach ($categories as $category): $id = (int) ($category['id'] ?? 0); ?>
                            <option value="<?= $id ?>" <?= $id === $selectedCategoryId ? 'selected' : '' ?>><?= $safe($category['nom'] ?? '') ?> — <?= (int) ($category['shops_count'] ?? 0) ?> boutique(s)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mt-5 grid gap-3">
                <?php foreach ($categories as $category):
                    $id = (int) ($category['id'] ?? 0);
                    $isActive = (int) ($category['actif'] ?? 0) === 1;
                    $shopsCount = (int) ($category['shops_count'] ?? 0);
                ?>
                    <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm <?= $id === $selectedCategoryId ? '' : 'hidden' ?>" data-category-panel data-category-id="<?= $id ?>">
                        <form method="post" action="<?= $url('/saas-admin/categories/' . $id . '/update') ?>" accept-charset="UTF-8">
                            <div class="grid gap-3 lg:grid-cols-2">
                                <div><label class="mb-2 block text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Nom</label><input class="field-control" name="nom" required maxlength="120" value="<?= $safe($category['nom'] ?? '') ?>"></div>
                                <div><label class="mb-2 block text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Slug</label><input class="field-control" name="slug" required maxlength="140" value="<?= $safe($category['slug'] ?? '') ?>"></div>
                            </div>
                            <div class="mt-3"><label class="mb-2 block text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Description</label><textarea class="field-control min-h-28" name="description"><?= $safe($category['description'] ?? '', '') ?></textarea></div>
                            <input type="hidden" name="actif" value="<?= $isActive ? '1' : '0' ?>">
                            <button class="btn-primary mt-4 w-auto px-5" type="submit">Enregistrer les modifications</button>
                        </form>

                        <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
                            <span class="rounded-lg px-2.5 py-1 text-xs font-bold <?= $isActive ? 'bg-teal-50 text-teal-700' : 'bg-red-50 text-red-700' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span>
                            <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700"><?= $shopsCount ?> boutique(s)</span>
                            <span class="rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700"><?= (int) ($category['active_shops_count'] ?? 0) ?> active(s)</span>
                            <span class="rounded-lg bg-red-50 px-2.5 py-1 text-xs font-bold text-red-700"><?= (int) ($category['inactive_shops_count'] ?? 0) ?> désactivée(s)</span>
                            <div class="ml-auto flex flex-wrap gap-2">
                                <form method="post" action="<?= $url('/saas-admin/categories/' . $id . '/toggle') ?>">
                                    <button class="rounded-lg px-3 py-2 text-xs font-bold <?= $isActive ? 'bg-amber-50 text-amber-800 hover:bg-amber-100' : 'bg-teal-50 text-teal-700 hover:bg-teal-100' ?>" type="button" data-confirm data-confirm-title="<?= $isActive ? 'Désactiver cette catégorie ?' : 'Réactiver cette catégorie ?' ?>" data-confirm-message="<?= $isActive ? 'Elle ne sera plus proposée aux nouvelles boutiques. Les boutiques existantes restent conservées.' : 'Elle sera de nouveau disponible pour les boutiques.' ?>" data-confirm-accept="<?= $isActive ? 'Oui, désactiver' : 'Oui, réactiver' ?>" data-confirm-progress="Mise à jour...">
                                        <?= $isActive ? 'Désactiver' : 'Réactiver' ?>
                                    </button>
                                </form>
                                <form method="post" action="<?= $url('/saas-admin/categories/' . $id . '/delete') ?>">
                                    <button class="rounded-lg bg-red-50 px-3 py-2 text-xs font-bold text-red-700 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50" type="button" <?= $shopsCount > 0 ? 'disabled title="Suppression impossible : des boutiques utilisent cette catégorie"' : '' ?> data-confirm data-confirm-title="Supprimer définitivement cette catégorie ?" data-confirm-message="Cette opération est irréversible. La catégorie <?= $safe($category['nom'] ?? '') ?> sera supprimée." data-confirm-accept="Supprimer définitivement" data-confirm-progress="Suppression...">Supprimer</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if ($categories === []): ?><div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center text-sm font-semibold text-slate-600">Aucune catégorie enregistrée.</div><?php endif; ?>
            </div>
        </section>
    </div>

    <section class="surface-panel">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-xs font-semibold uppercase tracking-[.16em] text-amber-700">Vue complète</p><h2 class="mt-2 text-xl font-bold text-slate-950">Toutes les catégories</h2><p class="mt-1 text-sm text-slate-500"><?= count($categories) ?> catégorie(s) enregistrée(s).</p></div>
            <input class="field-control w-full sm:max-w-xs" type="search" placeholder="Rechercher une catégorie" aria-label="Rechercher une catégorie" data-category-table-search>
        </div>
        <div class="mt-5 overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-[.12em] text-slate-500"><tr><th class="px-4 py-3">Catégorie</th><th class="px-4 py-3">Slug</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3 text-center">Boutiques</th><th class="px-4 py-3 text-center">Actives</th><th class="px-4 py-3 text-center">Désactivées</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-100 bg-white" data-category-table-body>
                    <?php foreach ($categories as $category): $id = (int) ($category['id'] ?? 0); $isActive = (int) ($category['actif'] ?? 0) === 1; ?>
                        <tr data-category-table-row data-search="<?= $safe(strtolower((string) ($category['nom'] ?? '') . ' ' . ($category['slug'] ?? '') . ' ' . ($category['description'] ?? ''))) ?>">
                            <td class="px-4 py-3"><p class="font-bold text-slate-900"><?= $safe($category['nom'] ?? '') ?></p><p class="mt-1 max-w-md truncate text-xs text-slate-500"><?= $safe($category['description'] ?? '', 'Aucune description') ?></p></td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600"><?= $safe($category['slug'] ?? '') ?></td>
                            <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-bold <?= $isActive ? 'bg-teal-50 text-teal-700' : 'bg-red-50 text-red-700' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span></td>
                            <td class="px-4 py-3 text-center font-semibold"><?= (int) ($category['shops_count'] ?? 0) ?></td><td class="px-4 py-3 text-center font-semibold text-emerald-700"><?= (int) ($category['active_shops_count'] ?? 0) ?></td><td class="px-4 py-3 text-center font-semibold text-red-700"><?= (int) ($category['inactive_shops_count'] ?? 0) ?></td>
                            <td class="px-4 py-3"><div class="flex justify-end"><button class="btn-secondary h-9 w-auto px-3 text-xs" type="button" data-open-category="<?= $id ?>">Afficher / modifier</button></div></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="hidden" data-category-table-empty><td class="px-4 py-10 text-center text-sm text-slate-500" colspan="7">Aucune catégorie ne correspond à la recherche.</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</section>

<script>
(() => {
    const selector = document.querySelector('[data-category-selector]');
    const panels = Array.from(document.querySelectorAll('[data-category-panel]'));
    const displaySelected = () => panels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.categoryId !== selector?.value));
    selector?.addEventListener('change', displaySelected);
    displaySelected();

    document.querySelectorAll('[data-open-category]').forEach((button) => button.addEventListener('click', () => {
        if (!selector) return;
        selector.value = button.dataset.openCategory || '';
        displaySelected();
        document.querySelector('#category-editor')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }));

    const search = document.querySelector('[data-category-table-search]');
    const rows = Array.from(document.querySelectorAll('[data-category-table-row]'));
    const empty = document.querySelector('[data-category-table-empty]');
    search?.addEventListener('input', () => {
        const query = search.value.trim().toLowerCase();
        let visible = 0;
        rows.forEach((row) => {
            const show = query === '' || (row.dataset.search || '').includes(query);
            row.classList.toggle('hidden', !show);
            if (show) visible++;
        });
        empty?.classList.toggle('hidden', visible > 0);
    });
})();
</script>
