<?php

$sales = is_array($sales ?? null) ? $sales : [];
$salesSummary = is_array($salesSummary ?? null) ? $salesSummary : [];
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
$statusLabel = static fn (string $status): string => $status === 'annulee' ? 'Annulée' : 'Validée';
$statusClass = static fn (string $status): string => $status === 'annulee' ? 'bg-red-50 text-red-700' : 'bg-teal-50 text-teal-700';
$icon = static function (string $name): string {
    $paths = [
        'plus' => '<path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'search' => '<path d="m21 21-4.3-4.3M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'filter' => '<path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'receipt' => '<path d="M7 3h10l2 2v16l-3-2-2 2-2-2-2 2-3 2V5l2-2Zm2 6h6M9 13h6M9 17h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'cash' => '<path d="M4 7h16v10H4V7Zm4 5h.01M16 12h.01M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    ];

    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . ($paths[$name] ?? $paths['receipt']) . '</svg>';
};
?>

<section class="space-y-5" data-sales-page>
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Vente</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Toutes les ventes</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Suivez les tickets, les modes de paiement, les crédits et les ventes annulées de la boutique active.
            </p>
        </div>
        <a class="btn-primary w-full gap-2 sm:w-auto" href="<?= $url('/pos') ?>">
            <?= $icon('plus') ?>
            <span>Nouvelle vente</span>
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="stat-card">
            <p class="text-sm text-slate-500">Tickets</p>
            <p class="mt-2 text-2xl font-bold"><?= (int) ($salesSummary['sales_count'] ?? 0) ?></p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Chiffre d’affaires</p>
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

        <div class="mt-5 grid gap-3 lg:grid-cols-[1.3fr_.75fr_.75fr_.75fr_.75fr]">
            <label class="relative block">
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"><?= $icon('search') ?></span>
                <input class="field-control pl-11" type="search" placeholder="Facture, client, caissier" data-sales-search>
            </label>
            <select class="field-control" data-sales-status>
                <option value="all">Tous statuts</option>
                <option value="validee">Validées</option>
                <option value="annulee">Annulées</option>
            </select>
            <select class="field-control" data-sales-payment>
                <option value="all">Tous paiements</option>
                <option value="cash">Cash</option>
                <option value="mobile_money">Mobile money</option>
                <option value="carte">Carte</option>
                <option value="virement">Virement</option>
                <option value="credit">Crédit</option>
                <option value="mixte">Mixte</option>
            </select>
            <select class="field-control" data-sales-period>
                <option value="all">Toute période</option>
                <option value="today">Aujourd’hui</option>
                <option value="week">7 derniers jours</option>
                <option value="month">30 derniers jours</option>
            </select>
            <select class="field-control" data-sales-debt>
                <option value="all">Tous montants</option>
                <option value="paid">Payées</option>
                <option value="debt">Avec crédit</option>
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

        <div class="mt-5 overflow-x-auto">
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
                            <td class="px-4 py-4 text-slate-600"><?= $safe($sale['customer_name'] ?? 'Client comptant') ?></td>
                            <td class="px-4 py-4 text-slate-600"><?= $safe($sale['user_name'] ?? '-') ?></td>
                            <td class="px-4 py-4 font-semibold"><?= (int) ($sale['articles_count'] ?? 0) ?> article(s)</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600"><?= $modeLabel($payment) ?></span>
                            </td>
                            <td class="px-4 py-4 font-bold text-slate-950"><?= $money($sale['total_montant'] ?? 0) ?></td>
                            <td class="px-4 py-4 font-semibold text-teal-700"><?= $money($sale['montant_recu'] ?? 0) ?></td>
                            <td class="px-4 py-4 font-semibold <?= $debt > 0 ? 'text-amber-700' : 'text-slate-500' ?>"><?= $money($debt) ?></td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold <?= $statusClass($status) ?>"><?= $statusLabel($status) ?></span>
                            </td>
                            <td class="px-4 py-4 text-slate-600"><?= $safe($dateLabel($sale['date_vente'] ?? null)) ?></td>
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
        const debt = root.querySelector('[data-sales-debt]');
        const count = root.querySelector('[data-sales-count]');
        const empty = root.querySelector('[data-sales-empty]');
        const reset = root.querySelector('[data-sales-reset]');
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

        const applyFilters = () => {
            const query = (search?.value || '').trim().toLowerCase();
            const statusValue = status?.value || 'all';
            const paymentValue = payment?.value || 'all';
            const periodValue = period?.value || 'all';
            const debtValue = debt?.value || 'all';
            let visible = 0;

            rows.forEach((row) => {
                const timestamp = Number(row.dataset.date || 0);
                const isVisible =
                    (query === '' || (row.dataset.search || '').includes(query)) &&
                    (statusValue === 'all' || row.dataset.status === statusValue) &&
                    (paymentValue === 'all' || row.dataset.payment === paymentValue) &&
                    (debtValue === 'all' || row.dataset.debt === debtValue) &&
                    matchesPeriod(timestamp, periodValue);

                row.classList.toggle('hidden', !isVisible);
                visible += isVisible ? 1 : 0;
            });

            if (count) {
                count.textContent = String(visible);
            }

            empty?.classList.toggle('hidden', visible !== 0);
        };

        [search, status, payment, period, debt].forEach((control) => {
            control?.addEventListener('input', applyFilters);
            control?.addEventListener('change', applyFilters);
        });

        reset?.addEventListener('click', () => {
            if (search) search.value = '';
            if (status) status.value = 'all';
            if (payment) payment.value = 'all';
            if (period) period.value = 'all';
            if (debt) debt.value = 'all';
            applyFilters();
        });

        applyFilters();
    })();
</script>
