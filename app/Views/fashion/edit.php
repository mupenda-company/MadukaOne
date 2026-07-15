<?php

$product = is_array($product ?? null) ? $product : [];
$details = is_array($details ?? null) ? $details : [];
$safe = static fn ($value, string $fallback = ''): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$gender = (string) ($details['sexe'] ?? 'mixte');
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Fiche textile</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950"><?= $safe($product['nom'] ?? '') ?></h1>
            <p class="mt-3 text-sm text-slate-600"><?= $safe($product['ref'] ?? '') ?> · Stock <?= (int) ($product['quantite_stock'] ?? 0) ?></p>
        </div>
        <a class="btn-secondary" href="<?= $url('/vetements') ?>">Retour</a>
    </div>

    <form class="surface-panel space-y-5" method="post" action="<?= $url('/vetements/produits/' . (int) ($product['id'] ?? 0) . '/details') ?>">
        <div class="grid gap-4 md:grid-cols-2">
            <div><label class="mb-2 block text-sm font-semibold text-slate-700">Taille</label><input class="field-control" name="taille" value="<?= $safe($details['taille'] ?? '') ?>" placeholder="S, M, L, 42..."></div>
            <div><label class="mb-2 block text-sm font-semibold text-slate-700">Couleur</label><input class="field-control" name="couleur" value="<?= $safe($details['couleur'] ?? '') ?>"></div>
            <div><label class="mb-2 block text-sm font-semibold text-slate-700">Marque</label><input class="field-control" name="marque" value="<?= $safe($details['marque'] ?? '') ?>"></div>
            <div><label class="mb-2 block text-sm font-semibold text-slate-700">Collection</label><input class="field-control" name="collection" value="<?= $safe($details['collection'] ?? '') ?>" placeholder="Ete 2026, rentree..."></div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Profil</label>
                <select class="field-control" name="sexe">
                    <?php foreach (['mixte' => 'Mixte', 'femme' => 'Femme', 'homme' => 'Homme', 'enfant' => 'Enfant'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $gender === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label class="mb-2 block text-sm font-semibold text-slate-700">Matiere</label><input class="field-control" name="matiere" value="<?= $safe($details['matiere'] ?? '') ?>" placeholder="Coton, jean, cuir..."></div>
            <div><label class="mb-2 block text-sm font-semibold text-slate-700">Saison</label><input class="field-control" name="saison" value="<?= $safe($details['saison'] ?? '') ?>"></div>
            <div><label class="mb-2 block text-sm font-semibold text-slate-700">Code modele</label><input class="field-control" name="code_modele" value="<?= $safe($details['code_modele'] ?? '') ?>"></div>
            <div class="md:col-span-2"><label class="mb-2 block text-sm font-semibold text-slate-700">Notes</label><textarea class="field-control min-h-28" name="notes"><?= $safe($details['notes'] ?? '') ?></textarea></div>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a class="btn-secondary w-full sm:w-auto" href="<?= $url('/vetements') ?>">Annuler</a>
            <button class="btn-primary w-full sm:w-auto" type="submit">Enregistrer la fiche</button>
        </div>
    </form>
</section>
