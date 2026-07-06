<?php

$product = is_array($product ?? null) ? $product : [];
$productId = (int) ($product['id'] ?? 0);
$stock = (int) ($product['quantite_stock'] ?? 0);
$minStock = (int) ($product['alerte_stock_min'] ?? 0);
$purchasePrice = (float) ($product['prix_achat'] ?? 0);
$salePrice = (float) ($product['prix_vente'] ?? 0);
$margin = $salePrice - $purchasePrice;
$isActive = (int) ($product['actif'] ?? 1) === 1;

$status = $stock === 0 ? 'Rupture' : ($stock <= $minStock ? 'Alerte stock' : 'Disponible');
$statusClass = $stock === 0 ? 'bg-red-50 text-red-700' : ($stock <= $minStock ? 'bg-amber-50 text-amber-700' : 'bg-teal-50 text-teal-700');
$today = new DateTimeImmutable('today');
$expirationLimit = $today->modify('+30 days');
$parseDate = static function ($value): ?DateTimeImmutable {
    $value = trim((string) ($value ?? ''));

    if ($value === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10));

    return $date instanceof DateTimeImmutable ? $date : null;
};
$formatDate = static fn (?DateTimeImmutable $date): string => $date instanceof DateTimeImmutable ? $date->format('d/m/Y') : '-';
$manufacturedAt = $parseDate($product['date_fabrication'] ?? null);
$expiresAt = $parseDate($product['date_expiration'] ?? null);
$expirationStatus = 'Non definie';
$expirationClass = 'bg-slate-100 text-slate-600';

if ($expiresAt instanceof DateTimeImmutable) {
    if ($expiresAt < $today) {
        $expirationStatus = 'Expire';
        $expirationClass = 'bg-red-50 text-red-700';
    } elseif ($expiresAt <= $expirationLimit) {
        $days = (int) $today->diff($expiresAt)->format('%a');
        $expirationStatus = $days === 0 ? 'Expire aujourd hui' : 'Expire dans ' . $days . ' j';
        $expirationClass = 'bg-orange-50 text-orange-700';
    } else {
        $expirationStatus = 'Valide';
        $expirationClass = 'bg-teal-50 text-teal-700';
    }
}

$formatMoney = static fn (float $value): string => number_format($value, 2, ',', ' ') . ' USD';
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');

$icon = static function (string $name): string {
    $paths = [
        'box' => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Zm0 9 8-4.5M12 12 4 7.5M12 12v9" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'edit' => '<path d="M4 20h4l10.5-10.5a2.8 2.8 0 0 0-4-4L4 16v4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="m13.5 6.5 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'arrow' => '<path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'chart' => '<path d="M4 19V5m0 14h16M8 15l3-4 3 2 5-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    ];

    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . ($paths[$name] ?? $paths['box']) . '</svg>';
};
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Catalogue</p>
            <h1 class="truncate text-3xl font-bold tracking-normal text-slate-950"><?= $safe($product['nom'] ?? null, 'Produit') ?></h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Fiche produit complete avec prix, stock, seuil d'alerte et statut operationnel.
            </p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row">
            <a class="btn-secondary gap-2" href="<?= $url('/products') ?>">
                <?= $icon('arrow') ?>
                <span>Retour</span>
            </a>
            <a class="btn-primary w-full gap-2 sm:w-auto" href="<?= $url('/products/' . $productId . '/edit') ?>">
                <?= $icon('edit') ?>
                <span>Modifier</span>
            </a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <article class="stat-card">
            <p class="text-sm text-slate-500">Prix d'achat</p>
            <p class="mt-2 text-2xl font-bold"><?= $formatMoney($purchasePrice) ?></p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Prix de vente</p>
            <p class="mt-2 text-2xl font-bold text-teal-700"><?= $formatMoney($salePrice) ?></p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Marge unitaire</p>
            <p class="mt-2 text-2xl font-bold <?= $margin < 0 ? 'text-red-700' : 'text-slate-950' ?>"><?= $formatMoney($margin) ?></p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Statut stock</p>
            <p class="mt-3"><span class="inline-flex rounded-full px-3 py-1 text-sm font-bold <?= $statusClass ?>"><?= $status ?></span></p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Expiration</p>
            <p class="mt-3"><span class="inline-flex rounded-full px-3 py-1 text-sm font-bold <?= $expirationClass ?>"><?= htmlspecialchars($expirationStatus, ENT_QUOTES, 'UTF-8') ?></span></p>
        </article>
    </div>

    <div class="grid gap-5 xl:grid-cols-[1fr_22rem]">
        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Informations produit</h2>
                    <p class="mt-1 text-sm text-slate-500">Identite commerciale et references de suivi.</p>
                </div>
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-slate-100 text-slate-600"><?= $icon('box') ?></span>
            </div>

            <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                <div class="signal-row">
                    <dt class="text-slate-500">Reference</dt>
                    <dd class="font-semibold text-slate-950"><?= $safe($product['ref'] ?? null, 'Non definie') ?></dd>
                </div>
                <div class="signal-row">
                    <dt class="text-slate-500">Code-barres</dt>
                    <dd class="font-semibold text-slate-950"><?= $safe($product['code_barre'] ?? null, 'Non defini') ?></dd>
                </div>
                <div class="signal-row">
                    <dt class="text-slate-500">Stock actuel</dt>
                    <dd class="font-semibold text-slate-950"><?= $stock ?></dd>
                </div>
                <div class="signal-row">
                    <dt class="text-slate-500">Seuil minimal</dt>
                    <dd class="font-semibold text-slate-950"><?= $minStock ?></dd>
                </div>
                <div class="signal-row">
                    <dt class="text-slate-500">Date de fabrication</dt>
                    <dd class="font-semibold text-slate-950"><?= $formatDate($manufacturedAt) ?></dd>
                </div>
                <div class="signal-row">
                    <dt class="text-slate-500">Date d'expiration</dt>
                    <dd class="font-semibold text-slate-950"><?= $formatDate($expiresAt) ?></dd>
                </div>
                <div class="signal-row">
                    <dt class="text-slate-500">Etat</dt>
                    <dd class="font-semibold text-slate-950"><?= $isActive ? 'Actif' : 'Inactif' ?></dd>
                </div>
                <div class="signal-row">
                    <dt class="text-slate-500">Creation</dt>
                    <dd class="font-semibold text-slate-950"><?= $safe($product['created_at'] ?? null) ?></dd>
                </div>
            </dl>

            <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4">
                <p class="text-sm font-semibold text-slate-950">Description</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    <?= nl2br($safe($product['description'] ?? null, 'Aucune description renseignee.')) ?>
                </p>
            </div>
        </section>

        <aside class="surface-panel h-fit">
            <h2 class="font-bold text-slate-950">Actions rapides</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Gerez la fiche produit sans quitter le catalogue.</p>
            <div class="mt-5 space-y-3">
                <a class="btn-secondary w-full gap-2" href="<?= $url('/products/' . $productId . '/edit') ?>">
                    <?= $icon('edit') ?>
                    <span>Modifier le produit</span>
                </a>
                <a class="btn-secondary w-full gap-2" href="<?= $url('/stock/movements') ?>">
                    <?= $icon('chart') ?>
                    <span>Voir les mouvements</span>
                </a>
                <form method="post" action="<?= $url('/products/' . $productId . '/delete') ?>" data-confirm-form>
                    <button
                        class="btn-danger w-full gap-2"
                        type="button"
                        data-confirm
                        data-confirm-title="Supprimer ce produit ?"
                        data-confirm-message="Le produit sera retire du catalogue visible de la boutique."
                        data-confirm-accept="Oui, supprimer"
                        data-confirm-progress="Suppression..."
                    >
                        <?= $icon('trash') ?>
                        <span>Supprimer</span>
                    </button>
                </form>
            </div>
        </aside>
    </div>
</section>
