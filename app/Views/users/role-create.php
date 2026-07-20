<?php

$permissionGroups = is_array($permissionGroups ?? null) ? $permissionGroups : [];
$roles = is_array($roles ?? null) ? $roles : [];
$roleStats = is_array($roleStats ?? null) ? $roleStats : [];
$planModules = is_array($planModules ?? null) ? $planModules : [];
$planSubscription = is_array($planSubscription ?? null) ? $planSubscription : null;
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');

$permissionItems = static function ($rawPermissions): array {
    $rawPermissions = trim((string) ($rawPermissions ?? ''));

    if ($rawPermissions === '') {
        return [];
    }

    $decoded = json_decode($rawPermissions, true);

    if (!is_array($decoded)) {
        return [$rawPermissions => true];
    }

    return $decoded;
};

$icon = static function (string $name): string {
    $paths = [
        'arrow' => '<path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'save' => '<path d="M5 4h12l2 2v14H5V4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M8 4v6h8V4M8 20v-6h8v6" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'shield' => '<path d="M12 3 5 6v5c0 4.2 2.7 8 7 10 4.3-2 7-5.8 7-10V6l-7-3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'key' => '<path d="M15 7a4 4 0 1 0-2.7 3.8L15 13.5V16h2.5v2.5H20V16l-5-5m-6-1h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'users' => '<path d="M16 19c0-2.2-1.8-4-4-4H8c-2.2 0-4 1.8-4 4m12-7a3 3 0 1 0 0-6m4 13c0-1.9-1.3-3.5-3-3.9M10 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
    ];

    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . ($paths[$name] ?? $paths['shield']) . '</svg>';
};
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Administration</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Ajouter un role</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Créez un profil d’accès avec uniquement les permissions disponibles dans le plan d’abonnement actif.
            </p>
        </div>
        <a class="btn-secondary gap-2" href="<?= $url('/roles') ?>">
            <?= $icon('arrow') ?>
            <span>Retour</span>
        </a>
    </div>

    <form class="grid gap-5 xl:grid-cols-[1fr_22rem]" method="post" action="<?= $url('/roles') ?>" accept-charset="UTF-8">
        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Informations du role</h2>
                    <p class="mt-1 text-sm text-slate-500">Nom visible dans l'administration et permissions associees.</p>
                </div>
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-teal-50 text-teal-700"><?= $icon('shield') ?></span>
            </div>

            <div class="mt-5 space-y-5">
                <label class="space-y-2">
                    <span class="text-sm font-semibold text-slate-700">Nom du role</span>
                    <input class="field-control" name="nom" type="text" maxlength="50" required placeholder="Ex: Responsable stock">
                </label>

                <div>
                    <div class="flex flex-col gap-3 rounded-xl border border-blue-100 bg-blue-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div><p class="text-xs font-bold uppercase tracking-[.12em] text-blue-600">Plan en cours</p><p class="mt-1 font-black text-blue-950"><?= $safe($planSubscription['plan_name'] ?? 'Aucun plan actif') ?></p></div>
                        <span class="w-fit rounded-full bg-white px-3 py-1.5 text-xs font-bold text-blue-700"><?= count($planModules) ?> module(s) actif(s)</span>
                    </div>
                    <p class="mt-5 text-sm font-semibold text-slate-700">Permissions du plan</p>
                    <p class="mt-1 text-sm text-slate-500">La liste est générée depuis les fonctionnalités réellement attribuées à l’abonnement.</p>

                    <div class="mt-4 grid gap-4 lg:grid-cols-3">
                        <?php foreach ($permissionGroups as $group): ?>
                            <fieldset class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <legend class="px-1 text-sm font-bold text-slate-950"><?= $safe($group['label'] ?? 'Groupe') ?></legend>
                                <div class="mt-3 space-y-3">
                                    <?php foreach (($group['items'] ?? []) as $permission => $label): ?>
                                        <label class="flex items-start gap-3 rounded-lg bg-white p-3 shadow-sm">
                                            <input class="mt-1 h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-600" name="permissions[]" type="checkbox" value="<?= $safe((string) $permission) ?>">
                                            <span>
                                                <span class="block text-sm font-semibold text-slate-900"><?= $safe((string) $label) ?></span>
                                                <span class="block text-xs text-slate-500"><?= $safe((string) $permission) ?></span>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($permissionGroups === []): ?><div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">Aucune permission n’est disponible. Vérifiez le statut de l’abonnement et les fonctionnalités attribuées à son plan.</div><?php endif; ?>
                </div>
            </div>
        </section>

        <aside class="space-y-5">
            <section class="surface-panel">
                <div class="flex items-start gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-700"><?= $icon('key') ?></span>
                    <div>
                        <h2 class="font-bold text-slate-950">Contrôle du plan</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">
                            Le serveur refuse automatiquement toute permission qui ne fait pas partie des modules du plan actif.
                        </p>
                    </div>
                </div>
            </section>

            <section class="surface-panel">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="font-bold text-slate-950">Roles existants</h2>
                        <p class="mt-1 text-sm text-slate-500"><?= (int) ($roleStats['total'] ?? count($roles)) ?> role(s) deja disponibles.</p>
                    </div>
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-blue-50 text-blue-700"><?= $icon('users') ?></span>
                </div>

                <div class="mt-4 max-h-[28rem] space-y-3 overflow-y-auto pr-1">
                    <?php if ($roles === []): ?>
                        <p class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-500">Aucun role existant.</p>
                    <?php endif; ?>

                    <?php foreach ($roles as $role): ?>
                        <?php $permissions = $permissionItems($role['permissions'] ?? null); ?>
                        <article class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="truncate text-sm font-bold text-slate-950"><?= $safe($role['nom'] ?? 'Role') ?></h3>
                                    <p class="mt-1 text-xs text-slate-500"><?= (int) ($role['users_count'] ?? 0) ?> utilisateur(s)</p>
                                </div>
                                <span class="rounded-full bg-white px-2 py-1 text-xs font-bold text-slate-600"><?= count($permissions) ?> perm.</span>
                            </div>

                            <?php if ($permissions !== []): ?>
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    <?php foreach (array_slice(array_keys($permissions), 0, 4) as $permission): ?>
                                        <span class="rounded-full bg-teal-50 px-2 py-1 text-[11px] font-bold text-teal-700"><?= $safe((string) $permission) ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($permissions) > 4): ?>
                                        <span class="rounded-full bg-slate-200 px-2 py-1 text-[11px] font-bold text-slate-600">+<?= count($permissions) - 4 ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <p class="mt-3 text-xs text-slate-500">Aucune permission.</p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="flex flex-col gap-3">
                <button class="btn-primary w-full gap-2" type="submit">
                    <?= $icon('save') ?>
                    <span>Enregistrer le role</span>
                </button>
                <a class="btn-secondary w-full" href="<?= $url('/roles') ?>">Annuler</a>
            </div>
        </aside>
    </form>
</section>
