<?php
$plan = is_array($plan ?? null) ? $plan : [];
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$money = static fn ($value): string => number_format((float) $value, 2, ',', ' ') . ' USD';
$limitText = static function ($value, string $singular, string $plural): string {
    $value = $value === null || $value === '' ? null : (int) $value;

    if ($value === null || $value <= 0) {
        return ucfirst($plural) . ' illimites';
    }

    return $value . ' ' . ($value === 1 ? $singular : $plural);
};
$features = array_values(array_filter(array_map('trim', preg_split('/\R+/', trim((string) ($plan['description'] ?? ''))) ?: [])));
?>
<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Plan abonnement</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950"><?= $safe($plan['nom'] ?? 'Plan') ?></h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Code <?= $safe($plan['code'] ?? '') ?>, <?= (int) ($plan['subscriptions_count'] ?? 0) ?> abonnement(s) lie(s).</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a class="btn-secondary w-auto px-4" href="<?= $url('/saas-admin/abonnements') ?>">Retour</a>
            <a class="btn-primary w-auto px-4" href="<?= $url('/saas-admin/abonnements/plans/' . (int) ($plan['id'] ?? 0) . '/edit') ?>">Modifier</a>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="stat-card"><p class="text-sm text-slate-500">Prix mensuel</p><p class="mt-2 text-2xl font-bold"><?= $money($plan['prix_mensuel_usd'] ?? 0) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Boutiques</p><p class="mt-2 text-2xl font-bold text-teal-700"><?= $safe($limitText($plan['limite_boutiques'] ?? null, 'boutique', 'boutiques')) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Utilisateurs</p><p class="mt-2 text-2xl font-bold text-blue-700"><?= $safe($limitText($plan['limite_utilisateurs'] ?? null, 'utilisateur', 'utilisateurs')) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Statut</p><p class="mt-2 text-2xl font-bold <?= (int) ($plan['actif'] ?? 0) === 1 ? 'text-teal-700' : 'text-red-700' ?>"><?= (int) ($plan['actif'] ?? 0) === 1 ? 'Actif' : 'Inactif' ?></p></article>
    </div>

    <section class="surface-panel">
        <div class="panel-header">
            <div>
                <h2 class="font-bold text-slate-950">Fonctionnalites du plan</h2>
                <p class="mt-1 text-sm text-slate-500">Les lignes ci-dessous proviennent de la description du plan.</p>
            </div>
            <form method="post" action="<?= $url('/saas-admin/abonnements/plans/' . (int) ($plan['id'] ?? 0) . '/delete') ?>" data-confirm-form>
                <button
                    class="h-11 rounded-lg bg-red-50 px-4 text-sm font-bold text-red-700 hover:bg-red-100"
                    type="button"
                    data-confirm
                    data-confirm-title="Supprimer ce plan ?"
                    data-confirm-message="Si le plan est deja utilise, il sera desactive pour proteger les abonnements existants."
                    data-confirm-accept="Oui, supprimer"
                    data-confirm-progress="Suppression..."
                >Supprimer</button>
            </form>
        </div>
        <?php if ($features === []): ?>
            <p class="mt-5 rounded-lg bg-slate-50 px-4 py-6 text-sm font-semibold text-slate-500">Aucune fonctionnalite detaillee.</p>
        <?php else: ?>
            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($features as $feature): ?>
                    <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700"><?= $safe($feature) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>
