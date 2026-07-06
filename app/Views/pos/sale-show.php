<?php

$sale = is_array($sale ?? null) ? $sale : [];
$saleDetails = is_array($saleDetails ?? null) ? $saleDetails : [];
$money = static fn ($value): string => number_format((float) $value, 2, ',', ' ') . ' USD';
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$dateLabel = static function ($value): string {
    $timestamp = strtotime((string) ($value ?? ''));
    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : '-';
};
$modeLabel = static fn (string $mode): string => match ($mode) {
    'mobile_money' => 'Mobile money',
    'carte' => 'Carte',
    'virement' => 'Virement',
    'credit' => 'Crédit',
    'mixte' => 'Mixte',
    default => 'Cash',
};
$status = (string) ($sale['statut'] ?? 'validee');
?>

<section class="space-y-5" data-sale-show>
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Vente</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950"><?= $safe($sale['numero_facture'] ?? '-') ?></h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">Détail complet de la vente enregistrée dans la boutique active.</p>
        </div>
        <div class="grid w-full gap-2 sm:grid-cols-2 lg:w-[28rem]">
            <a class="btn-secondary w-full" href="<?= $url('/sales') ?>">Retour</a>
            <a class="btn-secondary w-full" href="<?= $url('/sales/' . (int) ($sale['id'] ?? 0) . '/invoice') ?>" target="_blank">Imprimer</a>
            <a class="btn-primary w-full sm:col-span-2" href="<?= $url('/sales/' . (int) ($sale['id'] ?? 0) . '/edit') ?>">Modifier la vente</a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="stat-card">
            <p class="text-sm text-slate-500">Total</p>
            <p class="mt-2 text-2xl font-bold text-slate-950"><?= $money($sale['total_montant'] ?? 0) ?></p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Montant reçu</p>
            <p class="mt-2 text-2xl font-bold text-teal-700"><?= $money($sale['montant_recu'] ?? 0) ?></p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Crédit</p>
            <p class="mt-2 text-2xl font-bold text-amber-700"><?= $money($sale['montant_dette'] ?? 0) ?></p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Statut</p>
            <p class="mt-2 text-2xl font-bold <?= $status === 'annulee' ? 'text-red-700' : 'text-teal-700' ?>"><?= $status === 'annulee' ? 'Annulée' : 'Validée' ?></p>
        </article>
    </div>

    <div class="grid gap-5 xl:grid-cols-[1fr_22rem]">
        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Articles vendus</h2>
                    <p class="mt-1 text-sm text-slate-500"><?= count($saleDetails) ?> ligne(s) de vente.</p>
                </div>
            </div>
            <div class="responsive-table mt-5 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead>
                        <tr class="text-xs uppercase tracking-[.14em] text-slate-400">
                            <th class="px-4 py-3 font-semibold">Produit</th>
                            <th class="px-4 py-3 font-semibold">Qté</th>
                            <th class="px-4 py-3 font-semibold">Prix</th>
                            <th class="px-4 py-3 font-semibold">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($saleDetails as $detail): ?>
                            <tr>
                                <td class="px-4 py-4">
                                    <p class="font-bold text-slate-950"><?= $safe($detail['product_name'] ?? 'Produit') ?></p>
                                    <p class="mt-1 text-xs text-slate-500"><?= $safe($detail['product_ref'] ?? '') ?></p>
                                </td>
                                <td class="px-4 py-4 font-semibold" data-label="Quantité"><?= (int) ($detail['quantite'] ?? 0) ?></td>
                                <td class="px-4 py-4" data-label="Prix"><?= $money($detail['prix_unitaire_vendu'] ?? 0) ?></td>
                                <td class="px-4 py-4 font-bold" data-label="Total"><?= $money($detail['total_ligne'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="surface-panel h-fit space-y-4">
            <div>
                <p class="text-sm text-slate-500">Client</p>
                <p class="mt-1 font-bold text-slate-950"><?= $safe($sale['customer_name'] ?? 'Client comptant') ?></p>
            </div>
            <div>
                <p class="text-sm text-slate-500">Caissier</p>
                <p class="mt-1 font-bold text-slate-950"><?= $safe($sale['user_name'] ?? '-') ?></p>
            </div>
            <div>
                <p class="text-sm text-slate-500">Paiement</p>
                <p class="mt-1 font-bold text-slate-950"><?= $safe($modeLabel((string) ($sale['mode_paiement'] ?? 'cash'))) ?></p>
            </div>
            <div>
                <p class="text-sm text-slate-500">Date</p>
                <p class="mt-1 font-bold text-slate-950"><?= $safe($dateLabel($sale['date_vente'] ?? null)) ?></p>
            </div>
            <?php if ($status === 'annulee'): ?>
                <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700">
                    <p class="font-bold">Vente annulée</p>
                    <p class="mt-1"><?= $safe($sale['motif_annulation'] ?? 'Aucun motif') ?></p>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</section>
