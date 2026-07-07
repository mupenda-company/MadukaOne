<?php

$supplies = is_array($supplies ?? null) ? $supplies : [];
$pagination = is_array($pagination ?? null) ? $pagination : [
    'current_page' => 1,
    'total_items' => count($supplies),
    'total_pages' => 1,
    'from' => $supplies === [] ? 0 : 1,
    'to' => count($supplies),
];
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$money = static fn ($value): string => number_format((float) $value, 2, ',', ' ') . ' USD';
$dateLabel = static function ($value): string {
    $timestamp = strtotime((string) ($value ?? ''));
    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : '-';
};
$statusLabel = static fn (string $status): string => $status === 'annule' ? 'Annulé' : 'Reçu';
$statusClass = static fn (string $status): string => $status === 'annule' ? 'bg-red-50 text-red-700' : 'bg-teal-50 text-teal-700';
$pageUrl = static fn (int $page): string => $url('/supplies', ['page' => $page]);
$icon = static function (string $name): string {
    $paths = [
        'plus' => '<path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'eye' => '<path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="2"/>',
        'edit' => '<path d="M4 20h4l10.5-10.5a2.8 2.8 0 0 0-4-4L4 16v4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="m13.5 6.5 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'x' => '<path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'truck' => '<path d="M3 7h11v9H3V7Zm11 3h4l3 3v3h-7v-6ZM7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
    ];
    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . ($paths[$name] ?? $paths['truck']) . '</svg>';
};
?>

<section class="space-y-5" data-supplies-page>
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Approvisionnement</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Historique des approvisionnements</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Consultez les arrivages fournisseurs, vérifiez les lignes reçues et annulez proprement les entrées si nécessaire.
            </p>
        </div>
        <a class="btn-primary w-full gap-2 sm:w-auto" href="<?= $url('/supplies/create') ?>">
            <?= $icon('plus') ?>
            <span>Nouvel arrivage</span>
        </a>
    </div>

    <section class="surface-panel">
        <div class="panel-header">
            <div>
                <h2 class="font-bold text-slate-950">Arrivages fournisseurs</h2>
                <p class="mt-1 text-sm text-slate-500">
                    <?= (int) ($pagination['total_items'] ?? 0) ?> approvisionnement(s) enregistré(s).
                </p>
            </div>
            <span class="grid h-10 w-10 place-items-center rounded-lg bg-teal-50 text-teal-700"><?= $icon('truck') ?></span>
        </div>

        <div class="responsive-table mt-5 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead>
                    <tr class="text-xs uppercase tracking-[.14em] text-slate-400">
                        <th class="px-4 py-3 font-semibold">N° arrivage</th>
                        <th class="px-4 py-3 font-semibold">Fournisseur</th>
                        <th class="px-4 py-3 font-semibold">Date</th>
                        <th class="px-4 py-3 font-semibold">Lignes</th>
                        <th class="px-4 py-3 font-semibold">Total</th>
                        <th class="px-4 py-3 font-semibold">Statut</th>
                        <th class="px-4 py-3 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if ($supplies === []): ?>
                        <tr>
                            <td class="px-4 py-10 text-center text-sm text-slate-500" colspan="7">Aucun approvisionnement enregistré.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($supplies as $supply): ?>
                        <?php
                        $supplyId = (int) ($supply['id'] ?? 0);
                        $status = (string) ($supply['statut'] ?? '');
                        $isCancelled = $status === 'annule';
                        ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-4 font-bold text-slate-950" data-label="N° arrivage"><?= $safe($supply['numero_arrivage'] ?? '-') ?></td>
                            <td class="px-4 py-4 text-slate-700" data-label="Fournisseur"><?= $safe($supply['supplier_name'] ?? '-') ?></td>
                            <td class="px-4 py-4 text-slate-600" data-label="Date"><?= $safe($dateLabel($supply['date_approvisionnement'] ?? null)) ?></td>
                            <td class="px-4 py-4 text-slate-700" data-label="Lignes">
                                <?= (int) ($supply['lines_count'] ?? 0) ?> ligne(s), <?= (int) ($supply['total_units'] ?? 0) ?> unité(s)
                            </td>
                            <td class="px-4 py-4 font-bold text-slate-950" data-label="Total"><?= $money($supply['total_facture'] ?? 0) ?></td>
                            <td class="px-4 py-4" data-label="Statut">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold <?= $statusClass($status) ?>"><?= $statusLabel($status) ?></span>
                            </td>
                            <td class="px-4 py-4 text-right" data-label="Actions">
                                <div class="flex items-center justify-end gap-2">
                                    <a class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50" href="<?= $url('/supplies/' . $supplyId) ?>" title="Voir" aria-label="Voir">
                                        <?= $icon('eye') ?>
                                    </a>
                                    <a class="grid h-9 w-9 place-items-center rounded-lg border border-blue-100 bg-blue-50 text-blue-700 transition hover:bg-blue-100 <?= $isCancelled ? 'pointer-events-none opacity-40' : '' ?>" href="<?= $url('/supplies/' . $supplyId . '/edit') ?>" title="Modifier" aria-label="Modifier">
                                        <?= $icon('edit') ?>
                                    </a>
                                    <form method="post" action="<?= $url('/supplies/' . $supplyId . '/cancel') ?>" data-confirm-form>
                                        <button
                                            class="grid h-9 w-9 place-items-center rounded-lg border border-red-100 bg-red-50 text-red-700 transition hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-40"
                                            type="button"
                                            title="Annuler"
                                            aria-label="Annuler"
                                            data-confirm
                                            data-confirm-title="Annuler cet approvisionnement ?"
                                            data-confirm-message="Le stock sera diminué selon les lignes de cet arrivage et un mouvement d’annulation sera enregistré."
                                            data-confirm-accept="Oui, annuler"
                                            data-confirm-progress="Annulation..."
                                            <?= $isCancelled ? 'disabled' : '' ?>
                                        >
                                            <?= $icon('x') ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ((int) ($pagination['total_items'] ?? 0) > 0): ?>
            <?php
            $currentPage = (int) ($pagination['current_page'] ?? 1);
            $totalPages = (int) ($pagination['total_pages'] ?? 1);
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $currentPage + 2);
            ?>
            <div class="mt-5 flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-500">
                    Affichage de <strong class="text-slate-950"><?= (int) ($pagination['from'] ?? 0) ?></strong>
                    à <strong class="text-slate-950"><?= (int) ($pagination['to'] ?? 0) ?></strong>
                    sur <strong class="text-slate-950"><?= (int) ($pagination['total_items'] ?? 0) ?></strong>.
                </p>
                <nav class="flex flex-wrap items-center gap-2" aria-label="Pagination des approvisionnements">
                    <a class="btn-secondary h-10 px-3 <?= $currentPage <= 1 ? 'pointer-events-none opacity-50' : '' ?>" href="<?= $pageUrl(max(1, $currentPage - 1)) ?>">Précédent</a>
                    <?php for ($page = $startPage; $page <= $endPage; $page++): ?>
                        <a class="<?= $page === $currentPage ? 'inline-flex h-10 w-10 items-center justify-center rounded-lg bg-teal-700 text-sm font-bold text-white shadow-sm' : 'btn-secondary h-10 w-10 px-0' ?>" href="<?= $pageUrl($page) ?>" aria-current="<?= $page === $currentPage ? 'page' : 'false' ?>"><?= $page ?></a>
                    <?php endfor; ?>
                    <a class="btn-secondary h-10 px-3 <?= $currentPage >= $totalPages ? 'pointer-events-none opacity-50' : '' ?>" href="<?= $pageUrl(min($totalPages, $currentPage + 1)) ?>">Suivant</a>
                </nav>
            </div>
        <?php endif; ?>
    </section>
</section>
