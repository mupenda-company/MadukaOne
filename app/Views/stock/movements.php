<?php

$movements = is_array($movements ?? null) ? $movements : [];
$products = is_array($products ?? null) ? $products : [];
$stockStats = is_array($stockStats ?? null) ? $stockStats : [];
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$dateLabel = static function ($value): string {
    $timestamp = strtotime((string) ($value ?? ''));
    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : '-';
};
$typeLabel = static fn (string $type): string => match ($type) {
    'entree' => 'Entree',
    'sortie' => 'Sortie',
    'annulation' => 'Annulation',
    default => 'Ajustement',
};
$typeClass = static fn (string $type): string => match ($type) {
    'entree' => 'bg-teal-50 text-teal-700',
    'sortie' => 'bg-red-50 text-red-700',
    'annulation' => 'bg-amber-50 text-amber-700',
    default => 'bg-blue-50 text-blue-700',
};
$icon = static function (string $name): string {
    $paths = [
        'plus' => '<path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'search' => '<path d="m21 21-4.3-4.3M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'filter' => '<path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'stock' => '<path d="M4 19V5m0 14h16M8 16V9m4 7V6m4 10v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
    ];
    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . ($paths[$name] ?? $paths['stock']) . '</svg>';
};
?>

<section class="space-y-5" data-stock-page>
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Stock</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Mouvements de stock</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Journal immuable des entrees, sorties et ajustements de la boutique active.
            </p>
        </div>
        <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
            <a class="btn-primary w-full gap-2 sm:w-auto" href="<?= $url('/stock/adjustments', ['mode' => 'initial']) ?>">
                <?= $icon('plus') ?>
                <span>Inventaire initial</span>
            </a>
            <a class="btn-secondary w-full gap-2 sm:w-auto" href="<?= $url('/stock/adjustments', ['mode' => 'complete']) ?>">
                <?= $icon('stock') ?>
                <span>Inventaire complet</span>
            </a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <article class="stat-card"><p class="text-sm text-slate-500">Produits actifs</p><p class="mt-2 text-2xl font-bold"><?= (int) ($stockStats['active_products'] ?? 0) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Unites en stock</p><p class="mt-2 text-2xl font-bold"><?= (int) ($stockStats['units'] ?? 0) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Alertes stock</p><p class="mt-2 text-2xl font-bold text-amber-700"><?= (int) ($stockStats['stock_alerts'] ?? 0) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Alertes expiration</p><p class="mt-2 text-2xl font-bold text-orange-700"><?= (int) ($stockStats['expiration_alerts'] ?? 0) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Ruptures</p><p class="mt-2 text-2xl font-bold text-red-700"><?= (int) ($stockStats['ruptures'] ?? 0) ?></p></article>
    </div>

    <section class="surface-panel">
        <div class="panel-header gap-4">
            <div>
                <h2 class="font-bold text-slate-950">Historique des mouvements</h2>
                <p class="mt-1 text-sm text-slate-500">Recherche et filtres visuels sur les mouvements charges.</p>
            </div>
            <span class="grid h-10 w-10 place-items-center rounded-lg bg-teal-50 text-teal-700"><?= $icon('stock') ?></span>
        </div>

        <div class="mt-5 grid gap-3 lg:grid-cols-[1fr_14rem_14rem]">
            <label class="relative">
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"><?= $icon('search') ?></span>
                <input class="field-control pl-11" type="search" data-stock-search placeholder="Rechercher produit, motif, agent">
            </label>
            <label class="relative">
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"><?= $icon('filter') ?></span>
                <select class="field-control pl-11" data-stock-type>
                    <option value="all">Tous types</option>
                    <option value="entree">Entrees</option>
                    <option value="sortie">Sorties</option>
                    <option value="ajustement">Ajustements</option>
                    <option value="annulation">Annulations</option>
                </select>
            </label>
            <label class="relative">
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"><?= $icon('stock') ?></span>
                <select class="field-control pl-11" data-stock-product>
                    <option value="all">Tous produits</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= (int) ($product['id'] ?? 0) ?>"><?= $safe($product['nom'] ?? 'Produit') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div class="mt-5 overflow-hidden rounded-lg border border-slate-200">
            <div class="hidden grid-cols-[1.1fr_.7fr_.75fr_.75fr_.75fr_1fr_.85fr] gap-4 border-b border-slate-200 bg-slate-50 px-4 py-3 text-xs font-bold uppercase tracking-[.16em] text-slate-400 lg:grid">
                <span>Produit</span><span>Type</span><span>Quantite</span><span>Avant</span><span>Apres</span><span>Motif</span><span>Date</span>
            </div>
            <div class="divide-y divide-slate-200" data-stock-list>
                <?php if ($movements === []): ?>
                    <div class="px-4 py-10 text-center text-sm text-slate-500">Aucun mouvement de stock enregistre.</div>
                <?php endif; ?>
                <?php foreach ($movements as $movement): ?>
                    <?php
                    $type = (string) ($movement['type_mouvement'] ?? 'ajustement');
                    $productId = (int) ($movement['product_id'] ?? 0);
                    $searchText = strtolower((string) ($movement['product_name'] ?? '') . ' ' . ($movement['product_ref'] ?? '') . ' ' . ($movement['motif'] ?? '') . ' ' . ($movement['user_name'] ?? ''));
                    ?>
                    <article class="grid gap-3 px-4 py-4 transition hover:bg-slate-50 lg:grid-cols-[1.1fr_.7fr_.75fr_.75fr_.75fr_1fr_.85fr] lg:items-center" data-stock-row data-search="<?= $safe($searchText) ?>" data-type="<?= $safe($type) ?>" data-product="<?= $productId ?>">
                        <div class="min-w-0">
                            <p class="truncate font-bold text-slate-950"><?= $safe($movement['product_name'] ?? 'Produit') ?></p>
                            <p class="mt-1 text-xs text-slate-500"><?= $safe($movement['product_ref'] ?? 'Sans reference') ?></p>
                        </div>
                        <div><span class="inline-flex rounded-full px-3 py-1 text-xs font-bold <?= $typeClass($type) ?>"><?= $typeLabel($type) ?></span></div>
                        <div class="font-bold text-slate-950"><?= (int) ($movement['quantite'] ?? 0) ?></div>
                        <div class="text-sm text-slate-600"><?= (int) ($movement['stock_avant'] ?? 0) ?></div>
                        <div class="text-sm font-semibold text-slate-950"><?= (int) ($movement['stock_apres'] ?? 0) ?></div>
                        <div class="min-w-0 text-sm text-slate-600"><p class="truncate"><?= $safe($movement['motif'] ?? '-') ?></p><p class="mt-1 text-xs text-slate-400"><?= $safe($movement['user_name'] ?? '-') ?></p></div>
                        <div class="text-sm text-slate-600"><?= $safe($dateLabel($movement['date_mouvement'] ?? null)) ?></div>
                    </article>
                <?php endforeach; ?>
                <div class="hidden px-4 py-10 text-center text-sm text-slate-500" data-stock-empty>Aucun mouvement ne correspond aux filtres.</div>
            </div>
        </div>
    </section>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var page = document.querySelector('[data-stock-page]');
    var search = page?.querySelector('[data-stock-search]');
    var type = page?.querySelector('[data-stock-type]');
    var product = page?.querySelector('[data-stock-product]');
    var rows = Array.prototype.slice.call(page?.querySelectorAll('[data-stock-row]') || []);
    var empty = page?.querySelector('[data-stock-empty]');
    var apply = function () {
        var query = (search?.value || '').trim().toLowerCase();
        var selectedType = type?.value || 'all';
        var selectedProduct = product?.value || 'all';
        var visibleCount = 0;
        rows.forEach(function (row) {
            var visible = (query === '' || (row.getAttribute('data-search') || '').indexOf(query) !== -1)
                && (selectedType === 'all' || row.getAttribute('data-type') === selectedType)
                && (selectedProduct === 'all' || row.getAttribute('data-product') === selectedProduct);
            row.hidden = !visible;
            if (visible) visibleCount++;
        });
        empty?.classList.toggle('hidden', visibleCount !== 0);
    };
    search?.addEventListener('input', apply);
    type?.addEventListener('change', apply);
    product?.addEventListener('change', apply);
});
</script>
