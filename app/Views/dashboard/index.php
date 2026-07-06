<?php

$pageEyebrow = (string) ($pageEyebrow ?? 'Pilotage');
$activeShopName = (string) ($activeShop['nom'] ?? 'Boutique active');
$summary = is_array($summary ?? null) ? $summary : [];
$stats = is_array($stats ?? null) ? $stats : [];
$recentSignals = is_array($recentSignals ?? null) ? $recentSignals : [];
$monthlyTrend = is_array($monthlyTrend ?? null) ? $monthlyTrend : [];

$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$money = static fn ($value): string => number_format((float) $value, 2, ',', ' ') . ' USD';
$statToneClasses = [
    'teal' => 'border-teal-100 bg-teal-50 text-teal-700',
    'blue' => 'border-blue-100 bg-blue-50 text-blue-700',
    'emerald' => 'border-emerald-100 bg-emerald-50 text-emerald-700',
    'amber' => 'border-amber-100 bg-amber-50 text-amber-700',
    'slate' => 'border-slate-200 bg-slate-100 text-slate-700',
];
?>

<section class="space-y-5 sm:space-y-6" data-dashboard-page>
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-teal-700"><?= $safe($pageEyebrow) ?></p>
            <h1 class="mt-3 text-2xl font-semibold tracking-normal text-slate-950 sm:text-3xl">Tableau de bord admin</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                Vue centrale pour suivre les ventes du jour, le chiffre d’affaires, la marge, les charges et les signaux sensibles de <?= $safe($activeShopName) ?>.
            </p>
        </div>

        <div class="hero-action-panel min-w-0 lg:w-80">
            <p class="text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Boutique active</p>
            <p class="mt-2 truncate text-sm font-bold text-slate-950"><?= $safe($activeShopName) ?></p>
            <p class="mt-1 truncate text-xs text-slate-500"><?= $safe($activeShop['adresse'] ?? 'Adresse non définie') ?></p>
            <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                <div class="rounded-lg border border-teal-100 bg-teal-50 p-3">
                    <p class="text-[11px] font-bold uppercase tracking-[.12em] text-teal-700">Aujourd’hui</p>
                    <p class="mt-1 text-sm font-black text-slate-950"><?= (int) ($summary['today_sales'] ?? 0) ?> ticket(s)</p>
                </div>
                <div class="rounded-lg border border-blue-100 bg-blue-50 p-3">
                    <p class="text-[11px] font-bold uppercase tracking-[.12em] text-blue-700">Catalogue</p>
                    <p class="mt-1 text-sm font-black text-slate-950"><?= (int) ($summary['active_products'] ?? 0) ?> actif(s)</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">
        <?php foreach ($stats as $stat): ?>
            <?php $toneClass = $statToneClasses[$stat['tone'] ?? 'slate'] ?? $statToneClasses['slate']; ?>
            <article class="stat-card">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium leading-5 text-slate-500"><?= $safe($stat['label'] ?? '-') ?></p>
                        <p class="mt-3 break-words text-xl font-semibold tracking-normal text-slate-950 sm:text-2xl"><?= $safe($stat['value'] ?? '0') ?></p>
                    </div>
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border <?= $toneClass ?>">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 18V6m0 12h16M8 15l3-4 3 2 4-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-4 text-sm leading-5 text-slate-500"><?= $safe($stat['detail'] ?? '') ?></p>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="dashboard-content-grid grid min-w-0 gap-5 xl:grid-cols-[1.35fr_.65fr]">
        <section class="surface-panel min-w-0" data-dashboard-chart-panel>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="font-bold text-slate-950">Évolution commerciale</h2>
                    <p class="mt-1 text-sm text-slate-500">Chiffre d’affaires, marge brute et bénéfice net des six derniers mois.</p>
                </div>
                <a class="btn-secondary w-full sm:w-auto" href="<?= $url('/rapports/ventes') ?>">Rapports</a>
            </div>

            <div class="mt-5 overflow-x-auto rounded-lg border border-slate-200 bg-slate-50 p-3 sm:mt-6 sm:p-4" data-dashboard-chart-scroll>
                <div class="flex h-64 min-w-[560px] items-end gap-3 sm:h-80 sm:min-w-[680px] sm:gap-5" data-dashboard-chart-inner>
                    <?php foreach ($monthlyTrend as $month): ?>
                        <div class="flex min-w-0 flex-1 flex-col justify-end gap-3">
                            <div class="flex h-48 items-end justify-center gap-1.5 rounded-lg bg-white px-2 py-3 shadow-sm sm:h-64 sm:gap-2">
                                <div class="w-3 rounded-t-md bg-teal-600 sm:w-4" style="height: <?= (int) ($month['revenue_height'] ?? 8) ?>%;" title="CA <?= $safe($month['label'] ?? '') ?> : <?= $money($month['revenue'] ?? 0) ?>"></div>
                                <div class="w-3 rounded-t-md bg-blue-500 sm:w-4" style="height: <?= (int) ($month['margin_height'] ?? 8) ?>%;" title="Marge <?= $safe($month['label'] ?? '') ?> : <?= $money($month['gross_margin'] ?? 0) ?>"></div>
                                <div class="w-3 rounded-t-md <?= (float) ($month['net_profit'] ?? 0) < 0 ? 'bg-red-500' : 'bg-emerald-500' ?> sm:w-4" style="height: <?= (int) ($month['profit_height'] ?? 8) ?>%;" title="Bénéfice <?= $safe($month['label'] ?? '') ?> : <?= $money($month['net_profit'] ?? 0) ?>"></div>
                            </div>
                            <div class="text-center">
                                <p class="text-xs font-black text-slate-600"><?= $safe($month['label'] ?? '') ?></p>
                                <p class="mt-1 truncate text-[11px] font-semibold text-teal-700"><?= $money($month['revenue'] ?? 0) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-3 text-xs font-bold text-slate-500">
                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-teal-600"></span>Chiffre d’affaires</span>
                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-blue-500"></span>Marge brute</span>
                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-emerald-500"></span>Bénéfice net</span>
            </div>
        </section>

        <aside class="surface-panel min-w-0" data-dashboard-signals>
            <div class="flex flex-col gap-2">
                <div>
                    <h2 class="font-bold text-slate-950">Signaux rapides</h2>
                    <p class="mt-1 text-sm text-slate-500">Indicateurs utiles pour l’exploitation quotidienne.</p>
                </div>
            </div>

            <div class="mt-5 space-y-3">
                <?php foreach ($recentSignals as $signal): ?>
                    <div class="signal-row flex-col items-start sm:flex-row sm:items-center">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900"><?= $safe($signal['label'] ?? '-') ?></p>
                            <p class="mt-1 text-xs text-slate-500"><?= $safe($signal['hint'] ?? '') ?></p>
                        </div>
                        <span class="shrink-0 text-sm font-bold text-slate-950 sm:text-right"><?= $safe($signal['value'] ?? '0') ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-5 grid gap-3">
                <a class="btn-primary" href="<?= $url('/pos') ?>">Nouvelle vente</a>
                <a class="btn-secondary w-full" href="<?= $url('/stock') ?>">Voir le stock</a>
            </div>
        </aside>
    </div>
</section>
