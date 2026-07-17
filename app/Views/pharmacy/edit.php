<?php
$product = is_array($product ?? null) ? $product : [];
$details = is_array($details ?? null) ? $details : [];
$safe = static fn ($value, string $fallback = ''): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$stock = (int) ($product['quantite_stock'] ?? 0);
$minimum = (int) ($product['alerte_stock_min'] ?? 0);
$expiration = trim((string) ($product['date_expiration'] ?? ''));
$expired = $expiration !== '' && $expiration < date('Y-m-d');
?>
<section class="space-y-5">
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-950 via-teal-900 to-cyan-800 p-6 text-white shadow-xl sm:p-8">
        <div class="absolute -right-12 -top-16 h-56 w-56 rounded-full bg-cyan-300/20 blur-3xl"></div>
        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div><div class="flex items-center gap-3"><span class="grid h-12 w-12 place-items-center rounded-2xl bg-white/10 text-xl font-black"><?= strtoupper(substr((string) ($product['nom'] ?? 'M'), 0, 1)) ?></span><div><p class="text-xs font-black uppercase tracking-[.18em] text-emerald-200">Dossier pharmaceutique</p><p class="mt-1 text-sm text-white/60"><?= $safe($product['ref'] ?? 'Sans référence') ?></p></div></div><h1 class="mt-5 text-3xl font-black sm:text-4xl"><?= $safe($product['nom'] ?? 'Médicament') ?></h1><p class="mt-3 text-sm text-emerald-50/75">Complétez la traçabilité, la présentation et les règles de délivrance du médicament.</p></div>
            <a class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-white/10 px-5 py-3 text-sm font-bold hover:bg-white/20" href="<?= $url('/pharmacie') ?>">← Retour à l’espace pharmacie</a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <article class="stat-card"><p class="text-sm text-slate-500">Stock disponible</p><p class="mt-2 text-3xl font-black <?= $stock <= $minimum ? 'text-amber-700' : 'text-emerald-700' ?>"><?= $stock ?></p><p class="mt-1 text-xs text-slate-500">Seuil d’alerte : <?= $minimum ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Date de péremption</p><p class="mt-2 text-xl font-black <?= $expired ? 'text-red-700' : 'text-slate-950' ?>"><?= $safe($expiration, 'Non définie') ?></p><p class="mt-1 text-xs <?= $expired ? 'text-red-600' : 'text-slate-500' ?>"><?= $expired ? 'Produit périmé — à isoler' : 'Surveillance active' ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Délivrance</p><p class="mt-2 text-xl font-black text-violet-700"><?= (int) ($details['ordonnance_requise'] ?? 0) === 1 ? 'Sur ordonnance' : 'Vente libre' ?></p><p class="mt-1 text-xs text-slate-500">Contrôle au point de vente</p></article>
    </div>

    <form class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_320px]" method="post" action="<?= $url('/pharmacie/produits/' . (int) ($product['id'] ?? 0) . '/details') ?>" accept-charset="UTF-8">
        <div class="space-y-5">
            <section class="surface-panel">
                <div class="border-b border-slate-100 pb-5"><p class="text-xs font-black uppercase tracking-[.16em] text-teal-700">Identification clinique</p><h2 class="mt-2 text-xl font-bold">Présentation du médicament</h2><p class="mt-1 text-sm text-slate-500">Informations visibles par les équipes de l’officine.</p></div>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <label class="form-field"><span>Dosage</span><input name="dosage" value="<?= $safe($details['dosage'] ?? '') ?>" placeholder="Ex. 500 mg"></label>
                    <label class="form-field"><span>Forme pharmaceutique</span><select name="forme"><option value="">Sélectionner</option><?php foreach (['Comprimé','Gélule','Sirop','Solution injectable','Crème','Pommade','Gel','Collyre','Suppositoire','Sachet','Spray','Autre'] as $form): ?><option value="<?= $safe($form) ?>" <?= ($details['forme'] ?? '') === $form ? 'selected' : '' ?>><?= $safe($form) ?></option><?php endforeach; ?></select></label>
                    <label class="form-field"><span>Laboratoire / fabricant</span><input name="fabricant" value="<?= $safe($details['fabricant'] ?? '') ?>" placeholder="Nom du laboratoire"></label>
                    <label class="form-field"><span>Numéro de lot</span><input name="numero_lot" value="<?= $safe($details['numero_lot'] ?? '') ?>" placeholder="Ex. LOT-2026-001"></label>
                </div>
            </section>

            <section class="surface-panel">
                <div class="border-b border-slate-100 pb-5"><p class="text-xs font-black uppercase tracking-[.16em] text-cyan-700">Stock et vigilance</p><h2 class="mt-2 text-xl font-bold">Conservation et alertes</h2></div>
                <div class="mt-5 grid gap-4 md:grid-cols-2"><label class="form-field"><span>Délai d’alerte avant péremption</span><div class="relative"><input class="pr-16" name="alerte_expiration_jours" type="number" min="1" max="730" value="<?= $safe($details['alerte_expiration_jours'] ?? '30') ?>"><span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">jours</span></div></label><label class="form-field"><span>Emplacement physique</span><input name="emplacement" value="<?= $safe($details['emplacement'] ?? '') ?>" placeholder="Rayon A · Étagère 2 · Frigo"></label></div>
                <label class="mt-5 flex cursor-pointer items-start gap-4 rounded-2xl border border-violet-200 bg-violet-50 p-4"><input class="mt-1 h-5 w-5 rounded border-violet-300 text-violet-700 focus:ring-violet-600" type="checkbox" name="ordonnance_requise" value="1" <?= (int) ($details['ordonnance_requise'] ?? 0) === 1 ? 'checked' : '' ?>><span><strong class="block text-sm text-violet-950">Ordonnance médicale obligatoire</strong><span class="mt-1 block text-xs leading-5 text-violet-700">Signale à l’équipe qu’une ordonnance doit être contrôlée avant la délivrance.</span></span></label>
                <label class="form-field mt-5"><span>Notes pharmaceutiques internes</span><textarea name="notes" rows="5" placeholder="Conditions de conservation, précautions, informations utiles à l’équipe..."><?= $safe($details['notes'] ?? '') ?></textarea></label>
            </section>
        </div>

        <aside class="space-y-5 xl:sticky xl:top-5 xl:h-fit">
            <section class="rounded-2xl bg-gradient-to-br from-slate-950 to-teal-900 p-5 text-white shadow-lg"><p class="text-xs font-black uppercase tracking-[.16em] text-emerald-300">Contrôle qualité</p><h2 class="mt-3 text-lg font-bold">Avant d’enregistrer</h2><ul class="mt-4 space-y-3 text-sm leading-6 text-slate-300"><li>✓ Vérifier le dosage et la forme.</li><li>✓ Reporter exactement le numéro de lot.</li><li>✓ Définir l’emplacement de stockage.</li><li>✓ Activer l’ordonnance si nécessaire.</li></ul></section>
            <section class="surface-panel"><p class="text-sm font-bold text-slate-950">Informations commerciales</p><p class="mt-2 text-xs leading-5 text-slate-500">Les prix, dates de fabrication et péremption sont gérés dans la fiche produit commerciale.</p><a class="btn-secondary mt-4 w-full" href="<?= $url('/products/' . (int) ($product['id'] ?? 0) . '/edit') ?>">Modifier le produit</a></section>
            <button class="btn-primary w-full py-3" type="submit">Enregistrer la fiche pharmaceutique</button><a class="btn-secondary w-full" href="<?= $url('/pharmacie') ?>">Annuler</a>
        </aside>
    </form>
</section>
