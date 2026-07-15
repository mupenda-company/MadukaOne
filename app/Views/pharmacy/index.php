<?php

$products = is_array($products ?? null) ? $products : [];
$stats = is_array($stats ?? null) ? $stats : [];
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$badge = static function (bool $ok, string $yes = 'Configure', string $no = 'A completer'): string {
    $class = $ok ? 'bg-teal-50 text-teal-700' : 'bg-amber-50 text-amber-700';
    $label = $ok ? $yes : $no;

    return '<span class="rounded-lg px-2 py-1 text-xs font-bold ' . $class . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
};
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Pharmacie</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Gestion pharmacie</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">Completez les fiches medicaments avec dosage, forme, lot, fabricant, ordonnance et alertes d expiration.</p>
        </div>
        <a class="btn-primary w-auto px-4" href="<?= $url('/products/create') ?>">Nouveau medicament</a>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <article class="stat-card"><p class="text-sm text-slate-500">Medicaments</p><p class="mt-2 text-2xl font-bold"><?= (int) ($stats['total'] ?? 0) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Fiches completes</p><p class="mt-2 text-2xl font-bold text-teal-700"><?= (int) ($stats['configured'] ?? 0) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Expiration proche</p><p class="mt-2 text-2xl font-bold text-amber-700"><?= (int) ($stats['expiring'] ?? 0) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Expires</p><p class="mt-2 text-2xl font-bold text-red-700"><?= (int) ($stats['expired'] ?? 0) ?></p></article>
    </div>

    <section class="surface-panel">
        <div class="panel-header">
            <div>
                <h2 class="font-bold text-slate-950">Catalogue pharmacie</h2>
                <p class="mt-1 text-sm text-slate-500">Les ventes et le stock restent geres par les modules existants.</p>
            </div>
            <a class="btn-secondary" href="<?= $url('/stock/movements') ?>">Stock</a>
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Medicament</th>
                        <th>Dosage / forme</th>
                        <th>Lot / fabricant</th>
                        <th>Expiration</th>
                        <th>Stock</th>
                        <th>Statut</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <?php
                        $configured = trim((string) ($product['dosage'] ?? $product['forme'] ?? $product['numero_lot'] ?? '')) !== '';
                        $expiration = trim((string) ($product['date_expiration'] ?? ''));
                        $expired = $expiration !== '' && $expiration < date('Y-m-d');
                        ?>
                        <tr>
                            <td><span class="font-bold text-slate-950"><?= $safe($product['nom'] ?? '') ?></span><span class="mt-1 block text-xs text-slate-500"><?= $safe($product['ref'] ?? '') ?></span></td>
                            <td><?= $safe(trim((string) ($product['dosage'] ?? '') . ' ' . (string) ($product['forme'] ?? ''))) ?></td>
                            <td><?= $safe($product['numero_lot'] ?? '') ?><span class="block text-xs text-slate-500"><?= $safe($product['fabricant'] ?? '') ?></span></td>
                            <td><span class="<?= $expired ? 'font-bold text-red-700' : 'text-slate-700' ?>"><?= $safe($expiration) ?></span></td>
                            <td><?= (int) ($product['quantite_stock'] ?? 0) ?></td>
                            <td><?= $badge($configured) ?> <?= (int) ($product['ordonnance_requise'] ?? 0) === 1 ? '<span class="ml-1 rounded-lg bg-blue-50 px-2 py-1 text-xs font-bold text-blue-700">Ordonnance</span>' : '' ?></td>
                            <td class="text-right"><a class="btn-secondary h-10 w-auto px-3 text-xs" href="<?= $url('/pharmacie/produits/' . (int) $product['id'] . '/details') ?>">Fiche</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($products === []): ?>
                        <tr><td colspan="7" class="py-8 text-center text-sm font-semibold text-slate-500">Aucun produit dans cette pharmacie.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
