<?php

$products = is_array($products ?? null) ? $products : [];
$activeShop = is_array($activeShop ?? null) ? $activeShop : [];
$exchangeRate = (float) (($activeShop['taux_change_cdf'] ?? 2800) ?: 2800);
$activeProducts = count(array_filter($products, static fn (array $product): bool => (int) ($product['actif'] ?? 1) === 1));
$stockAlerts = count(array_filter($products, static fn (array $product): bool => (int) ($product['actif'] ?? 1) === 1 && (int) $product['quantite_stock'] <= (int) $product['alerte_stock_min']));
$stockBreaks = count(array_filter($products, static fn (array $product): bool => (int) ($product['actif'] ?? 1) === 1 && (int) $product['quantite_stock'] === 0));
$today = new DateTimeImmutable('today');
$expirationLimit = $today->modify('+30 days');
$parseDate = static function ($value): ?DateTimeImmutable {
    $value = trim((string) ($value ?? ''));

    if ($value === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10));

    return $date instanceof DateTimeImmutable ? $date : null;
};
$formatDate = static fn (?DateTimeImmutable $date): string => $date instanceof DateTimeImmutable ? $date->format('d/m/Y') : '-';
$formatProductMoney = static function (array $product, string $type) use ($exchangeRate): string {
    $usdField = $type === 'purchase' ? 'prix_achat' : 'prix_vente';
    $amountField = $type === 'purchase' ? 'prix_achat_montant' : 'prix_vente_montant';
    $currencyField = $type === 'purchase' ? 'prix_achat_devise' : 'prix_vente_devise';
    $currency = in_array(($product[$currencyField] ?? 'USD'), ['USD', 'CDF'], true) ? (string) $product[$currencyField] : 'USD';
    $amount = (float) ($product[$amountField] ?? $product[$usdField] ?? 0);
    $usd = (float) ($product[$usdField] ?? 0);
    $rate = max($exchangeRate, 0.0001);
    $formatted = static function (float $value, string $moneyCurrency): string {
        $decimals = $moneyCurrency === 'CDF' ? 0 : 2;

        return number_format($value, $decimals, ',', ' ') . ' ' . $moneyCurrency;
    };
    $main = $formatted($amount, $currency);

    if ($currency === 'USD') {
        return $main . '<span class="mt-1 block text-xs font-medium text-slate-500">' . $formatted($amount * $rate, 'CDF') . '</span>';
    }

    return $main . '<span class="mt-1 block text-xs font-medium text-slate-500">' . $formatted($usd, 'USD') . '</span>';
};
$expirationAlerts = count(array_filter($products, static function (array $product) use ($parseDate, $today, $expirationLimit): bool {
    if ((int) ($product['actif'] ?? 1) !== 1) {
        return false;
    }

    $date = $parseDate($product['date_expiration'] ?? null);

    return $date instanceof DateTimeImmutable && $date <= $expirationLimit;
}));

$icon = static function (string $name): string {
    $paths = [
        'plus' => '<path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'filter' => '<path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'search' => '<path d="m21 21-4.3-4.3M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'eye' => '<path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="2"/>',
        'edit' => '<path d="M4 20h4l10.5-10.5a2.8 2.8 0 0 0-4-4L4 16v4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="m13.5 6.5 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'box' => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Zm0 9 8-4.5M12 12 4 7.5M12 12v9" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
    ];

    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . ($paths[$name] ?? $paths['box']) . '</svg>';
};
?>

<section class="space-y-5" data-products-page>
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Catalogue</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Liste des produits</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Suivez les prix, les références et les seuils d’alerte stock par boutique.
            </p>
        </div>
        <a class="btn-primary w-full gap-2 sm:w-auto" href="<?= $url('/products/create') ?>">
            <?= $icon('plus') ?>
            <span>Ajouter un produit</span>
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="stat-card">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm text-slate-500">Produits actifs</p>
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-teal-50 text-teal-700"><?= $icon('box') ?></span>
            </div>
            <p class="mt-2 text-2xl font-bold"><?= $activeProducts ?></p>
        </article>
        <article class="stat-card">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm text-slate-500">Alertes stock</p>
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-amber-50 text-amber-700"><?= $icon('filter') ?></span>
            </div>
            <p class="mt-2 text-2xl font-bold text-amber-700"><?= $stockAlerts ?></p>
        </article>
        <article class="stat-card">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm text-slate-500">Alertes expiration</p>
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-orange-50 text-orange-700"><?= $icon('filter') ?></span>
            </div>
            <p class="mt-2 text-2xl font-bold text-orange-700"><?= $expirationAlerts ?></p>
        </article>
        <article class="stat-card">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm text-slate-500">Ruptures</p>
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-red-50 text-red-700"><?= $icon('trash') ?></span>
            </div>
            <p class="mt-2 text-2xl font-bold text-red-700"><?= $stockBreaks ?></p>
        </article>
    </div>

    <section class="surface-panel">
        <div class="panel-header">
            <div>
                <div class="flex items-center gap-2">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-slate-100 text-slate-600"><?= $icon('filter') ?></span>
                    <h2 class="font-bold text-slate-950">Filtres produits</h2>
                </div>
                <p class="mt-1 text-sm text-slate-500">Filtres visuels côté interface, sans rechargement de page.</p>
            </div>
            <button class="btn-secondary gap-2" type="button" data-products-reset>
                <span>Réinitialiser</span>
            </button>
        </div>

        <div class="mt-5 grid gap-3 lg:grid-cols-[1.4fr_.8fr_.8fr_.8fr]">
            <label class="relative block">
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"><?= $icon('search') ?></span>
                <input class="field-control pl-11" type="search" placeholder="Rechercher par nom, référence ou code-barres" data-products-search>
            </label>
            <select class="field-control" data-products-status>
                <option value="all">Tous les statuts</option>
                <option value="available">Disponible</option>
                <option value="alert">Alerte stock</option>
                <option value="expiration">Alerte expiration</option>
                <option value="break">Rupture</option>
            </select>
            <select class="field-control" data-products-stock>
                <option value="all">Tous les stocks</option>
                <option value="positive">Stock positif</option>
                <option value="low">Sous seuil minimum</option>
                <option value="zero">Stock à zéro</option>
            </select>
            <select class="field-control" data-products-price>
                <option value="all">Tous les prix</option>
                <option value="0-10">0 à 10 USD</option>
                <option value="10-50">10 à 50 USD</option>
                <option value="50+">50 USD et plus</option>
            </select>
        </div>
    </section>

    <section class="surface-panel">
        <div class="panel-header">
            <div>
                <h2 class="font-bold text-slate-950">Catalogue boutique</h2>
                <p class="mt-1 text-sm text-slate-500">
                    <span data-products-count><?= count($products) ?></span> produit(s) affiché(s).
                </p>
            </div>
            <div class="hidden rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-600 sm:block">
                Actions rapides disponibles
            </div>
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead>
                    <tr class="text-xs uppercase tracking-[.14em] text-slate-400">
                        <th class="px-4 py-3 font-semibold">Produit</th>
                        <th class="px-4 py-3 font-semibold">Code-barres</th>
                        <th class="px-4 py-3 font-semibold">Achat</th>
                        <th class="px-4 py-3 font-semibold">Vente</th>
                        <th class="px-4 py-3 font-semibold">Stock</th>
                        <th class="px-4 py-3 font-semibold">Alerte min.</th>
                        <th class="px-4 py-3 font-semibold">Fabrication</th>
                        <th class="px-4 py-3 font-semibold">Expiration</th>
                        <th class="px-4 py-3 font-semibold">Statut</th>
                        <th class="px-4 py-3 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" data-products-table>
                    <?php foreach ($products as $product): ?>
                        <?php
                        $stock = (int) $product['quantite_stock'];
                        $min = (int) $product['alerte_stock_min'];
                        $salePrice = (float) $product['prix_vente'];
                        $status = $stock === 0 ? 'Rupture' : ($stock <= $min ? 'Alerte' : 'Disponible');
                        $statusKey = $stock === 0 ? 'break' : ($stock <= $min ? 'alert' : 'available');
                        $statusClass = $stock === 0 ? 'bg-red-50 text-red-700' : ($stock <= $min ? 'bg-amber-50 text-amber-700' : 'bg-teal-50 text-teal-700');
                        $manufacturedAt = $parseDate($product['date_fabrication'] ?? null);
                        $expiresAt = $parseDate($product['date_expiration'] ?? null);
                        $expirationAlert = $expiresAt instanceof DateTimeImmutable && $expiresAt <= $expirationLimit;
                        $expirationText = 'Non definie';
                        $expirationClass = 'bg-slate-100 text-slate-600';

                        if ($expiresAt instanceof DateTimeImmutable) {
                            if ($expiresAt < $today) {
                                $expirationText = 'Expire';
                                $expirationClass = 'bg-red-50 text-red-700';
                            } elseif ($expiresAt <= $expirationLimit) {
                                $days = (int) $today->diff($expiresAt)->format('%a');
                                $expirationText = $days === 0 ? 'Aujourd hui' : 'Dans ' . $days . ' j';
                                $expirationClass = 'bg-orange-50 text-orange-700';
                            } else {
                                $expirationText = $formatDate($expiresAt);
                                $expirationClass = 'bg-teal-50 text-teal-700';
                            }
                        }
                        $searchText = strtolower(trim((string) ($product['nom'] ?? '') . ' ' . (string) ($product['ref'] ?? '') . ' ' . (string) ($product['code_barre'] ?? '')));
                        ?>
                        <tr
                            class="hover:bg-slate-50"
                            data-product-row
                            data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>"
                            data-status="<?= $statusKey ?>"
                            data-stock="<?= $stock ?>"
                            data-min="<?= $min ?>"
                            data-price="<?= $salePrice ?>"
                            data-expiration-alert="<?= $expirationAlert ? '1' : '0' ?>"
                        >
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-600"><?= $icon('box') ?></span>
                                    <span class="min-w-0">
                                        <span class="block truncate font-semibold text-slate-950"><?= htmlspecialchars((string) $product['nom'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="block truncate text-xs text-slate-500"><?= htmlspecialchars((string) $product['ref'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-slate-600"><?= htmlspecialchars((string) $product['code_barre'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-4 font-semibold"><?= $formatProductMoney($product, 'purchase') ?></td>
                            <td class="px-4 py-4 font-semibold"><?= $formatProductMoney($product, 'sale') ?></td>
                            <td class="px-4 py-4">
                                <span class="font-bold"><?= $stock ?></span>
                            </td>
                            <td class="px-4 py-4"><?= $min ?></td>
                            <td class="px-4 py-4 text-slate-600"><?= $formatDate($manufacturedAt) ?></td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold <?= $expirationClass ?>"><?= htmlspecialchars($expirationText, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($expiresAt instanceof DateTimeImmutable): ?>
                                    <span class="mt-1 block text-xs text-slate-500"><?= $formatDate($expiresAt) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold <?= $statusClass ?>"><?= $status ?></span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-950 focus:outline-none focus:ring-4 focus:ring-slate-200" href="<?= $url('/products/' . (int) $product['id']) ?>" title="Voir le produit" aria-label="Voir le produit">
                                        <?= $icon('eye') ?>
                                    </a>
                                    <a class="grid h-9 w-9 place-items-center rounded-lg border border-blue-100 bg-blue-50 text-blue-700 transition hover:bg-blue-100 focus:outline-none focus:ring-4 focus:ring-blue-100" href="<?= $url('/products/' . (int) $product['id'] . '/edit') ?>" title="Modifier le produit" aria-label="Modifier le produit">
                                        <?= $icon('edit') ?>
                                    </a>
                                    <form method="post" action="<?= $url('/products/' . (int) $product['id'] . '/delete') ?>" data-confirm-form>
                                        <button
                                            class="grid h-9 w-9 place-items-center rounded-lg border border-red-100 bg-red-50 text-red-700 transition hover:bg-red-100 focus:outline-none focus:ring-4 focus:ring-red-100"
                                            type="button"
                                            title="Supprimer le produit"
                                            aria-label="Supprimer le produit"
                                            data-confirm
                                            data-confirm-title="Supprimer ce produit ?"
                                            data-confirm-message="Le produit sera désactivé dans le catalogue de la boutique."
                                            data-confirm-accept="Oui, supprimer"
                                            data-confirm-progress="Suppression..."
                                        >
                                            <?= $icon('trash') ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="hidden rounded-lg border border-dashed border-slate-200 p-6 text-center text-sm font-semibold text-slate-500" data-products-empty>
                Aucun produit ne correspond aux filtres sélectionnés.
            </div>
        </div>
    </section>
</section>

<script>
    (() => {
        const root = document.querySelector('[data-products-page]');

        if (!root) {
            return;
        }

        const rows = [...root.querySelectorAll('[data-product-row]')];
        const search = root.querySelector('[data-products-search]');
        const status = root.querySelector('[data-products-status]');
        const stock = root.querySelector('[data-products-stock]');
        const price = root.querySelector('[data-products-price]');
        const count = root.querySelector('[data-products-count]');
        const empty = root.querySelector('[data-products-empty]');
        const reset = root.querySelector('[data-products-reset]');

        const matchesPrice = (value, range) => {
            if (range === 'all') {
                return true;
            }

            if (range === '50+') {
                return value >= 50;
            }

            const [min, max] = range.split('-').map(Number);
            return value >= min && value < max;
        };

        const matchesStock = (row, filter) => {
            const value = Number(row.dataset.stock || 0);
            const min = Number(row.dataset.min || 0);

            if (filter === 'positive') {
                return value > 0;
            }

            if (filter === 'low') {
                return value <= min;
            }

            if (filter === 'zero') {
                return value === 0;
            }

            return true;
        };

        const applyFilters = () => {
            const query = (search?.value || '').trim().toLowerCase();
            const statusValue = status?.value || 'all';
            const stockValue = stock?.value || 'all';
            const priceValue = price?.value || 'all';
            let visible = 0;

            rows.forEach((row) => {
                const isVisible =
                    (query === '' || (row.dataset.search || '').includes(query)) &&
                    (statusValue === 'all' || row.dataset.status === statusValue || (statusValue === 'expiration' && row.dataset.expirationAlert === '1')) &&
                    matchesStock(row, stockValue) &&
                    matchesPrice(Number(row.dataset.price || 0), priceValue);

                row.classList.toggle('hidden', !isVisible);
                visible += isVisible ? 1 : 0;
            });

            if (count) {
                count.textContent = String(visible);
            }

            empty?.classList.toggle('hidden', visible !== 0);
        };

        [search, status, stock, price].forEach((control) => {
            control?.addEventListener('input', applyFilters);
            control?.addEventListener('change', applyFilters);
        });

        reset?.addEventListener('click', () => {
            if (search) {
                search.value = '';
            }
            if (status) {
                status.value = 'all';
            }
            if (stock) {
                stock.value = 'all';
            }
            if (price) {
                price.value = 'all';
            }
            applyFilters();
        });

        applyFilters();
    })();
</script>
