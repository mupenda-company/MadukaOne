<?php

$sales = is_array($sales ?? null) ? $sales : [];
$salesSummary = is_array($salesSummary ?? null) ? $salesSummary : [];
$salesFilters = is_array($salesFilters ?? null) ? $salesFilters : [];
$activeShop = is_array($activeShop ?? null) ? $activeShop : [];
$salesCurrency = in_array(($activeShop['devise_principale'] ?? 'USD'), ['USD', 'CDF'], true) ? (string) $activeShop['devise_principale'] : 'USD';
$exchangeRate = (float) (($activeShop['taux_change_cdf'] ?? 2800) ?: 2800);
$money = static function ($value) use ($salesCurrency, $exchangeRate): string {
    $amount = (float) $value;

    if ($salesCurrency === 'CDF') {
        $amount *= $exchangeRate;
    }

    return number_format($amount, 2, ',', ' ') . ' ' . $salesCurrency;
};
$moneyExact = static function ($usdValue, $enteredValue = null, $currency = null) use ($exchangeRate): string {
    $currency = in_array(($currency ?? 'USD'), ['USD', 'CDF'], true) ? (string) $currency : 'USD';
    $enteredAmount = (float) ($enteredValue ?? 0);
    $usdAmount = (float) $usdValue;

    if ($enteredAmount <= 0) {
        $enteredAmount = $currency === 'CDF' ? $usdAmount * $exchangeRate : $usdAmount;
    }

    $primary = $currency === 'CDF'
        ? number_format($enteredAmount, 0, ',', ' ') . ' CDF'
        : number_format($enteredAmount, 2, ',', ' ') . ' USD';
    $secondary = $currency === 'CDF'
        ? number_format($usdAmount, 2, ',', ' ') . ' USD'
        : number_format($enteredAmount * $exchangeRate, 0, ',', ' ') . ' CDF';

    return $primary . ' <span class="block text-xs font-semibold text-slate-500">(' . $secondary . ')</span>';
};
$moneyDebtExact = static function (array $sale) use ($moneyExact, $money): string {
    $debtUsd = (float) ($sale['montant_dette'] ?? 0);
    $saleCurrency = in_array(($sale['devise_saisie'] ?? 'USD'), ['USD', 'CDF'], true) ? (string) $sale['devise_saisie'] : 'USD';
    $receivedCurrency = in_array(($sale['devise_recu'] ?? 'USD'), ['USD', 'CDF'], true) ? (string) $sale['devise_recu'] : 'USD';

    if ($saleCurrency === $receivedCurrency && (float) ($sale['total_montant_saisi'] ?? 0) > 0) {
        $debtEntered = max(0.0, (float) ($sale['total_montant_saisi'] ?? 0) - (float) ($sale['montant_recu_saisi'] ?? 0));

        return $moneyExact($debtUsd, $debtEntered, $saleCurrency);
    }

    return $money($debtUsd);
};
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
$statusLabel = static fn (string $status): string => $status === 'annulee' ? 'Annulée' : 'Validée';
$statusClass = static fn (string $status): string => $status === 'annulee' ? 'bg-red-50 text-red-700' : 'bg-teal-50 text-teal-700';
$filterValue = static fn (string $key, string $fallback = 'all'): string => (string) ($salesFilters[$key] ?? $fallback);
$selected = static fn (string $key, string $value): string => $filterValue($key) === $value ? 'selected' : '';
$dateStartValue = (string) ($salesFilters['date_debut'] ?? '');
$dateEndValue = (string) ($salesFilters['date_fin'] ?? '');
$exportFilters = array_filter([
    'search' => trim((string) ($salesFilters['search'] ?? '')),
    'status' => $filterValue('status') !== 'all' ? $filterValue('status') : null,
    'payment' => $filterValue('payment') !== 'all' ? $filterValue('payment') : null,
    'period' => $filterValue('period') !== 'all' ? $filterValue('period') : null,
    'debt' => $filterValue('debt') !== 'all' ? $filterValue('debt') : null,
    'date_debut' => $dateStartValue !== '' ? $dateStartValue : null,
    'date_fin' => $dateEndValue !== '' ? $dateEndValue : null,
], static fn ($value): bool => $value !== null && $value !== '');
$icon = static function (string $name): string {
    $paths = [
        'plus' => '<path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'search' => '<path d="m21 21-4.3-4.3M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'filter' => '<path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'receipt' => '<path d="M7 3h10l2 2v16l-3-2-2 2-2-2-2 2-3 2V5l2-2Zm2 6h6M9 13h6M9 17h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'cash' => '<path d="M4 7h16v10H4V7Zm4 5h.01M16 12h.01M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'eye' => '<path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="2"/>',
        'edit' => '<path d="M4 20h4l10.5-10.5a2.1 2.1 0 0 0-3-3L5 17v3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="m14 7 3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 14h10l1-14M9 7V4h6v3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'print' => '<path d="M7 8V4h10v4M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2M7 14h10v7H7v-7Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
    ];

    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . ($paths[$name] ?? $paths['receipt']) . '</svg>';
};
?>

<section class="space-y-5" data-sales-page>
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Vente</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Toutes les ventes</h1>
            <p class="mt-2 text-xs font-semibold text-slate-500">
                Devise d'affichage: <?= htmlspecialchars($salesCurrency, ENT_QUOTES, 'UTF-8') ?> · 1 USD = <?= number_format($exchangeRate, 2, ',', ' ') ?> CDF
            </p>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Suivez les tickets, les modes de paiement, les crédits et les ventes annulées de la boutique active.
            </p>
        </div>
        <div class="grid w-full grid-cols-1 gap-2 sm:grid-cols-2 lg:w-[28rem]">
            <a class="btn-secondary w-full px-4" href="<?= $url('/sales', $exportFilters + ['export_preview' => 'xlsx']) ?>" data-sales-export-link data-export-format="xlsx">Prévisualiser Excel</a>
            <a class="btn-secondary w-full px-4" href="<?= $url('/sales', $exportFilters + ['export_preview' => 'pdf']) ?>" data-sales-export-link data-export-format="pdf">Prévisualiser PDF</a>
            <a class="btn-primary w-full gap-2 px-4 sm:col-span-2" href="<?= $url('/pos') ?>">
                <?= $icon('plus') ?>
                <span>Nouvelle vente</span>
            </a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="stat-card">
            <p class="text-sm text-slate-500">Tickets</p>
            <p class="mt-2 text-2xl font-bold"><?= (int) ($salesSummary['sales_count'] ?? 0) ?></p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Chiffre d'affaires</p>
            <p class="mt-2 text-2xl font-bold text-teal-700"><?= $money($salesSummary['revenue'] ?? 0) ?></p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Crédits clients</p>
            <p class="mt-2 text-2xl font-bold text-amber-700"><?= $money($salesSummary['debt'] ?? 0) ?></p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Ventes annulées</p>
            <p class="mt-2 text-2xl font-bold text-red-700"><?= (int) ($salesSummary['cancelled_count'] ?? 0) ?></p>
        </article>
    </div>

    <section class="surface-panel">
        <div class="panel-header">
            <div>
                <h2 class="font-bold text-slate-950">Filtres des ventes</h2>
                <p class="mt-1 text-sm text-slate-500">Recherche et filtres visuels sans rechargement.</p>
            </div>
            <button class="btn-secondary gap-2" type="button" data-sales-reset>
                <?= $icon('filter') ?>
                <span>Réinitialiser</span>
            </button>
        </div>

        <div class="mt-5 grid gap-3 lg:grid-cols-[1.2fr_.75fr_.75fr_.7fr_.7fr_.7fr_.7fr]">
            <label class="relative block">
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"><?= $icon('search') ?></span>
                <input class="field-control pl-11" type="search" placeholder="Facture, client, caissier" value="<?= $safe($salesFilters['search'] ?? '') ?>" data-sales-search>
            </label>
            <select class="field-control" data-sales-status>
                <option value="all" <?= $selected('status', 'all') ?>>Tous statuts</option>
                <option value="validee" <?= $selected('status', 'validee') ?>>Validées</option>
                <option value="annulee" <?= $selected('status', 'annulee') ?>>Annulées</option>
            </select>
            <select class="field-control" data-sales-payment>
                <option value="all" <?= $selected('payment', 'all') ?>>Tous paiements</option>
                <option value="cash" <?= $selected('payment', 'cash') ?>>Cash</option>
                <option value="mobile_money" <?= $selected('payment', 'mobile_money') ?>>Mobile money</option>
                <option value="carte" <?= $selected('payment', 'carte') ?>>Carte</option>
                <option value="virement" <?= $selected('payment', 'virement') ?>>Virement</option>
                <option value="credit" <?= $selected('payment', 'credit') ?>>Crédit</option>
                <option value="mixte" <?= $selected('payment', 'mixte') ?>>Mixte</option>
            </select>
            <select class="field-control" data-sales-period>
                <option value="all" <?= $selected('period', 'all') ?>>Toute période</option>
                <option value="today" <?= $selected('period', 'today') ?>>Aujourd'hui</option>
                <option value="week" <?= $selected('period', 'week') ?>>7 derniers jours</option>
                <option value="month" <?= $selected('period', 'month') ?>>30 derniers jours</option>
            </select>
            <input class="field-control" type="date" value="<?= $safe($dateStartValue, '') ?>" data-sales-date-start aria-label="Date début">
            <input class="field-control" type="date" value="<?= $safe($dateEndValue, '') ?>" data-sales-date-end aria-label="Date fin">
            <select class="field-control" data-sales-debt>
                <option value="all" <?= $selected('debt', 'all') ?>>Tous montants</option>
                <option value="paid" <?= $selected('debt', 'paid') ?>>Payées</option>
                <option value="debt" <?= $selected('debt', 'debt') ?>>Avec crédit</option>
            </select>
        </div>
    </section>

    <section class="surface-panel">
        <div class="panel-header">
            <div>
                <h2 class="font-bold text-slate-950">Historique des ventes</h2>
                <p class="mt-1 text-sm text-slate-500"><span data-sales-count><?= count($sales) ?></span> vente(s) affichée(s).</p>
            </div>
            <span class="grid h-10 w-10 place-items-center rounded-lg bg-teal-50 text-teal-700"><?= $icon('receipt') ?></span>
        </div>

        <div class="responsive-table mt-5 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead>
                    <tr class="text-xs uppercase tracking-[.14em] text-slate-400">
                        <th class="px-4 py-3 font-semibold">Facture</th>
                        <th class="px-4 py-3 font-semibold">Client</th>
                        <th class="px-4 py-3 font-semibold">Caissier</th>
                        <th class="px-4 py-3 font-semibold">Articles</th>
                        <th class="px-4 py-3 font-semibold">Paiement</th>
                        <th class="px-4 py-3 font-semibold">Total</th>
                        <th class="px-4 py-3 font-semibold">Reçu</th>
                        <th class="px-4 py-3 font-semibold">Crédit</th>
                        <th class="px-4 py-3 font-semibold">Statut</th>
                        <th class="px-4 py-3 font-semibold">Date</th>
                        <th class="px-4 py-3 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" data-sales-table>
                    <?php foreach ($sales as $sale): ?>
                        <?php
                        $status = (string) ($sale['statut'] ?? 'validee');
                        $payment = (string) ($sale['mode_paiement'] ?? 'cash');
                        $debt = (float) ($sale['montant_dette'] ?? 0);
                        $timestamp = strtotime((string) ($sale['date_vente'] ?? ''));
                        $searchText = strtolower(trim((string) ($sale['numero_facture'] ?? '') . ' ' . (string) ($sale['customer_name'] ?? '') . ' ' . (string) ($sale['user_name'] ?? '')));
                        ?>
                        <tr
                            class="hover:bg-slate-50"
                            data-sale-row
                            data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>"
                            data-status="<?= $safe($status) ?>"
                            data-payment="<?= $safe($payment) ?>"
                            data-debt="<?= $debt > 0 ? 'debt' : 'paid' ?>"
                            data-date="<?= $timestamp !== false ? $timestamp : 0 ?>"
                        >
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-600"><?= $icon('receipt') ?></span>
                                    <span>
                                        <span class="block font-bold text-slate-950"><?= $safe($sale['numero_facture'] ?? '-') ?></span>
                                        <span class="block text-xs text-slate-500">#<?= (int) ($sale['id'] ?? 0) ?></span>
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-slate-600" data-label="Client"><?= $safe($sale['customer_name'] ?? 'Client comptant') ?></td>
                            <td class="px-4 py-4 text-slate-600" data-label="Caissier"><?= $safe($sale['user_name'] ?? '-') ?></td>
                            <td class="px-4 py-4 font-semibold" data-label="Articles"><?= (int) ($sale['articles_count'] ?? 0) ?> article(s)</td>
                            <td class="px-4 py-4" data-label="Paiement">
                                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600"><?= $modeLabel($payment) ?></span>
                            </td>
                            <td class="px-4 py-4 font-bold text-slate-950" data-label="Total"><?= $moneyExact($sale['total_montant'] ?? 0, $sale['total_montant_saisi'] ?? null, $sale['devise_saisie'] ?? null) ?></td>
                            <td class="px-4 py-4 font-semibold text-teal-700" data-label="Reçu"><?= $moneyExact($sale['montant_recu'] ?? 0, $sale['montant_recu_saisi'] ?? null, $sale['devise_recu'] ?? null) ?></td>
                            <td class="px-4 py-4 font-semibold <?= $debt > 0 ? 'text-amber-700' : 'text-slate-500' ?>" data-label="Crédit"><?= $moneyDebtExact($sale) ?></td>
                            <td class="px-4 py-4" data-label="Statut">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold <?= $statusClass($status) ?>"><?= $statusLabel($status) ?></span>
                            </td>
                            <td class="px-4 py-4 text-slate-600" data-label="Date"><?= $safe($dateLabel($sale['date_vente'] ?? null)) ?></td>
                            <td class="px-4 py-4" data-label="Actions">
                                <div class="flex justify-end gap-2">
                                    <a class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 text-slate-600 transition hover:border-teal-200 hover:bg-teal-50 hover:text-teal-700" href="<?= $url('/sales/' . (int) ($sale['id'] ?? 0)) ?>" title="Voir la vente" aria-label="Voir la vente">
                                        <?= $icon('eye') ?>
                                    </a>
                                    <?php if ($status !== 'annulee'): ?>
                                        <a class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700" href="<?= $url('/sales/' . (int) ($sale['id'] ?? 0) . '/edit') ?>" title="Modifier" aria-label="Modifier">
                                            <?= $icon('edit') ?>
                                        </a>
                                    <?php endif; ?>
                                    <a class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 text-slate-600 transition hover:border-slate-300 hover:bg-slate-100 hover:text-slate-900" href="<?= $url('/sales/' . (int) ($sale['id'] ?? 0) . '/invoice') ?>" target="_blank" title="Imprimer la facture" aria-label="Imprimer la facture">
                                        <?= $icon('print') ?>
                                    </a>
                                    <?php if ($status !== 'annulee'): ?>
                                        <form method="post" action="<?= $url('/sales/' . (int) ($sale['id'] ?? 0) . '/cancel') ?>" data-confirm-form>
                                            <input type="hidden" name="reason" value="Vente annulée depuis l'historique des ventes">
                                            <button class="inline-flex h-9 items-center gap-2 rounded-lg border border-red-200 px-3 text-xs font-bold text-red-700 transition hover:bg-red-50" type="button" title="Annuler la vente" aria-label="Annuler la vente" data-confirm data-confirm-title="Annuler cette vente ?" data-confirm-message="Les articles seront automatiquement retournés au stock. La vente restera visible avec le statut Annulée." data-confirm-accept="Oui, annuler la vente" data-confirm-progress="Annulation et retour du stock...">
                                                <?= $icon('trash') ?> <span>Annuler</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="<?= $sales === [] ? '' : 'hidden' ?> rounded-lg border border-dashed border-slate-200 p-6 text-center text-sm font-semibold text-slate-500" data-sales-empty>
                Aucune vente ne correspond aux filtres.
            </div>
        </div>
    </section>
</section>

<script>
    (() => {
        const root = document.querySelector('[data-sales-page]');

        if (!root) {
            return;
        }

        const rows = [...root.querySelectorAll('[data-sale-row]')];
        const search = root.querySelector('[data-sales-search]');
        const status = root.querySelector('[data-sales-status]');
        const payment = root.querySelector('[data-sales-payment]');
        const period = root.querySelector('[data-sales-period]');
        const dateStart = root.querySelector('[data-sales-date-start]');
        const dateEnd = root.querySelector('[data-sales-date-end]');
        const debt = root.querySelector('[data-sales-debt]');
        const count = root.querySelector('[data-sales-count]');
        const empty = root.querySelector('[data-sales-empty]');
        const reset = root.querySelector('[data-sales-reset]');
        const exportLinks = [...root.querySelectorAll('[data-sales-export-link]')];
        const day = 24 * 60 * 60;

        const matchesPeriod = (timestamp, value) => {
            if (value === 'all') {
                return true;
            }

            const now = Math.floor(Date.now() / 1000);
            const date = new Date(timestamp * 1000);
            const today = new Date();

            if (value === 'today') {
                return date.toDateString() === today.toDateString();
            }

            if (value === 'week') {
                return timestamp >= now - (7 * day);
            }

            if (value === 'month') {
                return timestamp >= now - (30 * day);
            }

            return true;
        };

        const currentFilters = () => ({
            search: (search?.value || '').trim(),
            status: status?.value || 'all',
            payment: payment?.value || 'all',
            period: period?.value || 'all',
            date_debut: dateStart?.value || '',
            date_fin: dateEnd?.value || '',
            debt: debt?.value || 'all',
        });

        const updateExportLinks = () => {
            const filters = currentFilters();

            exportLinks.forEach((link) => {
                const nextUrl = new URL(link.getAttribute('href'), window.location.href);
                const format = link.dataset.exportFormat || nextUrl.searchParams.get('export_preview') || 'pdf';
                nextUrl.search = '';
                nextUrl.searchParams.set('export_preview', format);

                Object.entries(filters).forEach(([key, value]) => {
                    if (value && value !== 'all') {
                        nextUrl.searchParams.set(key, value);
                    }
                });

                link.setAttribute('href', nextUrl.pathname + nextUrl.search);
            });
        };

        const applyFilters = () => {
            const filters = currentFilters();
            const query = filters.search.toLowerCase();
            const statusValue = filters.status;
            const paymentValue = filters.payment;
            const periodValue = filters.period;
            const startValue = filters.date_debut ? Math.floor(new Date(`${filters.date_debut}T00:00:00`).getTime() / 1000) : null;
            const endValue = filters.date_fin ? Math.floor(new Date(`${filters.date_fin}T23:59:59`).getTime() / 1000) : null;
            const debtValue = filters.debt;
            let visible = 0;

            rows.forEach((row) => {
                const timestamp = Number(row.dataset.date || 0);
                const isVisible =
                    (query === '' || (row.dataset.search || '').includes(query)) &&
                    (statusValue === 'all' || row.dataset.status === statusValue) &&
                    (paymentValue === 'all' || row.dataset.payment === paymentValue) &&
                    (debtValue === 'all' || row.dataset.debt === debtValue) &&
                    (startValue === null || timestamp >= startValue) &&
                    (endValue === null || timestamp <= endValue) &&
                    ((startValue !== null || endValue !== null) || matchesPeriod(timestamp, periodValue));

                row.classList.toggle('hidden', !isVisible);
                visible += isVisible ? 1 : 0;
            });

            if (count) {
                count.textContent = String(visible);
            }

            empty?.classList.toggle('hidden', visible !== 0);
            updateExportLinks();
        };

        [search, status, payment, period, dateStart, dateEnd, debt].forEach((control) => {
            control?.addEventListener('input', applyFilters);
            control?.addEventListener('change', applyFilters);
        });

        reset?.addEventListener('click', () => {
            if (search) search.value = '';
            if (status) status.value = 'all';
            if (payment) payment.value = 'all';
            if (period) period.value = 'all';
            if (dateStart) dateStart.value = '';
            if (dateEnd) dateEnd.value = '';
            if (debt) debt.value = 'all';
            applyFilters();
        });

        applyFilters();
    })();
</script>
