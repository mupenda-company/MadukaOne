<?php

$cards = is_array($cards ?? null) ? $cards : [];
$overview = is_array($overview ?? null) ? $overview : [];
$monthlySales = is_array($monthlySales ?? null) ? $monthlySales : [];
$dailySales = is_array($dailySales ?? null) ? $dailySales : [];
$paymentBreakdown = is_array($paymentBreakdown ?? null) ? $paymentBreakdown : [];
$topProducts = is_array($topProducts ?? null) ? $topProducts : [];
$recentSales = is_array($recentSales ?? null) ? $recentSales : [];
$reportFilter = is_array($reportFilter ?? null) ? $reportFilter : [
    'period' => 'current_month',
    'label' => 'Mois en cours',
    'start' => null,
    'end' => null,
];
$periodDisplay = (string) ($periodDisplay ?? '');

$money = static fn ($value): string => number_format((float) $value, 2, ',', ' ') . ' USD';
$number = static fn ($value): string => number_format((float) $value, 0, ',', ' ');
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$dateLabel = static function ($value, string $fallback = '-'): string {
    $timestamp = strtotime((string) ($value ?? ''));
    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : $fallback;
};
$modeLabel = static fn (string $mode): string => match ($mode) {
    'mobile_money' => 'Mobile money',
    'carte' => 'Carte',
    'virement' => 'Virement',
    'credit' => 'Crédit',
    'mixte' => 'Mixte',
    default => 'Cash',
};
$toneClass = static fn (string $tone): string => match ($tone) {
    'blue' => 'bg-blue-50 text-blue-700',
    'amber' => 'bg-amber-50 text-amber-700',
    'slate' => 'bg-slate-100 text-slate-700',
    default => 'bg-teal-50 text-teal-700',
};
$statusClass = static fn (string $status): string => $status === 'annulee' ? 'bg-red-50 text-red-700' : 'bg-teal-50 text-teal-700';
$statusLabel = static fn (string $status): string => $status === 'annulee' ? 'Annulée' : 'Validée';
$delta = (float) ($overview['month_delta_percent'] ?? 0);
$deltaClass = $delta < 0 ? 'text-red-700 bg-red-50' : 'text-teal-700 bg-teal-50';
$selectedPeriod = (string) ($reportFilter['period'] ?? 'current_month');
$selected = static fn (string $period): string => $selectedPeriod === $period ? 'selected' : '';
$filterStart = $reportFilter['start'] ?? null;
$filterEnd = $reportFilter['end'] ?? null;
$dateStartValue = (string) ($reportFilter['date_debut'] ?? ($filterStart !== null ? date('Y-m-d', strtotime((string) $filterStart)) : date('Y-m-01')));
$dateEndValue = (string) ($reportFilter['date_fin'] ?? ($filterEnd !== null ? date('Y-m-d', strtotime((string) $filterEnd . ' -1 second')) : date('Y-m-d')));
$exportQuery = ['period' => $selectedPeriod];
if ($selectedPeriod === 'custom') {
    $exportQuery['date_debut'] = $dateStartValue;
    $exportQuery['date_fin'] = $dateEndValue;
}
$filterRange = $filterStart !== null && $filterEnd !== null
    ? date('d/m/Y', strtotime((string) $filterStart)) . ' - ' . date('d/m/Y', strtotime((string) $filterEnd . ' -1 second'))
    : 'Toutes les dates';
$periodDisplay = $periodDisplay !== '' ? $periodDisplay : ($filterStart !== null && $filterEnd !== null
    ? 'Vente du ' . date('d/m/Y', strtotime((string) $filterStart)) . ' au ' . date('d/m/Y', strtotime((string) $filterEnd . ' -1 second'))
    : 'Toutes les ventes disponibles');

$dailyRevenueMax = 0.0;
$dailyTicketMax = 0;
$dailyRevenueTotal = 0.0;
$dailyBest = null;

foreach ($dailySales as $day) {
    $revenue = (float) ($day['revenue'] ?? 0);
    $tickets = (int) ($day['tickets'] ?? 0);
    $dailyRevenueMax = max($dailyRevenueMax, $revenue);
    $dailyTicketMax = max($dailyTicketMax, $tickets);
    $dailyRevenueTotal += $revenue;

    if ($dailyBest === null || $revenue > (float) ($dailyBest['revenue'] ?? 0)) {
        $dailyBest = $day;
    }
}

$dailyCount = max(1, count($dailySales));
$dailyAverage = $dailyRevenueTotal / $dailyCount;
$chartWidth = 960;
$chartHeight = 320;
$chartPadX = 48;
$chartPadTop = 26;
$chartPadBottom = 44;
$chartPlotWidth = $chartWidth - ($chartPadX * 2);
$chartPlotHeight = $chartHeight - $chartPadTop - $chartPadBottom;
$linePoints = [];
$areaPoints = [];
$ticketBars = [];
$labelStep = max(1, (int) ceil($dailyCount / 8));

foreach (array_values($dailySales) as $index => $day) {
    $revenue = (float) ($day['revenue'] ?? 0);
    $tickets = (int) ($day['tickets'] ?? 0);
    $x = $dailyCount > 1 ? $chartPadX + ($index * ($chartPlotWidth / ($dailyCount - 1))) : $chartWidth / 2;
    $revenueRatio = $dailyRevenueMax > 0 ? $revenue / $dailyRevenueMax : 0;
    $ticketRatio = $dailyTicketMax > 0 ? $tickets / $dailyTicketMax : 0;
    $y = $chartPadTop + ($chartPlotHeight - ($revenueRatio * $chartPlotHeight));
    $barHeight = max(4, $ticketRatio * ($chartPlotHeight * 0.72));
    $barWidth = max(8, min(24, ($chartPlotWidth / $dailyCount) - 6));
    $barX = $x - ($barWidth / 2);
    $barY = $chartPadTop + $chartPlotHeight - $barHeight;

    $linePoints[] = round($x, 2) . ',' . round($y, 2);
    $ticketBars[] = [
        'x' => round($barX, 2),
        'y' => round($barY, 2),
        'width' => round($barWidth, 2),
        'height' => round($barHeight, 2),
        'label' => (string) ($day['label'] ?? ''),
        'revenue' => $revenue,
        'tickets' => $tickets,
        'show_label' => $index === 0 || $index === $dailyCount - 1 || $index % $labelStep === 0,
        'label_x' => round($x, 2),
    ];
}

if ($linePoints !== []) {
    $firstX = explode(',', $linePoints[0])[0];
    $lastX = explode(',', $linePoints[count($linePoints) - 1])[0];
    $bottomY = $chartPadTop + $chartPlotHeight;
    $areaPoints = array_merge([$firstX . ',' . $bottomY], $linePoints, [$lastX . ',' . $bottomY]);
}
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Rapports</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Rapport professionnel des ventes</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                Synthèse détaillée des ventes validées, encaissements, crédits, produits performants et derniers tickets de la boutique active.
            </p>
            <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold text-slate-600">
                <span class="rounded-lg border border-teal-200 bg-teal-50 px-3 py-2 text-teal-700"><?= $safe($periodDisplay) ?></span>
                <span class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2"><?= $safe($filterRange) ?></span>
                <span class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">Première vente : <?= $safe($dateLabel($overview['first_sale_at'] ?? null, 'Aucune')) ?></span>
                <span class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">Dernière vente : <?= $safe($dateLabel($overview['last_sale_at'] ?? null, 'Aucune')) ?></span>
                <span class="rounded-lg px-3 py-2 <?= $deltaClass ?>">Mois courant : <?= $delta >= 0 ? '+' : '' ?><?= number_format($delta, 1, ',', ' ') ?>%</span>
            </div>
        </div>
        <div class="grid w-full grid-cols-1 gap-2 sm:grid-cols-2 lg:w-[28rem]">
            <a class="btn-secondary w-full px-4" href="<?= $url('/rapports/ventes', $exportQuery + ['export_preview' => 'xlsx']) ?>">Prévisualiser Excel</a>
            <a class="btn-secondary w-full px-4" href="<?= $url('/rapports/ventes', $exportQuery + ['export_preview' => 'pdf']) ?>">Prévisualiser PDF</a>
            <a class="btn-secondary w-full px-4" href="<?= $url('/sales') ?>">Voir les tickets</a>
            <a class="btn-primary w-full px-4" href="<?= $url('/pos') ?>">Nouvelle vente</a>
        </div>
    </div>

    <form method="get" action="<?= $url('/rapports/ventes') ?>" class="surface-panel" data-report-filter-form>
        <div class="grid gap-4 xl:grid-cols-[minmax(14rem,18rem)_1fr_auto] xl:items-end">
            <div class="w-full">
                <label for="report-period" class="mb-2 block text-sm font-bold text-slate-700">Filtrer le rapport</label>
                <select id="report-period" name="period" class="field-control" data-report-period>
                    <option value="current_month" <?= $selected('current_month') ?>>Mois en cours</option>
                    <option value="today" <?= $selected('today') ?>>Aujourd'hui</option>
                    <option value="last_7_days" <?= $selected('last_7_days') ?>>7 derniers jours</option>
                    <option value="last_30_days" <?= $selected('last_30_days') ?>>30 derniers jours</option>
                    <option value="current_year" <?= $selected('current_year') ?>>Année en cours</option>
                    <option value="custom" <?= $selected('custom') ?>>Période personnalisée</option>
                    <option value="all" <?= $selected('all') ?>>Toutes les ventes</option>
                </select>
                <p class="mt-2 text-sm text-slate-500">Par défaut, le rapport affiche le mois en cours.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="date-debut" class="mb-2 block text-sm font-bold text-slate-700">Date début</label>
                    <input id="date-debut" class="field-control" name="date_debut" type="date" value="<?= $safe($dateStartValue) ?>" data-report-date>
                </div>
                <div>
                    <label for="date-fin" class="mb-2 block text-sm font-bold text-slate-700">Date fin</label>
                    <input id="date-fin" class="field-control" name="date_fin" type="date" value="<?= $safe($dateEndValue) ?>" data-report-date>
                </div>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <a class="btn-secondary w-full sm:w-auto" href="<?= $url('/rapports/ventes') ?>">Réinitialiser</a>
                <button class="btn-primary w-full sm:w-auto" type="submit">Appliquer</button>
            </div>
        </div>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <?php foreach ($cards as $card): ?>
            <?php $tone = (string) ($card['tone'] ?? 'teal'); ?>
            <article class="stat-card">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-500"><?= $safe($card['label'] ?? '-') ?></p>
                        <p class="mt-2 text-2xl font-bold text-slate-950"><?= $safe($card['value'] ?? '0') ?></p>
                        <p class="mt-2 text-xs font-medium text-slate-500"><?= $safe($card['detail'] ?? '') ?></p>
                    </div>
                    <span class="h-10 w-2 rounded-full <?= $toneClass($tone) ?>"></span>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="surface-panel">
            <p class="text-sm text-slate-500">Ticket moyen</p>
            <p class="mt-2 text-xl font-bold text-slate-950"><?= $money($overview['average_ticket'] ?? 0) ?></p>
        </article>
        <article class="surface-panel">
            <p class="text-sm text-slate-500">Clients identifiés</p>
            <p class="mt-2 text-xl font-bold text-slate-950"><?= (int) ($overview['customers_count'] ?? 0) ?></p>
        </article>
        <article class="surface-panel">
            <p class="text-sm text-slate-500">Ventes annulées</p>
            <p class="mt-2 text-xl font-bold text-red-700"><?= (int) ($overview['cancelled_count'] ?? 0) ?></p>
        </article>
        <article class="surface-panel">
            <p class="text-sm text-slate-500">CA mois courant</p>
            <p class="mt-2 text-xl font-bold text-teal-700"><?= $money($overview['current_month_revenue'] ?? 0) ?></p>
        </article>
    </div>

    <div class="grid gap-5 xl:grid-cols-[1.35fr_.65fr]">
        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Évolution mensuelle du chiffre d'affaires</h2>
                    <p class="mt-1 text-sm text-slate-500">Ventes validées pour la période : <?= $safe($periodDisplay) ?>.</p>
                </div>
                <span class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-bold text-slate-700"><?= date('Y') ?></span>
            </div>

            <div class="mt-6 flex h-72 items-end gap-2 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:gap-3">
                <?php foreach ($monthlySales as $month): ?>
                    <div class="group flex min-w-0 flex-1 flex-col items-center justify-end gap-2">
                        <div class="flex h-full w-full items-end rounded-lg bg-white px-1 py-2 shadow-sm">
                            <div
                                class="w-full rounded-t-md bg-gradient-to-t from-teal-700 to-teal-400 transition group-hover:from-teal-800 group-hover:to-teal-500"
                                style="height: <?= (int) ($month['height'] ?? 8) ?>%;"
                                title="<?= $safe($month['label'] ?? '') ?> - <?= $money($month['revenue'] ?? 0) ?>"
                            ></div>
                        </div>
                        <p class="text-[11px] font-bold text-slate-500"><?= $safe($month['label'] ?? '') ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <div class="signal-row"><span class="text-slate-500">Mois courant</span><strong><?= $money($overview['current_month_revenue'] ?? 0) ?></strong></div>
                <div class="signal-row"><span class="text-slate-500">Mois précédent</span><strong><?= $money($overview['previous_month_revenue'] ?? 0) ?></strong></div>
                <div class="signal-row"><span class="text-slate-500">Variation</span><strong class="<?= $delta < 0 ? 'text-red-700' : 'text-teal-700' ?>"><?= $delta >= 0 ? '+' : '' ?><?= number_format($delta, 1, ',', ' ') ?>%</strong></div>
            </div>
        </section>

        <aside class="surface-panel">
            <h2 class="font-bold text-slate-950">Paiements par mode</h2>
            <p class="mt-1 text-sm text-slate-500">Répartition des ventes validées.</p>
            <div class="mt-5 space-y-3">
                <?php if ($paymentBreakdown === []): ?>
                    <p class="rounded-lg border border-dashed border-slate-200 p-4 text-sm font-semibold text-slate-500">Aucun paiement enregistré.</p>
                <?php endif; ?>
                <?php foreach ($paymentBreakdown as $payment): ?>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-bold text-slate-950"><?= $safe($modeLabel((string) ($payment['mode_paiement'] ?? 'cash'))) ?></p>
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-600"><?= (int) ($payment['tickets'] ?? 0) ?> ticket(s)</span>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                            <div><p class="text-slate-500">Total</p><p class="font-bold text-slate-950"><?= $money($payment['revenue'] ?? 0) ?></p></div>
                            <div><p class="text-slate-500">Crédit</p><p class="font-bold text-amber-700"><?= $money($payment['debt'] ?? 0) ?></p></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </aside>
    </div>

    <section class="surface-panel">
        <div class="panel-header">
            <div>
                <h2 class="font-bold text-slate-950">Tendance journalière</h2>
                <p class="mt-1 text-sm text-slate-500">Évolution du chiffre d'affaires et du nombre de tickets sur la période sélectionnée.</p>
            </div>
            <div class="hidden items-center gap-4 text-xs font-bold text-slate-500 sm:flex">
                <span class="inline-flex items-center gap-2"><span class="h-2 w-6 rounded-full bg-teal-600"></span>Chiffre d'affaires</span>
                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-blue-500"></span>Tickets</span>
            </div>
        </div>

        <div class="mt-5 grid gap-4 xl:grid-cols-[1fr_16rem]">
            <div class="overflow-x-auto rounded-lg border border-slate-200 bg-slate-50 p-3">
                <svg class="min-w-[720px] w-full" viewBox="0 0 <?= $chartWidth ?> <?= $chartHeight ?>" role="img" aria-label="Graphique des tendances journalières">
                    <defs>
                        <linearGradient id="dailyRevenueArea" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#0f766e" stop-opacity="0.24" />
                            <stop offset="100%" stop-color="#0f766e" stop-opacity="0.02" />
                        </linearGradient>
                    </defs>
                    <?php for ($grid = 0; $grid <= 4; $grid++): ?>
                        <?php $gridY = $chartPadTop + (($chartPlotHeight / 4) * $grid); ?>
                        <line x1="<?= $chartPadX ?>" y1="<?= $gridY ?>" x2="<?= $chartWidth - $chartPadX ?>" y2="<?= $gridY ?>" stroke="#e2e8f0" stroke-width="1" />
                    <?php endfor; ?>
                    <?php foreach ($ticketBars as $bar): ?>
                        <rect x="<?= $bar['x'] ?>" y="<?= $bar['y'] ?>" width="<?= $bar['width'] ?>" height="<?= $bar['height'] ?>" rx="4" fill="#3b82f6" opacity="0.72">
                            <title><?= $safe($bar['label']) ?> : <?= (int) $bar['tickets'] ?> ticket(s), <?= $money($bar['revenue']) ?></title>
                        </rect>
                    <?php endforeach; ?>
                    <?php if ($areaPoints !== []): ?>
                        <polygon points="<?= $safe(implode(' ', $areaPoints)) ?>" fill="url(#dailyRevenueArea)" />
                    <?php endif; ?>
                    <?php if ($linePoints !== []): ?>
                        <polyline points="<?= $safe(implode(' ', $linePoints)) ?>" fill="none" stroke="#0f766e" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                        <?php foreach ($linePoints as $index => $point): ?>
                            <?php [$pointX, $pointY] = explode(',', $point); ?>
                            <circle cx="<?= $pointX ?>" cy="<?= $pointY ?>" r="4.5" fill="#ffffff" stroke="#0f766e" stroke-width="3">
                                <title><?= $safe($dailySales[$index]['label'] ?? '') ?> : <?= $money($dailySales[$index]['revenue'] ?? 0) ?></title>
                            </circle>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php foreach ($ticketBars as $bar): ?>
                        <?php if ($bar['show_label']): ?>
                            <text x="<?= $bar['label_x'] ?>" y="<?= $chartHeight - 14 ?>" text-anchor="middle" fill="#64748b" font-size="13" font-weight="700"><?= $safe($bar['label']) ?></text>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </svg>
            </div>

            <div class="grid gap-3 sm:grid-cols-3 xl:grid-cols-1">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-medium text-slate-500">Total période</p>
                    <p class="mt-2 text-xl font-bold text-slate-950"><?= $money($dailyRevenueTotal) ?></p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-medium text-slate-500">Moyenne jour</p>
                    <p class="mt-2 text-xl font-bold text-teal-700"><?= $money($dailyAverage) ?></p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-medium text-slate-500">Meilleur jour</p>
                    <p class="mt-2 text-xl font-bold text-blue-700"><?= $money($dailyBest['revenue'] ?? 0) ?></p>
                    <p class="mt-1 text-xs font-semibold text-slate-500"><?= $safe($dailyBest['label'] ?? 'Aucune donnée') ?></p>
                </div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-3 text-xs font-bold text-slate-500 sm:hidden">
            <span class="inline-flex items-center gap-2"><span class="h-2 w-6 rounded-full bg-teal-600"></span>Chiffre d'affaires</span>
            <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-blue-500"></span>Tickets</span>
        </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-[.9fr_1.1fr]">
        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Top produits vendus</h2>
                    <p class="mt-1 text-sm text-slate-500">Classement par chiffre d'affaires validé.</p>
                </div>
                <a class="btn-secondary" href="<?= $url('/products') ?>">Catalogue</a>
            </div>
            <div class="mt-5 space-y-3">
                <?php if ($topProducts === []): ?>
                    <p class="rounded-lg border border-dashed border-slate-200 p-4 text-sm font-semibold text-slate-500">Aucun produit vendu.</p>
                <?php endif; ?>
                <?php foreach ($topProducts as $index => $product): ?>
                    <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-bold text-slate-950"><?= $safe($product['product_name'] ?? 'Produit') ?></p>
                                <p class="mt-1 truncate text-xs text-slate-500"><?= $safe($product['product_ref'] ?? 'Sans référence') ?></p>
                            </div>
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-teal-50 text-sm font-black text-teal-700"><?= $index + 1 ?></span>
                        </div>
                        <div class="mt-4 grid grid-cols-3 gap-3 text-sm">
                            <div><p class="text-slate-500">Quantité</p><p class="font-bold"><?= $number($product['quantity'] ?? 0) ?></p></div>
                            <div><p class="text-slate-500">Ventes</p><p class="font-bold text-teal-700"><?= $money($product['revenue'] ?? 0) ?></p></div>
                            <div><p class="text-slate-500">Marge</p><p class="font-bold text-blue-700"><?= $money($product['margin'] ?? 0) ?></p></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Derniers tickets</h2>
                    <p class="mt-1 text-sm text-slate-500">Contrôle des ventes les plus récentes.</p>
                </div>
                <a class="btn-secondary" href="<?= $url('/sales') ?>">Historique complet</a>
            </div>
            <div class="mt-5 overflow-hidden rounded-lg border border-slate-200">
                <div class="hidden grid-cols-[1fr_.75fr_.75fr_.75fr_.75fr] gap-4 border-b border-slate-200 bg-slate-50 px-4 py-3 text-xs font-bold uppercase tracking-[.14em] text-slate-400 lg:grid">
                    <span>Facture</span><span>Client</span><span>Total</span><span>Crédit</span><span>Statut</span>
                </div>
                <div class="divide-y divide-slate-200">
                    <?php if ($recentSales === []): ?>
                        <p class="p-6 text-center text-sm font-semibold text-slate-500">Aucune vente disponible.</p>
                    <?php endif; ?>
                    <?php foreach ($recentSales as $sale): ?>
                        <?php
                        $status = (string) ($sale['statut'] ?? 'validee');
                        $debt = (float) ($sale['montant_dette'] ?? 0);
                        ?>
                        <article class="grid gap-3 px-4 py-4 lg:grid-cols-[1fr_.75fr_.75fr_.75fr_.75fr] lg:items-center">
                            <div class="min-w-0">
                                <p class="truncate font-bold text-slate-950"><?= $safe($sale['numero_facture'] ?? '-') ?></p>
                                <p class="mt-1 text-xs text-slate-500"><?= $safe($dateLabel($sale['date_vente'] ?? null)) ?> - <?= (int) ($sale['items_count'] ?? 0) ?> article(s)</p>
                            </div>
                            <p class="truncate text-sm text-slate-600"><?= $safe($sale['customer_name'] ?? 'Client comptant') ?></p>
                            <p class="font-bold text-slate-950"><?= $money($sale['total_montant'] ?? 0) ?></p>
                            <p class="font-bold <?= $debt > 0 ? 'text-amber-700' : 'text-slate-500' ?>"><?= $money($debt) ?></p>
                            <p><span class="inline-flex rounded-full px-3 py-1 text-xs font-bold <?= $statusClass($status) ?>"><?= $safe($statusLabel($status)) ?></span></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </div>
</section>

<script>
    document.querySelectorAll('[data-report-filter-form]').forEach((form) => {
        const period = form.querySelector('[data-report-period]');

        form.querySelectorAll('[data-report-date]').forEach((input) => {
            input.addEventListener('change', () => {
                if (period instanceof HTMLSelectElement) {
                    period.value = 'custom';
                }
            });
        });
    });
</script>
