<?php

$expenses = is_array($expenses ?? null) ? $expenses : [];
$activeShop = is_array($activeShop ?? null) ? $activeShop : [];
$filters = is_array($filters ?? null) ? $filters : [];
$expenseCategories = is_array($expenseCategories ?? null) ? $expenseCategories : ['transport', 'facture', 'loyer', 'salaire', 'perte_avarie', 'autre'];
$availableProfit = max(0.0, (float) ($availableProfit ?? 0));
$expenseCurrency = in_array(($activeShop['devise_principale'] ?? 'USD'), ['USD', 'CDF'], true) ? (string) $activeShop['devise_principale'] : 'USD';
$exchangeRate = (float) (($activeShop['taux_change_cdf'] ?? 2800) ?: 2800);
$availableProfitInput = $expenseCurrency === 'CDF' ? $availableProfit * $exchangeRate : $availableProfit;
$availableProfitUsdInput = number_format($availableProfit, 2, '.', '');
$availableProfitCdfInput = number_format($availableProfit * $exchangeRate, 2, '.', '');
$returnTo = '/expenses' . (!empty($_SERVER['QUERY_STRING']) ? '?' . (string) $_SERVER['QUERY_STRING'] : '');
$activeExpenseTotal = array_sum(array_map(
    static fn (array $expense): float => (($expense['statut'] ?? 'active') === 'active') ? (float) $expense['montant'] : 0.0,
    $expenses
));
$categoryLabels = [
    'transport' => 'Transport',
    'facture' => 'Electricite / facture',
    'loyer' => 'Loyer',
    'salaire' => 'Salaire',
    'perte_avarie' => 'Perte ou avarie',
    'autre' => 'Autre',
];
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
$amountForInput = static function ($value) use ($expenseCurrency, $exchangeRate): string {
    $amount = (float) $value;

    if ($expenseCurrency === 'CDF') {
        $amount *= $exchangeRate;
    }

    return number_format($amount, 2, '.', '');
};
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Finances</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Charges de la boutique</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Enregistrez, filtrez, corrigez et annulez les depenses operationnelles.</p>
        </div>
        <div class="hero-action-panel">
            <p class="text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Depenses actives</p>
            <p class="mt-2 text-2xl font-bold text-slate-950"><?= $money($activeExpenseTotal) ?></p>
            <p class="mt-1 text-xs font-semibold text-slate-500">1 USD = <?= number_format($exchangeRate, 2, ',', ' ') ?> CDF</p>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <article class="stat-card">
            <p class="text-sm text-slate-500">Benefice disponible</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700"><?= $money($availableProfit) ?></p>
            <p class="mt-1 text-xs font-semibold text-slate-500">Les sorties ne peuvent pas depasser ce solde.</p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Charges affichees</p>
            <p class="mt-2 text-2xl font-bold text-amber-700"><?= count($expenses) ?></p>
            <p class="mt-1 text-xs font-semibold text-slate-500">Selon les filtres actifs.</p>
        </article>
    </div>

    <div class="grid gap-5 xl:grid-cols-[23rem_1fr]">
        <form class="surface-panel h-fit space-y-4" method="post" action="<?= $url('/expenses') ?>" accept-charset="UTF-8">
            <div>
                <h2 class="font-bold text-slate-950">Nouvelle depense</h2>
                <p class="mt-1 text-sm text-slate-500">Saisie rapide pour les charges courantes.</p>
            </div>
            <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">Titre</span>
                <input class="field-control" name="titre" type="text" placeholder="Ex. Carburant generateur">
            </label>
            <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">Categorie</span>
                <select class="field-control" name="categorie">
                    <?php foreach ($expenseCategories as $category): ?>
                        <option value="<?= $safe($category) ?>"><?= $safe($categoryLabels[$category] ?? $category) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">Devise</span>
                <select class="field-control" name="devise" data-expense-currency>
                    <option value="USD" <?= $expenseCurrency === 'USD' ? 'selected' : '' ?>>USD</option>
                    <option value="CDF" <?= $expenseCurrency === 'CDF' ? 'selected' : '' ?>>CDF</option>
                </select>
            </label>
            <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">Montant</span>
                <input
                    class="field-control"
                    name="montant"
                    type="number"
                    min="0"
                    max="<?= $safe(number_format($availableProfitInput, 2, '.', '')) ?>"
                    step="0.01"
                    placeholder="0.00"
                    data-expense-amount
                    data-max-usd="<?= $safe($availableProfitUsdInput) ?>"
                    data-max-cdf="<?= $safe($availableProfitCdfInput) ?>"
                >
                <span class="mt-2 block text-xs font-semibold text-slate-500">Maximum autorise: <?= $money($availableProfit) ?></span>
            </label>
            <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500">La date de la depense est enregistree automatiquement lors de la validation.</p>
            <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">Description</span>
                <textarea class="field-control min-h-24" name="description" placeholder="Note interne"></textarea>
            </label>
            <button class="btn-primary" type="submit">Ajouter la depense</button>
        </form>

        <section class="space-y-5">
            <form class="surface-panel" method="get" action="<?= $url('/expenses') ?>" accept-charset="UTF-8">
                <div class="panel-header">
                    <div>
                        <h2 class="font-bold text-slate-950">Filtres avances</h2>
                        <p class="mt-1 text-sm text-slate-500">Recherche, periode, categorie et statut.</p>
                    </div>
                    <a class="btn-secondary h-10 w-full sm:w-auto" href="<?= $url('/expenses') ?>">Reinitialiser</a>
                </div>
                <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                    <input class="field-control xl:col-span-2" name="search" value="<?= $safe($filters['search'] ?? '') ?>" placeholder="Titre ou description">
                    <select class="field-control" name="category">
                        <option value="">Toutes categories</option>
                        <?php foreach ($expenseCategories as $category): ?>
                            <option value="<?= $safe($category) ?>" <?= ($filters['category'] ?? '') === $category ? 'selected' : '' ?>><?= $safe($categoryLabels[$category] ?? $category) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="field-control" name="status">
                        <option value="">Tous statuts</option>
                        <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Actives</option>
                        <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Annulees</option>
                    </select>
                    <button class="btn-primary h-12" type="submit">Filtrer</button>
                    <input class="field-control" name="date_debut" type="date" value="<?= $safe($filters['date_debut'] ?? '') ?>">
                    <input class="field-control" name="date_fin" type="date" value="<?= $safe($filters['date_fin'] ?? '') ?>">
                </div>
            </form>

            <section class="surface-panel">
                <div class="panel-header">
                    <div>
                        <h2 class="font-bold text-slate-950">Historique des charges</h2>
                        <p class="mt-1 text-sm text-slate-500"><?= count($expenses) ?> depense(s) trouvee(s).</p>
                    </div>
                    <button class="btn-secondary" type="button">Exporter</button>
                </div>

                <div class="mt-5 space-y-3">
                    <?php foreach ($expenses as $expense): ?>
                        <?php
                        $expenseId = (int) ($expense['id'] ?? 0);
                        $isCancelled = ($expense['statut'] ?? 'active') === 'cancelled';
                        $editMax = $isCancelled ? 0.0 : $availableProfit + (float) ($expense['montant'] ?? 0);
                        $editCurrency = in_array(($expense['devise_saisie'] ?? 'USD'), ['USD', 'CDF'], true) ? (string) $expense['devise_saisie'] : 'USD';
                        $editRate = (float) (($expense['taux_change_saisie'] ?? $exchangeRate) ?: $exchangeRate);
                        $editMaxInput = $editCurrency === 'CDF' ? $editMax * $editRate : $editMax;
                        ?>
                        <article class="rounded-lg border border-slate-200 bg-white p-4 <?= $isCancelled ? 'opacity-75' : '' ?>">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="truncate font-semibold text-slate-950"><?= $safe($expense['titre'] ?? '') ?></p>
                                        <span class="rounded-lg px-2 py-1 text-[11px] font-black uppercase <?= $isCancelled ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' ?>"><?= $isCancelled ? 'Annulee' : 'Active' ?></span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500"><?= $safe($categoryLabels[$expense['categorie'] ?? ''] ?? ($expense['categorie'] ?? '')) ?> - <?= $safe($expense['date_depense'] ?? '') ?> - <?= $safe($expense['user'] ?? '') ?></p>
                                    <?php if ($isCancelled && !empty($expense['cancellation_reason'])): ?>
                                        <p class="mt-2 text-xs font-semibold text-red-700">Motif: <?= $safe($expense['cancellation_reason']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                                    <strong class="mr-2 text-right"><?= $expenseMoney($expense) ?></strong>
                                    <a class="btn-secondary h-10 w-auto px-3 text-xs" href="<?= $url('/expenses/' . $expenseId) ?>">Detail</a>
                                    <?php if (!$isCancelled): ?>
                                        <details class="relative">
                                            <summary class="btn-secondary h-10 w-auto cursor-pointer list-none px-3 text-xs">Modifier</summary>
                                            <form class="absolute right-0 z-20 mt-2 w-[min(28rem,calc(100vw-2rem))] space-y-3 rounded-lg border border-slate-200 bg-white p-4 text-left shadow-xl" method="post" action="<?= $url('/expenses/' . $expenseId . '/update') ?>" accept-charset="UTF-8">
                                                <input type="hidden" name="return_to" value="<?= $safe($returnTo) ?>">
                                                <div class="grid gap-3 sm:grid-cols-2">
                                                    <label class="block sm:col-span-2"><span class="mb-2 block text-xs font-bold uppercase tracking-[.14em] text-slate-400">Titre</span><input class="field-control" name="titre" value="<?= $safe($expense['titre'] ?? '') ?>" required></label>
                                                    <label class="block"><span class="mb-2 block text-xs font-bold uppercase tracking-[.14em] text-slate-400">Categorie</span><select class="field-control" name="categorie"><?php foreach ($expenseCategories as $category): ?><option value="<?= $safe($category) ?>" <?= ($expense['categorie'] ?? '') === $category ? 'selected' : '' ?>><?= $safe($categoryLabels[$category] ?? $category) ?></option><?php endforeach; ?></select></label>
                                                    <label class="block"><span class="mb-2 block text-xs font-bold uppercase tracking-[.14em] text-slate-400">Devise</span><select class="field-control" name="devise"><option value="USD" <?= ($expense['devise_saisie'] ?? 'USD') === 'USD' ? 'selected' : '' ?>>USD</option><option value="CDF" <?= ($expense['devise_saisie'] ?? 'USD') === 'CDF' ? 'selected' : '' ?>>CDF</option></select></label>
                                                    <label class="block sm:col-span-2"><span class="mb-2 block text-xs font-bold uppercase tracking-[.14em] text-slate-400">Montant</span><input class="field-control" name="montant" type="number" min="0" max="<?= $safe(number_format($editMaxInput, 2, '.', '')) ?>" step="0.01" value="<?= $safe(number_format((float) ($expense['montant_saisi'] ?? $amountForInput($expense['montant'] ?? 0)), 2, '.', '')) ?>" required></label>
                                                    <label class="block sm:col-span-2"><span class="mb-2 block text-xs font-bold uppercase tracking-[.14em] text-slate-400">Description</span><textarea class="field-control min-h-24" name="description"><?= $safe($expense['description'] ?? '') ?></textarea></label>
                                                </div>
                                                <button class="btn-primary h-10 w-full" type="submit">Enregistrer</button>
                                            </form>
                                        </details>
                                        <form method="post" action="<?= $url('/expenses/' . $expenseId . '/cancel') ?>" data-confirm-form>
                                            <input type="hidden" name="return_to" value="<?= $safe($returnTo) ?>">
                                            <input type="hidden" name="cancellation_reason" value="Annulation depuis l historique des charges">
                                            <button class="h-10 rounded-lg bg-red-50 px-3 text-xs font-bold text-red-700 hover:bg-red-100" type="button" data-confirm data-confirm-title="Annuler cette depense ?" data-confirm-message="La depense sera marquee comme annulee et retiree des totaux financiers." data-confirm-accept="Oui, annuler" data-confirm-progress="Annulation...">Annuler</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    <?php if ($expenses === []): ?>
                        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-sm font-semibold text-slate-500">Aucune depense ne correspond aux filtres.</div>
                    <?php endif; ?>
                </div>
            </section>
        </section>
    </div>
</section>

<script>
const expenseCurrency = document.querySelector('[data-expense-currency]');
const expenseAmount = document.querySelector('[data-expense-amount]');

expenseCurrency?.addEventListener('change', () => {
    if (!expenseAmount) {
        return;
    }

    const key = expenseCurrency.value === 'CDF' ? 'maxCdf' : 'maxUsd';
    expenseAmount.max = expenseAmount.dataset[key] || expenseAmount.max;
});
</script>
