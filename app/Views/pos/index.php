
<?php $products = is_array($products ?? null) ? $products : []; ?>

<section class="space-y-5" data-pos-root data-pos-endpoint="<?= $url('/pos/sale') ?>">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Caisse</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Vente POS</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Interface rapide pour téléphone et comptoir : sélectionnez, contrôlez le panier et encaissez.
            </p>
        </div>
        <div class="hero-action-panel">
            <p class="text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Total panier</p>
            <p class="mt-2 text-3xl font-bold text-slate-950" data-pos-total>0,00 USD</p>
            <a class="mt-4 inline-flex text-sm font-bold text-teal-700 hover:text-teal-900" href="<?= $url('/sales') ?>">Voir les ventes</a>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-[1fr_24rem]">
        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Produits disponibles</h2>
                    <p class="mt-1 text-sm text-slate-500">Touchez un article pour l’ajouter au panier.</p>
                </div>
                <input class="field-control max-w-xs" type="search" placeholder="Chercher un article" data-pos-search>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($products as $product): ?>
                    <button
                        class="rounded-xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md"
                        type="button"
                        data-pos-product
                        data-id="<?= (int) $product['id'] ?>"
                        data-name="<?= htmlspecialchars((string) $product['nom'], ENT_QUOTES, 'UTF-8') ?>"
                        data-ref="<?= htmlspecialchars((string) $product['ref'], ENT_QUOTES, 'UTF-8') ?>"
                        data-price="<?= (float) $product['prix_vente'] ?>"
                        data-stock="<?= (int) $product['quantite_stock'] ?>"
                    >
                        <span class="block text-xs font-semibold uppercase tracking-[.14em] text-slate-400"><?= htmlspecialchars((string) $product['ref'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="mt-2 block font-bold text-slate-950"><?= htmlspecialchars((string) $product['nom'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="mt-4 flex items-end justify-between gap-3">
                            <span class="text-xl font-bold text-teal-700"><?= number_format((float) $product['prix_vente'], 2, ',', ' ') ?> USD</span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Stock <?= (int) $product['quantite_stock'] ?></span>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>
        </section>

        <aside class="surface-panel h-fit xl:sticky xl:top-20">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Panier</h2>
                    <p class="mt-1 text-sm text-slate-500" data-pos-count>0 article</p>
                </div>
                <button class="btn-secondary" type="button" data-pos-clear>Vider</button>
            </div>

            <div class="mt-5 max-h-[26rem] space-y-3 overflow-y-auto pr-1" data-pos-cart>
                <div class="rounded-lg border border-dashed border-slate-200 p-5 text-center text-sm text-slate-500" data-pos-empty>
                    Aucun article dans le panier.
                </div>
            </div>

            <div class="mt-5 space-y-3 border-t border-slate-200 pt-5">
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Client</span>
                    <input class="field-control" type="text" data-pos-customer placeholder="Client comptant">
                </label>
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Mode de paiement</span>
                    <select class="field-control" data-pos-payment>
                        <option value="cash">Cash</option>
                        <option value="mobile_money">Mobile money</option>
                        <option value="carte">Carte</option>
                        <option value="credit">Crédit</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Montant reçu</span>
                    <input class="field-control" type="number" min="0" step="0.01" data-pos-received placeholder="0.00">
                </label>
                <div class="signal-row">
                    <span class="text-slate-500">Reste / monnaie</span>
                    <strong data-pos-change>0,00 USD</strong>
                </div>
                <p class="hidden rounded-lg bg-red-50 px-4 py-3 text-sm font-semibold text-red-700" data-pos-message></p>
                <button class="btn-primary" type="button" data-pos-submit>Valider la vente</button>
            </div>
        </aside>
    </div>
</section>
