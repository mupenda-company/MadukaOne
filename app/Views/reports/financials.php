
<?php
$cards = is_array($cards ?? null) ? $cards : [];
$chartBars = is_array($chartBars ?? null) ? $chartBars : [];
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Rapports</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Statistiques financières</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Synthèse visuelle du chiffre d’affaires, des charges et du bénéfice net.
            </p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row">
            <a class="btn-secondary" href="<?= $url('/rapports/financiers', ['export' => 'pdf']) ?>">Export PDF</a>
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

    <div class="grid gap-5 xl:grid-cols-[1.2fr_.8fr]">
        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Performance</h2>
                    <p class="mt-1 text-sm text-slate-500">Comparaison mensuelle du résultat net.</p>
                </div>
                <a class="btn-secondary" href="<?= $url('/expenses') ?>">Charges</a>
            </div>
            <div class="mt-6 flex h-80 items-end gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                <?php foreach ($chartBars as $height): ?>
                    <div class="flex flex-1 items-end">
                        <div class="w-full rounded-t-md bg-gradient-to-t from-teal-700 to-teal-400" style="height: <?= (int) $height ?>%;"></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <aside class="surface-panel">
            <h2 class="font-bold text-slate-950">Contrôles rapides</h2>
            <div class="mt-5 space-y-3">
                <a class="signal-row hover:bg-slate-100" href="<?= $url('/rapports/ventes') ?>">
                    <span>Rapport des ventes</span>
                    <strong>Ouvrir</strong>
                </a>
                <a class="signal-row hover:bg-slate-100" href="<?= $url('/rapports/stock') ?>">
                    <span>Rapport de stock</span>
                    <strong>Ouvrir</strong>
                </a>
                <a class="signal-row hover:bg-slate-100" href="<?= $url('/backup/manual') ?>" data-confirm data-confirm-title="Lancer la sauvegarde ?" data-confirm-message="Le déclencheur manuel va demander une sauvegarde de secours." data-confirm-accept="Lancer">
                    <span>Sauvegarde de secours</span>
                    <strong>Lancer</strong>
                </a>
            </div>
        </aside>
    </div>
</section>
