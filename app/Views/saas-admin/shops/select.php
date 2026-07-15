<?php
$shops = is_array($shops ?? null) ? $shops : [];
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$activeCount = count(array_filter($shops, static fn (array $shop): bool => (int) ($shop['actif'] ?? 0) === 1));
$plannedCount = count(array_filter($shops, static fn (array $shop): bool => trim((string) ($shop['plan_name'] ?? '')) !== ''));
?>
<section class="space-y-5" data-saas-shop-select>
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Espace boutique</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Choisir une boutique</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">Selectionnez la boutique dans laquelle vous voulez entrer en acces anonyme super admin.</p>
        </div>
        <a class="btn-secondary" href="<?= $url('/saas-admin') ?>">Retour pilotage</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <article class="stat-card"><p class="text-sm text-slate-500">Boutiques</p><p class="mt-2 text-2xl font-bold"><?= count($shops) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Actives</p><p class="mt-2 text-2xl font-bold text-teal-700"><?= $activeCount ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Avec plan</p><p class="mt-2 text-2xl font-bold text-blue-700"><?= $plannedCount ?></p></article>
    </div>

    <section class="surface-panel">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-bold text-slate-950">Liste des boutiques</h2>
                <p class="mt-1 text-sm text-slate-500"><span data-select-visible-count><?= count($shops) ?></span> boutique(s) disponible(s).</p>
            </div>
            <div class="w-full lg:w-96">
                <label class="sr-only" for="shop-select-search">Filtrer une boutique</label>
                <input class="field-control" id="shop-select-search" type="search" placeholder="Rechercher par nom, categorie, contact ou abonnement" data-select-filter>
            </div>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-select-list>
            <?php foreach ($shops as $shop): ?>
                <?php
                    $shopId = (int) ($shop['id'] ?? 0);
                    $isActive = (int) ($shop['actif'] ?? 0) === 1;
                    $categoryName = (string) (($shop['category_name'] ?? '') !== '' ? $shop['category_name'] : 'Sans categorie');
                    $contact = (string) (($shop['email'] ?? '') !== '' ? $shop['email'] : ($shop['telephone'] ?? 'Contact non defini'));
                    $planName = (string) (($shop['plan_name'] ?? '') !== '' ? $shop['plan_name'] : 'Sans plan');
                    $search = strtolower((string) ($shop['nom'] ?? '') . ' ' . $categoryName . ' ' . $contact . ' ' . $planName . ' ' . ($shop['adresse'] ?? ''));
                ?>
                <article
                    class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-teal-200 hover:shadow-md"
                    data-select-row
                    data-search="<?= $safe($search) ?>"
                >
                    <div class="flex items-start gap-3">
                        <div class="grid h-12 w-12 shrink-0 place-items-center rounded-lg <?= $isActive ? 'bg-teal-50 text-teal-700' : 'bg-red-50 text-red-700' ?>">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 10 12 4l8 6v10H4V10Zm5 10v-6h6v6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="truncate text-base font-bold text-slate-950"><?= $safe($shop['nom'] ?? '') ?></h3>
                            <p class="mt-1 truncate text-sm text-slate-500"><?= $safe($contact) ?></p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="rounded-lg px-2.5 py-1 text-xs font-bold <?= $isActive ? 'bg-teal-50 text-teal-700' : 'bg-red-50 text-red-700' ?>"><?= $isActive ? 'Active' : 'Suspendue' ?></span>
                                <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700"><?= $safe($categoryName) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-2 text-sm">
                        <div class="signal-row"><span class="text-slate-500">Plan</span><strong><?= $safe($planName) ?></strong></div>
                        <div class="signal-row"><span class="text-slate-500">Utilisateurs</span><strong><?= (int) ($shop['users_count'] ?? 0) ?></strong></div>
                        <div class="signal-row"><span class="text-slate-500">Produits</span><strong><?= (int) ($shop['products_count'] ?? 0) ?></strong></div>
                    </div>

                    <a class="btn-primary mt-4 w-full" href="<?= $url('/saas-admin/boutiques/' . $shopId . '/access') ?>">Acceder a cette boutique</a>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="mt-5 hidden rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center" data-select-empty>
            <p class="text-sm font-bold text-slate-700">Aucune boutique ne correspond au filtre.</p>
        </div>
    </section>
</section>

<script>
const selectSearch = document.querySelector('[data-select-filter]');
const selectRows = Array.from(document.querySelectorAll('[data-select-row]'));
const selectCount = document.querySelector('[data-select-visible-count]');
const selectEmpty = document.querySelector('[data-select-empty]');

selectSearch?.addEventListener('input', (event) => {
    const query = event.target.value.trim().toLowerCase();
    let count = 0;

    selectRows.forEach((row) => {
        const visible = query === '' || (row.dataset.search || '').includes(query);
        row.classList.toggle('hidden', !visible);

        if (visible) {
            count++;
        }
    });

    if (selectCount) {
        selectCount.textContent = String(count);
    }

    selectEmpty?.classList.toggle('hidden', count > 0);
});
</script>
