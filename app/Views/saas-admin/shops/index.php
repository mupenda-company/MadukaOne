<?php
$shops = is_array($shops ?? null) ? $shops : [];
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$money = static fn ($value): string => number_format((float) $value, 2, ',', ' ') . ' USD';
$activeCount = 0;
$suspendedCount = 0;
$usersCount = 0;
$productsCount = 0;
$salesCount = 0;

foreach ($shops as $shop) {
    if ((int) ($shop['actif'] ?? 0) === 1) {
        $activeCount++;
    } else {
        $suspendedCount++;
    }

    $usersCount += (int) ($shop['users_count'] ?? 0);
    $productsCount += (int) ($shop['products_count'] ?? 0);
    $salesCount += (int) ($shop['sales_count'] ?? 0);
}
?>
<section class="space-y-5" data-saas-shops>
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Multi-boutiques</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Toutes les boutiques</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">Vue de controle pour ouvrir une boutique en acces anonyme, surveiller ses volumes et gerer son etat SaaS.</p>
        </div>
        <a class="btn-primary w-full gap-2 sm:w-auto" href="<?= $url('/saas-admin/boutiques/create') ?>">Ajouter une boutique</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <article class="stat-card">
            <p class="text-sm text-slate-500">Boutiques</p>
            <p class="mt-2 text-2xl font-bold"><?= count($shops) ?></p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Actives</p>
            <p class="mt-2 text-2xl font-bold text-teal-700"><?= $activeCount ?></p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Suspendues</p>
            <p class="mt-2 text-2xl font-bold text-red-700"><?= $suspendedCount ?></p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Utilisateurs</p>
            <p class="mt-2 text-2xl font-bold text-blue-700"><?= $usersCount ?></p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Ventes</p>
            <p class="mt-2 text-2xl font-bold text-amber-700"><?= $salesCount ?></p>
        </article>
    </div>

    <section class="surface-panel">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <h2 class="font-bold text-slate-950">Parc boutiques</h2>
                <p class="mt-1 text-sm text-slate-500"><span data-shop-visible-count><?= count($shops) ?></span> boutique(s) affichee(s), <?= $productsCount ?> produit(s) suivis.</p>
            </div>
            <div class="w-full lg:w-80">
                <label class="sr-only" for="shop-search">Filtrer une boutique</label>
                <input class="field-control" id="shop-search" type="search" placeholder="Filtrer par nom, email ou telephone" data-table-filter>
            </div>
        </div>

        <div class="mt-5 grid gap-4 xl:grid-cols-2" data-shops-list>
            <?php foreach ($shops as $shop): ?>
                <?php
                    $shopId = (int) ($shop['id'] ?? 0);
                    $isActive = (int) ($shop['actif'] ?? 0) === 1;
                    $contact = (string) (($shop['email'] ?? '') !== '' ? $shop['email'] : ($shop['telephone'] ?? 'Contact non defini'));
                    $categoryName = (string) (($shop['category_name'] ?? '') !== '' ? $shop['category_name'] : 'Sans categorie');
                    $search = strtolower((string) ($shop['nom'] ?? '') . ' ' . $contact . ' ' . ($shop['adresse'] ?? '') . ' ' . $categoryName);
                    $subscriptionStatus = (string) ($shop['subscription_status'] ?? 'non configure');
                ?>
                <article
                    class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-teal-200 hover:shadow-md"
                    data-filter-row
                    data-search="<?= $safe($search) ?>"
                >
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex min-w-0 gap-3">
                            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-lg <?= $isActive ? 'bg-teal-50 text-teal-700' : 'bg-red-50 text-red-700' ?>">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M4 10 12 4l8 6v10H4V10Zm5 10v-6h6v6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="truncate text-base font-bold text-slate-950"><?= $safe($shop['nom'] ?? '') ?></h3>
                                    <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-bold <?= $isActive ? 'bg-teal-50 text-teal-700' : 'bg-red-50 text-red-700' ?>"><?= $isActive ? 'Active' : 'Suspendue' ?></span>
                                </div>
                                <p class="mt-1 truncate text-sm text-slate-500"><?= $safe($contact) ?></p>
                                <p class="mt-1 text-xs font-bold text-amber-700"><?= $safe($categoryName) ?></p>
                                <p class="mt-1 line-clamp-2 text-xs text-slate-400"><?= $safe($shop['adresse'] ?? 'Adresse non definie') ?></p>
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-wrap gap-2 lg:justify-end">
                            <a class="btn-primary h-10 w-auto px-4" href="<?= $url('/saas-admin/boutiques/' . $shopId . '/access') ?>">Acceder</a>
                            <a class="btn-secondary h-10 w-auto px-4" href="<?= $url('/saas-admin/boutiques/' . $shopId . '/edit') ?>">Modifier</a>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-lg bg-slate-50 px-3 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Abonnement</p>
                            <p class="mt-1 truncate text-sm font-bold text-slate-950"><?= $safe($shop['plan_name'] ?? 'Sans plan') ?></p>
                            <p class="mt-1 text-xs text-slate-500"><?= $safe($subscriptionStatus) ?> - <?= $money($shop['prix_mensuel_usd'] ?? 0) ?></p>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-3 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Volumes</p>
                            <p class="mt-1 text-sm font-bold text-slate-950"><?= (int) ($shop['users_count'] ?? 0) ?> utilisateur(s)</p>
                            <p class="mt-1 text-xs text-slate-500"><?= (int) ($shop['products_count'] ?? 0) ?> produits, <?= (int) ($shop['sales_count'] ?? 0) ?> ventes</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-3 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Devise</p>
                            <p class="mt-1 text-sm font-bold text-slate-950"><?= $safe($shop['devise_principale'] ?? 'USD') ?></p>
                            <p class="mt-1 text-xs text-slate-500">1 USD = <?= number_format((float) ($shop['taux_change_cdf'] ?? 0), 2, ',', ' ') ?> CDF</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-3 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Gestion</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <a class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-100" href="<?= $url('/saas-admin/abonnements#shop-' . $shopId) ?>">Abonnement</a>
                                <form method="post" action="<?= $url('/saas-admin/boutiques/' . $shopId . '/toggle') ?>">
                                    <button class="rounded-lg px-3 py-2 text-xs font-bold transition <?= $isActive ? 'bg-red-50 text-red-700 hover:bg-red-100' : 'bg-teal-50 text-teal-700 hover:bg-teal-100' ?>" type="button" data-confirm data-confirm-title="Changer le statut ?" data-confirm-message="Cette action active ou suspend la boutique." data-confirm-accept="Confirmer"><?= $isActive ? 'Suspendre' : 'Activer' ?></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if ($shops === []): ?>
                <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center">
                    <p class="text-sm font-bold text-slate-700">Aucune boutique creee.</p>
                    <p class="mt-1 text-sm text-slate-500">Ajoutez une boutique pour commencer le pilotage SaaS.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="mt-5 hidden rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center" data-shop-empty-state>
            <p class="text-sm font-bold text-slate-700">Aucune boutique ne correspond au filtre.</p>
            <p class="mt-1 text-sm text-slate-500">Essayez un nom, un email ou une adresse differente.</p>
        </div>
    </section>
</section>
<script>
const shopSearch = document.querySelector('[data-table-filter]');
const visibleCount = document.querySelector('[data-shop-visible-count]');
const emptyState = document.querySelector('[data-shop-empty-state]');
const shopRows = Array.from(document.querySelectorAll('[data-filter-row]'));

shopSearch?.addEventListener('input', (event) => {
    const query = event.target.value.trim().toLowerCase();
    let count = 0;

    shopRows.forEach((row) => {
        const isVisible = query === '' || (row.dataset.search || '').includes(query);
        row.classList.toggle('hidden', !isVisible);

        if (isVisible) {
            count++;
        }
    });

    if (visibleCount) {
        visibleCount.textContent = String(count);
    }

    emptyState?.classList.toggle('hidden', count > 0);
});
</script>
