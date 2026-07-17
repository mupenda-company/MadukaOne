<?php

$activeShop = is_array($activeShop ?? null) ? $activeShop : [];
$isPharmacy = (string) ($activeShop['category_slug'] ?? '') === 'pharmacies';
$isFashion = (string) ($activeShop['category_slug'] ?? '') === 'magasins-de-vetements';
$catalogProfile = is_array($productCatalogProfile ?? null) ? $productCatalogProfile : [];
$itemNoun = (string) ($catalogProfile['unit'] ?? 'produit');
$catalogReturnPath = (string) ($catalogReturnPath ?? '/products');
$productCategories = is_array($productCategories ?? null) ? $productCategories : [];
$nextReference = (string) ($nextReference ?? 'PRD-000001');
$defaultCurrency = in_array(($activeShop['devise_principale'] ?? 'USD'), ['USD', 'CDF'], true) ? (string) $activeShop['devise_principale'] : 'USD';
$exchangeRate = (float) ($activeShop['taux_change_cdf'] ?? 2800);
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700"><?= htmlspecialchars((string) ($catalogProfile['eyebrow'] ?? 'Catalogue'), ENT_QUOTES, 'UTF-8') ?></p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950"><?= htmlspecialchars((string) ($catalogProfile['create_title'] ?? ('Ajouter un ' . $itemNoun)), ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                <?= htmlspecialchars((string) ($catalogProfile['create_description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>
        <a class="btn-secondary" href="<?= $url($catalogReturnPath) ?>">Retour à l’espace métier</a>
    </div>

    <form class="grid gap-5 xl:grid-cols-[1fr_22rem]" method="post" action="<?= $url('/products') ?>" accept-charset="UTF-8">
        <section class="surface-panel">
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block sm:col-span-2">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Nom du <?= $itemNoun ?></span>
                    <input class="field-control" name="nom" type="text" placeholder="<?= htmlspecialchars((string) ($catalogProfile['item_example'] ?? 'Ex. Produit principal'), ENT_QUOTES, 'UTF-8') ?>">
                </label>
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Référence interne</span>
                    <input class="field-control bg-slate-50 font-semibold text-slate-600" name="ref" type="text" value="<?= htmlspecialchars($nextReference, ENT_QUOTES, 'UTF-8') ?>" readonly>
                </label>
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Code-barres</span>
                    <input class="field-control" name="code_barre" type="text" placeholder="Scanner ou saisir">
                </label>
                <div class="block sm:col-span-2">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <span class="block text-sm font-semibold text-slate-700">Catégorie de l’article</span>
                        <button class="text-sm font-bold text-teal-700 hover:text-teal-900" type="button" data-product-category-open>Ajouter une catégorie</button>
                    </div>
                    <select class="field-control" name="category_id" data-product-category-select>
                        <option value="">Sans catégorie</option>
                        <?php foreach ($productCategories as $category): ?>
                            <option value="<?= (int) ($category['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($category['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid gap-2">
                    <span class="text-sm font-semibold text-slate-700">Prix d'achat</span>
                    <div class="grid grid-cols-[1fr_6rem] gap-2">
                        <input class="field-control" name="prix_achat_montant" type="number" min="0" step="0.01" placeholder="0.00">
                        <select class="field-control" name="prix_achat_devise" aria-label="Devise du prix d'achat">
                            <option value="USD" <?= $defaultCurrency === 'USD' ? 'selected' : '' ?>>USD</option>
                            <option value="CDF" <?= $defaultCurrency === 'CDF' ? 'selected' : '' ?>>CDF</option>
                        </select>
                    </div>
                </div>
                <div class="grid gap-2">
                    <span class="text-sm font-semibold text-slate-700">Prix de vente</span>
                    <div class="grid grid-cols-[1fr_6rem] gap-2">
                        <input class="field-control" name="prix_vente_montant" type="number" min="0" step="0.01" placeholder="0.00">
                        <select class="field-control" name="prix_vente_devise" aria-label="Devise du prix de vente">
                            <option value="USD" <?= $defaultCurrency === 'USD' ? 'selected' : '' ?>>USD</option>
                            <option value="CDF" <?= $defaultCurrency === 'CDF' ? 'selected' : '' ?>>CDF</option>
                        </select>
                    </div>
                </div>
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Stock initial</span>
                    <input class="field-control" name="quantite_stock" type="number" min="0" step="1" value="0">
                </label>
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Alerte stock minimal</span>
                    <input class="field-control" name="alerte_stock_min" type="number" min="0" step="1" value="5">
                </label>
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Date de fabrication</span>
                    <input class="field-control" name="date_fabrication" type="date">
                </label>
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Date d'expiration</span>
                    <input class="field-control" name="date_expiration" type="date">
                </label>
                <label class="block sm:col-span-2">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Description</span>
                    <textarea class="field-control min-h-28" name="description" placeholder="Informations utiles pour les vendeurs"></textarea>
                </label>
            </div>
        </section>

        <aside class="surface-panel h-fit">
            <h2 class="font-bold text-slate-950">Controle stock et dates</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                Le seuil minimal declenchera les alertes stock. La date d'expiration declenchera aussi une alerte 30 jours avant l'echeance.
            </p>
            <div class="mt-5 rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm text-blue-700">
                Taux actuel: 1 USD = <?= number_format($exchangeRate, 2, ',', ' ') ?> CDF.
            </div>
            <div class="mt-5 rounded-lg border border-amber-100 bg-amber-50 p-4 text-sm text-amber-700">
                Les mouvements de stock réels doivent ensuite passer par le journal de stock pour garder l’audit fiable.
            </div>
            <button class="btn-primary mt-6" type="submit">Enregistrer</button>
        </aside>
    </form>

    <div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4" data-product-category-modal>
        <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-2xl">
            <h2 class="text-lg font-bold text-slate-950">Nouvelle catégorie</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Ajoutez une catégorie adaptée à votre activité et sélectionnez-la directement.</p>
            <div class="mt-4 space-y-3">
                <input class="field-control" type="text" placeholder="<?= htmlspecialchars((string) ($catalogProfile['category_example'] ?? 'Ex. Général'), ENT_QUOTES, 'UTF-8') ?>" data-product-category-name>
                <p class="hidden rounded-lg bg-red-50 p-3 text-sm font-semibold text-red-700" data-product-category-error></p>
                <div class="grid gap-2 sm:grid-cols-2">
                    <button class="btn-secondary" type="button" data-product-category-close>Annuler</button>
                    <button class="btn-primary" type="button" data-product-category-save>Enregistrer</button>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    (() => {
        const endpoint = '<?= $url('/products/categories') ?>';
        const modal = document.querySelector('[data-product-category-modal]');
        const open = document.querySelector('[data-product-category-open]');
        const close = document.querySelector('[data-product-category-close]');
        const save = document.querySelector('[data-product-category-save]');
        const input = document.querySelector('[data-product-category-name]');
        const error = document.querySelector('[data-product-category-error]');
        const select = document.querySelector('[data-product-category-select]');

        const hideModal = () => {
            modal?.classList.add('hidden');
            modal?.classList.remove('flex');
        };

        open?.addEventListener('click', () => {
            error?.classList.add('hidden');
            if (input) input.value = '';
            modal?.classList.remove('hidden');
            modal?.classList.add('flex');
            window.setTimeout(() => input?.focus(), 0);
        });
        close?.addEventListener('click', hideModal);
        modal?.addEventListener('click', (event) => {
            if (event.target === modal) hideModal();
        });
        save?.addEventListener('click', async () => {
            const name = (input?.value || '').trim();
            if (name === '') {
                if (error) {
                    error.textContent = 'Le nom de la catégorie est obligatoire.';
                    error.classList.remove('hidden');
                }
                return;
            }

            save.disabled = true;
            save.textContent = 'Enregistrement...';

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ nom: name }),
                });
                const data = await response.json();

                if (!response.ok || !data.ok || !data.category) {
                    throw new Error(data.message || 'Création impossible.');
                }

                if (select) {
                    let option = [...select.options].find((item) => item.value === String(data.category.id));
                    if (!option) {
                        option = new Option(data.category.nom, data.category.id);
                        select.add(option);
                    }
                    select.value = String(data.category.id);
                }
                hideModal();
            } catch (exception) {
                if (error) {
                    error.textContent = exception.message || 'Création impossible.';
                    error.classList.remove('hidden');
                }
            } finally {
                save.disabled = false;
                save.textContent = 'Enregistrer';
            }
        });
    })();
</script>
