
<?php
$cards = is_array($cards ?? null) ? $cards : [];
$chartBars = is_array($chartBars ?? null) ? $chartBars : [];
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Rapports</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Statistiques de stock</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Visualisez entrées, sorties, ruptures et mouvements sensibles du stock.
            </p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row">
            <a class="btn-secondary" href="<?= $url('/rapports/stock', ['export' => 'pdf']) ?>">Export PDF</a>
            <a class="btn-primary w-full sm:w-auto" href="<?= $url('/backup/manual') ?>" data-confirm data-confirm-title="Lancer la sauvegarde ?" data-confirm-message="Le déclencheur manuel va demander une sauvegarde de secours." data-confirm-accept="Lancer">Sauvegarde</a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <?php foreach ($cards as $card): ?>
            <article class="stat-card">
                <p class="text-sm text-slate-500"><?= htmlspecialchars((string) $card['label'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mt-3 text-2xl font-bold text-slate-950"><?= htmlspecialchars((string) $card['value'], ENT_QUOTES, 'UTF-8') ?></p>
            </article>
        <?php endforeach; ?>
    </div>

    <section class="surface-panel">
        <div class="panel-header">
            <div>
                <h2 class="font-bold text-slate-950">Flux de stock</h2>
                <p class="mt-1 text-sm text-slate-500">Barres visuelles prêtes pour les mouvements réels.</p>
            </div>
            <a class="btn-secondary" href="<?= $url('/supplies/create') ?>">Nouvel arrivage</a>
        </div>
        <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($chartBars as $index => $height): ?>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="flex h-36 items-end rounded-lg bg-white p-2">
                        <div class="w-full rounded-t-md bg-gradient-to-t from-teal-700 to-teal-400" style="height: <?= (int) $height ?>%;"></div>
                    </div>
                    <p class="mt-3 text-sm font-semibold text-slate-700">Semaine <?= $index + 1 ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</section>
