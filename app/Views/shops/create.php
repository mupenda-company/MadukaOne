<?php
$categories = is_array($categories ?? null) ? $categories : [];
$allowance = is_array($shopAllowance ?? null) ? $shopAllowance : [];
$safe = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
?>
<section class="space-y-5">
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Extension de votre activite</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Nouvelle boutique</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">La nouvelle boutique utilisera le plan actif et sa date de validite actuelle.</p>
        </div>
        <a class="btn-secondary w-full sm:w-auto" href="<?= $url('/dashboard') ?>">Annuler</a>
    </div>

    <div class="grid gap-5 xl:grid-cols-[1fr_22rem]">
        <form class="surface-panel space-y-5" method="post" action="<?= $url('/shops') ?>" accept-charset="UTF-8">
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-2 block text-sm font-semibold" for="shop_name">Nom de la boutique</label>
                    <input class="field-control" id="shop_name" name="nom" type="text" placeholder="Ex: Boutique Centre Ville" required>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold" for="shop_category">Categorie</label>
                    <select class="field-control" id="shop_category" name="category_id">
                        <option value="">Selectionner une categorie</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) ($category['id'] ?? 0) ?>"><?= $safe($category['nom'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold" for="shop_phone">Telephone</label>
                    <input class="field-control" id="shop_phone" name="telephone" type="tel" placeholder="+243...">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold" for="shop_email">Email</label>
                    <input class="field-control" id="shop_email" name="email" type="email" placeholder="boutique@entreprise.com">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold" for="shop_currency">Devise principale</label>
                    <select class="field-control" id="shop_currency" name="devise_principale">
                        <option value="USD">USD</option>
                        <option value="CDF">CDF</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold" for="shop_rate">Taux CDF pour 1 USD</label>
                    <input class="field-control" id="shop_rate" name="taux_change_cdf" type="number" min="0.01" step="0.01" value="2800" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-2 block text-sm font-semibold" for="shop_address">Adresse</label>
                    <textarea class="field-control min-h-24" id="shop_address" name="adresse" placeholder="Avenue, commune, ville"></textarea>
                </div>
            </div>
            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                <a class="btn-secondary w-full sm:w-auto" href="<?= $url('/dashboard') ?>">Annuler</a>
                <button class="btn-primary w-full sm:w-auto" type="submit">Creer la boutique</button>
            </div>
        </form>

        <aside class="space-y-5">
            <section class="surface-panel">
                <h2 class="font-bold text-slate-950">Disponibilite du plan</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="rounded-lg bg-slate-50 p-3"><dt class="text-slate-500">Plan actif</dt><dd class="mt-1 font-bold text-slate-950"><?= $safe($allowance['subscription']['plan_name'] ?? 'Non configure') ?></dd></div>
                    <div class="rounded-lg bg-teal-50 p-3"><dt class="text-teal-700">Boutiques restantes</dt><dd class="mt-1 text-2xl font-bold text-teal-800"><?= $allowance['remaining'] === null ? 'Illimite' : (int) $allowance['remaining'] ?></dd></div>
                    <div class="rounded-lg bg-slate-50 p-3"><dt class="text-slate-500">Utilisation</dt><dd class="mt-1 font-bold text-slate-950"><?= (int) ($allowance['used'] ?? 0) ?> / <?= $allowance['limit'] === null ? 'Illimite' : (int) $allowance['limit'] ?></dd></div>
                </dl>
            </section>
        </aside>
    </div>
</section>
