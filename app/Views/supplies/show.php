<?php

$supply = is_array($supply ?? null) ? $supply : [];
$details = is_array($details ?? null) ? $details : [];
$activeShop = is_array($activeShop ?? null) ? $activeShop : [];
$exchangeRate = (float) (($activeShop['taux_change_cdf'] ?? 2800) ?: 2800);
$supplyId = (int) ($supply['id'] ?? 0);
$status = (string) ($supply['statut'] ?? '');
$isCancelled = $status === 'annule';
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$money = static function ($value) use ($exchangeRate): string {
    $usd = (float) $value;
    return number_format($usd, 2, ',', ' ') . ' USD (' . number_format($usd * $exchangeRate, 2, ',', ' ') . ' CDF)';
};
$detailMoney = static function (array $detail, string $field, string $fallbackField) use ($money): string {
    $enteredAmount = $detail[$field] ?? null;
    $enteredCurrency = (string) ($detail['devise_saisie'] ?? 'USD');
    $enteredRate = (float) (($detail['taux_change_saisie'] ?? 2800) ?: 2800);

    if ($enteredAmount === null || !in_array($enteredCurrency, ['USD', 'CDF'], true)) {
        return $money($detail[$fallbackField] ?? 0);
    }

    $enteredAmount = (float) $enteredAmount;

    if ($enteredCurrency === 'CDF') {
        return number_format($enteredAmount, 2, ',', ' ') . ' CDF ('
            . number_format($enteredAmount / max($enteredRate, 0.0001), 2, ',', ' ')
            . ' USD)';
    }

    return number_format($enteredAmount, 2, ',', ' ') . ' USD ('
        . number_format($enteredAmount * $enteredRate, 2, ',', ' ')
        . ' CDF)';
};
$dateLabel = static function ($value): string {
    $timestamp = strtotime((string) ($value ?? ''));
    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : '-';
};
$statusLabel = static fn (string $status): string => $status === 'annule' ? 'Annulé' : 'Reçu';
$statusClass = static fn (string $status): string => $status === 'annule' ? 'bg-red-50 text-red-700' : 'bg-teal-50 text-teal-700';
$totalUnits = array_reduce($details, static fn (int $carry, array $detail): int => $carry + (int) ($detail['quantite'] ?? 0), 0);
$icon = static function (string $name): string {
    $paths = [
        'arrow' => '<path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'edit' => '<path d="M4 20h4l10.5-10.5a2.8 2.8 0 0 0-4-4L4 16v4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="m13.5 6.5 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'x' => '<path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
    ];
    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . ($paths[$name] ?? $paths['arrow']) . '</svg>';
};
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Approvisionnement</p>
            <h1 class="truncate text-3xl font-bold tracking-normal text-slate-950"><?= $safe($supply['numero_arrivage'] ?? 'Arrivage') ?></h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Détail de l’arrivage fournisseur, des lignes reçues et de l’impact stock enregistré.
            </p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row">
            <a class="btn-secondary gap-2" href="<?= $url('/supplies') ?>"><?= $icon('arrow') ?><span>Historique</span></a>
            <a class="btn-primary w-full gap-2 sm:w-auto <?= $isCancelled ? 'pointer-events-none opacity-40' : '' ?>" href="<?= $url('/supplies/' . $supplyId . '/edit') ?>"><?= $icon('edit') ?><span>Modifier</span></a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="stat-card"><p class="text-sm text-slate-500">Fournisseur</p><p class="mt-2 truncate text-xl font-bold"><?= $safe($supply['supplier_name'] ?? 'Non renseigné') ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Date</p><p class="mt-2 text-xl font-bold"><?= $safe($dateLabel($supply['date_approvisionnement'] ?? null)) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Total facture</p><p class="mt-2 text-xl font-bold text-teal-700"><?= $money($supply['total_facture'] ?? 0) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Statut</p><p class="mt-3"><span class="inline-flex rounded-full px-3 py-1 text-sm font-bold <?= $statusClass($status) ?>"><?= $statusLabel($status) ?></span></p></article>
    </div>

    <div class="grid gap-5 xl:grid-cols-[1fr_22rem]">
        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Articles reçus</h2>
                    <p class="mt-1 text-sm text-slate-500"><?= count($details) ?> ligne(s), <?= $totalUnits ?> unité(s).</p>
                </div>
            </div>

            <div class="responsive-table mt-5 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead>
                        <tr class="text-xs uppercase tracking-[.14em] text-slate-400">
                            <th class="px-4 py-3 font-semibold">Produit</th>
                            <th class="px-4 py-3 font-semibold">Quantité</th>
                            <th class="px-4 py-3 font-semibold">Prix achat</th>
                            <th class="px-4 py-3 font-semibold">Total ligne</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($details as $detail): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-4" data-label="Produit">
                                    <p class="font-bold text-slate-950"><?= $safe($detail['product_name'] ?? 'Produit') ?></p>
                                    <p class="mt-1 text-xs text-slate-500"><?= $safe($detail['product_ref'] ?? 'Sans référence') ?></p>
                                </td>
                                <td class="px-4 py-4 font-bold" data-label="Quantité"><?= (int) ($detail['quantite'] ?? 0) ?></td>
                                <td class="px-4 py-4" data-label="Prix achat"><?= $detailMoney($detail, 'prix_achat_saisi', 'prix_achat_facture') ?></td>
                                <td class="px-4 py-4 font-bold text-slate-950" data-label="Total ligne"><?= $detailMoney($detail, 'total_ligne_saisi', 'total_ligne') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="surface-panel h-fit">
            <h2 class="font-bold text-slate-950">Actions</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Modifier ajuste le stock par écart. Annuler crée des mouvements d’annulation.</p>
            <div class="mt-5 space-y-3">
                <a class="btn-secondary w-full gap-2 <?= $isCancelled ? 'pointer-events-none opacity-40' : '' ?>" href="<?= $url('/supplies/' . $supplyId . '/edit') ?>"><?= $icon('edit') ?><span>Modifier</span></a>
                <form method="post" action="<?= $url('/supplies/' . $supplyId . '/cancel') ?>" data-confirm-form>
                    <button
                        class="btn-danger w-full gap-2 disabled:cursor-not-allowed disabled:opacity-40"
                        type="button"
                        data-confirm
                        data-confirm-title="Annuler cet approvisionnement ?"
                        data-confirm-message="Le stock sera diminué selon les lignes de cet arrivage et un mouvement d’annulation sera enregistré."
                        data-confirm-accept="Oui, annuler"
                        data-confirm-progress="Annulation..."
                        <?= $isCancelled ? 'disabled' : '' ?>
                    >
                        <?= $icon('x') ?>
                        <span>Annuler l’approvisionnement</span>
                    </button>
                </form>
            </div>
        </aside>
    </div>
</section>
