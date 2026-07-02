
<?php $expenses = is_array($expenses ?? null) ? $expenses : []; ?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Finances</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Charges de la boutique</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Enregistrez les dépenses opérationnelles et gardez une lecture claire du coût quotidien.
            </p>
        </div>
        <div class="hero-action-panel">
            <p class="text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Dépenses visibles</p>
            <p class="mt-2 text-2xl font-bold text-slate-950">
                <?= number_format(array_sum(array_map(static fn (array $expense): float => (float) $expense['montant'], $expenses)), 2, ',', ' ') ?> USD
            </p>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-[23rem_1fr]">
        <form class="surface-panel h-fit space-y-4" method="post" action="<?= $url('/expenses') ?>" accept-charset="UTF-8">
            <div>
                <h2 class="font-bold text-slate-950">Nouvelle dépense</h2>
                <p class="mt-1 text-sm text-slate-500">Saisie rapide pour les charges courantes.</p>
            </div>
            <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">Titre</span>
                <input class="field-control" name="titre" type="text" placeholder="Ex. Carburant générateur">
            </label>
            <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">Catégorie</span>
                <select class="field-control" name="categorie">
                    <option value="transport">Transport</option>
                    <option value="facture">Électricité / facture</option>
                    <option value="loyer">Loyer</option>
                    <option value="salaire">Salaire</option>
                    <option value="perte_avarie">Perte ou avarie</option>
                    <option value="autre">Autre</option>
                </select>
            </label>
            <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">Montant</span>
                <input class="field-control" name="montant" type="number" min="0" step="0.01" placeholder="0.00">
            </label>
            <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">Date</span>
                <input class="field-control" name="date_depense" type="date" value="<?= date('Y-m-d') ?>">
            </label>
            <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">Description</span>
                <textarea class="field-control min-h-24" name="description" placeholder="Note interne"></textarea>
            </label>
            <button class="btn-primary" type="submit">Ajouter la dépense</button>
        </form>

        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Historique des charges</h2>
                    <p class="mt-1 text-sm text-slate-500">Liste statique prête pour pagination et filtres.</p>
                </div>
                <button class="btn-secondary" type="button">Exporter</button>
            </div>

            <div class="mt-5 space-y-3">
                <?php foreach ($expenses as $expense): ?>
                    <article class="signal-row">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-950"><?= htmlspecialchars((string) $expense['titre'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="mt-1 text-xs text-slate-500">
                                <?= htmlspecialchars((string) $expense['categorie'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) $expense['date_depense'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) $expense['user'], ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>
                        <strong class="shrink-0 text-right"><?= number_format((float) $expense['montant'], 2, ',', ' ') ?> USD</strong>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</section>
