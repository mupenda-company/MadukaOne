<?php

$supplier = is_array($supplier ?? null) ? $supplier : [];

$icon = static function (string $name): string {
    $paths = [
        'arrow' => '<path d="M19 12H5m6-6-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'save' => '<path d="M5 4h12l2 2v14H5V4Zm4 0v6h6V4M8 20v-6h8v6" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
    ];

    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . ($paths[$name] ?? $paths['save']) . '</svg>';
};
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Fournisseur</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Modifier le fournisseur</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Mettez à jour les coordonnées utilisées dans les arrivages.</p>
        </div>
        <a class="btn-secondary gap-2" href="<?= $url('/suppliers') ?>"><?= $icon('arrow') ?><span>Retour</span></a>
    </div>

    <section class="surface-panel max-w-3xl">
        <form class="space-y-5" method="post" action="<?= $url('/suppliers/' . (int) $supplier['id'] . '/update') ?>" accept-charset="UTF-8">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-800" for="supplier_name">Nom du fournisseur</label>
                <input class="field-control" id="supplier_name" name="nom" type="text" value="<?= htmlspecialchars((string) ($supplier['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="120" required>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-800" for="supplier_contact">Contact principal</label>
                <input class="field-control" id="supplier_contact" name="contact_nom" type="text" value="<?= htmlspecialchars((string) ($supplier['contact_nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="120">
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-800" for="supplier_phone">Téléphone</label>
                    <input class="field-control" id="supplier_phone" name="telephone" type="tel" value="<?= htmlspecialchars((string) ($supplier['telephone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="30" inputmode="tel">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-800" for="supplier_email">Email</label>
                    <input class="field-control" id="supplier_email" name="email" type="email" value="<?= htmlspecialchars((string) ($supplier['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="190">
                </div>
            </div>

            <button class="btn-primary gap-2" type="submit">
                <?= $icon('save') ?>
                <span>Enregistrer les modifications</span>
            </button>
        </form>
    </section>
</section>
