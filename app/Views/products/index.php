
<?php $products = is_array($products ?? null) ? $products : []; ?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Catalogue</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Liste des produits</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Suivez les prix, les références et les seuils d’alerte stock par boutique.
            </p>
        </div>
        <a class="btn-primary w-full sm:w-auto" href="<?= $url('/products/create') ?>">Ajouter un produit</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <article class="stat-card">
            <p class="text-sm text-slate-500">Produits actifs</p>
            <p class="mt-2 text-2xl font-bold"><?= count($products) ?></p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Alertes stock</p>
            <p class="mt-2 text-2xl font-bold text-amber-700">
                <?= count(array_filter($products, static fn (array $product): bool => (int) $product['quantite_stock'] <= (int) $product['alerte_stock_min'])) ?>
            </p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Ruptures</p>
            <p class="mt-2 text-2xl font-bold text-red-700">
                <?= count(array_filter($products, static fn (array $product): bool => (int) $product['quantite_stock'] === 0)) ?>
            </p>
        </article>
    </div>

    <section class="surface-panel">
        <div class="panel-header">
            <div>
                <h2 class="font-bold text-slate-950">Catalogue boutique</h2>
                <p class="mt-1 text-sm text-slate-500">Vue prête pour la recherche, le tri et les actions CRUD.</p>
            </div>
            <input class="field-control max-w-xs" type="search" placeholder="Rechercher un produit">
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
                        <th class="px-4 py-3 text-right font-semibold">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($products as $product): ?>
                        <?php
                        $stock = (int) $product['quantite_stock'];
                        $min = (int) $product['alerte_stock_min'];
                        $status = $stock === 0 ? 'Rupture' : ($stock <= $min ? 'Alerte' : 'Disponible');
                        $statusClass = $stock === 0 ? 'bg-red-50 text-red-700' : ($stock <= $min ? 'bg-amber-50 text-amber-700' : 'bg-teal-50 text-teal-700');
                        ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-4">
                                <p class="font-semibold text-slate-950"><?= htmlspecialchars((string) $product['nom'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-slate-500"><?= htmlspecialchars((string) $product['ref'], ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-4 text-slate-600"><?= htmlspecialchars((string) $product['code_barre'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-4 font-semibold"><?= number_format((float) $product['prix_achat'], 2, ',', ' ') ?> USD</td>
                            <td class="px-4 py-4 font-semibold"><?= number_format((float) $product['prix_vente'], 2, ',', ' ') ?> USD</td>
                            <td class="px-4 py-4 font-bold"><?= $stock ?></td>
                            <td class="px-4 py-4"><?= $min ?></td>
                            <td class="px-4 py-4 text-right">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold <?= $statusClass ?>"><?= $status ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
