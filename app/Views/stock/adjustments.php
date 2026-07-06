<?php

$products = is_array($products ?? null) ? $products : [];
$recentMovements = is_array($recentMovements ?? null) ? $recentMovements : [];
$stockStats = is_array($stockStats ?? null) ? $stockStats : [];
$inventoryMode = (string) ($inventoryMode ?? 'adjustment');
$isCompleteInventory = $inventoryMode === 'complete';
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$icon = static function (string $name): string {
    $paths = [
        'arrow' => '<path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'save' => '<path d="M5 4h12l2 2v14H5V4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M8 4v6h8V4M8 20v-6h8v6" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'stock' => '<path d="M4 19V5m0 14h16M8 16V9m4 7V6m4 10v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'search' => '<path d="m21 21-4.3-4.3M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
    ];
    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . ($paths[$name] ?? $paths['stock']) . '</svg>';
};
?>

<section class="space-y-5 <?= $isCompleteInventory ? 'is-complete-inventory' : 'is-stock-adjustment' ?>" data-stock-adjustments>
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Stock</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950"><?= $isCompleteInventory ? 'Inventaire complet' : 'Ajustement de stock' ?></h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                <?= $isCompleteInventory
                    ? 'Renseignez le stock physique final de chaque produit, puis validez tout l’inventaire en une seule action.'
                    : 'Sélectionnez l’opération sur une ligne produit, puis saisissez le stock final ou la quantité à mouvementer.' ?>
            </p>
        </div>
        <a class="btn-secondary gap-2" href="<?= $url('/stock/movements') ?>">
            <?= $icon('arrow') ?>
            <span>Mouvements</span>
        </a>
    </div>

    <div class="grid gap-5 xl:grid-cols-[1fr_22rem]">
        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950"><?= $isCompleteInventory ? 'Comptage complet des produits' : 'Inventaire par produit' ?></h2>
                    <p class="mt-1 text-sm text-slate-500">
                        <?= $isCompleteInventory
                            ? 'Chaque champ est prérempli avec le stock actuel. Modifiez seulement les écarts constatés.'
                            : 'Le stock disponible avant inventaire et le seuil d’alerte sont visibles avant validation.' ?>
                    </p>
                </div>
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-teal-50 text-teal-700"><?= $icon('stock') ?></span>
            </div>

            <div class="mt-5">
                <label class="relative block max-w-xl">
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"><?= $icon('search') ?></span>
                    <input class="field-control pl-11" type="search" placeholder="Rechercher un produit" data-adjustment-search>
                </label>
            </div>

            <?php if ($isCompleteInventory): ?>
                <form class="mt-5" method="post" action="<?= $url('/stock/adjustments') ?>" accept-charset="UTF-8">
                    <input type="hidden" name="inventory_mode" value="complete">
            <?php endif; ?>

            <div class="responsive-table mt-5 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead>
                        <tr class="text-xs uppercase tracking-[.14em] text-slate-400">
                            <th class="px-4 py-3 font-semibold">Produit</th>
                            <th class="px-4 py-3 font-semibold">Stock avant inventaire</th>
                            <th class="px-4 py-3 font-semibold">Seuil d’alerte</th>
                            <?php if (!$isCompleteInventory): ?>
                                <th class="px-4 py-3 font-semibold">Opération</th>
                            <?php endif; ?>
                            <th class="px-4 py-3 font-semibold"><?= $isCompleteInventory ? 'Stock physique final' : 'Stock / quantité' ?></th>
                            <th class="px-4 py-3 text-right font-semibold"><?= $isCompleteInventory ? 'État' : 'Validation' ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($products as $product): ?>
                            <?php
                            $stock = (int) ($product['quantite_stock'] ?? 0);
                            $min = (int) ($product['alerte_stock_min'] ?? 0);
                            $productId = (int) ($product['id'] ?? 0);
                            $formId = 'stock-adjustment-' . $productId;
                            $statusClass = $stock === 0 ? 'bg-red-50 text-red-700' : ($stock <= $min ? 'bg-amber-50 text-amber-700' : 'bg-teal-50 text-teal-700');
                            $searchText = strtolower(trim((string) ($product['nom'] ?? '') . ' ' . (string) ($product['ref'] ?? '') . ' ' . (string) ($product['code_barre'] ?? '')));
                            ?>
                            <tr class="align-middle hover:bg-slate-50" data-adjustment-row data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>">
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-600"><?= $icon('stock') ?></span>
                                        <span class="min-w-0">
                                            <span class="block truncate font-semibold text-slate-950"><?= $safe($product['nom'] ?? 'Produit') ?></span>
                                            <span class="block truncate text-xs text-slate-500"><?= $safe($product['ref'] ?? 'Sans référence') ?></span>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold <?= $statusClass ?>"><?= $stock ?> unité(s)</span>
                                </td>
                                <td class="px-4 py-4 font-semibold text-slate-700"><?= $min ?> unité(s)</td>
                                <?php if (!$isCompleteInventory): ?>
                                    <td class="px-4 py-4">
                                        <select class="field-control h-10 min-w-40 py-0" name="type_mouvement" form="<?= $formId ?>" aria-label="Opération" required>
                                            <option value="ajustement">Stock final</option>
                                            <option value="entree">Entrée</option>
                                            <option value="sortie">Sortie</option>
                                        </select>
                                    </td>
                                <?php endif; ?>
                                <td class="px-4 py-4">
                                    <?php if ($isCompleteInventory): ?>
                                        <input class="field-control h-10 min-w-32 py-0" name="items[<?= $productId ?>]" type="number" min="0" step="1" value="<?= $stock ?>" required>
                                    <?php else: ?>
                                        <input class="field-control h-10 min-w-32 py-0" name="quantite" form="<?= $formId ?>" type="number" min="0" step="1" required placeholder="Ex: <?= $stock ?>">
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <?php if ($isCompleteInventory): ?>
                                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">À contrôler</span>
                                    <?php else: ?>
                                        <form id="<?= $formId ?>" method="post" action="<?= $url('/stock/adjustments') ?>" accept-charset="UTF-8">
                                            <input type="hidden" name="product_id" value="<?= $productId ?>">
                                        </form>
                                        <button class="btn-primary h-10 gap-2 px-4" type="submit" form="<?= $formId ?>">
                                            <?= $icon('save') ?>
                                            <span>Valider</span>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="<?= $products === [] ? '' : 'hidden' ?> rounded-lg border border-dashed border-slate-200 p-6 text-center text-sm font-semibold text-slate-500" data-adjustment-empty>
                    Aucun produit actif disponible pour l’inventaire.
                </div>
            </div>

            <?php if ($isCompleteInventory): ?>
                    <div class="mt-5 flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-slate-500">Les produits inchangés seront conservés sans mouvement inutile.</p>
                        <button class="btn-primary gap-2" type="submit">
                            <?= $icon('save') ?>
                            <span>Valider l’inventaire complet</span>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </section>

        <aside class="space-y-5">
            <section class="surface-panel">
                <h2 class="font-bold text-slate-950">Synthèse stock</h2>
                <div class="mt-4 space-y-3">
                    <div class="signal-row"><span class="text-slate-500">Produits actifs</span><strong><?= (int) ($stockStats['active_products'] ?? 0) ?></strong></div>
                    <div class="signal-row"><span class="text-slate-500">Unités en stock</span><strong><?= (int) ($stockStats['units'] ?? 0) ?></strong></div>
                    <div class="signal-row"><span class="text-slate-500">Alertes</span><strong class="text-amber-700"><?= (int) ($stockStats['stock_alerts'] ?? 0) ?></strong></div>
                    <div class="signal-row"><span class="text-slate-500">Alertes expiration</span><strong class="text-orange-700"><?= (int) ($stockStats['expiration_alerts'] ?? 0) ?></strong></div>
                    <div class="signal-row"><span class="text-slate-500">Ruptures</span><strong class="text-red-700"><?= (int) ($stockStats['ruptures'] ?? 0) ?></strong></div>
                </div>
            </section>

            <section class="surface-panel">
                <h2 class="font-bold text-slate-950">Derniers mouvements</h2>
                <div class="mt-4 space-y-3">
                    <?php if ($recentMovements === []): ?>
                        <p class="text-sm text-slate-500">Aucun mouvement récent.</p>
                    <?php endif; ?>
                    <?php foreach ($recentMovements as $movement): ?>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="truncate text-sm font-bold text-slate-950"><?= $safe($movement['product_name'] ?? 'Produit') ?></p>
                            <p class="mt-1 text-xs text-slate-500"><?= $safe($movement['type_mouvement'] ?? '-') ?> - <?= (int) ($movement['quantite'] ?? 0) ?> unité(s)</p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </aside>
    </div>
</section>

<script>
    (() => {
        const root = document.querySelector('[data-stock-adjustments]');

        if (!root) {
            return;
        }

        const search = root.querySelector('[data-adjustment-search]');
        const rows = [...root.querySelectorAll('[data-adjustment-row]')];
        const empty = root.querySelector('[data-adjustment-empty]');

        const filterRows = () => {
            const query = (search?.value || '').trim().toLowerCase();
            let visible = 0;

            rows.forEach((row) => {
                const isVisible = query === '' || (row.dataset.search || '').includes(query);
                row.classList.toggle('hidden', !isVisible);
                visible += isVisible ? 1 : 0;
            });

            empty?.classList.toggle('hidden', visible !== 0);
        };

        search?.addEventListener('input', filterRows);
        filterRows();
    })();
</script>
