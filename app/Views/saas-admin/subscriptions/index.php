<?php
$shops = is_array($shops ?? null) ? $shops : [];
$plans = is_array($plans ?? null) ? $plans : [];
$features = is_array($features ?? null) ? $features : [];
$featureIdsByShop = is_array($featureIdsByShop ?? null) ? $featureIdsByShop : [];
$assignments = is_array($assignments ?? null) ? $assignments : [];
$moduleCatalog = is_array($moduleCatalog ?? null) ? $moduleCatalog : [];
$planAssignments = is_array($assignments['plans'] ?? null) ? $assignments['plans'] : [];
$categoryPlanAssignments = is_array($assignments['category_plans'] ?? null) ? $assignments['category_plans'] : [];
$featuresById = [];
foreach ($features as $feature) { $featuresById[(int) ($feature['id'] ?? 0)] = $feature; }
$safe = static fn ($value, string $fallback = ''): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$money = static fn ($value): string => number_format((float) $value, 2, ',', ' ') . ' USD';
$limitText = static function ($value, string $singular, string $plural): string {
    $limit = (int) ($value ?? 0);
    return $limit > 0 ? $limit . ' ' . ($limit === 1 ? $singular : $plural) : ucfirst($plural) . ' illimites';
};
$activeSubscriptions = count(array_filter($shops, static fn (array $shop): bool => in_array((string) ($shop['subscription_status'] ?? ''), ['trial', 'active'], true)));
$plansById = [];
foreach ($plans as $plan) { $plansById[(int) ($plan['id'] ?? 0)] = $plan; }
?>

<section class="space-y-5" data-subscriptions-page>
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-950 via-slate-900 to-amber-950 p-6 text-white shadow-xl sm:p-8">
        <div class="absolute -right-20 -top-24 h-64 w-64 rounded-full bg-amber-400/20 blur-3xl"></div>
        <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-xs font-bold uppercase tracking-[.18em] text-amber-300">Offre SaaS et acces modules</p>
                <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Plans et abonnements</h1>
                <p class="mt-3 text-sm leading-6 text-slate-300">Configurez les limites commerciales, visualisez les vrais modules issus du catalogue Fonctionnalites et affectez les plans aux boutiques.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <a class="btn-secondary w-full sm:w-auto" href="#shops">Abonnements boutiques</a>
                <a class="btn-primary w-full sm:w-auto" href="<?= $url('/saas-admin/fonctionnalites') ?>">Catalogue fonctionnalites</a>
            </div>
        </div>
    </div>

    <nav class="flex gap-2 overflow-x-auto rounded-xl border border-slate-200 bg-white p-2 shadow-sm">
        <?php foreach ([['plans','Plans commerciaux'],['new-plan','Nouveau plan'],['shops','Boutiques']] as [$anchor,$label]): ?><a class="whitespace-nowrap rounded-lg bg-slate-50 px-4 py-2 text-sm font-bold text-slate-600 transition hover:bg-amber-50 hover:text-amber-700" href="#<?= $anchor ?>"><?= $label ?></a><?php endforeach; ?>
    </nav>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="stat-card"><p class="text-sm text-slate-500">Plans actifs</p><p class="mt-2 text-3xl font-black"><?= count($plans) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Boutiques</p><p class="mt-2 text-3xl font-black text-blue-700"><?= count($shops) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Abonnements actifs / essai</p><p class="mt-2 text-3xl font-black text-teal-700"><?= $activeSubscriptions ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Modules disponibles</p><p class="mt-2 text-3xl font-black text-amber-700"><?= count($features) ?></p></article>
    </div>

    <section id="plans" class="scroll-mt-24 space-y-4">
        <div class="surface-panel"><div class="panel-header"><div><h2 class="text-xl font-black text-slate-950">Plans commerciaux et modules reels</h2><p class="mt-1 text-sm text-slate-500">Les modules ci-dessous proviennent directement des affectations de la page Fonctionnalites.</p></div></div></div>
        <div class="grid gap-4 xl:grid-cols-2">
            <?php foreach ($plans as $plan): ?>
                <?php
                $planId = (int) ($plan['id'] ?? 0);
                $moduleIds = array_map('intval', (array) ($planAssignments[$planId] ?? []));
                $recommended = strtolower((string) ($plan['code'] ?? '')) === 'pro';
                ?>
                <article class="relative overflow-hidden rounded-2xl border bg-white shadow-sm <?= $recommended ? 'border-amber-300 ring-2 ring-amber-200' : 'border-slate-200' ?>">
                    <div class="h-1.5 bg-gradient-to-r <?= $recommended ? 'from-amber-400 via-orange-500 to-rose-500' : 'from-slate-700 via-slate-500 to-slate-300' ?>"></div>
                    <div class="p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div><div class="flex flex-wrap items-center gap-2"><h3 class="text-xl font-black text-slate-950"><?= $safe($plan['nom'] ?? '') ?></h3><?php if ($recommended): ?><span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-800">Recommande</span><?php endif; ?></div><p class="mt-1 text-xs font-bold uppercase tracking-[.14em] text-slate-400"><?= $safe($plan['code'] ?? '') ?> · <?= (int) ($plan['subscriptions_count'] ?? 0) ?> abonnement(s)</p></div>
                            <div class="text-right"><strong class="text-2xl font-black text-slate-950"><?= $money($plan['prix_mensuel_usd'] ?? 0) ?></strong><p class="text-xs text-slate-400">par mois</p></div>
                        </div>
                        <div class="mt-4 grid gap-2 sm:grid-cols-3"><span class="rounded-lg bg-slate-50 px-3 py-2 text-sm font-bold text-slate-700"><?= $safe($limitText($plan['limite_boutiques'] ?? null, 'boutique', 'boutiques')) ?></span><span class="rounded-lg bg-slate-50 px-3 py-2 text-sm font-bold text-slate-700"><?= $safe($limitText($plan['limite_utilisateurs'] ?? null, 'utilisateur', 'utilisateurs')) ?></span><span class="rounded-lg bg-slate-50 px-3 py-2 text-sm font-bold text-slate-700"><?= $safe($limitText($plan['limite_produits'] ?? null, 'produit', 'produits')) ?></span></div>

                        <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-3"><p class="text-xs font-bold uppercase tracking-[.14em] text-slate-400">Modules inclus</p><span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-600"><?= count($moduleIds) ?> / <?= count($features) ?></span></div>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <?php foreach ($moduleIds as $featureId): ?>
                                    <?php if (isset($featuresById[$featureId])): $feature = $featuresById[$featureId]; $code = (string) ($feature['code'] ?? ''); $catalog = $moduleCatalog[$code] ?? []; ?>
                                        <details class="rounded-lg border border-slate-200 bg-white p-3"><summary class="cursor-pointer text-sm font-bold text-slate-800"><?= $safe($catalog['label'] ?? $feature['nom'] ?? $code) ?><code class="ml-2 text-xs text-slate-400"><?= $safe($code) ?></code></summary><ul class="mt-3 space-y-1.5"><?php foreach ((array) ($catalog['capabilities'] ?? []) as $capability): ?><li class="flex gap-2 text-xs leading-5 text-slate-600"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-teal-500"></span><?= $safe($capability) ?></li><?php endforeach; ?></ul></details>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php if ($moduleIds === []): ?><p class="text-sm font-semibold text-amber-700">Aucun module affecte a ce plan.</p><?php endif; ?>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-2"><a class="btn-secondary h-10 w-auto px-3 text-xs" href="<?= $url('/saas-admin/abonnements/plans/' . $planId) ?>">Details</a><a class="btn-secondary h-10 w-auto px-3 text-xs" href="<?= $url('/saas-admin/abonnements/plans/' . $planId . '/edit') ?>">Modifier</a><a class="btn-primary h-10 w-auto px-3 text-xs" href="<?= $url('/saas-admin/fonctionnalites#plans') ?>">Gerer les modules</a><form method="post" action="<?= $url('/saas-admin/abonnements/plans/' . $planId . '/delete') ?>" data-confirm-form><button class="h-10 rounded-lg bg-red-50 px-3 text-xs font-bold text-red-700" type="button" data-confirm data-confirm-title="Supprimer ce plan ?" data-confirm-message="Un plan utilise sera desactive afin de proteger ses abonnements." data-confirm-accept="Oui, supprimer">Supprimer</button></form></div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="new-plan" class="surface-panel scroll-mt-24">
        <details><summary class="cursor-pointer text-xl font-black text-slate-950">Creer un nouveau plan commercial</summary>
            <form class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4" method="post" action="<?= $url('/saas-admin/abonnements/plans') ?>">
                <label class="text-sm font-semibold">Nom<input class="field-control mt-2" name="nom" required></label><label class="text-sm font-semibold">Code<input class="field-control mt-2" name="code" required></label><label class="text-sm font-semibold">Prix mensuel USD<input class="field-control mt-2" name="prix_mensuel_usd" type="number" min="0" step="0.01" value="0"></label><label class="flex items-center gap-2 self-end rounded-lg border border-slate-200 px-4 py-3 text-sm font-bold"><input type="checkbox" name="actif" value="1" checked> Plan actif</label>
                <label class="text-sm font-semibold">Limite boutiques<input class="field-control mt-2" name="limite_boutiques" type="number" min="1" placeholder="Vide = illimite"></label><label class="text-sm font-semibold">Limite utilisateurs<input class="field-control mt-2" name="limite_utilisateurs" type="number" min="1" placeholder="Vide = illimite"></label><label class="text-sm font-semibold">Limite produits<input class="field-control mt-2" name="limite_produits" type="number" min="1" placeholder="Vide = illimite"></label>
                <textarea class="field-control min-h-24 md:col-span-2 xl:col-span-4" name="description" placeholder="Description commerciale du plan"></textarea><p class="text-sm text-slate-500 md:col-span-2 xl:col-span-3">Les modules seront affectes apres creation depuis la page Fonctionnalites.</p><button class="btn-primary xl:justify-self-end" type="submit">Ajouter le plan</button>
            </form>
        </details>
    </section>

    <section id="shops" class="scroll-mt-24 space-y-4">
        <div class="surface-panel"><div class="panel-header"><div><h2 class="text-xl font-black text-slate-950">Abonnements par boutique</h2><p class="mt-1 text-sm text-slate-500">Affectez un plan autorise par la categorie et controlez les modules effectivement disponibles.</p></div><input class="field-control w-full sm:max-w-xs" type="search" placeholder="Rechercher une boutique" data-shop-search></div></div>
        <div class="grid gap-4" data-shop-list>
            <?php foreach ($shops as $shop): ?>
                <?php
                $shopId = (int) ($shop['id'] ?? 0); $currentPlanId = (int) ($shop['plan_id'] ?? 0); $selectedFeatures = array_map('intval', (array) ($featureIdsByShop[$shopId] ?? [])); $categoryId = (int) ($shop['category_id'] ?? 0); $allowedPlanIds = array_map('intval', (array) ($categoryPlanAssignments[$categoryId] ?? [])); $availablePlans = array_values(array_filter($plans, static fn (array $plan): bool => in_array((int) ($plan['id'] ?? 0), $allowedPlanIds, true))); $status = (string) ($shop['subscription_status'] ?? 'non_configure');
                ?>
                <form id="shop-<?= $shopId ?>" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" method="post" action="<?= $url('/saas-admin/abonnements/boutiques/' . $shopId) ?>" data-shop-card data-search="<?= $safe(strtolower((string) ($shop['nom'] ?? '') . ' ' . ($shop['category_name'] ?? '') . ' ' . ($shop['plan_name'] ?? ''))) ?>">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"><div><div class="flex flex-wrap items-center gap-2"><h3 class="text-lg font-black text-slate-950"><?= $safe($shop['nom'] ?? '') ?></h3><span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700"><?= $safe($shop['category_name'] ?? 'Sans categorie') ?></span><span class="rounded-full px-3 py-1 text-xs font-bold <?= in_array($status, ['trial','active'], true) ? 'bg-teal-50 text-teal-700' : 'bg-amber-50 text-amber-700' ?>"><?= $safe($status) ?></span></div><p class="mt-2 text-sm text-slate-500">Plan actuel : <strong class="text-slate-800"><?= $safe($shop['plan_name'] ?? 'Sans plan') ?></strong></p></div><button class="btn-primary w-full lg:w-auto" type="submit">Enregistrer l abonnement</button></div>
                    <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4"><label class="text-sm font-semibold">Plan<select class="field-control mt-2" name="plan_id" required><option value="">Selectionner</option><?php foreach ($availablePlans as $plan): ?><option value="<?= (int) ($plan['id'] ?? 0) ?>" <?= $currentPlanId === (int) ($plan['id'] ?? 0) ? 'selected' : '' ?>><?= $safe($plan['nom'] ?? '') ?> - <?= $money($plan['prix_mensuel_usd'] ?? 0) ?></option><?php endforeach; ?></select></label><label class="text-sm font-semibold">Statut<select class="field-control mt-2" name="statut"><?php foreach (['trial','active','past_due','suspended','cancelled'] as $option): ?><option value="<?= $option ?>" <?= $status === $option ? 'selected' : '' ?>><?= $option ?></option><?php endforeach; ?></select></label><label class="text-sm font-semibold">Debut<input class="field-control mt-2" name="date_debut" type="date" value="<?= $safe($shop['date_debut'] ?? date('Y-m-d')) ?>"></label><label class="text-sm font-semibold">Fin<input class="field-control mt-2" name="date_fin" type="date" value="<?= $safe($shop['date_fin'] ?? '') ?>"></label></div>
                    <label class="mt-3 inline-flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold"><input type="checkbox" name="renouvellement_auto" value="1" <?= (int) ($shop['renouvellement_auto'] ?? 1) === 1 ? 'checked' : '' ?>> Renouvellement automatique</label>
                    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4"><div class="flex items-center justify-between"><p class="text-xs font-bold uppercase tracking-[.14em] text-slate-400">Modules effectifs</p><span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-600"><?= count($selectedFeatures) ?> module(s)</span></div><div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-4"><?php foreach ($selectedFeatures as $featureId): ?><?php if (isset($featuresById[$featureId])): $feature=$featuresById[$featureId]; $code=(string)($feature['code']??''); ?><div class="rounded-lg border border-slate-200 bg-white p-3"><p class="text-sm font-bold text-slate-800"><?= $safe($moduleCatalog[$code]['label'] ?? $feature['nom'] ?? '') ?></p><code class="text-xs text-slate-400"><?= $safe($code) ?></code></div><?php endif; ?><?php endforeach; ?><?php if ($selectedFeatures === []): ?><p class="text-sm font-semibold text-amber-700">Aucun module effectif. Verifiez le plan et son autorisation pour cette categorie.</p><?php endif; ?></div></div>
                    <textarea class="field-control mt-4 min-h-20" name="notes" placeholder="Notes internes"><?= $safe($shop['subscription_notes'] ?? '') ?></textarea>
                </form>
            <?php endforeach; ?>
        </div>
    </section>
</section>

<script>
const shopSearch = document.querySelector('[data-shop-search]');
const shopCards = Array.from(document.querySelectorAll('[data-shop-card]'));
shopSearch?.addEventListener('input', () => { const query = shopSearch.value.trim().toLowerCase(); shopCards.forEach((card) => card.classList.toggle('hidden', query !== '' && !(card.dataset.search || '').includes(query))); });
</script>
