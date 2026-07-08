<?php

$customers = is_array($customers ?? null) ? $customers : [];
$withPhone = count(array_filter($customers, static fn (array $customer): bool => trim((string) ($customer['telephone'] ?? '')) !== ''));
$withEmail = count(array_filter($customers, static fn (array $customer): bool => trim((string) ($customer['email'] ?? '')) !== ''));
$withDebt = count(array_filter($customers, static fn (array $customer): bool => (float) ($customer['dette_actuelle'] ?? 0) > 0));
$totalDebt = array_reduce($customers, static fn (float $sum, array $customer): float => $sum + (float) ($customer['dette_actuelle'] ?? 0), 0.0);
$activeShop = is_array($activeShop ?? null) ? $activeShop : [];
$customerCurrency = in_array(($activeShop['devise_principale'] ?? 'USD'), ['USD', 'CDF'], true) ? (string) $activeShop['devise_principale'] : 'USD';
$exchangeRate = (float) (($activeShop['taux_change_cdf'] ?? 2800) ?: 2800);
$money = static function ($value) use ($customerCurrency, $exchangeRate): string {
    $amount = (float) $value;

    if ($customerCurrency === 'CDF') {
        $amount *= $exchangeRate;
    }

    return number_format($amount, 2, ',', ' ') . ' ' . $customerCurrency;
};
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$icon = static function (string $name): string {
    $paths = [
        'plus' => '<path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'search' => '<path d="m21 21-4.3-4.3M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'refresh' => '<path d="M20 12a8 8 0 0 1-14.3 4.9M4 12A8 8 0 0 1 18.3 7.1M18 4v4h-4M6 20v-4h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'user' => '<path d="M20 19c0-2.8-2.2-5-5-5H9c-2.8 0-5 2.2-5 5m8-8a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'phone' => '<path d="M6.6 4h3l1.5 4-2 1.2a11 11 0 0 0 5.7 5.7l1.2-2 4 1.5v3a2 2 0 0 1-2.2 2A16 16 0 0 1 4.6 6.2 2 2 0 0 1 6.6 4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'mail' => '<path d="M4 6h16v12H4V6Zm0 1 8 6 8-6" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'eye' => '<path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="2"/>',
        'edit' => '<path d="M4 20h4l10.5-10.5a2.8 2.8 0 0 0-4-4L4 16v4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="m13.5 6.5 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'cash' => '<path d="M4 7h16v10H4V7Zm4 5h.01M16 12h.01M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    ];

    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . ($paths[$name] ?? $paths['user']) . '</svg>';
};
?>

<section class="space-y-5" data-customers-page>
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Relation client</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Gestion des clients</h1>
            <p class="mt-2 text-xs font-semibold text-slate-500">
                Devise d'affichage: <?= htmlspecialchars($customerCurrency, ENT_QUOTES, 'UTF-8') ?> · 1 USD = <?= number_format($exchangeRate, 2, ',', ' ') ?> CDF
            </p>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Centralisez les clients de la boutique active et suivez les crédits à encaisser.
            </p>
        </div>
        <a class="btn-primary w-full gap-2 sm:w-auto" href="<?= $url('/pos') ?>">
            <?= $icon('plus') ?>
            <span>Nouvelle vente</span>
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="stat-card"><p class="text-sm text-slate-500">Clients</p><p class="mt-2 text-2xl font-bold"><?= count($customers) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Avec téléphone</p><p class="mt-2 text-2xl font-bold text-blue-700"><?= $withPhone ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Clients à crédit</p><p class="mt-2 text-2xl font-bold text-amber-700"><?= $withDebt ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Dette totale</p><p class="mt-2 text-2xl font-bold text-red-700"><?= $money($totalDebt) ?></p></article>
    </div>

    <div class="grid gap-5 xl:grid-cols-[.9fr_1.5fr]">
        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Ajouter un client</h2>
                    <p class="mt-1 text-sm text-slate-500">Informations utiles pour les ventes à crédit.</p>
                </div>
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-teal-50 text-teal-700"><?= $icon('plus') ?></span>
            </div>

            <form class="mt-5 space-y-4" method="post" action="<?= $url('/customers') ?>" accept-charset="UTF-8">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-800" for="customer_name">Nom du client</label>
                    <input class="field-control" id="customer_name" name="nom" type="text" maxlength="120" placeholder="Ex: Patient Mbuyi" required>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-800" for="customer_phone">Téléphone</label>
                        <input class="field-control" id="customer_phone" name="telephone" type="tel" maxlength="30" placeholder="+243..." inputmode="tel">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-800" for="customer_email">Email</label>
                        <input class="field-control" id="customer_email" name="email" type="email" maxlength="190" placeholder="client@email.com">
                    </div>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-800" for="customer_debt">Dette actuelle (<?= htmlspecialchars($customerCurrency, ENT_QUOTES, 'UTF-8') ?>)</label>
                    <input class="field-control" id="customer_debt" name="dette_actuelle" type="number" min="0" step="0.01" value="0">
                </div>
                <button class="btn-primary w-full gap-2" type="submit">
                    <?= $icon('plus') ?>
                    <span>Enregistrer le client</span>
                </button>
            </form>
        </section>

        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Répertoire clients</h2>
                    <p class="mt-1 text-sm text-slate-500"><span data-customers-count><?= count($customers) ?></span> client(s) affiché(s).</p>
                </div>
                <button class="btn-secondary gap-2" type="button" data-customers-reset>
                    <?= $icon('refresh') ?>
                    <span>Réinitialiser</span>
                </button>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-[1.2fr_.8fr]">
                <label class="relative block">
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"><?= $icon('search') ?></span>
                    <input class="field-control pl-11" type="search" placeholder="Rechercher par nom, téléphone ou email" data-customers-search>
                </label>
                <select class="field-control" data-customers-status>
                    <option value="all">Tous les clients</option>
                    <option value="debt">Avec crédit</option>
                    <option value="paid">Sans dette</option>
                    <option value="incomplete">Contact incomplet</option>
                </select>
            </div>

            <div class="responsive-table mt-5 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead>
                        <tr class="text-xs uppercase tracking-[.14em] text-slate-400">
                            <th class="px-4 py-3 font-semibold">Client</th>
                            <th class="px-4 py-3 font-semibold">Téléphone</th>
                            <th class="px-4 py-3 font-semibold">Email</th>
                            <th class="px-4 py-3 font-semibold">Dette</th>
                            <th class="px-4 py-3 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($customers as $customer): ?>
                            <?php
                            $phone = trim((string) ($customer['telephone'] ?? ''));
                            $email = trim((string) ($customer['email'] ?? ''));
                            $debt = (float) ($customer['dette_actuelle'] ?? 0);
                            $searchText = strtolower(trim((string) ($customer['nom'] ?? '') . ' ' . $phone . ' ' . $email));
                            ?>
                            <tr
                                class="hover:bg-slate-50"
                                data-customer-row
                                data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>"
                                data-debt="<?= $debt > 0 ? 'debt' : 'paid' ?>"
                                data-contact="<?= ($phone === '' || $email === '') ? 'incomplete' : 'complete' ?>"
                            >
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-600"><?= $icon('user') ?></span>
                                        <span>
                                            <span class="block font-semibold text-slate-950"><?= $safe($customer['nom'] ?? 'Client') ?></span>
                                            <span class="block text-xs text-slate-500">ID #<?= (int) ($customer['id'] ?? 0) ?></span>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4"><?= $phone !== '' ? '<a class="font-semibold text-teal-700 hover:text-teal-900" href="tel:' . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . '</a>' : '<span class="text-slate-400">Non renseigné</span>' ?></td>
                                <td class="px-4 py-4"><?= $email !== '' ? '<a class="font-semibold text-blue-700 hover:text-blue-900" href="mailto:' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</a>' : '<span class="text-slate-400">Non renseigné</span>' ?></td>
                                <td class="px-4 py-4 font-bold <?= $debt > 0 ? 'text-amber-700' : 'text-slate-500' ?>"><?= $money($debt) ?></td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-950 focus:outline-none focus:ring-4 focus:ring-slate-200" href="<?= $url('/customers/' . (int) $customer['id']) ?>" title="Voir le client" aria-label="Voir le client"><?= $icon('eye') ?></a>
                                        <a class="grid h-9 w-9 place-items-center rounded-lg border border-blue-100 bg-blue-50 text-blue-700 transition hover:bg-blue-100 focus:outline-none focus:ring-4 focus:ring-blue-100" href="<?= $url('/customers/' . (int) $customer['id'] . '/edit') ?>" title="Modifier le client" aria-label="Modifier le client"><?= $icon('edit') ?></a>
                                        <?php if ($debt > 0): ?>
                                            <form method="post" action="<?= $url('/customers/' . (int) $customer['id'] . '/settle-debt') ?>" data-confirm-form>
                                                <input type="hidden" name="amount" value="<?= htmlspecialchars((string) $debt, ENT_QUOTES, 'UTF-8') ?>">
                                                <button
                                                    class="inline-flex h-9 items-center gap-2 rounded-lg border border-teal-100 bg-teal-50 px-3 text-xs font-bold text-teal-700 transition hover:bg-teal-100 focus:outline-none focus:ring-4 focus:ring-teal-100"
                                                    type="button"
                                                    data-confirm
                                                    data-confirm-title="Régler la dette ?"
                                                    data-confirm-message="Cette action réglera la dette de <?= $safe($customer['nom'] ?? 'ce client') ?> pour <?= $money($debt) ?> et actualisera les factures liées."
                                                    data-confirm-accept="Régler"
                                                    data-confirm-progress="Règlement..."
                                                >
                                                    <?= $icon('cash') ?>
                                                    <span>Régler</span>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" action="<?= $url('/customers/' . (int) $customer['id'] . '/delete') ?>" data-confirm-form>
                                            <button
                                                class="grid h-9 w-9 place-items-center rounded-lg border border-red-100 bg-red-50 text-red-700 transition hover:bg-red-100 focus:outline-none focus:ring-4 focus:ring-red-100"
                                                type="button"
                                                title="Supprimer le client"
                                                aria-label="Supprimer le client"
                                                data-confirm
                                                data-confirm-title="Supprimer ce client ?"
                                                data-confirm-message="Cette action supprimera le client si aucune vente ne lui est liée."
                                                data-confirm-accept="Oui, supprimer"
                                                data-confirm-progress="Suppression..."
                                            ><?= $icon('trash') ?></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="<?= $customers === [] ? '' : 'hidden' ?> rounded-lg border border-dashed border-slate-200 p-6 text-center text-sm font-semibold text-slate-500" data-customers-empty>
                    Aucun client ne correspond aux filtres.
                </div>
            </div>
        </section>
    </div>
</section>

<script>
    (() => {
        const root = document.querySelector('[data-customers-page]');
        if (!root) return;

        const rows = [...root.querySelectorAll('[data-customer-row]')];
        const search = root.querySelector('[data-customers-search]');
        const status = root.querySelector('[data-customers-status]');
        const count = root.querySelector('[data-customers-count]');
        const empty = root.querySelector('[data-customers-empty]');
        const reset = root.querySelector('[data-customers-reset]');

        const applyFilters = () => {
            const query = (search?.value || '').trim().toLowerCase();
            const statusValue = status?.value || 'all';
            let visible = 0;

            rows.forEach((row) => {
                const matchesStatus = statusValue === 'all'
                    || row.dataset.debt === statusValue
                    || row.dataset.contact === statusValue;
                const isVisible = (query === '' || (row.dataset.search || '').includes(query)) && matchesStatus;
                row.classList.toggle('hidden', !isVisible);
                visible += isVisible ? 1 : 0;
            });

            if (count) count.textContent = String(visible);
            empty?.classList.toggle('hidden', visible !== 0);
        };

        search?.addEventListener('input', applyFilters);
        status?.addEventListener('change', applyFilters);
        reset?.addEventListener('click', () => {
            if (search) search.value = '';
            if (status) status.value = 'all';
            applyFilters();
        });
        applyFilters();
    })();
</script>
