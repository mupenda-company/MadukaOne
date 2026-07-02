<?php

$pageEyebrow = (string) ($pageEyebrow ?? 'Pilotage');
$activeShopName = (string) ($activeShop['nom'] ?? 'Boutique active');
$statToneClasses = [
    'teal' => 'border-teal-100 bg-teal-50 text-teal-700',
    'blue' => 'border-blue-100 bg-blue-50 text-blue-700',
    'amber' => 'border-amber-100 bg-amber-50 text-amber-700',
    'slate' => 'border-slate-200 bg-slate-100 text-slate-700',
];
?>
<section class="space-y-6">
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-teal-700"><?= htmlspecialchars($pageEyebrow, ENT_QUOTES, 'UTF-8') ?></p>
            <h1 class="mt-3 text-2xl font-semibold tracking-normal text-slate-950 sm:text-3xl">Tableau de bord admin</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Vue centrale pour suivre les revenus, les charges, la marge et les signaux sensibles de <?= htmlspecialchars($activeShopName, ENT_QUOTES, 'UTF-8') ?>.
            </p>
        </div>

        <div class="hero-action-panel">
            <p class="text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Boutique active</p>
            <p class="mt-2 truncate text-sm font-bold text-slate-950"><?= htmlspecialchars($activeShopName, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="mt-1 truncate text-xs text-slate-500"><?= htmlspecialchars((string) ($activeShop['adresse'] ?? 'Adresse non définie'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <?php foreach ($stats as $stat): ?>
            <?php $toneClass = $statToneClasses[$stat['tone'] ?? 'slate'] ?? $statToneClasses['slate']; ?>
            <article class="stat-card">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-500"><?= htmlspecialchars((string) $stat['label'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="mt-3 text-2xl font-semibold tracking-normal text-slate-950"><?= htmlspecialchars((string) $stat['value'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border <?= $toneClass ?>">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 18V6m0 12h16M8 15l3-4 3 2 4-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-4 text-sm text-slate-500"><?= htmlspecialchars((string) $stat['detail'], ENT_QUOTES, 'UTF-8') ?></p>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="grid gap-4 xl:grid-cols-[1.3fr_.7fr]">
        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <p class="text-sm font-semibold text-slate-950">Evolution commerciale</p>
                    <p class="mt-1 text-xs text-slate-500">Zone réservée au graphique chiffre d affaires, marge et bénéfice net.</p>
                </div>
                <a class="btn-secondary" href="<?= $url('/rapports/ventes') ?>">Rapports</a>
            </div>

            <div class="mt-6 flex h-72 items-end gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                <?php foreach ([42, 56, 38, 72, 64, 86, 78, 92, 70, 96, 84, 100] as $height): ?>
                    <div class="flex flex-1 items-end">
                        <div class="w-full rounded-t-md bg-gradient-to-t from-teal-700 to-teal-400" style="height: <?= (int) $height ?>%;"></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <aside class="surface-panel">
            <div class="panel-header">
                <div>
                    <p class="text-sm font-semibold text-slate-950">Signaux rapides</p>
                    <p class="mt-1 text-xs text-slate-500">Indicateurs utiles pour l exploitation quotidienne.</p>
                </div>
            </div>

            <div class="mt-5 space-y-3">
                <?php foreach ($recentSignals as $signal): ?>
                    <div class="signal-row">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900"><?= htmlspecialchars((string) $signal['label'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="mt-1 truncate text-xs text-slate-500"><?= htmlspecialchars((string) $signal['hint'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <span class="shrink-0 text-sm font-bold text-slate-950"><?= htmlspecialchars((string) $signal['value'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </aside>
    </div>
</section>
