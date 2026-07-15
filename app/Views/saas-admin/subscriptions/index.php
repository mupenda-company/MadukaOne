<?php
$shops = is_array($shops ?? null) ? $shops : [];
$plans = is_array($plans ?? null) ? $plans : [];
$features = is_array($features ?? null) ? $features : [];
$featureIdsByShop = is_array($featureIdsByShop ?? null) ? $featureIdsByShop : [];
$assignments = is_array($assignments ?? null) ? $assignments : [];
$categoryPlanAssignments = is_array($assignments['category_plans'] ?? null) ? $assignments['category_plans'] : [];
$featuresById = [];
foreach ($features as $feature) {
    $featuresById[(int) ($feature['id'] ?? 0)] = $feature;
}
$safe = static fn ($value, string $fallback = ''): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$money = static fn ($value): string => number_format((float) $value, 2, ',', ' ') . ' USD';
$limitText = static function ($value, string $singular, string $plural): string {
    $value = $value === null || $value === '' ? null : (int) $value;

    if ($value === null || $value <= 0) {
        return ucfirst($plural) . ' illimites';
    }

    return $value . ' ' . ($value === 1 ? $singular : $plural);
};
$planFeatures = static function ($description): array {
    $lines = preg_split('/\R+/', trim((string) ($description ?? ''))) ?: [];

    return array_values(array_filter(array_map('trim', $lines), static fn (string $line): bool => $line !== ''));
};
?>
<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Abonnements</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Plans, statuts et modules</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Pilotez le plan commercial, les dates, le statut et les fonctionnalites autorisees pour chaque boutique.</p>
        </div>
        <a class="btn-secondary" href="<?= $url('/saas-admin/fonctionnalites') ?>">Catalogue fonctionnalites</a>
    </div>

    <div class="grid gap-5 xl:grid-cols-[.85fr_1.15fr]">
        <section class="surface-panel">
            <div><h2 class="font-bold text-slate-950">Plans commerciaux</h2><p class="mt-1 text-sm text-slate-500">Ajoutez une offre SaaS facturable.</p></div>
            <form class="mt-5 space-y-4" method="post" action="<?= $url('/saas-admin/abonnements/plans') ?>">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                    <div><label class="mb-2 block text-sm font-semibold" for="plan_nom">Nom</label><input class="field-control" id="plan_nom" name="nom" required></div>
                    <div><label class="mb-2 block text-sm font-semibold" for="plan_code">Code</label><input class="field-control" id="plan_code" name="code" required></div>
                    <div><label class="mb-2 block text-sm font-semibold" for="limite_boutiques">Limite boutiques</label><input class="field-control" id="limite_boutiques" name="limite_boutiques" type="number" min="1" placeholder="Vide = illimite"></div>
                    <div><label class="mb-2 block text-sm font-semibold" for="prix_mensuel_usd">Prix mensuel USD</label><input class="field-control" id="prix_mensuel_usd" name="prix_mensuel_usd" type="number" min="0" step="0.01" value="0"></div>
                    <div><label class="mb-2 block text-sm font-semibold" for="limite_utilisateurs">Limite utilisateurs</label><input class="field-control" id="limite_utilisateurs" name="limite_utilisateurs" type="number" min="1"></div>
                    <div><label class="mb-2 block text-sm font-semibold" for="limite_produits">Limite produits</label><input class="field-control" id="limite_produits" name="limite_produits" type="number" min="1"></div>
                    <label class="mt-7 inline-flex h-12 items-center gap-3 rounded-lg border border-slate-200 px-4 text-sm font-semibold"><input class="h-4 w-4" type="checkbox" name="actif" value="1" checked> Actif</label>
                </div>
                <textarea class="field-control min-h-28" name="description" placeholder="Une fonctionnalite par ligne"></textarea>
                <button class="btn-primary" type="submit">Ajouter le plan</button>
            </form>

            <div class="mt-6 grid gap-3">
                <?php foreach ($plans as $plan): ?>
                    <?php $items = $planFeatures($plan['description'] ?? ''); ?>
                    <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm <?= ($plan['code'] ?? '') === 'pro' ? 'ring-1 ring-teal-500' : '' ?>">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-bold text-slate-950"><?= $safe($plan['nom'] ?? '') ?></h3>
                                    <?php if (($plan['code'] ?? '') === 'pro'): ?>
                                        <span class="rounded-lg bg-amber-100 px-2 py-1 text-[11px] font-black uppercase text-amber-800">Populaire</span>
                                    <?php endif; ?>
                                </div>
                                <p class="mt-1 text-xs font-semibold uppercase tracking-[.14em] text-slate-400"><?= $safe($plan['code'] ?? '') ?></p>
                            </div>
                            <strong class="shrink-0 text-xl font-black text-slate-950"><?= $money($plan['prix_mensuel_usd'] ?? 0) ?></strong>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a class="btn-secondary h-10 w-auto px-3 text-xs" href="<?= $url('/saas-admin/abonnements/plans/' . (int) ($plan['id'] ?? 0)) ?>">Details</a>
                            <a class="btn-secondary h-10 w-auto px-3 text-xs" href="<?= $url('/saas-admin/abonnements/plans/' . (int) ($plan['id'] ?? 0) . '/edit') ?>">Modifier</a>
                            <form method="post" action="<?= $url('/saas-admin/abonnements/plans/' . (int) ($plan['id'] ?? 0) . '/delete') ?>" data-confirm-form>
                                <button
                                    class="h-10 rounded-lg bg-red-50 px-3 text-xs font-bold text-red-700 hover:bg-red-100"
                                    type="button"
                                    data-confirm
                                    data-confirm-title="Supprimer ce plan ?"
                                    data-confirm-message="Si le plan est deja utilise, il sera desactive pour proteger les abonnements existants."
                                    data-confirm-accept="Oui, supprimer"
                                    data-confirm-progress="Suppression..."
                                >Supprimer</button>
                            </form>
                        </div>
                        <div class="mt-4 grid gap-2 sm:grid-cols-2">
                            <span class="rounded-lg bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700"><?= $safe($limitText($plan['limite_boutiques'] ?? null, 'boutique', 'boutiques')) ?></span>
                            <span class="rounded-lg bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700"><?= $safe($limitText($plan['limite_utilisateurs'] ?? null, 'utilisateur', 'utilisateurs')) ?></span>
                        </div>
                        <?php if ($items !== []): ?>
                            <ul class="mt-4 grid gap-2">
                                <?php foreach (array_slice($items, 0, 8) as $item): ?>
                                    <li class="rounded-lg bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-600"><?= $safe($item) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="surface-panel">
            <div class="panel-header"><div><h2 class="font-bold text-slate-950">Abonnements par boutique</h2><p class="mt-1 text-sm text-slate-500">Plan obligatoire, filtre par categorie, renouvellement et modules inclus dans le plan.</p></div></div>
            <div class="mt-5 space-y-4">
                <?php foreach ($shops as $shop): ?>
                    <?php
                    $shopId = (int) ($shop['id'] ?? 0);
                    $selectedFeatures = array_map('intval', $featureIdsByShop[$shopId] ?? []);
                    $currentPlanId = (int) ($shop['plan_id'] ?? 0);
                    $categoryId = (int) ($shop['category_id'] ?? 0);
                    $allowedPlanIds = array_map('intval', $categoryPlanAssignments[$categoryId] ?? []);
                    $availablePlans = array_values(array_filter($plans, static fn (array $plan): bool => in_array((int) ($plan['id'] ?? 0), $allowedPlanIds, true)));
                    ?>
                    <form id="shop-<?= (int) $shop['id'] ?>" class="rounded-lg border border-slate-200 p-4" method="post" action="<?= $url('/saas-admin/abonnements/boutiques/' . (int) $shop['id']) ?>">
                        <div class="panel-header">
                            <div><h3 class="font-bold text-slate-950"><?= $safe($shop['nom'] ?? '') ?></h3><p class="mt-1 text-sm text-slate-500"><?= $safe($shop['plan_name'] ?? 'Sans plan') ?> · <?= $safe($shop['subscription_status'] ?? 'non configure') ?></p></div>
                            <button class="btn-secondary" type="submit">Enregistrer</button>
                        </div>
                        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <div><label class="mb-2 block text-sm font-semibold">Plan</label><select class="field-control" name="plan_id" required><option value="">Selectionner un plan</option><?php foreach ($availablePlans as $plan): ?><option value="<?= (int) $plan['id'] ?>" <?= $currentPlanId === (int) ($plan['id'] ?? 0) ? 'selected' : '' ?>><?= $safe($plan['nom'] ?? '') ?> - <?= $money($plan['prix_mensuel_usd'] ?? 0) ?></option><?php endforeach; ?></select><?php if ($availablePlans === []): ?><p class="mt-2 text-xs font-semibold text-red-600">Aucun plan n'est classe dans cette categorie.</p><?php endif; ?></div>
                            <div><label class="mb-2 block text-sm font-semibold">Statut</label><select class="field-control" name="statut"><?php foreach (['trial','active','past_due','suspended','cancelled'] as $status): ?><option value="<?= $status ?>" <?= ($shop['subscription_status'] ?? 'trial') === $status ? 'selected' : '' ?>><?= $safe($status) ?></option><?php endforeach; ?></select></div>
                            <div><label class="mb-2 block text-sm font-semibold">Debut</label><input class="field-control" name="date_debut" type="date" value="<?= $safe($shop['date_debut'] ?? date('Y-m-d')) ?>"></div>
                            <div><label class="mb-2 block text-sm font-semibold">Fin</label><input class="field-control" name="date_fin" type="date" value="<?= $safe($shop['date_fin'] ?? '') ?>"></div>
                        </div>
                        <label class="mt-3 inline-flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold"><input class="h-4 w-4" type="checkbox" name="renouvellement_auto" value="1" <?= (int) ($shop['renouvellement_auto'] ?? 1) === 1 ? 'checked' : '' ?>> Renouvellement automatique</label>
                        <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs font-bold uppercase tracking-[.14em] text-slate-400">Fonctionnalites disponibles</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <?php foreach ($selectedFeatures as $featureId): ?>
                                    <?php if (isset($featuresById[$featureId])): ?>
                                        <span class="rounded-lg bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-sm"><?= $safe($featuresById[$featureId]['nom'] ?? '') ?></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php if ($selectedFeatures === []): ?>
                                    <span class="text-sm font-semibold text-slate-500">Aucune fonctionnalite active pour ce plan, ou le plan n'est pas classe dans la categorie de la boutique.</span>
                                <?php endif; ?>
                            </div>
                            <p class="mt-3 text-xs text-slate-500">Pour modifier ces modules, utilisez la page Fonctionnalites.</p>
                        </div>
                        <textarea class="field-control mt-3 min-h-20" name="notes" placeholder="Notes internes"><?= $safe($shop['subscription_notes'] ?? '') ?></textarea>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</section>
