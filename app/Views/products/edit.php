<?php

$product = is_array($product ?? null) ? $product : [];
$productId = (int) ($product['id'] ?? 0);
$stock = (int) ($product['quantite_stock'] ?? 0);
$minStock = (int) ($product['alerte_stock_min'] ?? 0);
$purchasePrice = (float) ($product['prix_achat'] ?? 0);
$salePrice = (float) ($product['prix_vente'] ?? 0);
$margin = $salePrice - $purchasePrice;
$isActive = (int) ($product['actif'] ?? 1) === 1;
$stockStatus = $stock === 0 ? 'Rupture' : ($stock <= $minStock ? 'Alerte stock' : 'Disponible');
$stockStatusClass = $stock === 0 ? 'bg-red-50 text-red-700' : ($stock <= $minStock ? 'bg-amber-50 text-amber-700' : 'bg-teal-50 text-teal-700');
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
$expiresAt = $parseDate($product['date_expiration'] ?? null);
$expirationStatus = 'Aucune date d expiration';
$expirationStatusClass = 'bg-slate-100 text-slate-600';

if ($expiresAt instanceof DateTimeImmutable) {
    if ($expiresAt < $today) {
        $expirationStatus = 'Produit expire';
        $expirationStatusClass = 'bg-red-50 text-red-700';
    } elseif ($expiresAt <= $expirationLimit) {
        $days = (int) $today->diff($expiresAt)->format('%a');
        $expirationStatus = $days === 0 ? 'Expire aujourd hui' : 'Expire dans ' . $days . ' jour(s)';
        $expirationStatusClass = 'bg-orange-50 text-orange-700';
    } else {
        $expirationStatus = 'Expiration surveillee';
        $expirationStatusClass = 'bg-teal-50 text-teal-700';
    }
}

$value = static fn ($field, string $fallback = ''): string => htmlspecialchars((string) (($product[$field] ?? '') !== '' ? $product[$field] : $fallback), ENT_QUOTES, 'UTF-8');
$formatMoney = static fn (float $amount): string => number_format($amount, 2, ',', ' ') . ' USD';

$icon = static function (string $name): string {
    $paths = [
        'save' => '<path d="M5 4h12l2 2v14H5V4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M8 4v6h8V4M8 20v-6h8v6" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'arrow' => '<path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'box' => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Zm0 9 8-4.5M12 12 4 7.5M12 12v9" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'alert' => '<path d="M12 9v4M12 17h.01M10.3 4.5 2.7 18a2 2 0 0 0 1.8 3h15a2 2 0 0 0 1.8-3L13.7 4.5a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    ];

    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . ($paths[$name] ?? $paths['box']) . '</svg>';
};
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Catalogue</p>
            <h1 class="truncate text-3xl font-bold tracking-normal text-slate-950">Modifier le produit</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Ajustez les informations commerciales du produit. Le stock reste gere dans le module Stock pour conserver la tracabilite.
            </p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row">
            <a class="btn-secondary gap-2" href="<?= $url('/products/' . $productId) ?>">
                <?= $icon('arrow') ?>
                <span>Detail</span>
            </a>
            <a class="btn-secondary gap-2" href="<?= $url('/products') ?>">
                <span>Catalogue</span>
            </a>
        </div>
    </div>

    <form class="grid gap-5 xl:grid-cols-[1fr_22rem]" method="post" action="<?= $url('/products/' . $productId . '/update') ?>" accept-charset="UTF-8">
        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Informations principales</h2>
                    <p class="mt-1 text-sm text-slate-500">Nom, references, description et activation dans le catalogue.</p>
                </div>
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-teal-50 text-teal-700"><?= $icon('box') ?></span>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <label class="space-y-2 lg:col-span-2">
                    <span class="text-sm font-semibold text-slate-700">Nom du produit</span>
                    <input class="field-control" name="nom" type="text" value="<?= $value('nom') ?>" required placeholder="Ex: Savon carton 24 pcs">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-slate-700">Reference interne</span>
                    <input class="field-control" name="ref" type="text" value="<?= $value('ref') ?>" placeholder="REF-001">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-slate-700">Code-barres</span>
                    <input class="field-control" name="code_barre" type="text" value="<?= $value('code_barre') ?>" placeholder="Scanner ou saisir le code">
                </label>

                <label class="space-y-2 lg:col-span-2">
                    <span class="text-sm font-semibold text-slate-700">Description</span>
                    <textarea class="field-control min-h-32" name="description" placeholder="Details utiles pour les vendeurs et le stock"><?= $value('description') ?></textarea>
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-slate-700">Date de fabrication</span>
                    <input class="field-control" name="date_fabrication" type="date" value="<?= $value('date_fabrication') ?>">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-slate-700">Date d'expiration</span>
                    <input class="field-control" name="date_expiration" type="date" value="<?= $value('date_expiration') ?>">
                </label>

                <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 lg:col-span-2">
                    <span>
                        <span class="block text-sm font-semibold text-slate-900">Produit actif</span>
                        <span class="block text-sm text-slate-500">Visible dans le catalogue et la caisse POS.</span>
                    </span>
                    <input class="h-5 w-5 rounded border-slate-300 text-teal-700 focus:ring-teal-600" name="actif" type="checkbox" value="1" <?= $isActive ? 'checked' : '' ?>>
                </label>
            </div>
        </section>

        <aside class="space-y-5">
            <section class="surface-panel">
                <h2 class="font-bold text-slate-950">Prix et seuils</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Ces donnees alimentent les marges, alertes et rapports.</p>

                <div class="mt-5 space-y-4">
                    <label class="space-y-2">
                        <span class="text-sm font-semibold text-slate-700">Prix d'achat</span>
                        <input class="field-control" name="prix_achat" type="number" min="0" step="0.01" value="<?= $value('prix_achat', '0') ?>" required>
                    </label>
                    <label class="space-y-2">
                        <span class="text-sm font-semibold text-slate-700">Prix de vente</span>
                        <input class="field-control" name="prix_vente" type="number" min="0" step="0.01" value="<?= $value('prix_vente', '0') ?>" required>
                    </label>
                    <label class="space-y-2">
                        <span class="text-sm font-semibold text-slate-700">Alerte stock minimal</span>
                        <input class="field-control" name="alerte_stock_min" type="number" min="0" step="1" value="<?= $value('alerte_stock_min', '0') ?>" required>
                    </label>
                </div>

                <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-slate-500">Marge actuelle</span>
                        <strong class="<?= $margin < 0 ? 'text-red-700' : 'text-slate-950' ?>"><?= $formatMoney($margin) ?></strong>
                    </div>
                </div>
            </section>

            <section class="surface-panel">
                <div class="flex items-start gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-amber-50 text-amber-700"><?= $icon('alert') ?></span>
                    <div>
                        <h2 class="font-bold text-slate-950">Stock actuel</h2>
                        <p class="mt-1 text-sm text-slate-500">La quantite se corrige uniquement via les mouvements de stock.</p>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                    <span class="text-sm text-slate-500"><?= $stock ?> en stock</span>
                    <span class="rounded-full px-3 py-1 text-xs font-bold <?= $stockStatusClass ?>"><?= $stockStatus ?></span>
                </div>
            </section>

            <section class="surface-panel">
                <div class="flex items-start gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-orange-50 text-orange-700"><?= $icon('alert') ?></span>
                    <div>
                        <h2 class="font-bold text-slate-950">Expiration</h2>
                        <p class="mt-1 text-sm text-slate-500">Une alerte est affichee 30 jours avant la date d'expiration.</p>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                    <span class="text-sm text-slate-500"><?= $value('date_expiration', 'Non definie') ?></span>
                    <span class="rounded-full px-3 py-1 text-xs font-bold <?= $expirationStatusClass ?>"><?= htmlspecialchars($expirationStatus, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </section>

            <div class="flex flex-col gap-3">
                <button class="btn-primary w-full gap-2" type="submit">
                    <?= $icon('save') ?>
                    <span>Enregistrer les modifications</span>
                </button>
                <a class="btn-secondary w-full" href="<?= $url('/products/' . $productId) ?>">Annuler</a>
            </div>
        </aside>
    </form>
</section>
