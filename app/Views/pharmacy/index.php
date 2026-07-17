<?php
$products = is_array($products ?? null) ? $products : [];
$stats = is_array($stats ?? null) ? $stats : [];
$safe = static fn ($value, string $fallback = '—'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$today = new DateTimeImmutable('today');
$lowStock = count(array_filter($products, static fn (array $p): bool => (int) ($p['quantite_stock'] ?? 0) <= (int) ($p['alerte_stock_min'] ?? 0)));
$icon = static function (string $path, string $class = 'h-5 w-5'): string {
    return '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="' . $path . '" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
};
?>
<section class="space-y-5">
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-950 via-teal-900 to-cyan-800 p-6 text-white shadow-xl sm:p-8">
        <div class="absolute -right-16 -top-20 h-64 w-64 rounded-full bg-cyan-300/20 blur-3xl"></div>
        <div class="absolute bottom-0 right-1/3 h-36 w-36 rounded-full bg-emerald-300/10 blur-2xl"></div>
        <div class="relative flex flex-col gap-7 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <div class="mb-5 flex items-center gap-3"><span class="grid h-12 w-12 place-items-center rounded-2xl border border-white/20 bg-white/10"><?= $icon('M12 3v18M3 12h18', 'h-6 w-6') ?></span><div><p class="text-xs font-black uppercase tracking-[.2em] text-emerald-200">Espace officine</p><p class="mt-1 text-sm text-white/60">Pilotage pharmaceutique moderne</p></div></div>
                <h1 class="text-3xl font-black tracking-tight sm:text-4xl">Centre de gestion de la pharmacie</h1>
                <p class="mt-4 max-w-2xl text-sm leading-7 text-emerald-50/80">Supervisez les médicaments, lots, ordonnances, emplacements et dates de péremption depuis un espace clinique clair et sécurisé.</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row"><a class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-5 py-3 text-sm font-bold hover:bg-white/20" href="<?= $url('/stock/movements') ?>"><?= $icon('M4 19V5m0 14h16M8 15l3-4 3 2 5-7') ?> Stock</a><a class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-black text-teal-900 shadow-lg hover:bg-emerald-50" href="<?= $url('/products/create') ?>"><?= $icon('M12 5v14M5 12h14') ?> Nouveau médicament</a></div>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <?php
        $cards = [
            ['Médicaments', (int) ($stats['total'] ?? 0), 'from-cyan-500 to-blue-600', 'M4 7h16v10H4zM8 7V4h8v3'],
            ['Fiches complètes', (int) ($stats['configured'] ?? 0), 'from-emerald-500 to-teal-600', 'm5 12 4 4L19 6'],
            ['Stock critique', $lowStock, 'from-amber-400 to-orange-500', 'M12 9v4m0 4h.01M10 4 2 1 2 20h16a2 2 0 0 0 2-3L14 4a2 2 0 0 0-4 0'],
            ['Péremption proche', (int) ($stats['expiring'] ?? 0), 'from-orange-500 to-red-500', 'M12 8v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0'],
            ['Ordonnance', (int) ($stats['prescription'] ?? 0), 'from-violet-500 to-indigo-600', 'M6 3h9l3 3v15H6zM14 3v4h4M9 12h6M9 16h4'],
        ];
        foreach ($cards as [$label, $value, $gradient, $path]): ?>
            <article class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r <?= $gradient ?>"></div><div class="flex items-center justify-between"><p class="text-sm font-semibold text-slate-500"><?= $safe($label) ?></p><span class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br <?= $gradient ?> text-white shadow-sm"><?= $icon($path) ?></span></div><p class="mt-4 text-3xl font-black text-slate-950"><?= $value ?></p></article>
        <?php endforeach; ?>
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_300px]">
        <section class="surface-panel overflow-hidden p-0">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-xs font-black uppercase tracking-[.16em] text-teal-700">Inventaire clinique</p><h2 class="mt-2 text-xl font-bold">Médicaments et traçabilité</h2><p class="mt-1 text-sm text-slate-500">Informations pharmaceutiques essentielles et état du stock.</p></div><a class="btn-secondary" href="<?= $url('/products') ?>">Catalogue complet</a></div>
            <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Médicament</th><th>Présentation</th><th>Traçabilité</th><th>Péremption</th><th>Stock</th><th>Contrôle</th><th class="text-right">Action</th></tr></thead><tbody>
                <?php foreach ($products as $product):
                    $configured = trim((string) ($product['dosage'] ?? '') . (string) ($product['forme'] ?? '') . (string) ($product['numero_lot'] ?? '')) !== '';
                    $expiration = trim((string) ($product['date_expiration'] ?? ''));
                    $expired = $expiration !== '' && $expiration < $today->format('Y-m-d');
                    $stock = (int) ($product['quantite_stock'] ?? 0); $minimum = (int) ($product['alerte_stock_min'] ?? 0);
                ?>
                <tr><td><div class="flex items-center gap-3"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-50 font-black text-emerald-700"><?= strtoupper(substr((string) ($product['nom'] ?? 'M'), 0, 1)) ?></span><div><span class="font-bold text-slate-950"><?= $safe($product['nom']) ?></span><span class="mt-1 block text-xs text-slate-500"><?= $safe($product['ref']) ?> · <?= $safe($product['category_name'] ?? 'Sans catégorie') ?></span></div></div></td>
                <td><span class="font-semibold"><?= $safe(trim((string) ($product['dosage'] ?? '') . ' ' . (string) ($product['forme'] ?? ''))) ?></span><span class="mt-1 block text-xs text-slate-500"><?= $safe($product['fabricant'] ?? 'Fabricant non renseigné') ?></span></td>
                <td><span class="rounded-lg bg-slate-100 px-2 py-1 text-xs font-bold text-slate-700">Lot <?= $safe($product['numero_lot'] ?? '—') ?></span><span class="mt-2 block text-xs text-slate-500"><?= $safe($product['emplacement'] ?? 'Emplacement non défini') ?></span></td>
                <td><span class="rounded-lg px-2 py-1 text-xs font-bold <?= $expired ? 'bg-red-100 text-red-700' : ($expiration === '' ? 'bg-slate-100 text-slate-600' : 'bg-emerald-100 text-emerald-700') ?>"><?= $expired ? 'Périmé' : $safe($expiration, 'Non définie') ?></span></td>
                <td><span class="text-lg font-black <?= $stock <= $minimum ? 'text-amber-700' : 'text-slate-950' ?>"><?= $stock ?></span><span class="block text-xs text-slate-500">Seuil <?= $minimum ?></span></td>
                <td><div class="flex flex-wrap gap-1"><?php if ((int) ($product['ordonnance_requise'] ?? 0) === 1): ?><span class="rounded-lg bg-violet-100 px-2 py-1 text-xs font-bold text-violet-700">Ordonnance</span><?php endif; ?><span class="rounded-lg px-2 py-1 text-xs font-bold <?= $configured ? 'bg-teal-100 text-teal-700' : 'bg-amber-100 text-amber-700' ?>"><?= $configured ? 'Fiche complète' : 'À compléter' ?></span></div></td>
                <td class="text-right"><a class="btn-secondary h-10 w-auto px-3 text-xs" href="<?= $url('/pharmacie/produits/' . (int) $product['id'] . '/details') ?>">Ouvrir la fiche</a></td></tr>
                <?php endforeach; ?>
                <?php if ($products === []): ?><tr><td colspan="7" class="py-12 text-center"><span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-emerald-50 text-emerald-700"><?= $icon('M12 5v14M5 12h14', 'h-7 w-7') ?></span><p class="mt-4 font-bold text-slate-900">Aucun médicament enregistré</p><p class="mt-1 text-sm text-slate-500">Commencez par créer votre premier médicament.</p></td></tr><?php endif; ?>
            </tbody></table></div>
        </section>

        <aside class="space-y-5">
            <section class="rounded-2xl bg-gradient-to-br from-slate-950 to-teal-900 p-5 text-white shadow-lg"><p class="text-xs font-black uppercase tracking-[.16em] text-emerald-300">Vigilance</p><h2 class="mt-3 text-xl font-bold">Sécurité de l’officine</h2><div class="mt-5 space-y-3 text-sm text-slate-300"><p class="flex gap-3"><span class="text-emerald-300">✓</span> Vérifier les numéros de lots.</p><p class="flex gap-3"><span class="text-emerald-300">✓</span> Isoler les produits périmés.</p><p class="flex gap-3"><span class="text-emerald-300">✓</span> Contrôler les ordonnances.</p><p class="flex gap-3"><span class="text-emerald-300">✓</span> Respecter les emplacements.</p></div></section>
            <section class="surface-panel"><h2 class="font-bold">Accès rapides</h2><div class="mt-4 space-y-2"><a class="block rounded-xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 hover:bg-emerald-100" href="<?= $url('/pos') ?>">Caisse pharmacie</a><a class="block rounded-xl bg-cyan-50 px-4 py-3 text-sm font-bold text-cyan-800 hover:bg-cyan-100" href="<?= $url('/supplies') ?>">Réceptions et lots</a><a class="block rounded-xl bg-violet-50 px-4 py-3 text-sm font-bold text-violet-800 hover:bg-violet-100" href="<?= $url('/rapports/ventes') ?>">Rapports de ventes</a></div></section>
        </aside>
    </div>
</section>
