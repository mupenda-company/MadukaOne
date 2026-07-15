<?php
$shop = is_array($shop ?? null) ? $shop : null;
$categories = is_array($categories ?? null) ? $categories : [];
$isEdit = $shop !== null;
$safe = static fn ($value, string $fallback = ''): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$action = $isEdit ? $url('/saas-admin/boutiques/' . (int) $shop['id'] . '/update') : $url('/saas-admin/boutiques');
?>
<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Boutique</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950"><?= $isEdit ? 'Modifier la boutique' : 'Nouvelle boutique' ?></h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Ces parametres servent au contexte boutique et a l'affichage monetaire.</p>
        </div>
        <a class="btn-secondary" href="<?= $url('/saas-admin/boutiques') ?>">Retour</a>
    </div>
    <form class="surface-panel max-w-4xl space-y-4" method="post" action="<?= $action ?>" accept-charset="UTF-8">
        <div class="grid gap-4 md:grid-cols-2">
            <div><label class="mb-2 block text-sm font-semibold" for="nom">Nom</label><input class="field-control" id="nom" name="nom" value="<?= $safe($shop['nom'] ?? '') ?>" required maxlength="120"></div>
            <div>
                <label class="mb-2 block text-sm font-semibold" for="category_id">Categorie</label>
                <select class="field-control" id="category_id" name="category_id" required>
                    <?php foreach ($categories as $category): ?>
                        <?php $categoryId = (int) ($category['id'] ?? 0); ?>
                        <option value="<?= $categoryId ?>" <?= $categoryId === (int) ($shop['category_id'] ?? 0) ? 'selected' : '' ?>>
                            <?= $safe($category['nom'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label class="mb-2 block text-sm font-semibold" for="email">Email</label><input class="field-control" id="email" name="email" type="email" value="<?= $safe($shop['email'] ?? '') ?>" maxlength="190"></div>
            <div><label class="mb-2 block text-sm font-semibold" for="telephone">Telephone</label><input class="field-control" id="telephone" name="telephone" value="<?= $safe($shop['telephone'] ?? '') ?>" maxlength="50"></div>
            <div><label class="mb-2 block text-sm font-semibold" for="adresse">Adresse</label><input class="field-control" id="adresse" name="adresse" value="<?= $safe($shop['adresse'] ?? '') ?>" maxlength="255"></div>
            <div><label class="mb-2 block text-sm font-semibold" for="devise_principale">Devise principale</label><select class="field-control" id="devise_principale" name="devise_principale"><option value="USD" <?= ($shop['devise_principale'] ?? 'USD') === 'USD' ? 'selected' : '' ?>>USD</option><option value="CDF" <?= ($shop['devise_principale'] ?? '') === 'CDF' ? 'selected' : '' ?>>CDF</option></select></div>
            <div><label class="mb-2 block text-sm font-semibold" for="taux_change_cdf">Taux CDF</label><input class="field-control" id="taux_change_cdf" name="taux_change_cdf" type="number" min="1" step="0.0001" value="<?= $safe($shop['taux_change_cdf'] ?? '2800') ?>" required></div>
        </div>
        <label class="inline-flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold"><input class="h-4 w-4" type="checkbox" name="actif" value="1" <?= (int) ($shop['actif'] ?? 1) === 1 ? 'checked' : '' ?>> Boutique active</label>
        <div class="flex flex-col gap-3 sm:flex-row"><button class="btn-primary sm:w-auto" type="submit"><?= $isEdit ? 'Enregistrer' : 'Creer la boutique' ?></button><a class="btn-secondary" href="<?= $url('/saas-admin/boutiques') ?>">Annuler</a></div>
    </form>
</section>
