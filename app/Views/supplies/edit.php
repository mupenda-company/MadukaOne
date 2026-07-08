<?php

$supply = is_array($supply ?? null) ? $supply : [];
$details = is_array($details ?? null) ? $details : [];
$suppliers = is_array($suppliers ?? null) ? $suppliers : [];
$products = is_array($products ?? null) ? $products : [];
$supplyId = (int) ($supply['id'] ?? 0);
$safe = static fn ($value, string $fallback = ''): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$dateValue = static function ($value): string {
    $timestamp = strtotime((string) ($value ?? ''));
    return $timestamp !== false ? date('Y-m-d\TH:i', $timestamp) : date('Y-m-d\TH:i');
};
$icon = static function (string $name): string {
    $paths = [
        'arrow' => '<path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'save' => '<path d="M5 4h12l2 2v14H5V4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M8 4v6h8V4M8 20v-6h8v6" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
    ];
    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . ($paths[$name] ?? $paths['save']) . '</svg>';
};
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Approvisionnement</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Modifier l’approvisionnement</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Les écarts de quantité mettront le stock à jour avec des mouvements traçables.
            </p>
        </div>
        <a class="btn-secondary gap-2" href="<?= $url('/supplies/' . $supplyId) ?>"><?= $icon('arrow') ?><span>Détail</span></a>
    </div>

    <form class="space-y-5" method="post" action="<?= $url('/supplies/' . $supplyId . '/update') ?>" accept-charset="UTF-8">
        <section class="surface-panel">
            <div class="grid gap-4 sm:grid-cols-3">
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Fournisseur</span>
                    <select class="field-control" name="supplier_id">
                        <?php foreach ($suppliers as $supplier): ?>
                            <?php $selected = (int) ($supplier['id'] ?? 0) === (int) ($supply['supplier_id'] ?? 0); ?>
                            <option value="<?= (int) $supplier['id'] ?>" <?= $selected ? 'selected' : '' ?>><?= $safe($supplier['nom'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">N° arrivage</span>
                    <input class="field-control" name="numero_arrivage" type="text" value="<?= $safe($supply['numero_arrivage'] ?? '') ?>">
                </label>
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Date</span>
                    <input class="field-control" name="date_approvisionnement" type="datetime-local" value="<?= $dateValue($supply['date_approvisionnement'] ?? null) ?>">
                </label>
            </div>
        </section>

        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Articles reçus</h2>
                    <p class="mt-1 text-sm text-slate-500">Modifiez les quantités ou les prix, puis validez.</p>
                </div>
                <button class="btn-secondary" type="button" data-supply-add-line>Ajouter une ligne</button>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-[.14em] text-slate-400">
                            <th class="px-3 py-3">Produit</th>
                            <th class="px-3 py-3">Quantité</th>
                            <th class="px-3 py-3">Prix achat</th>
                            <th class="px-3 py-3">Total ligne</th>
                            <th class="px-3 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" data-supply-lines>
                        <?php foreach ($details as $detail): ?>
                            <tr>
                                <td class="px-3 py-3">
                                    <select class="field-control" name="product_id[]" data-supply-product>
                                        <?php foreach ($products as $product): ?>
                                            <?php $selected = (int) ($product['id'] ?? 0) === (int) ($detail['product_id'] ?? 0); ?>
                                            <option value="<?= (int) $product['id'] ?>" data-price="<?= (float) $product['prix_achat'] ?>" <?= $selected ? 'selected' : '' ?>>
                                                <?= $safe((string) ($product['ref'] ?? '') . ' - ' . (string) ($product['nom'] ?? '')) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="px-3 py-3"><input class="field-control min-w-24" name="quantite[]" type="number" min="1" step="1" value="<?= (int) ($detail['quantite'] ?? 1) ?>" data-supply-qty></td>
                                <td class="px-3 py-3"><input class="field-control min-w-28" name="prix_achat_facture[]" type="number" min="0" step="0.01" value="<?= number_format((float) ($detail['prix_achat_facture'] ?? 0), 2, '.', '') ?>" data-supply-price></td>
                                <td class="px-3 py-3 font-bold text-slate-950" data-supply-line-total>0,00 USD</td>
                                <td class="px-3 py-3 text-right"><button class="rounded-lg px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50" type="button" data-supply-remove>Retirer</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-lg font-bold text-slate-950">Total: <span data-supply-total>0,00 USD</span></p>
                <button class="btn-primary w-full gap-2 sm:w-auto" type="submit"><?= $icon('save') ?><span>Enregistrer les modifications</span></button>
            </div>
        </section>
    </form>
</section>

<template data-supply-row-template>
    <tr>
        <td class="px-3 py-3">
            <select class="field-control" name="product_id[]" data-supply-product>
                <?php foreach ($products as $product): ?>
                    <option value="<?= (int) $product['id'] ?>" data-price="<?= (float) $product['prix_achat'] ?>">
                        <?= $safe((string) ($product['ref'] ?? '') . ' - ' . (string) ($product['nom'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="px-3 py-3"><input class="field-control min-w-24" name="quantite[]" type="number" min="1" step="1" value="1" data-supply-qty></td>
        <td class="px-3 py-3"><input class="field-control min-w-28" name="prix_achat_facture[]" type="number" min="0" step="0.01" value="0.00" data-supply-price></td>
        <td class="px-3 py-3 font-bold text-slate-950" data-supply-line-total>0,00 USD</td>
        <td class="px-3 py-3 text-right"><button class="rounded-lg px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50" type="button" data-supply-remove>Retirer</button></td>
    </tr>
</template>

<script>
    (() => {
        const lines = document.querySelector('[data-supply-lines]');
        const template = document.querySelector('[data-supply-row-template]');
        const totalTarget = document.querySelector('[data-supply-total]');
        const money = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD' });

        const recalc = () => {
            let total = 0;
            lines?.querySelectorAll('tr').forEach((row) => {
                const qty = Number(row.querySelector('[data-supply-qty]')?.value || 0);
                const price = Number(row.querySelector('[data-supply-price]')?.value || 0);
                const lineTotal = qty * price;
                total += lineTotal;
                row.querySelector('[data-supply-line-total]').textContent = money.format(lineTotal);
            });
            if (totalTarget) totalTarget.textContent = money.format(total);
        };

        const addLine = () => {
            const fragment = template.content.cloneNode(true);
            const row = fragment.querySelector('tr');
            const product = row.querySelector('[data-supply-product]');
            const price = row.querySelector('[data-supply-price]');
            price.value = Number(product.selectedOptions[0]?.dataset.price || 0).toFixed(2);
            lines.appendChild(fragment);
            recalc();
        };

        document.querySelectorAll('[data-supply-add-line]').forEach((button) => button.addEventListener('click', addLine));
        lines?.addEventListener('input', recalc);
        lines?.addEventListener('change', (event) => {
            if (event.target.matches('[data-supply-product]')) {
                const row = event.target.closest('tr');
                row.querySelector('[data-supply-price]').value = Number(event.target.selectedOptions[0]?.dataset.price || 0).toFixed(2);
            }
            recalc();
        });
        lines?.addEventListener('click', (event) => {
            if (event.target.matches('[data-supply-remove]')) {
                event.target.closest('tr')?.remove();
                recalc();
            }
        });
        recalc();
    })();
</script>
