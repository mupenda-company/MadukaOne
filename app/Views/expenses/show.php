<?php

$expense = is_array($expense ?? null) ? $expense : [];
$activeShop = is_array($activeShop ?? null) ? $activeShop : [];
$expenseCurrency = in_array(($activeShop['devise_principale'] ?? 'USD'), ['USD', 'CDF'], true) ? (string) $activeShop['devise_principale'] : 'USD';
$exchangeRate = (float) (($activeShop['taux_change_cdf'] ?? 2800) ?: 2800);
$isCancelled = ($expense['statut'] ?? 'active') === 'cancelled';
$safe = static fn ($value, string $fallback = ''): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$money = static function ($value) use ($expenseCurrency, $exchangeRate): string {
    $usd = (float) $value;
    $cdf = $usd * $exchangeRate;
    $usdLabel = number_format($usd, 2, ',', ' ') . ' USD';
    $cdfLabel = number_format($cdf, 2, ',', ' ') . ' CDF';

    return $expenseCurrency === 'CDF'
        ? $cdfLabel . ' (' . $usdLabel . ')'
        : $usdLabel . ' (' . $cdfLabel . ')';
};
$expenseMoney = static function (array $expense) use ($money): string {
    $enteredAmount = $expense['montant_saisi'] ?? null;
    $enteredCurrency = (string) ($expense['devise_saisie'] ?? 'USD');
    $enteredRate = (float) (($expense['taux_change_saisie'] ?? 2800) ?: 2800);

    if ($enteredAmount === null || !in_array($enteredCurrency, ['USD', 'CDF'], true)) {
        return $money($expense['montant'] ?? 0);
    }

    $enteredAmount = (float) $enteredAmount;

    if ($enteredCurrency === 'CDF') {
        $convertedUsd = $enteredAmount / max($enteredRate, 0.0001);

        return number_format($enteredAmount, 2, ',', ' ') . ' CDF ('
            . number_format($convertedUsd, 2, ',', ' ')
            . ' USD)';
    }

    return number_format($enteredAmount, 2, ',', ' ') . ' USD ('
        . number_format($enteredAmount * $enteredRate, 2, ',', ' ')
        . ' CDF)';
};
$categoryLabels = [
    'transport' => 'Transport',
    'facture' => 'Electricite / facture',
    'loyer' => 'Loyer',
    'salaire' => 'Salaire',
    'perte_avarie' => 'Perte ou avarie',
    'autre' => 'Autre',
];
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Detail charge</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950"><?= $safe($expense['titre'] ?? 'Depense') ?></h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Lecture complete de la depense enregistree.</p>
        </div>
        <a class="btn-secondary" href="<?= $url('/expenses') ?>">Retour aux charges</a>
    </div>

    <div class="grid gap-5 lg:grid-cols-[1fr_22rem]">
        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Informations</h2>
                    <p class="mt-1 text-sm text-slate-500">Reference #<?= (int) ($expense['id'] ?? 0) ?></p>
                </div>
                <span class="rounded-lg px-3 py-2 text-xs font-black uppercase <?= $isCancelled ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' ?>"><?= $isCancelled ? 'Annulee' : 'Active' ?></span>
            </div>

            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[.14em] text-slate-400">Montant</dt><dd class="mt-2 text-xl font-black text-slate-950"><?= $expenseMoney($expense) ?></dd></div>
                <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[.14em] text-slate-400">Categorie</dt><dd class="mt-2 font-semibold text-slate-950"><?= $safe($categoryLabels[$expense['categorie'] ?? ''] ?? ($expense['categorie'] ?? '')) ?></dd></div>
                <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[.14em] text-slate-400">Date</dt><dd class="mt-2 font-semibold text-slate-950"><?= $safe($expense['date_depense'] ?? '') ?></dd></div>
                <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[.14em] text-slate-400">Enregistre par</dt><dd class="mt-2 font-semibold text-slate-950"><?= $safe($expense['user_name'] ?? '') ?></dd></div>
            </dl>

            <div class="mt-5 rounded-lg border border-slate-200 p-4">
                <h3 class="font-bold text-slate-950">Description</h3>
                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600"><?= $safe($expense['description'] ?? 'Aucune description.') ?></p>
            </div>
        </section>

        <aside class="surface-panel h-fit">
            <h2 class="font-bold text-slate-950">Audit</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="text-slate-500">Creation</dt><dd class="font-semibold text-slate-950"><?= $safe($expense['created_at'] ?? '') ?></dd></div>
                <div><dt class="text-slate-500">Derniere modification</dt><dd class="font-semibold text-slate-950"><?= $safe($expense['updated_at'] ?? '') ?></dd></div>
                <?php if ($isCancelled): ?>
                    <div><dt class="text-slate-500">Annulation</dt><dd class="font-semibold text-red-700"><?= $safe($expense['cancelled_at'] ?? '') ?></dd></div>
                    <div><dt class="text-slate-500">Motif</dt><dd class="font-semibold text-red-700"><?= $safe($expense['cancellation_reason'] ?? 'Non renseigne') ?></dd></div>
                <?php endif; ?>
            </dl>
        </aside>
    </div>
</section>
