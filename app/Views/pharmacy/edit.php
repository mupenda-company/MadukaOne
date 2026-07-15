<?php

$product = is_array($product ?? null) ? $product : [];
$details = is_array($details ?? null) ? $details : [];
$safe = static fn ($value, string $fallback = ''): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Fiche medicament</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950"><?= $safe($product['nom'] ?? '') ?></h1>
            <p class="mt-3 text-sm text-slate-600"><?= $safe($product['ref'] ?? '') ?> · Stock <?= (int) ($product['quantite_stock'] ?? 0) ?></p>
        </div>
        <a class="btn-secondary" href="<?= $url('/pharmacie') ?>">Retour</a>
    </div>

    <form class="surface-panel space-y-5" method="post" action="<?= $url('/pharmacie/produits/' . (int) ($product['id'] ?? 0) . '/details') ?>">
        <div class="grid gap-4 md:grid-cols-2">
            <div><label class="mb-2 block text-sm font-semibold text-slate-700">Dosage</label><input class="field-control" name="dosage" value="<?= $safe($details['dosage'] ?? '') ?>" placeholder="ex: 500 mg"></div>
            <div><label class="mb-2 block text-sm font-semibold text-slate-700">Forme pharmaceutique</label><input class="field-control" name="forme" value="<?= $safe($details['forme'] ?? '') ?>" placeholder="Comprime, sirop, gelule"></div>
            <div><label class="mb-2 block text-sm font-semibold text-slate-700">Fabricant</label><input class="field-control" name="fabricant" value="<?= $safe($details['fabricant'] ?? '') ?>"></div>
            <div><label class="mb-2 block text-sm font-semibold text-slate-700">Numero de lot</label><input class="field-control" name="numero_lot" value="<?= $safe($details['numero_lot'] ?? '') ?>"></div>
            <div><label class="mb-2 block text-sm font-semibold text-slate-700">Alerte expiration en jours</label><input class="field-control" name="alerte_expiration_jours" type="number" min="1" value="<?= $safe($details['alerte_expiration_jours'] ?? '30') ?>"></div>
            <div><label class="mb-2 block text-sm font-semibold text-slate-700">Emplacement</label><input class="field-control" name="emplacement" value="<?= $safe($details['emplacement'] ?? '') ?>" placeholder="Rayon, armoire, frigo"></div>
            <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-4 md:col-span-2">
                <input class="h-5 w-5 rounded border-slate-300 text-teal-700 focus:ring-teal-600" type="checkbox" name="ordonnance_requise" value="1" <?= (int) ($details['ordonnance_requise'] ?? 0) === 1 ? 'checked' : '' ?>>
                <span class="text-sm font-semibold text-slate-700">Ordonnance obligatoire avant la vente</span>
            </label>
            <div class="md:col-span-2"><label class="mb-2 block text-sm font-semibold text-slate-700">Notes</label><textarea class="field-control min-h-28" name="notes"><?= $safe($details['notes'] ?? '') ?></textarea></div>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a class="btn-secondary w-full sm:w-auto" href="<?= $url('/pharmacie') ?>">Annuler</a>
            <button class="btn-primary w-full sm:w-auto" type="submit">Enregistrer la fiche</button>
        </div>
    </form>
</section>
