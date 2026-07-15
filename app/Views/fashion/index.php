<?php

$products = is_array($products ?? null) ? $products : [];
$stats = is_array($stats ?? null) ? $stats : [];
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$badge = static function (bool $ok): string {
    $class = $ok ? 'bg-teal-50 text-teal-700' : 'bg-amber-50 text-amber-700';
    $label = $ok ? 'Configure' : 'A completer';

    return '<span class="rounded-lg px-2 py-1 text-xs font-bold ' . $class . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
};
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Vetements</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Gestion vetements</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">Pilotez les tailles, couleurs, marques, collections, matieres et variantes textiles sans dupliquer le catalogue produit.</p>
        </div>
        <a class="btn-primary w-auto px-4" href="<?= $url('/products/create') ?>">Nouvel article</a>
    </div>

    <div class="grid gap-4 md:grid-cols-5">
        <article class="stat-card"><p class="text-sm text-slate-500">Articles</p><p class="mt-2 text-2xl font-bold"><?= (int) ($stats['total'] ?? 0) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Fiches completes</p><p class="mt-2 text-2xl font-bold text-teal-700"><?= (int) ($stats['configured'] ?? 0) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Variantes</p><p class="mt-2 text-2xl font-bold text-blue-700"><?= (int) ($stats['variants'] ?? 0) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Collections</p><p class="mt-2 text-2xl font-bold text-indigo-700"><?= (int) ($stats['collections'] ?? 0) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Marques</p><p class="mt-2 text-2xl font-bold text-slate-950"><?= (int) ($stats['brands'] ?? 0) ?></p></article>
    </div>

    <section class="surface-panel">
        <div class="panel-header">
            <div>
                <h2 class="font-bold text-slate-950">Catalogue textile</h2>
                <p class="mt-1 text-sm text-slate-500">Les ventes, approvisionnements et mouvements de stock restent centralises dans les modules existants.</p>
            </div>
            <a class="btn-secondary" href="<?= $url('/stock/movements') ?>">Stock</a>
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Article</th>
                        <th>Variante</th>
                        <th>Marque / collection</th>
                        <th>Profil</th>
                        <th>Stock</th>
                        <th>Statut</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <?php $configured = trim((string) ($product['taille'] ?? $product['couleur'] ?? $product['marque'] ?? '')) !== ''; ?>
                        <tr>
                            <td><span class="font-bold text-slate-950"><?= $safe($product['nom'] ?? '') ?></span><span class="mt-1 block text-xs text-slate-500"><?= $safe($product['ref'] ?? '') ?></span></td>
                            <td><span class="font-semibold"><?= $safe($product['taille'] ?? '') ?></span><span class="block text-xs text-slate-500"><?= $safe($product['couleur'] ?? '') ?></span></td>
                            <td><?= $safe($product['marque'] ?? '') ?><span class="block text-xs text-slate-500"><?= $safe($product['collection'] ?? '') ?></span></td>
                            <td><?= $safe($product['sexe'] ?? 'mixte') ?><span class="block text-xs text-slate-500"><?= $safe($product['matiere'] ?? '') ?></span></td>
                            <td><?= (int) ($product['quantite_stock'] ?? 0) ?></td>
                            <td><?= $badge($configured) ?></td>
                            <td class="text-right"><a class="btn-secondary h-10 w-auto px-3 text-xs" href="<?= $url('/vetements/produits/' . (int) $product['id'] . '/details') ?>">Fiche</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($products === []): ?>
                        <tr><td colspan="7" class="py-8 text-center text-sm font-semibold text-slate-500">Aucun article dans cette boutique.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
