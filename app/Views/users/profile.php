<?php

$profile = is_array($profile ?? null) ? $profile : (is_array($currentUser ?? null) ? $currentUser : []);
$hasPassword = (bool) ($hasPassword ?? false);
$userName = (string) ($profile['nom'] ?? 'Utilisateur');
$userEmail = (string) ($profile['email'] ?? '');
$userPhone = (string) ($profile['telephone'] ?? '');
$userRole = ucfirst(str_replace('_', ' ', (string) ($profile['role_name'] ?? $profile['role'] ?? $profile['role_legacy'] ?? 'agent')));
$authProvider = ucfirst((string) ($profile['auth_provider'] ?? 'local'));
$activeShopName = (string) ($activeShop['nom'] ?? 'Boutique active');
$userInitial = strtoupper(substr($userName, 0, 1) ?: 'U');
$safe = static fn ($value, string $fallback = ''): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div class="max-w-3xl">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Compte boutique</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Parametres du profil</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Modifiez votre profil utilisateur dans l'espace boutique. Ce profil reste separe du profil de gestion SaaS.
            </p>
        </div>

        <div class="hero-action-panel">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-400">Session active</p>
            <p class="mt-2 font-semibold text-slate-950"><?= $safe($activeShopName) ?></p>
            <p class="mt-1 text-sm text-slate-500"><?= $safe($userRole) ?></p>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-[22rem_1fr]">
        <aside class="surface-panel h-fit">
            <div class="flex items-center gap-4">
                <div class="grid h-16 w-16 place-items-center rounded-xl bg-slate-950 text-xl font-black text-white">
                    <?= $safe($userInitial) ?>
                </div>
                <div class="min-w-0">
                    <h2 class="truncate text-lg font-bold text-slate-950"><?= $safe($userName) ?></h2>
                    <p class="truncate text-sm text-slate-500"><?= $safe($userEmail, 'Email non defini') ?></p>
                </div>
            </div>

            <dl class="mt-6 space-y-3 text-sm">
                <div class="signal-row">
                    <dt class="text-slate-500">Role</dt>
                    <dd class="max-w-36 truncate text-right font-semibold text-slate-950"><?= $safe($userRole) ?></dd>
                </div>
                <div class="signal-row">
                    <dt class="text-slate-500">Connexion</dt>
                    <dd class="font-semibold text-slate-950"><?= $safe($authProvider) ?></dd>
                </div>
                <div class="signal-row">
                    <dt class="text-slate-500">Boutique</dt>
                    <dd class="max-w-36 truncate text-right font-semibold text-slate-950"><?= $safe($activeShopName) ?></dd>
                </div>
                <div class="signal-row">
                    <dt class="text-slate-500">Mot de passe</dt>
                    <dd class="font-semibold <?= $hasPassword ? 'text-teal-700' : 'text-amber-700' ?>"><?= $hasPassword ? 'Configure' : 'A definir' ?></dd>
                </div>
            </dl>

            <form class="mt-6" method="post" action="<?= $url('/logout') ?>" accept-charset="UTF-8" data-confirm-form>
                <button
                    class="flex w-full items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700 transition hover:bg-red-100"
                    type="button"
                    data-confirm
                    data-confirm-title="Se deconnecter ?"
                    data-confirm-message="Votre session boutique sera fermee."
                    data-confirm-accept="Oui, deconnecter"
                    data-confirm-progress="Deconnexion..."
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 17 15 12l-5-5M15 12H3m9-8h6a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3h-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Deconnexion
                </button>
            </form>
        </aside>

        <div class="grid gap-5">
            <form class="surface-panel space-y-5" method="post" action="<?= $url('/profil') ?>" accept-charset="UTF-8">
                <div class="panel-header">
                    <div>
                        <h2 class="font-bold text-slate-950">Informations personnelles</h2>
                        <p class="mt-1 text-sm text-slate-500">Ces donnees alimentent le layout et les actions realisees dans la boutique.</p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Nom complet</span>
                        <input class="field-control" name="nom" type="text" maxlength="160" value="<?= $safe($userName) ?>" required>
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Email</span>
                        <input class="field-control" name="email" type="email" maxlength="190" value="<?= $safe($userEmail) ?>" required>
                    </label>
                    <label class="block md:col-span-2">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Telephone</span>
                        <input class="field-control" name="telephone" type="tel" maxlength="60" value="<?= $safe($userPhone) ?>" placeholder="+243 ...">
                    </label>
                </div>

                <div class="flex justify-end border-t border-slate-200 pt-5">
                    <button class="btn-primary w-full sm:w-auto" type="submit">Enregistrer le profil</button>
                </div>
            </form>

            <form class="surface-panel space-y-5" method="post" action="<?= $url('/profil/password') ?>" accept-charset="UTF-8">
                <div class="panel-header">
                    <div>
                        <h2 class="font-bold text-slate-950">Securite</h2>
                        <p class="mt-1 text-sm text-slate-500"><?= $hasPassword ? 'Confirmez votre mot de passe actuel avant de le remplacer.' : 'Definissez un mot de passe local pour ce compte boutique.' ?></p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <?php if ($hasPassword): ?>
                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold text-slate-700">Mot de passe actuel</span>
                            <input class="field-control" name="current_password" type="password" autocomplete="current-password">
                        </label>
                    <?php endif; ?>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Nouveau mot de passe</span>
                        <input class="field-control" name="password" type="password" minlength="8" autocomplete="new-password">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Confirmation</span>
                        <input class="field-control" name="password_confirmation" type="password" minlength="8" autocomplete="new-password">
                    </label>
                </div>

                <div class="flex justify-end border-t border-slate-200 pt-5">
                    <button class="btn-secondary w-full sm:w-auto" type="submit">Mettre a jour le mot de passe</button>
                </div>
            </form>
        </div>
    </div>
</section>
