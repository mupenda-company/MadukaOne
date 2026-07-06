<?php

$sale = is_array($sale ?? null) ? $sale : [];
$saleDetails = is_array($saleDetails ?? null) ? $saleDetails : [];
$customers = is_array($customers ?? null) ? $customers : [];
$money = static fn ($value): string => number_format((float) $value, 2, ',', ' ') . ' USD';
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$selected = static fn ($current, $expected): string => (string) $current === (string) $expected ? 'selected' : '';
$saleId = (int) ($sale['id'] ?? 0);
$isCancelled = (string) ($sale['statut'] ?? '') === 'annulee';
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Vente</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Modifier <?= $safe($sale['numero_facture'] ?? '-') ?></h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">Ajustez le client, le mode de paiement et le montant reçu sans modifier les articles vendus.</p>
        </div>
        <div class="grid w-full gap-2 sm:grid-cols-2 lg:w-[24rem]">
            <a class="btn-secondary w-full" href="<?= $url('/sales/' . $saleId) ?>">Annuler</a>
            <a class="btn-secondary w-full" href="<?= $url('/sales') ?>">Historique</a>
        </div>
    </div>

    <?php if ($isCancelled): ?>
        <div class="surface-panel border-red-100 bg-red-50 text-sm font-semibold text-red-700">
            Cette vente est annulée et ne peut plus être modifiée.
        </div>
    <?php endif; ?>

    <form class="grid gap-5 xl:grid-cols-[1fr_22rem]" method="post" action="<?= $url('/sales/' . $saleId . '/update') ?>" accept-charset="UTF-8">
        <section class="surface-panel space-y-5">
            <div>
                <h2 class="font-bold text-slate-950">Règlement</h2>
                <p class="mt-1 text-sm text-slate-500">Le crédit est recalculé automatiquement à partir du total et du montant reçu.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block md:col-span-2">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Client</span>
                    <select class="field-control" name="customer_id" <?= $isCancelled ? 'disabled' : '' ?>>
                        <option value="">Client comptant</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= (int) $customer['id'] ?>" <?= $selected($sale['customer_id'] ?? '', $customer['id'] ?? '') ?>>
                                <?= $safe($customer['nom'] ?? 'Client') ?>
                                <?php if (($customer['telephone'] ?? '') !== ''): ?>
                                    - <?= $safe($customer['telephone']) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Mode de paiement</span>
                    <select class="field-control" name="mode_paiement" <?= $isCancelled ? 'disabled' : '' ?>>
                        <option value="cash" <?= $selected($sale['mode_paiement'] ?? '', 'cash') ?>>Cash</option>
                        <option value="mobile_money" <?= $selected($sale['mode_paiement'] ?? '', 'mobile_money') ?>>Mobile money</option>
                        <option value="carte" <?= $selected($sale['mode_paiement'] ?? '', 'carte') ?>>Carte</option>
                        <option value="virement" <?= $selected($sale['mode_paiement'] ?? '', 'virement') ?>>Virement</option>
                        <option value="credit" <?= $selected($sale['mode_paiement'] ?? '', 'credit') ?>>Crédit</option>
                        <option value="mixte" <?= $selected($sale['mode_paiement'] ?? '', 'mixte') ?>>Mixte</option>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Montant reçu</span>
                    <input class="field-control" name="montant_recu" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($sale['montant_recu'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" <?= $isCancelled ? 'disabled' : '' ?>>
                </label>
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[.12em] text-slate-400">Total</p>
                        <p class="mt-1 text-lg font-bold text-slate-950"><?= $money($sale['total_montant'] ?? 0) ?></p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[.12em] text-slate-400">Reçu actuel</p>
                        <p class="mt-1 text-lg font-bold text-teal-700"><?= $money($sale['montant_recu'] ?? 0) ?></p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[.12em] text-slate-400">Crédit actuel</p>
                        <p class="mt-1 text-lg font-bold text-amber-700"><?= $money($sale['montant_dette'] ?? 0) ?></p>
                    </div>
                </div>
            </div>

            <button class="btn-primary w-full sm:w-auto" type="submit" <?= $isCancelled ? 'disabled' : '' ?>>Enregistrer les modifications</button>
        </section>

        <aside class="surface-panel h-fit">
            <h2 class="font-bold text-slate-950">Articles non modifiables</h2>
            <p class="mt-1 text-sm text-slate-500">Pour préserver la traçabilité stock, les lignes de produits restent figées.</p>

            <div class="mt-5 space-y-3">
                <?php foreach ($saleDetails as $detail): ?>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <p class="text-sm font-bold text-slate-950"><?= $safe($detail['product_name'] ?? 'Produit') ?></p>
                        <div class="mt-2 flex justify-between gap-3 text-sm text-slate-600">
                            <span><?= (int) ($detail['quantite'] ?? 0) ?> x <?= $money($detail['prix_unitaire_vendu'] ?? 0) ?></span>
                            <strong><?= $money($detail['total_ligne'] ?? 0) ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </aside>
    </form>
</section>
