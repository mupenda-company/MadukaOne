<?php

$activeShop = is_array($activeShop ?? null) ? $activeShop : [];
$defaultCurrency = in_array(($activeShop['devise_principale'] ?? 'USD'), ['USD', 'CDF'], true) ? (string) $activeShop['devise_principale'] : 'USD';
$exchangeRate = (float) ($activeShop['taux_change_cdf'] ?? 2800);
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Catalogue</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Ajouter un produit</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Créez une fiche produit complète avec prix, stock initial et seuil d’alerte minimal.
            </p>
        </div>
        <a class="btn-secondary" href="<?= $url('/products') ?>">Retour à la liste</a>
    </div>

    <form class="grid gap-5 xl:grid-cols-[1fr_22rem]" method="post" action="<?= $url('/products') ?>" accept-charset="UTF-8">
        <section class="surface-panel">
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block sm:col-span-2">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Nom du produit</span>
                    <input class="field-control" name="nom" type="text" placeholder="Ex. Chargeur rapide USB-C">
                </label>
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Référence interne</span>
                    <input class="field-control" name="ref" type="text" placeholder="ELC-001">
                </label>
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Code-barres</span>
                    <input class="field-control" name="code_barre" type="text" placeholder="Scanner ou saisir">
                </label>
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
            <button class="btn-primary mt-6" type="submit">Enregistrer le produit</button>
        </aside>
    </form>
</section>
