<?php

$products = is_array($products ?? null) ? $products : [];
$recentMovements = is_array($recentMovements ?? null) ? $recentMovements : [];
$stockStats = is_array($stockStats ?? null) ? $stockStats : [];
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$icon = static function (string $name): string {
    $paths = [
        'arrow' => '<path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'save' => '<path d="M5 4h12l2 2v14H5V4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M8 4v6h8V4M8 20v-6h8v6" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'stock' => '<path d="M4 19V5m0 14h16M8 16V9m4 7V6m4 10v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
    ];
    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . ($paths[$name] ?? $paths['stock']) . '</svg>';
};
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Stock</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Ajustement de stock</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Enregistrez une entree, une sortie manuelle ou un stock final apres inventaire.
            </p>
        </div>
        <a class="btn-secondary gap-2" href="<?= $url('/stock/movements') ?>">
            <?= $icon('arrow') ?>
            <span>Mouvements</span>
        </a>
    </div>

    <form class="grid gap-5 xl:grid-cols-[1fr_22rem]" method="post" action="<?= $url('/stock/adjustments') ?>" accept-charset="UTF-8">
        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Nouveau mouvement</h2>
                    <p class="mt-1 text-sm text-slate-500">Chaque operation cree une ligne immutable dans le journal stock.</p>
                </div>
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-teal-50 text-teal-700"><?= $icon('stock') ?></span>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <label class="space-y-2 lg:col-span-2">
                    <span class="text-sm font-semibold text-slate-700">Produit</span>
                    <select class="field-control" name="product_id" required>
                        <option value="">Selectionner un produit</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= (int) ($product['id'] ?? 0) ?>">
                                <?= $safe($product['nom'] ?? 'Produit') ?> - stock <?= (int) ($product['quantite_stock'] ?? 0) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-slate-700">Operation</span>
                    <select class="field-control" name="type_mouvement" required>
                        <option value="ajustement">Stock final apres inventaire</option>
                        <option value="entree">Entree manuelle</option>
                        <option value="sortie">Sortie manuelle</option>
                    </select>
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-slate-700">Quantite / stock final</span>
                    <input class="field-control" name="quantite" type="number" min="1" step="1" required placeholder="Ex: 12">
                </label>

                <label class="space-y-2 lg:col-span-2">
                    <span class="text-sm font-semibold text-slate-700">Motif</span>
                    <textarea class="field-control min-h-28" name="motif" maxlength="255" required placeholder="Ex: Correction inventaire physique, casse, retour boutique"></textarea>
                </label>
            </div>
        </section>

        <aside class="space-y-5">
            <section class="surface-panel">
                <h2 class="font-bold text-slate-950">Synthese stock</h2>
                <div class="mt-4 space-y-3">
                    <div class="signal-row"><span class="text-slate-500">Produits actifs</span><strong><?= (int) ($stockStats['active_products'] ?? 0) ?></strong></div>
                    <div class="signal-row"><span class="text-slate-500">Unites en stock</span><strong><?= (int) ($stockStats['units'] ?? 0) ?></strong></div>
                    <div class="signal-row"><span class="text-slate-500">Alertes</span><strong class="text-amber-700"><?= (int) ($stockStats['stock_alerts'] ?? 0) ?></strong></div>
                    <div class="signal-row"><span class="text-slate-500">Ruptures</span><strong class="text-red-700"><?= (int) ($stockStats['ruptures'] ?? 0) ?></strong></div>
                </div>
            </section>

            <section class="surface-panel">
                <h2 class="font-bold text-slate-950">Derniers mouvements</h2>
                <div class="mt-4 space-y-3">
                    <?php if ($recentMovements === []): ?>
                        <p class="text-sm text-slate-500">Aucun mouvement recent.</p>
                    <?php endif; ?>
                    <?php foreach ($recentMovements as $movement): ?>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="truncate text-sm font-bold text-slate-950"><?= $safe($movement['product_name'] ?? 'Produit') ?></p>
                            <p class="mt-1 text-xs text-slate-500"><?= $safe($movement['type_mouvement'] ?? '-') ?> - <?= (int) ($movement['quantite'] ?? 0) ?> unite(s)</p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="flex flex-col gap-3">
                <button class="btn-primary w-full gap-2" type="submit">
                    <?= $icon('save') ?>
                    <span>Enregistrer le mouvement</span>
                </button>
                <a class="btn-secondary w-full" href="<?= $url('/stock/movements') ?>">Annuler</a>
            </div>
        </aside>
    </form>
</section>
