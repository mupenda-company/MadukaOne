
<?php
$products = is_array($products ?? null) ? $products : [];
$customers = is_array($customers ?? null) ? $customers : [];
$latestSales = is_array($latestSales ?? null) ? $latestSales : [];
$activeShop = is_array($activeShop ?? null) ? $activeShop : [];
$posCurrency = in_array(($activeShop['devise_principale'] ?? 'USD'), ['USD', 'CDF'], true) ? (string) $activeShop['devise_principale'] : 'USD';
$exchangeRate = (float) (($activeShop['taux_change_cdf'] ?? 2800) ?: 2800);
$money = static function ($value) use ($posCurrency, $exchangeRate): string {
    $amount = (float) $value;

    if ($posCurrency === 'CDF') {
        $amount *= $exchangeRate;
    }

    return number_format($amount, 2, ',', ' ') . ' ' . $posCurrency;
};
$moneyDual = static function ($value) use ($posCurrency, $exchangeRate): array {
    $amount = (float) $value;
    $usd = number_format($amount, 2, ',', ' ') . ' USD';
    $cdf = number_format($amount * $exchangeRate, 2, ',', ' ') . ' CDF';

    return $posCurrency === 'CDF'
        ? ['primary' => $cdf, 'secondary' => $usd]
        : ['primary' => $usd, 'secondary' => $cdf];
};
$zeroTotal = $moneyDual(0);
$exchangeRateLabel = '1 USD = ' . number_format($exchangeRate, 2, ',', ' ') . ' CDF';
$today = new DateTimeImmutable('today');
$expirationLimit = $today->modify('+30 days');
$parseDate = static function ($value): ?DateTimeImmutable {
    $date = trim((string) ($value ?? ''));
    if ($date === '') {
        return null;
    }

    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', substr($date, 0, 10));

    return $parsed instanceof DateTimeImmutable ? $parsed : null;
};
$dateLabel = static fn (DateTimeImmutable $date): string => $date->format('d/m/Y');
?>

<section
    class="space-y-5"
    data-pos-root
    data-pos-endpoint="<?= $url('/pos/sale') ?>"
    data-pos-customer-endpoint="<?= $url('/pos/customer') ?>"
    data-pos-currency="<?= htmlspecialchars($posCurrency, ENT_QUOTES, 'UTF-8') ?>"
    data-pos-exchange-rate="<?= htmlspecialchars((string) $exchangeRate, ENT_QUOTES, 'UTF-8') ?>"
>
    <script type="application/json" data-pos-customers-json><?= json_encode($customers, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
    <div class="dashboard-hero items-stretch lg:items-center">
        <div class="min-w-0 flex-1">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Caisse</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Vente POS</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Interface rapide pour telephone et comptoir : selectionnez, controlez le panier et encaissez.
            </p>
        </div>
        <div class="grid w-full shrink-0 gap-3 sm:grid-cols-2 lg:w-[30rem] lg:max-w-none">
            <div class="hero-action-panel lg:max-w-none">
                <p class="text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Total panier</p>
                <p class="mt-2 break-words text-3xl font-bold leading-tight text-slate-950" data-pos-total><?= htmlspecialchars($zeroTotal['primary'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mt-1 text-sm font-semibold text-slate-500" data-pos-total-secondary><?= htmlspecialchars($zeroTotal['secondary'], ENT_QUOTES, 'UTF-8') ?></p>
                <a class="mt-4 inline-flex text-sm font-bold text-teal-700 hover:text-teal-900" href="<?= $url('/sales') ?>">Voir les ventes</a>
            </div>
            <div class="hero-action-panel lg:max-w-none">
                <p class="text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Taux de change</p>
                <p class="mt-2 text-lg font-black leading-tight text-slate-950"><?= htmlspecialchars($exchangeRateLabel, ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mt-2 text-xs font-semibold leading-5 text-slate-500">Utilise pour les conversions du panier et des produits.</p>
            </div>
        </div>
    </div>

    <div class="grid min-w-0 items-start gap-5 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <section class="surface-panel min-w-0">
            <div class="panel-header flex-col sm:flex-row sm:items-center">
                <div class="min-w-0">
                    <h2 class="font-bold text-slate-950">Produits disponibles</h2>
                    <p class="mt-1 text-sm text-slate-500">Touchez un article pour l'ajouter au panier.</p>
                </div>
                <input class="field-control w-full sm:max-w-xs" type="search" placeholder="Chercher un article" data-pos-search>
            </div>

            <div class="mt-5 grid items-stretch gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($products as $product): ?>
                    <?php
                    $stock = (int) ($product['quantite_stock'] ?? 0);
                    $minStock = (int) ($product['alerte_stock_min'] ?? 0);
                    $expiresAt = $parseDate($product['date_expiration'] ?? null);
                    $isOutOfStock = $stock <= 0;
                    $isLowStock = !$isOutOfStock && $minStock > 0 && $stock <= $minStock;
                    $isExpired = $expiresAt instanceof DateTimeImmutable && $expiresAt < $today;
                    $isNearExpiration = !$isExpired && $expiresAt instanceof DateTimeImmutable && $expiresAt <= $expirationLimit;
                    $price = $moneyDual($product['prix_vente'] ?? 0);
                    ?>
                    <button
                        class="flex h-full min-h-[9.5rem] flex-col rounded-xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md"
                        type="button"
                        data-pos-product
                        data-id="<?= (int) $product['id'] ?>"
                        data-name="<?= htmlspecialchars((string) $product['nom'], ENT_QUOTES, 'UTF-8') ?>"
                        data-ref="<?= htmlspecialchars((string) $product['ref'], ENT_QUOTES, 'UTF-8') ?>"
                        data-price="<?= (float) $product['prix_vente'] ?>"
                        data-stock="<?= $stock ?>"
                        data-alert-stock-min="<?= $minStock ?>"
                        data-expiration-date="<?= htmlspecialchars((string) ($product['date_expiration'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    >
                        <span class="block text-xs font-semibold uppercase tracking-[.14em] text-slate-400"><?= htmlspecialchars((string) $product['ref'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="mt-2 block min-h-6 font-bold text-slate-950"><?= htmlspecialchars((string) $product['nom'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="mt-auto flex items-end justify-between gap-3 pt-4">
                            <span class="min-w-0">
                                <span class="block break-words text-xl font-bold leading-tight text-teal-700"><?= htmlspecialchars($price['primary'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="mt-1 block text-xs font-semibold text-slate-500"><?= htmlspecialchars($price['secondary'], ENT_QUOTES, 'UTF-8') ?></span>
                            </span>
                            <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Stock <?= $stock ?></span>
                        </span>
                        <?php if ($isOutOfStock || $isLowStock || $isExpired || $isNearExpiration): ?>
                            <span class="mt-3 flex flex-wrap gap-2">
                                <?php if ($isOutOfStock): ?>
                                    <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-700">Rupture de stock</span>
                                <?php elseif ($isLowStock): ?>
                                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">Stock bas: seuil <?= $minStock ?></span>
                                <?php endif; ?>
                                <?php if ($isExpired && $expiresAt instanceof DateTimeImmutable): ?>
                                    <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-700">Date expiree le <?= htmlspecialchars($dateLabel($expiresAt), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php elseif ($isNearExpiration && $expiresAt instanceof DateTimeImmutable): ?>
                                    <span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-bold text-orange-700">Expire le <?= htmlspecialchars($dateLabel($expiresAt), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </section>

        <aside class="surface-panel h-fit min-w-0 xl:sticky xl:top-20">
            <div class="panel-header items-center">
                <div class="min-w-0">
                    <h2 class="font-bold text-slate-950">Panier</h2>
                    <p class="mt-1 text-sm text-slate-500" data-pos-count>0 article</p>
                </div>
                <button class="btn-secondary" type="button" data-pos-clear>Vider</button>
            </div>

            <div class="mt-5 max-h-[26rem] space-y-3 overflow-y-auto pr-1" data-pos-cart>
                <div class="rounded-lg border border-dashed border-slate-200 p-5 text-center text-sm text-slate-500" data-pos-empty>
                    Aucun article dans le panier.
                </div>
            </div>

            <div class="mt-5 space-y-3 border-t border-slate-200 pt-5">
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Client</span>
                    <input class="field-control" type="search" data-pos-customer-search placeholder="Rechercher un client">
                    <input type="hidden" data-pos-customer-id>
                    <div class="mt-2 hidden overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm" data-pos-customer-results></div>
                    <div class="mt-2 hidden rounded-lg border border-teal-100 bg-teal-50 p-3" data-pos-customer-selected></div>
                    <div class="mt-2 hidden rounded-lg border border-dashed border-slate-200 bg-slate-50 p-3" data-pos-customer-empty>
                        <p class="text-sm font-semibold text-slate-700">Aucun client trouve.</p>
                        <button class="mt-2 btn-secondary h-9 w-full" type="button" data-pos-customer-add-toggle>Ajouter ce client</button>
                    </div>
                    <div class="mt-2 hidden space-y-2 rounded-lg border border-slate-200 bg-white p-3" data-pos-customer-form>
                        <input class="field-control" type="text" data-pos-customer-name placeholder="Nom du client">
                        <input class="field-control" type="tel" data-pos-customer-phone placeholder="Telephone">
                        <div class="grid gap-2 sm:grid-cols-2">
                            <button class="btn-secondary h-10" type="button" data-pos-customer-cancel>Annuler</button>
                            <button class="btn-primary h-10" type="button" data-pos-customer-save>Enregistrer</button>
                        </div>
                    </div>
                </label>
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Mode de paiement</span>
                    <select class="field-control" data-pos-payment>
                        <option value="cash">Cash</option>
                        <option value="mobile_money">Mobile money</option>
                        <option value="carte">Carte</option>
                        <option value="credit">Credit</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Devise et montant recus</span>
                    <div class="grid gap-3 sm:grid-cols-[.75fr_1.25fr]">
                        <select class="field-control" data-pos-received-currency aria-label="Devise recue">
                            <option value="USD" <?= $posCurrency === 'USD' ? 'selected' : '' ?>>USD</option>
                            <option value="CDF" <?= $posCurrency === 'CDF' ? 'selected' : '' ?>>CDF</option>
                        </select>
                        <input class="field-control" type="number" min="0" step="0.01" data-pos-received placeholder="0.00">
                    </div>
                </label>
                <div class="signal-row">
                    <span class="text-slate-500">Reste / monnaie</span>
                    <strong data-pos-change><?= $money(0) ?></strong>
                </div>
                <p class="hidden rounded-lg bg-red-50 px-4 py-3 text-sm font-semibold text-red-700" data-pos-message></p>
                <button class="btn-primary" type="button" data-pos-submit>Valider la vente</button>
            </div>

            <div class="mt-5 border-t border-slate-200 pt-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-950">10 derni&egrave;res ventes valid&eacute;es</h3>
                        <p class="mt-1 text-xs text-slate-500">Mise &agrave; jour apr&egrave;s chaque validation.</p>
                    </div>
                    <a class="text-xs font-bold text-teal-700 hover:text-teal-900" href="<?= $url('/sales') ?>">Tout voir</a>
                </div>

                <div class="mt-4 space-y-2" data-pos-latest-sales>
                    <?php if ($latestSales === []): ?>
                        <div class="rounded-lg border border-dashed border-slate-200 p-4 text-center text-sm text-slate-500" data-pos-latest-empty>
                            Aucune vente valid&eacute;e pour le moment.
                        </div>
                    <?php endif; ?>

                    <?php foreach ($latestSales as $sale): ?>
                        <?php
                        $saleDate = (string) ($sale['date_vente'] ?? '');
                        $displayDate = $saleDate;
                        if ($saleDate !== '') {
                            try {
                                $displayDate = (new DateTime($saleDate))->format('d/m/Y H:i');
                            } catch (Throwable) {
                                $displayDate = $saleDate;
                            }
                        }
                        ?>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3" data-pos-latest-row>
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-950"><?= htmlspecialchars((string) ($sale['numero_facture'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="mt-1 truncate text-xs text-slate-500">
                                        <?= htmlspecialchars((string) ($sale['customer_name'] ?? 'Client comptant'), ENT_QUOTES, 'UTF-8') ?>
                                        &middot;
                                        <?= htmlspecialchars($displayDate, ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>
                                <span class="shrink-0 rounded-full bg-teal-50 px-2 py-1 text-xs font-bold text-teal-700">Valid&eacute;e</span>
                            </div>
                            <div class="mt-3 flex items-center justify-between gap-3 text-xs text-slate-500">
                                <span><?= (int) ($sale['articles_count'] ?? 0) ?> article(s)</span>
                                <strong class="text-sm text-slate-950"><?= $money($sale['total_montant'] ?? 0) ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </div>

    <div class="fixed inset-0 z-[75] hidden items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm" data-pos-confirm-modal role="dialog" aria-modal="true" aria-labelledby="pos-confirm-title">
        <div class="w-full max-w-2xl rounded-xl border border-white/80 bg-white p-5 shadow-2xl shadow-slate-950/20 outline-none sm:p-6" data-pos-confirm-panel tabindex="-1">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Confirmation</p>
                    <h2 class="text-xl font-bold tracking-normal text-slate-950" id="pos-confirm-title">Confirmer la vente</h2>
                    <p class="mt-1 text-sm text-slate-500">Verifiez les informations avant l'enregistrement definitif.</p>
                </div>
                <button class="icon-btn" type="button" data-pos-confirm-close aria-label="Fermer">x</button>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Client</p>
                    <p class="mt-1 font-bold text-slate-950" data-pos-confirm-client>Client comptant</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Paiement</p>
                    <p class="mt-1 font-bold text-slate-950" data-pos-confirm-payment>Cash</p>
                </div>
            </div>

            <div class="mt-5 max-h-64 overflow-y-auto rounded-lg border border-slate-200">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-950 text-xs uppercase tracking-[.12em] text-white">
                        <tr>
                            <th class="px-3 py-3 font-semibold">Article</th>
                            <th class="px-3 py-3 text-right font-semibold">Qte</th>
                            <th class="px-3 py-3 text-right font-semibold">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" data-pos-confirm-items></tbody>
                </table>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                <div class="signal-row">
                    <span class="text-slate-500">Total</span>
                    <strong data-pos-confirm-total><?= $money(0) ?></strong>
                </div>
                <div class="signal-row">
                    <span class="text-slate-500">Recu</span>
                    <strong data-pos-confirm-received><?= $money(0) ?></strong>
                </div>
                <div class="signal-row">
                    <span class="text-slate-500">Reste / monnaie</span>
                    <strong data-pos-confirm-change><?= $money(0) ?></strong>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button class="btn-secondary w-full sm:w-auto" type="button" data-pos-confirm-cancel>Annuler</button>
                <button class="btn-primary w-full sm:w-auto" type="button" data-pos-confirm-accept>Confirmer la vente</button>
            </div>
        </div>
    </div>
</section>
