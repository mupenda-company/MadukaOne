<?php
$shop = is_array($shop ?? null) ? $shop : null;
$categories = is_array($categories ?? null) ? $categories : [];
$isEdit = $shop !== null;
$safe = static fn ($value, string $fallback = ''): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$action = $isEdit ? $url('/saas-admin/boutiques/' . (int) $shop['id'] . '/update') : $url('/saas-admin/boutiques');
$businessSettings = is_array($businessSettings ?? null) ? $businessSettings : [
    'sales_credit_enabled' => '1',
    'partial_payments_enabled' => '1',
    'discounts_enabled' => '1',
    'taxes_enabled' => '0',
    'expiration_dates_enabled' => '1',
    'variants_enabled' => '0',
    'multi_warehouse_enabled' => '0',
    'multi_shop_enabled' => '0',
    'tables_enabled' => '0',
    'reservations_enabled' => '0',
];
$settingEnabled = static fn (string $key): bool => (string) ($businessSettings[$key] ?? '0') === '1';
$settingGroups = [
    'Vente et caisse' => [
        'sales_credit_enabled' => 'Vente a credit',
        'partial_payments_enabled' => 'Paiements partiels',
        'discounts_enabled' => 'Remises',
        'taxes_enabled' => 'Taxes',
    ],
    'Catalogue et stock' => [
        'expiration_dates_enabled' => 'Dates d expiration',
        'variants_enabled' => 'Variantes produit',
        'multi_warehouse_enabled' => 'Multi-depots',
        'multi_shop_enabled' => 'Multi-boutiques',
    ],
    'Services specialises' => [
        'tables_enabled' => 'Tables restaurant/bar',
        'reservations_enabled' => 'Reservations hotel/restaurant',
    ],
];
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
    <form class="space-y-5" method="post" action="<?= $action ?>" accept-charset="UTF-8">
        <section class="surface-panel max-w-5xl space-y-4">
            <div>
                <h2 class="font-bold text-slate-950">Informations boutique</h2>
                <p class="mt-1 text-sm text-slate-500">Ces parametres restent visibles et modifiables uniquement depuis l'administration SaaS.</p>
            </div>
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
        </section>

        <section class="surface-panel max-w-5xl">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Configuration metier</h2>
                    <p class="mt-1 text-sm text-slate-500">Options imposees par le SaaS admin selon l'activite de la boutique.</p>
                </div>
                <span class="rounded-lg bg-amber-50 px-3 py-2 text-xs font-bold uppercase tracking-[.14em] text-amber-700">SaaS admin</span>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                <?php foreach ($settingGroups as $groupTitle => $groupSettings): ?>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-4">
                        <h3 class="text-sm font-bold uppercase tracking-[.14em] text-slate-500"><?= $safe($groupTitle) ?></h3>
                        <div class="mt-4 space-y-3">
                            <?php foreach ($groupSettings as $key => $label): ?>
                                <label class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-3 py-3">
                                    <span class="text-sm font-semibold text-slate-700"><?= $safe($label) ?></span>
                                    <input class="h-5 w-5 rounded border-slate-300 text-teal-700 focus:ring-teal-600" type="checkbox" name="<?= $safe($key) ?>" value="1" <?= $settingEnabled($key) ? 'checked' : '' ?>>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="flex flex-col gap-3 sm:flex-row"><button class="btn-primary sm:w-auto" type="submit"><?= $isEdit ? 'Enregistrer' : 'Creer la boutique' ?></button><a class="btn-secondary" href="<?= $url('/saas-admin/boutiques') ?>">Annuler</a></div>
    </form>
</section>
