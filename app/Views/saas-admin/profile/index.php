<?php
$profile = is_array($profile ?? null) ? $profile : [];
$hasPassword = (bool) ($hasPassword ?? false);
$safe = static fn ($value, string $fallback = ''): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$name = (string) ($profile['nom'] ?? 'Super Admin');
$initial = strtoupper(substr($name, 0, 1) ?: 'U');
$roleName = (string) ($profile['role_name'] ?? 'Super Admin');
$authProvider = ucfirst((string) ($profile['auth_provider'] ?? 'local'));
?>
<section class="space-y-5">
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Compte SaaS</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Profil administrateur SaaS</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">Modifiez uniquement votre compte de gestion SaaS. Ce profil est separe du profil utilisateur affiche dans les boutiques.</p>
        </div>
        <a class="btn-secondary" href="<?= $url('/saas-admin') ?>">Retour pilotage</a>
    </div>

    <div class="grid gap-5 xl:grid-cols-[22rem_1fr]">
        <aside class="surface-panel">
            <div class="flex items-center gap-4">
                <div class="grid h-16 w-16 place-items-center rounded-xl bg-slate-950 text-xl font-black text-white"><?= $safe($initial) ?></div>
                <div class="min-w-0">
                    <h2 class="truncate text-lg font-bold text-slate-950"><?= $safe($name) ?></h2>
                    <p class="truncate text-sm text-slate-500"><?= $safe($profile['email'] ?? '') ?></p>
                </div>
            </div>

            <dl class="mt-6 space-y-3 text-sm">
                <div class="signal-row">
                    <dt class="text-slate-500">Role</dt>
                    <dd class="max-w-36 truncate text-right font-semibold text-slate-950"><?= $safe($roleName, 'Super Admin') ?></dd>
                </div>
                <div class="signal-row">
                    <dt class="text-slate-500">Connexion</dt>
                    <dd class="font-semibold text-slate-950"><?= $safe($authProvider) ?></dd>
                </div>
                <div class="signal-row">
                    <dt class="text-slate-500">Portee</dt>
                    <dd class="font-semibold text-slate-950">SaaS global</dd>
                </div>
                <div class="signal-row">
                    <dt class="text-slate-500">Mot de passe</dt>
                    <dd class="font-semibold <?= $hasPassword ? 'text-teal-700' : 'text-amber-700' ?>"><?= $hasPassword ? 'Configure' : 'A definir' ?></dd>
                </div>
            </dl>
            <form class="mt-6" method="post" action="<?= $url('/logout') ?>" accept-charset="UTF-8" data-confirm-form>
                <button class="flex w-full items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700 transition hover:bg-red-100" type="button" data-confirm data-confirm-title="Se deconnecter ?" data-confirm-message="Votre session sera fermee." data-confirm-accept="Oui, deconnecter" data-confirm-progress="Deconnexion...">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 17 15 12l-5-5M15 12H3m9-8h6a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3h-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Deconnexion
                </button>
            </form>
        </aside>

        <div class="grid gap-5">
            <form class="surface-panel space-y-5" method="post" action="<?= $url('/saas-admin/profil') ?>" accept-charset="UTF-8">
                <div>
                    <h2 class="font-bold text-slate-950">Informations personnelles</h2>
                    <p class="mt-1 text-sm text-slate-500">Ces informations alimentent le layout et les sessions de l'administration SaaS.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700" for="saas_profile_nom">Nom complet</label>
                        <input class="field-control" id="saas_profile_nom" name="nom" required maxlength="160" value="<?= $safe($profile['nom'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700" for="saas_profile_email">Email</label>
                        <input class="field-control" id="saas_profile_email" name="email" type="email" required maxlength="190" value="<?= $safe($profile['email'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700" for="saas_profile_phone">Telephone</label>
                        <input class="field-control" id="saas_profile_phone" name="telephone" maxlength="60" value="<?= $safe($profile['telephone'] ?? '') ?>" placeholder="+243 ...">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700" for="saas_profile_role">Role SaaS</label>
                        <input class="field-control bg-slate-50" id="saas_profile_role" value="<?= $safe($roleName, 'Super Admin') ?>" readonly>
                    </div>
                </div>

                <div class="flex justify-end border-t border-slate-200 pt-5">
                    <button class="btn-primary w-full sm:w-auto" type="submit">Enregistrer le profil</button>
                </div>
            </form>

            <form class="surface-panel space-y-5" method="post" action="<?= $url('/saas-admin/profil/password') ?>" accept-charset="UTF-8">
                <div>
                    <h2 class="font-bold text-slate-950">Securite du compte</h2>
                    <p class="mt-1 text-sm text-slate-500"><?= $hasPassword ? 'Confirmez le mot de passe actuel avant de le remplacer.' : 'Definissez un mot de passe local pour ce compte SaaS.' ?></p>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <?php if ($hasPassword): ?>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700" for="current_password">Mot de passe actuel</label>
                            <input class="field-control" id="current_password" name="current_password" type="password" autocomplete="current-password">
                        </div>
                    <?php endif; ?>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700" for="password">Nouveau mot de passe</label>
                        <input class="field-control" id="password" name="password" type="password" minlength="8" autocomplete="new-password">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700" for="password_confirmation">Confirmation</label>
                        <input class="field-control" id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password">
                    </div>
                </div>

                <div class="flex justify-end border-t border-slate-200 pt-5">
                    <button class="btn-secondary w-full sm:w-auto" type="submit">Mettre a jour le mot de passe</button>
                </div>
            </form>
        </div>
    </div>
</section>
