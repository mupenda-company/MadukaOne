<?php
$plan = is_array($plan ?? null) ? $plan : [];
$safe = static fn ($value, string $fallback = ''): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
?>
<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Edition plan</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Modifier <?= $safe($plan['nom'] ?? 'le plan') ?></h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Ajustez le prix, les limites et les fonctionnalites affichees dans l espace SaaS.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a class="btn-secondary w-auto px-4" href="<?= $url('/saas-admin/abonnements/plans/' . (int) ($plan['id'] ?? 0)) ?>">Details</a>
            <a class="btn-secondary w-auto px-4" href="<?= $url('/saas-admin/abonnements') ?>">Retour</a>
        </div>
    </div>

    <form class="surface-panel space-y-5" method="post" action="<?= $url('/saas-admin/abonnements/plans/' . (int) ($plan['id'] ?? 0) . '/update') ?>">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700" for="plan_nom">Nom</label>
                <input class="field-control" id="plan_nom" name="nom" required maxlength="120" value="<?= $safe($plan['nom'] ?? '') ?>">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700" for="plan_code">Code</label>
                <input class="field-control" id="plan_code" name="code" required maxlength="80" value="<?= $safe($plan['code'] ?? '') ?>">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700" for="prix_mensuel_usd">Prix mensuel USD</label>
                <input class="field-control" id="prix_mensuel_usd" name="prix_mensuel_usd" type="number" min="0" step="0.01" value="<?= $safe($plan['prix_mensuel_usd'] ?? '0') ?>">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700" for="limite_boutiques">Limite boutiques</label>
                <input class="field-control" id="limite_boutiques" name="limite_boutiques" type="number" min="1" value="<?= $safe($plan['limite_boutiques'] ?? '') ?>" placeholder="Vide = illimite">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700" for="limite_utilisateurs">Limite utilisateurs</label>
                <input class="field-control" id="limite_utilisateurs" name="limite_utilisateurs" type="number" min="1" value="<?= $safe($plan['limite_utilisateurs'] ?? '') ?>" placeholder="Vide = illimite">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700" for="limite_produits">Limite produits</label>
                <input class="field-control" id="limite_produits" name="limite_produits" type="number" min="1" value="<?= $safe($plan['limite_produits'] ?? '') ?>" placeholder="Vide = illimite">
            </div>
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700" for="description">Fonctionnalites</label>
            <textarea class="field-control min-h-64" id="description" name="description" placeholder="Une fonctionnalite par ligne"><?= $safe($plan['description'] ?? '') ?></textarea>
        </div>

        <label class="inline-flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700">
            <input class="h-4 w-4" type="checkbox" name="actif" value="1" <?= (int) ($plan['actif'] ?? 0) === 1 ? 'checked' : '' ?>>
            Plan actif
        </label>

        <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
            <a class="btn-secondary w-full sm:w-auto" href="<?= $url('/saas-admin/abonnements') ?>">Annuler</a>
            <button class="btn-primary w-full sm:w-auto" type="submit">Enregistrer les modifications</button>
        </div>
    </form>

    <form class="surface-panel border-red-100 bg-red-50/40" method="post" action="<?= $url('/saas-admin/abonnements/plans/' . (int) ($plan['id'] ?? 0) . '/delete') ?>" data-confirm-form>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-red-900">Zone suppression</h2>
                <p class="mt-1 text-sm text-red-700">Un plan utilise par une boutique sera desactive, pas supprime physiquement.</p>
            </div>
            <button
                class="h-11 rounded-lg bg-red-700 px-4 text-sm font-bold text-white hover:bg-red-800"
                type="button"
                data-confirm
                data-confirm-title="Supprimer ce plan ?"
                data-confirm-message="Si le plan est deja utilise, il sera desactive pour proteger les abonnements existants."
                data-confirm-accept="Oui, supprimer"
                data-confirm-progress="Suppression..."
            >Supprimer le plan</button>
        </div>
    </form>
</section>
