<?php

$suppliers = is_array($suppliers ?? null) ? $suppliers : [];
$withPhone = count(array_filter($suppliers, static fn (array $supplier): bool => trim((string) ($supplier['telephone'] ?? '')) !== ''));
$withEmail = count(array_filter($suppliers, static fn (array $supplier): bool => trim((string) ($supplier['email'] ?? '')) !== ''));

$icon = static function (string $name): string {
    $paths = [
        'plus' => '<path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'search' => '<path d="m21 21-4.3-4.3M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'truck' => '<path d="M3 7h11v9H3V7Zm11 3h4l3 3v3h-7v-6ZM7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'phone' => '<path d="M6.6 4h3l1.5 4-2 1.2a11 11 0 0 0 5.7 5.7l1.2-2 4 1.5v3a2 2 0 0 1-2.2 2A16 16 0 0 1 4.6 6.2 2 2 0 0 1 6.6 4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'mail' => '<path d="M4 6h16v12H4V6Zm0 1 8 6 8-6" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'user' => '<path d="M20 19c0-2.8-2.2-5-5-5H9c-2.8 0-5 2.2-5 5m8-8a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'refresh' => '<path d="M20 12a8 8 0 0 1-14.3 4.9M4 12A8 8 0 0 1 18.3 7.1M18 4v4h-4M6 20v-4h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'supply' => '<path d="M12 3v18M6 8l6-5 6 5M5 15h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'eye' => '<path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="2"/>',
        'edit' => '<path d="M4 20h4l10.5-10.5a2.8 2.8 0 0 0-4-4L4 16v4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="m13.5 6.5 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    ];

    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . ($paths[$name] ?? $paths['truck']) . '</svg>';
};
?>

<section class="space-y-5" data-suppliers-page>
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Partenaires d’achat</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Gestion des fournisseurs</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Centralisez les contacts fournisseurs de la boutique active pour accélérer les arrivages et garder une base fiable.
            </p>
        </div>
        <a class="btn-primary w-full gap-2 sm:w-auto" href="<?= $url('/supplies/create') ?>">
            <?= $icon('supply') ?>
            <span>Nouvel arrivage</span>
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <article class="stat-card">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm text-slate-500">Fournisseurs</p>
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-teal-50 text-teal-700"><?= $icon('truck') ?></span>
            </div>
            <p class="mt-2 text-2xl font-bold"><?= count($suppliers) ?></p>
        </article>
        <article class="stat-card">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm text-slate-500">Avec téléphone</p>
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-blue-50 text-blue-700"><?= $icon('phone') ?></span>
            </div>
            <p class="mt-2 text-2xl font-bold"><?= $withPhone ?></p>
        </article>
        <article class="stat-card">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm text-slate-500">Avec email</p>
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-amber-50 text-amber-700"><?= $icon('mail') ?></span>
            </div>
            <p class="mt-2 text-2xl font-bold"><?= $withEmail ?></p>
        </article>
    </div>

    <div class="grid gap-5 xl:grid-cols-[.9fr_1.4fr]">
        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Ajouter un fournisseur</h2>
                    <p class="mt-1 text-sm text-slate-500">Informations utilisées dans les arrivages.</p>
                </div>
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-teal-50 text-teal-700"><?= $icon('plus') ?></span>
            </div>

            <form class="mt-5 space-y-4" method="post" action="<?= $url('/suppliers') ?>" accept-charset="UTF-8">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-800" for="supplier_name">Nom du fournisseur</label>
                    <input class="field-control" id="supplier_name" name="nom" type="text" placeholder="Ex: Congo Distribution" maxlength="120" required>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-800" for="supplier_contact">Contact principal</label>
                    <input class="field-control" id="supplier_contact" name="contact_nom" type="text" placeholder="Nom du responsable" maxlength="120">
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-800" for="supplier_phone">Téléphone</label>
                        <input class="field-control" id="supplier_phone" name="telephone" type="tel" placeholder="+243..." maxlength="30" inputmode="tel">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-800" for="supplier_email">Email</label>
                        <input class="field-control" id="supplier_email" name="email" type="email" placeholder="contact@fournisseur.com" maxlength="190">
                    </div>
                </div>

                <button class="btn-primary w-full gap-2" type="submit">
                    <?= $icon('plus') ?>
                    <span>Enregistrer le fournisseur</span>
                </button>
            </form>
        </section>

        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Répertoire fournisseurs</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        <span data-suppliers-count><?= count($suppliers) ?></span> fournisseur(s) affiché(s).
                    </p>
                </div>
                <button class="btn-secondary gap-2" type="button" data-suppliers-reset>
                    <?= $icon('refresh') ?>
                    <span>Réinitialiser</span>
                </button>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-[1.2fr_.8fr]">
                <label class="relative block">
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"><?= $icon('search') ?></span>
                    <input class="field-control pl-11" type="search" placeholder="Rechercher par nom, contact, téléphone ou email" data-suppliers-search>
                </label>
                <select class="field-control" data-suppliers-contact>
                    <option value="all">Tous les fournisseurs</option>
                    <option value="phone">Avec téléphone</option>
                    <option value="email">Avec email</option>
                    <option value="incomplete">Contact incomplet</option>
                </select>
            </div>

            <div class="responsive-table mt-5 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead>
                        <tr class="text-xs uppercase tracking-[.14em] text-slate-400">
                            <th class="px-4 py-3 font-semibold">Fournisseur</th>
                            <th class="px-4 py-3 font-semibold">Contact</th>
                            <th class="px-4 py-3 font-semibold">Téléphone</th>
                            <th class="px-4 py-3 font-semibold">Email</th>
                            <th class="px-4 py-3 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" data-suppliers-table>
                        <?php foreach ($suppliers as $supplier): ?>
                            <?php
                            $phone = trim((string) ($supplier['telephone'] ?? ''));
                            $email = trim((string) ($supplier['email'] ?? ''));
                            $contact = trim((string) ($supplier['contact_nom'] ?? ''));
                            $searchText = strtolower(trim((string) ($supplier['nom'] ?? '') . ' ' . $contact . ' ' . $phone . ' ' . $email));
                            ?>
                            <tr
                                class="hover:bg-slate-50"
                                data-supplier-row
                                data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>"
                                data-phone="<?= $phone !== '' ? '1' : '0' ?>"
                                data-email="<?= $email !== '' ? '1' : '0' ?>"
                            >
                                <td class="px-4 py-4" data-label="Fournisseur">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-600"><?= $icon('truck') ?></span>
                                        <span class="min-w-0">
                                            <span class="block truncate font-semibold text-slate-950"><?= htmlspecialchars((string) $supplier['nom'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="block truncate text-xs text-slate-500">ID #<?= (int) $supplier['id'] ?></span>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-slate-600"><?= htmlspecialchars($contact !== '' ? $contact : 'Non renseigné', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-4">
                                    <?php if ($phone !== ''): ?>
                                        <a class="font-semibold text-teal-700 hover:text-teal-900" href="tel:<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?></a>
                                    <?php else: ?>
                                        <span class="text-slate-400">Non renseigné</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-4">
                                    <?php if ($email !== ''): ?>
                                        <a class="font-semibold text-blue-700 hover:text-blue-900" href="mailto:<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></a>
                                    <?php else: ?>
                                        <span class="text-slate-400">Non renseigné</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-950 focus:outline-none focus:ring-4 focus:ring-slate-200" href="<?= $url('/suppliers/' . (int) $supplier['id']) ?>" title="Voir le fournisseur" aria-label="Voir le fournisseur">
                                            <?= $icon('eye') ?>
                                        </a>
                                        <a class="grid h-9 w-9 place-items-center rounded-lg border border-blue-100 bg-blue-50 text-blue-700 transition hover:bg-blue-100 focus:outline-none focus:ring-4 focus:ring-blue-100" href="<?= $url('/suppliers/' . (int) $supplier['id'] . '/edit') ?>" title="Modifier le fournisseur" aria-label="Modifier le fournisseur">
                                            <?= $icon('edit') ?>
                                        </a>
                                        <a class="btn-secondary h-9 gap-2 px-3" href="<?= $url('/supplies/create', ['supplier_id' => (int) $supplier['id']]) ?>">
                                            <?= $icon('supply') ?>
                                            <span>Arrivage</span>
                                        </a>
                                        <form method="post" action="<?= $url('/suppliers/' . (int) $supplier['id'] . '/delete') ?>" data-confirm-form>
                                            <button
                                                class="grid h-9 w-9 place-items-center rounded-lg border border-red-100 bg-red-50 text-red-700 transition hover:bg-red-100 focus:outline-none focus:ring-4 focus:ring-red-100"
                                                type="button"
                                                title="Supprimer le fournisseur"
                                                aria-label="Supprimer le fournisseur"
                                                data-confirm
                                                data-confirm-title="Supprimer ce fournisseur ?"
                                                data-confirm-message="Cette action supprimera le fournisseur si aucun arrivage ne lui est lié."
                                                data-confirm-accept="Oui, supprimer"
                                                data-confirm-progress="Suppression..."
                                            >
                                                <?= $icon('trash') ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="<?= $suppliers === [] ? '' : 'hidden' ?> rounded-lg border border-dashed border-slate-200 p-6 text-center text-sm font-semibold text-slate-500" data-suppliers-empty>
                    Aucun fournisseur ne correspond aux filtres.
                </div>
            </div>
        </section>
    </div>
</section>

<script>
    (() => {
        const root = document.querySelector('[data-suppliers-page]');

        if (!root) {
            return;
        }

        const rows = [...root.querySelectorAll('[data-supplier-row]')];
        const search = root.querySelector('[data-suppliers-search]');
        const contact = root.querySelector('[data-suppliers-contact]');
        const count = root.querySelector('[data-suppliers-count]');
        const empty = root.querySelector('[data-suppliers-empty]');
        const reset = root.querySelector('[data-suppliers-reset]');

        const matchesContact = (row, filter) => {
            const hasPhone = row.dataset.phone === '1';
            const hasEmail = row.dataset.email === '1';

            if (filter === 'phone') {
                return hasPhone;
            }

            if (filter === 'email') {
                return hasEmail;
            }

            if (filter === 'incomplete') {
                return !hasPhone || !hasEmail;
            }

            return true;
        };

        const applyFilters = () => {
            const query = (search?.value || '').trim().toLowerCase();
            const contactValue = contact?.value || 'all';
            let visible = 0;

            rows.forEach((row) => {
                const isVisible =
                    (query === '' || (row.dataset.search || '').includes(query)) &&
                    matchesContact(row, contactValue);

                row.classList.toggle('hidden', !isVisible);
                visible += isVisible ? 1 : 0;
            });

            if (count) {
                count.textContent = String(visible);
            }

            empty?.classList.toggle('hidden', visible !== 0);
        };

        search?.addEventListener('input', applyFilters);
        contact?.addEventListener('change', applyFilters);
        reset?.addEventListener('click', () => {
            if (search) {
                search.value = '';
            }
            if (contact) {
                contact.value = 'all';
            }
            applyFilters();
        });

        applyFilters();
    })();
</script>
