<?php

$subscription = is_array($subscription ?? null) ? $subscription : null;
$payments = is_array($payments ?? null) ? $payments : [];
$activeShop = is_array($activeShop ?? null) ? $activeShop : [];
$shopCategory = (string) ($activeShop['category_name'] ?? 'Sans categorie');
$exchangeRate = (float) (($activeShop['taux_change_cdf'] ?? $subscription['taux_change_cdf'] ?? 2800) ?: 2800);
$shopCurrency = in_array(($activeShop['devise_principale'] ?? $subscription['devise_principale'] ?? 'USD'), ['USD', 'CDF'], true) ? (string) ($activeShop['devise_principale'] ?? $subscription['devise_principale']) : 'USD';
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$date = static function ($value): string {
    $timestamp = strtotime((string) ($value ?? ''));

    return $timestamp !== false ? date('d/m/Y', $timestamp) : '-';
};
$money = static function ($usdValue) use ($shopCurrency, $exchangeRate): string {
    $usd = (float) $usdValue;

    if ($shopCurrency === 'CDF') {
        return number_format($usd * $exchangeRate, 0, ',', ' ') . ' CDF <span class="block text-xs font-semibold text-slate-500">(' . number_format($usd, 2, ',', ' ') . ' USD)</span>';
    }

    return number_format($usd, 2, ',', ' ') . ' USD <span class="block text-xs font-semibold text-slate-500">(' . number_format($usd * $exchangeRate, 0, ',', ' ') . ' CDF)</span>';
};
$status = (string) ($subscription['statut'] ?? 'non_configure');
$statusLabels = [
    'trial' => 'Essai',
    'active' => 'Actif',
    'past_due' => 'Paiement en retard',
    'suspended' => 'Suspendu',
    'cancelled' => 'Annule',
    'non_configure' => 'Non configure',
];
$statusClass = match ($status) {
    'trial', 'active' => 'bg-teal-50 text-teal-700',
    'past_due' => 'bg-amber-50 text-amber-700',
    'suspended', 'cancelled' => 'bg-red-50 text-red-700',
    default => 'bg-slate-100 text-slate-600',
};
$daysLeft = null;
if ($subscription !== null && trim((string) ($subscription['date_fin'] ?? '')) !== '') {
    $end = DateTimeImmutable::createFromFormat('!Y-m-d', substr((string) $subscription['date_fin'], 0, 10));
    if ($end instanceof DateTimeImmutable) {
        $daysLeft = (int) (new DateTimeImmutable('today'))->diff($end)->format('%r%a');
    }
}
$features = preg_split('/\R+/', trim((string) ($subscription['plan_description'] ?? ''))) ?: [];
$features = array_values(array_filter(array_map('trim', $features), static fn (string $line): bool => $line !== ''));
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Abonnement</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Abonnement de la boutique</h1>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-teal-700"><?= $safe($activeShop['nom'] ?? 'Boutique') ?></span>
                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">Categorie : <?= $safe($shopCategory) ?></span>
            </div>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Consultez le plan actif, les informations de paiement et le renouvellement de votre boutique.
            </p>
        </div>
        <?php if ($subscription !== null): ?>
            <form method="post" action="<?= $url('/shops/subscription/renew') ?>" data-confirm-form>
                <button
                    class="btn-primary w-full px-5 sm:w-auto"
                    type="button"
                    data-confirm
                    data-confirm-title="Demander le renouvellement ?"
                    data-confirm-message="Une demande de paiement sera ajoutee a votre historique d abonnement."
                    data-confirm-accept="Oui, renouveler"
                    data-confirm-progress="Creation..."
                >Renouveler</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($subscription === null): ?>
        <section class="surface-panel">
            <h2 class="font-bold text-slate-950">Aucun abonnement configure</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Contactez l'administrateur SaaS pour rattacher un plan a cette boutique.</p>
        </section>
    <?php else: ?>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="stat-card">
                <p class="text-sm text-slate-500">Plan actuel</p>
                <p class="mt-2 text-2xl font-bold"><?= $safe($subscription['plan_name'] ?? 'Sans plan') ?></p>
                <p class="mt-1 text-xs font-bold uppercase tracking-[.14em] text-slate-400"><?= $safe($subscription['plan_code'] ?? '') ?></p>
            </article>
            <article class="stat-card">
                <p class="text-sm text-slate-500">Statut</p>
                <p class="mt-3"><span class="rounded-full px-3 py-1 text-sm font-bold <?= $statusClass ?>"><?= $safe($statusLabels[$status] ?? $status) ?></span></p>
            </article>
            <article class="stat-card">
                <p class="text-sm text-slate-500">Prix mensuel</p>
                <p class="mt-2 text-2xl font-bold text-teal-700"><?= $money($subscription['prix_mensuel_usd'] ?? 0) ?></p>
            </article>
            <article class="stat-card">
                <p class="text-sm text-slate-500">Prochaine echeance</p>
                <p class="mt-2 text-2xl font-bold"><?= $date($subscription['date_fin'] ?? null) ?></p>
                <?php if ($daysLeft !== null): ?>
                    <p class="mt-1 text-xs font-semibold <?= $daysLeft < 0 ? 'text-red-700' : ($daysLeft <= 7 ? 'text-amber-700' : 'text-slate-500') ?>">
                        <?= $daysLeft < 0 ? abs($daysLeft) . ' jour(s) de retard' : $daysLeft . ' jour(s) restant(s)' ?>
                    </p>
                <?php endif; ?>
            </article>
        </div>

        <div class="grid gap-5 xl:grid-cols-[1fr_24rem]">
            <section class="surface-panel">
                <div class="panel-header">
                    <div>
                        <h2 class="font-bold text-slate-950">Informations de paiement</h2>
                        <p class="mt-1 text-sm text-slate-500">Historique des renouvellements et demandes de paiement.</p>
                    </div>
                </div>
                <div class="responsive-table mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-[.14em] text-slate-400">
                                <th class="px-4 py-3 font-semibold">Reference</th>
                                <th class="px-4 py-3 font-semibold">Plan</th>
                                <th class="px-4 py-3 font-semibold">Periode</th>
                                <th class="px-4 py-3 font-semibold">Montant</th>
                                <th class="px-4 py-3 font-semibold">Statut</th>
                                <th class="px-4 py-3 font-semibold">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($payments as $payment): ?>
                                <?php
                                $paymentStatus = (string) ($payment['statut'] ?? 'pending');
                                $paymentClass = match ($paymentStatus) {
                                    'paid' => 'bg-teal-50 text-teal-700',
                                    'failed', 'cancelled' => 'bg-red-50 text-red-700',
                                    default => 'bg-amber-50 text-amber-700',
                                };
                                ?>
                                <tr>
                                    <td class="px-4 py-4 font-bold text-slate-950"><?= $safe($payment['reference'] ?? null) ?></td>
                                    <td class="px-4 py-4 text-slate-600"><?= $safe($payment['plan_name'] ?? $subscription['plan_name'] ?? '-') ?></td>
                                    <td class="px-4 py-4 text-slate-600"><?= $date($payment['periode_debut'] ?? null) ?> - <?= $date($payment['periode_fin'] ?? null) ?></td>
                                    <td class="px-4 py-4 font-bold"><?= $money($payment['montant_usd'] ?? 0) ?></td>
                                    <td class="px-4 py-4"><span class="rounded-full px-3 py-1 text-xs font-bold <?= $paymentClass ?>"><?= $safe($paymentStatus) ?></span></td>
                                    <td class="px-4 py-4 text-slate-600"><?= $date($payment['created_at'] ?? null) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($payments === []): ?>
                        <p class="rounded-lg border border-dashed border-slate-200 p-6 text-center text-sm font-semibold text-slate-500">Aucun paiement enregistre pour le moment.</p>
                    <?php endif; ?>
                </div>
            </section>

            <aside class="space-y-5">
                <section class="surface-panel">
                    <h2 class="font-bold text-slate-950">Renouvellement</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Activez ou desactivez la reconduction automatique de votre abonnement.</p>
                    <form class="mt-5 space-y-4" method="post" action="<?= $url('/shops/subscription/auto-renew') ?>">
                        <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                            <span class="text-sm font-semibold text-slate-800">Renouvellement automatique</span>
                            <input class="h-5 w-5 rounded border-slate-300 text-teal-700 focus:ring-teal-600" type="checkbox" name="renouvellement_auto" value="1" <?= (int) ($subscription['renouvellement_auto'] ?? 1) === 1 ? 'checked' : '' ?>>
                        </label>
                        <button class="btn-secondary w-full" type="submit">Mettre a jour</button>
                    </form>
                </section>

                <section class="surface-panel">
                    <h2 class="font-bold text-slate-950">Modules inclus</h2>
                    <div class="mt-4 grid gap-2">
                        <?php foreach (array_slice($features, 0, 12) as $feature): ?>
                            <span class="rounded-lg bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700"><?= $safe($feature) ?></span>
                        <?php endforeach; ?>
                        <?php if ($features === []): ?>
                            <span class="text-sm font-semibold text-slate-500">Aucun module detaille pour ce plan.</span>
                        <?php endif; ?>
                    </div>
                </section>
            </aside>
        </div>
    <?php endif; ?>
</section>
