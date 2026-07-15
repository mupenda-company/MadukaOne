<?php

$shop = is_array($shop ?? null) ? $shop : [];
$profile = is_array($shopCategoryProfile ?? null) ? $shopCategoryProfile : [];
$spaces = is_array($profile['spaces'] ?? null) ? $profile['spaces'] : [];
$focus = is_array($profile['focus'] ?? null) ? $profile['focus'] : [];
$enabledModules = is_array($enabledModules ?? null) ? $enabledModules : [];
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');

$icon = static function (string $name): string {
    $paths = [
        'activity' => '<path d="M4 12h4l2-6 4 12 2-6h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'open' => '<path d="M7 17 17 7m0 0H9m8 0v8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'check' => '<path d="m5 13 4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    ];

    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . ($paths[$name] ?? $paths['activity']) . '</svg>';
};
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Activite boutique</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950"><?= $safe($profile['activity_label'] ?? 'Administration activite') ?></h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                Interface adaptee a la categorie
                <strong class="text-slate-950"><?= $safe($profile['name'] ?? $shop['category_name'] ?? 'Boutiques') ?></strong>
                pour la boutique <?= $safe($shop['nom'] ?? '') ?>.
            </p>
        </div>
        <a class="btn-secondary gap-2" href="<?= $url('/shops/settings') ?>">
            <?= $icon('activity') ?>
            <span>Parametres</span>
        </a>
    </div>

    <div class="grid gap-5 xl:grid-cols-[1fr_22rem]">
        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Espaces disponibles</h2>
                    <p class="mt-1 text-sm text-slate-500"><?= $safe($profile['description'] ?? $shop['category_description'] ?? '') ?></p>
                </div>
                <span class="rounded-lg bg-teal-50 px-3 py-1 text-xs font-bold text-teal-700"><?= $safe($profile['primary_unit'] ?? 'Article') ?></span>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-2">
                <?php foreach ($spaces as $space): ?>
                    <a class="group rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-teal-300 hover:shadow-md" href="<?= $url((string) ($space['href'] ?? '/dashboard')) ?>">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="font-bold text-slate-950"><?= $safe($space['label'] ?? '') ?></h3>
                                <p class="mt-2 text-sm leading-6 text-slate-500"><?= $safe($space['description'] ?? '') ?></p>
                            </div>
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-700 transition group-hover:bg-teal-50 group-hover:text-teal-700"><?= $icon('open') ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <aside class="space-y-5">
            <section class="surface-panel">
                <h2 class="font-bold text-slate-950">Modules actifs</h2>
                <?php if ($enabledModules === []): ?>
                    <p class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm font-semibold text-amber-800">
                        Aucun module actif detecte pour l abonnement actuel.
                    </p>
                <?php else: ?>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <?php foreach ($enabledModules as $module): ?>
                            <span class="rounded-lg bg-teal-50 px-3 py-1 text-xs font-bold text-teal-700">
                                <?= $safe($module['nom'] ?? $module['code'] ?? '') ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="surface-panel">
                <h2 class="font-bold text-slate-950">Priorites de gestion</h2>
                <div class="mt-4 space-y-2">
                    <?php foreach ($focus as $item): ?>
                        <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-teal-50 text-teal-700"><?= $icon('check') ?></span>
                            <span><?= $safe($item) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="surface-panel">
                <h2 class="font-bold text-slate-950">Categorie actuelle</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="rounded-lg bg-slate-50 p-3">
                        <dt class="font-semibold text-slate-500">Nom</dt>
                        <dd class="mt-1 font-bold text-slate-950"><?= $safe($profile['name'] ?? '') ?></dd>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <dt class="font-semibold text-slate-500">Code</dt>
                        <dd class="mt-1 font-mono text-slate-950"><?= $safe($profile['slug'] ?? '') ?></dd>
                    </div>
                </dl>
            </section>
        </aside>
    </div>
</section>
