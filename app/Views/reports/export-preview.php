<?php

$exportFormat = strtolower((string) ($exportFormat ?? 'pdf'));
$reportFilter = is_array($reportFilter ?? null) ? $reportFilter : ['period' => 'current_month', 'label' => 'Mois en cours'];
$activeShop = is_array($activeShop ?? null) ? $activeShop : [];
$overview = is_array($overview ?? null) ? $overview : [];
$cards = is_array($cards ?? null) ? $cards : [];
$paymentBreakdown = is_array($paymentBreakdown ?? null) ? $paymentBreakdown : [];
$topProducts = is_array($topProducts ?? null) ? $topProducts : [];
$recentSales = is_array($recentSales ?? null) ? $recentSales : [];
$periodDisplay = (string) ($periodDisplay ?? '');

$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$previewCurrency = in_array(($activeShop['devise_principale'] ?? 'USD'), ['USD', 'CDF'], true) ? (string) $activeShop['devise_principale'] : 'USD';
$exchangeRate = (float) (($activeShop['taux_change_cdf'] ?? 2800) ?: 2800);
$money = static function ($value) use ($previewCurrency, $exchangeRate): string {
    $amount = (float) $value;
    $usd = number_format($amount, 2, ',', ' ') . ' USD';
    $cdf = number_format($amount * $exchangeRate, 2, ',', ' ') . ' CDF';

    return $previewCurrency === 'CDF' ? $cdf . ' (' . $usd . ')' : $usd . ' (' . $cdf . ')';
};
$dateLabel = static function ($value, string $fallback = '-'): string {
    $timestamp = strtotime((string) ($value ?? ''));
    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : $fallback;
};
$period = (string) ($reportFilter['period'] ?? 'current_month');
$exportBasePath = (string) ($exportBasePath ?? '/rapports/ventes');
$filterStart = $reportFilter['start'] ?? null;
$filterEnd = $reportFilter['end'] ?? null;
$dateStartValue = (string) ($reportFilter['date_debut'] ?? ($filterStart !== null ? date('Y-m-d', strtotime((string) $filterStart)) : ''));
$dateEndValue = (string) ($reportFilter['date_fin'] ?? ($filterEnd !== null ? date('Y-m-d', strtotime((string) $filterEnd . ' -1 second')) : ''));
$periodDisplay = $periodDisplay !== '' ? $periodDisplay : ($filterStart !== null && $filterEnd !== null
    ? 'Vente du ' . date('d/m/Y', strtotime((string) $filterStart)) . ' au ' . date('d/m/Y', strtotime((string) $filterEnd . ' -1 second'))
    : 'Toutes les ventes disponibles');
$formatLabel = $exportFormat === 'xlsx' ? 'Excel' : strtoupper($exportFormat);
$confirmQuery = ['period' => $period, 'export' => $exportFormat, 'confirm' => '1'];
$cancelQuery = ['period' => $period];
foreach (['search', 'status', 'payment', 'debt', 'date_debut', 'date_fin'] as $filterKey) {
    $filterValue = trim((string) ($reportFilter[$filterKey] ?? ''));
    if ($filterValue !== '' && $filterValue !== 'all') {
        $confirmQuery[$filterKey] = $filterValue;
        $cancelQuery[$filterKey] = $filterValue;
    }
}
if ($period === 'custom') {
    $confirmQuery['date_debut'] = $dateStartValue;
    $confirmQuery['date_fin'] = $dateEndValue;
    $cancelQuery['date_debut'] = $dateStartValue;
    $cancelQuery['date_fin'] = $dateEndValue;
}
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Prévisualisation export</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Rapport des ventes - <?= $safe($formatLabel) ?></h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                Vérifiez le contenu qui sera exporté. Le téléchargement ne commence qu'après confirmation.
            </p>
            <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold text-slate-600">
                <span class="rounded-lg border border-teal-200 bg-teal-50 px-3 py-2 text-teal-700"><?= $safe($periodDisplay) ?></span>
                <span class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">Boutique : <?= $safe($activeShop['nom'] ?? 'Boutique active') ?></span>
                <span class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">Généré le : <?= date('d/m/Y H:i') ?></span>
            </div>
        </div>
        <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
            <a class="btn-secondary w-full sm:w-auto" href="<?= $url($exportBasePath, $cancelQuery) ?>">Annuler</a>
            <a class="btn-primary w-full sm:w-auto" href="<?= $url($exportBasePath, $confirmQuery) ?>">Confirmer l'export <?= $safe($formatLabel) ?></a>
        </div>
    </div>

    <section class="surface-panel">
        <div class="panel-header">
            <div>
                <h2 class="font-bold text-slate-950">Boutique active</h2>
                <p class="mt-1 text-sm text-slate-500">Ces informations apparaîtront dans l'en-tête du PDF.</p>
            </div>
        </div>
        <div class="mt-5 grid gap-3 sm:grid-cols-3">
            <div class="signal-row"><span class="text-slate-500">Nom</span><strong><?= $safe($activeShop['nom'] ?? '-') ?></strong></div>
            <div class="signal-row"><span class="text-slate-500">Adresse</span><strong><?= $safe($activeShop['adresse'] ?? '-') ?></strong></div>
            <div class="signal-row"><span class="text-slate-500">Téléphone</span><strong><?= $safe($activeShop['telephone'] ?? '-') ?></strong></div>
        </div>
    </section>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <?php foreach ($cards as $card): ?>
            <article class="stat-card">
                <p class="text-sm font-medium text-slate-500"><?= $safe($card['label'] ?? '-') ?></p>
                <p class="mt-2 text-2xl font-bold text-slate-950"><?= $safe($card['value'] ?? '0') ?></p>
                <p class="mt-2 text-xs font-medium text-slate-500"><?= $safe($card['detail'] ?? '') ?></p>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="grid gap-5 xl:grid-cols-[.85fr_1.15fr]">
        <section class="surface-panel">
            <h2 class="font-bold text-slate-950">Paiements inclus</h2>
            <div class="mt-5 space-y-3">
                <?php if ($paymentBreakdown === []): ?>
                    <p class="rounded-lg border border-dashed border-slate-200 p-4 text-sm font-semibold text-slate-500">Aucun paiement dans cette période.</p>
                <?php endif; ?>
                <?php foreach ($paymentBreakdown as $payment): ?>
                    <div class="signal-row">
                        <span class="text-slate-500"><?= $safe($payment['mode_paiement'] ?? 'cash') ?> - <?= (int) ($payment['tickets'] ?? 0) ?> ticket(s)</span>
                        <strong><?= $money($payment['revenue'] ?? 0) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="surface-panel">
            <h2 class="font-bold text-slate-950">Top produits inclus</h2>
            <div class="mt-5 overflow-hidden rounded-lg border border-slate-200">
                <div class="hidden grid-cols-[1fr_.6fr_.7fr_.7fr] gap-4 border-b border-slate-200 bg-slate-50 px-4 py-3 text-xs font-bold uppercase tracking-[.14em] text-slate-400 lg:grid">
                    <span>Produit</span><span>Quantité</span><span>Ventes</span><span>Marge</span>
                </div>
                <div class="divide-y divide-slate-200">
                    <?php if ($topProducts === []): ?>
                        <p class="p-6 text-center text-sm font-semibold text-slate-500">Aucun produit vendu.</p>
                    <?php endif; ?>
                    <?php foreach ($topProducts as $product): ?>
                        <article class="grid gap-2 px-4 py-4 lg:grid-cols-[1fr_.6fr_.7fr_.7fr] lg:items-center">
                            <p class="font-bold text-slate-950"><?= $safe($product['product_name'] ?? 'Produit') ?></p>
                            <p><?= number_format((float) ($product['quantity'] ?? 0), 0, ',', ' ') ?></p>
                            <p class="font-bold text-teal-700"><?= $money($product['revenue'] ?? 0) ?></p>
                            <p class="font-bold text-blue-700"><?= $money($product['margin'] ?? 0) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </div>

    <section class="surface-panel">
        <div class="panel-header">
            <div>
                <h2 class="font-bold text-slate-950">Derniers tickets inclus</h2>
                <p class="mt-1 text-sm text-slate-500"><?= count($recentSales) ?> ticket(s) affiché(s) dans l'aperçu.</p>
            </div>
        </div>
        <div class="mt-5 overflow-hidden rounded-lg border border-slate-200">
            <div class="hidden grid-cols-[1fr_.8fr_.7fr_.7fr_.6fr] gap-4 border-b border-slate-200 bg-slate-50 px-4 py-3 text-xs font-bold uppercase tracking-[.14em] text-slate-400 lg:grid">
                <span>Facture</span><span>Date</span><span>Client</span><span>Total</span><span>Statut</span>
            </div>
            <div class="divide-y divide-slate-200">
                <?php if ($recentSales === []): ?>
                    <p class="p-6 text-center text-sm font-semibold text-slate-500">Aucune vente disponible.</p>
                <?php endif; ?>
                <?php foreach ($recentSales as $sale): ?>
                    <article class="grid gap-2 px-4 py-4 lg:grid-cols-[1fr_.8fr_.7fr_.7fr_.6fr] lg:items-center">
                        <p class="font-bold text-slate-950"><?= $safe($sale['numero_facture'] ?? '-') ?></p>
                        <p class="text-sm text-slate-600"><?= $safe($dateLabel($sale['date_vente'] ?? null)) ?></p>
                        <p class="text-sm text-slate-600"><?= $safe($sale['customer_name'] ?? 'Client comptant') ?></p>
                        <p class="font-bold text-slate-950"><?= $money($sale['total_montant'] ?? 0) ?></p>
                        <p class="text-sm font-bold text-teal-700"><?= $safe($sale['statut'] ?? '-') ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</section>
