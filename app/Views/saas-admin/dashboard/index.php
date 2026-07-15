<?php
$stats = is_array($stats ?? null) ? $stats : [];
$shops = is_array($shops ?? null) ? $shops : [];
$attentionShops = is_array($attentionShops ?? null) ? $attentionShops : [];
$subscriptions = is_array($subscriptions ?? null) ? $subscriptions : [];
$distributions = is_array($distributions ?? null) ? $distributions : [];
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$money = static fn ($value): string => number_format((float) $value, 2, ',', ' ') . ' USD';
$percent = static function ($value, $total): int {
    $total = max(1, (int) $total);

    return (int) round(((int) $value / $total) * 100);
};
$statusLabel = static fn (string $status): string => [
    'trial' => 'Essai',
    'active' => 'Actif',
    'past_due' => 'Impayes',
    'suspended' => 'Suspendu',
    'cancelled' => 'Annule',
    'non_configure' => 'Non configure',
][$status] ?? $status;
$shopTotal = max(1, (int) ($stats['shops'] ?? 0));
$activationRate = $percent($stats['active_shops'] ?? 0, $shopTotal);
$subscriptionRate = $percent($stats['active_subscriptions'] ?? 0, $shopTotal);
?>
<section class="space-y-5">
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Pilotage SaaS</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Tableau de bord de gestion globale</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">Vue de pilotage pour suivre le parc boutiques, les abonnements, les volumes d'activite, les alertes commerciales et les actions prioritaires.</p>
        </div>
        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
            <a class="btn-secondary w-full sm:w-auto" href="<?= $url('/saas-admin/abonnements') ?>">Piloter les abonnements</a>
            <a class="btn-primary w-full gap-2 sm:w-auto" href="<?= $url('/saas-admin/boutiques/create') ?>">Nouvelle boutique</a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="stat-card">
            <p class="text-sm text-slate-500">Boutiques actives</p>
            <p class="mt-2 text-3xl font-black text-teal-700"><?= (int) ($stats['active_shops'] ?? 0) ?><span class="text-base font-bold text-slate-400"> / <?= (int) ($stats['shops'] ?? 0) ?></span></p>
            <div class="mt-4 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-teal-600" style="width: <?= $activationRate ?>%"></div></div>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Abonnements OK</p>
            <p class="mt-2 text-3xl font-black text-blue-700"><?= (int) ($stats['active_subscriptions'] ?? 0) ?><span class="text-base font-bold text-slate-400"> / <?= (int) ($stats['shops'] ?? 0) ?></span></p>
            <div class="mt-4 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-blue-600" style="width: <?= $subscriptionRate ?>%"></div></div>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">MRR estime</p>
            <p class="mt-2 text-3xl font-black text-slate-950"><?= $money($stats['monthly_revenue'] ?? 0) ?></p>
            <p class="mt-3 text-xs font-semibold text-slate-500"><?= (int) ($stats['plans'] ?? 0) ?> plan(s) actif(s)</p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Chiffre d'affaires boutiques</p>
            <p class="mt-2 text-3xl font-black text-amber-700"><?= $money($stats['sales_revenue'] ?? 0) ?></p>
            <p class="mt-3 text-xs font-semibold text-slate-500"><?= (int) ($stats['sales'] ?? 0) ?> vente(s) enregistree(s)</p>
        </article>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="surface-panel">
            <p class="text-sm text-slate-500">Utilisateurs</p>
            <p class="mt-2 text-2xl font-bold"><?= (int) ($stats['users'] ?? 0) ?></p>
        </article>
        <article class="surface-panel">
            <p class="text-sm text-slate-500">Produits suivis</p>
            <p class="mt-2 text-2xl font-bold"><?= (int) ($stats['products'] ?? 0) ?></p>
        </article>
        <article class="surface-panel">
            <p class="text-sm text-slate-500">Fonctionnalites actives</p>
            <p class="mt-2 text-2xl font-bold"><?= (int) ($stats['features'] ?? 0) ?></p>
        </article>
        <article class="surface-panel">
            <p class="text-sm text-slate-500">Alertes commerciales</p>
            <p class="mt-2 text-2xl font-bold text-red-700"><?= (int) ($stats['past_due_subscriptions'] ?? 0) + (int) ($stats['suspended_subscriptions'] ?? 0) + (int) ($stats['suspended_shops'] ?? 0) ?></p>
        </article>
    </div>

    <div class="grid gap-5 xl:grid-cols-[1.25fr_.75fr]">
        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Boutiques a traiter</h2>
                    <p class="mt-1 text-sm text-slate-500">Priorite aux boutiques suspendues, sans abonnement ou avec echeance proche.</p>
                </div>
                <a class="btn-secondary" href="<?= $url('/saas-admin/boutiques') ?>">Toutes les boutiques</a>
            </div>
            <div class="mt-5 space-y-3">
                <?php foreach ($attentionShops as $shop): ?>
                    <?php $status = (string) ($shop['subscription_status'] ?? 'non_configure'); ?>
                    <div class="rounded-lg border border-slate-200 bg-white p-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0">
                                <p class="truncate font-bold text-slate-950"><?= $safe($shop['nom'] ?? '') ?></p>
                                <p class="mt-1 text-xs font-semibold text-slate-500"><?= $safe($shop['plan_name'] ?? 'Sans plan') ?> - fin <?= $safe($shop['date_fin'] ?? 'non definie') ?></p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-lg px-3 py-1 text-xs font-bold <?= (int) ($shop['actif'] ?? 0) === 1 ? 'bg-teal-50 text-teal-700' : 'bg-red-50 text-red-700' ?>"><?= (int) ($shop['actif'] ?? 0) === 1 ? 'Boutique active' : 'Boutique suspendue' ?></span>
                                <span class="rounded-lg bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700"><?= $safe($statusLabel($status)) ?></span>
                            </div>
                        </div>
                        <div class="mt-3 grid gap-2 text-xs font-semibold text-slate-500 sm:grid-cols-2">
                            <span><?= (int) ($shop['users_count'] ?? 0) ?> utilisateur(s)</span>
                            <span><?= (int) ($shop['products_count'] ?? 0) ?> produit(s)</span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if ($attentionShops === []): ?>
                    <p class="rounded-lg border border-dashed border-slate-200 p-6 text-center text-sm font-semibold text-slate-500">Aucune alerte prioritaire.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Actions rapides</h2>
                    <p class="mt-1 text-sm text-slate-500">Acces direct aux operations SaaS frequentes.</p>
                </div>
            </div>
            <div class="mt-5 grid gap-3">
                <a class="signal-row hover:bg-slate-50" href="<?= $url('/saas-admin/boutiques/create') ?>"><span>Creer une boutique</span><strong>Ouvrir</strong></a>
                <a class="signal-row hover:bg-slate-50" href="<?= $url('/saas-admin/utilisateurs') ?>"><span>Verifier les acces</span><strong>Ouvrir</strong></a>
                <a class="signal-row hover:bg-slate-50" href="<?= $url('/saas-admin/abonnements') ?>"><span>Affecter un plan</span><strong>Ouvrir</strong></a>
                <a class="signal-row hover:bg-slate-50" href="<?= $url('/saas-admin/fonctionnalites') ?>"><span>Activer des modules</span><strong>Ouvrir</strong></a>
                <a class="signal-row hover:bg-slate-50" href="<?= $url('/saas-admin/parametres') ?>"><span>Parametres SaaS</span><strong>Ouvrir</strong></a>
            </div>
        </section>
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        <?php foreach ([['title' => 'Repartition par plan', 'rows' => $distributions['plans'] ?? []], ['title' => 'Statuts abonnements', 'rows' => $distributions['statuses'] ?? []], ['title' => 'Categories boutiques', 'rows' => $distributions['categories'] ?? []]] as $block): ?>
            <section class="surface-panel">
                <h2 class="font-bold text-slate-950"><?= $safe($block['title']) ?></h2>
                <div class="mt-5 space-y-3">
                    <?php foreach ($block['rows'] as $row): ?>
                        <?php $share = $percent($row['total'] ?? 0, $shopTotal); ?>
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                                <span class="truncate font-semibold text-slate-700"><?= $safe($statusLabel((string) ($row['label'] ?? ''))) ?></span>
                                <strong><?= (int) ($row['total'] ?? 0) ?></strong>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-teal-600" style="width: <?= $share ?>%"></div></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (($block['rows'] ?? []) === []): ?>
                        <p class="rounded-lg border border-dashed border-slate-200 p-5 text-center text-sm font-semibold text-slate-500">Aucune donnee.</p>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Boutiques recentes</h2>
                    <p class="mt-1 text-sm text-slate-500">Volumes rapides par boutique.</p>
                </div>
                <a class="btn-secondary" href="<?= $url('/saas-admin/boutiques') ?>">Tout voir</a>
            </div>
            <div class="mt-5 space-y-3">
                <?php foreach ($shops as $shop): ?>
                    <div class="signal-row">
                        <span class="min-w-0">
                            <span class="block truncate font-semibold text-slate-950"><?= $safe($shop['nom'] ?? '') ?></span>
                            <span class="block text-xs text-slate-500"><?= (int) ($shop['users_count'] ?? 0) ?> utilisateur(s) - <?= (int) ($shop['products_count'] ?? 0) ?> produit(s) - <?= $money($shop['sales_total'] ?? 0) ?></span>
                        </span>
                        <span class="rounded-lg px-3 py-1 text-xs font-bold <?= (int) ($shop['actif'] ?? 0) === 1 ? 'bg-teal-50 text-teal-700' : 'bg-red-50 text-red-700' ?>"><?= (int) ($shop['actif'] ?? 0) === 1 ? 'Active' : 'Suspendue' ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if ($shops === []): ?><p class="rounded-lg border border-dashed border-slate-200 p-5 text-center text-sm font-semibold text-slate-500">Aucune boutique.</p><?php endif; ?>
            </div>
        </section>

        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Derniers abonnements</h2>
                    <p class="mt-1 text-sm text-slate-500">Lecture rapide des plans et echeances.</p>
                </div>
                <a class="btn-secondary" href="<?= $url('/saas-admin/abonnements') ?>">Gerer</a>
            </div>
            <div class="mt-5 space-y-3">
                <?php foreach ($subscriptions as $subscription): ?>
                    <?php $status = (string) ($subscription['statut'] ?? 'trial'); ?>
                    <div class="signal-row">
                        <span class="min-w-0">
                            <span class="block truncate font-semibold text-slate-950"><?= $safe($subscription['shop_name'] ?? '') ?></span>
                            <span class="block text-xs text-slate-500"><?= $safe($subscription['plan_name'] ?? 'Sans plan') ?> - fin <?= $safe($subscription['date_fin'] ?? 'non definie') ?></span>
                        </span>
                        <span class="rounded-lg bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700"><?= $safe($statusLabel($status)) ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if ($subscriptions === []): ?><p class="rounded-lg border border-dashed border-slate-200 p-5 text-center text-sm font-semibold text-slate-500">Aucun abonnement configure.</p><?php endif; ?>
            </div>
        </section>
    </div>
</section>
