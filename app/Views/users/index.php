<?php

$users = is_array($users ?? null) ? $users : [];
$userStats = is_array($userStats ?? null) ? $userStats : [];
$currentUserId = (int) ($currentUser['id'] ?? 0);
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
$resetCredentials = $_SESSION['flash']['reset_credentials'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
unset($_SESSION['flash']['reset_credentials']);

$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$dateLabel = static function ($value): string {
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return 'Jamais';
    }

    $timestamp = strtotime($value);
    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : $value;
};

$initials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $letters = '';

    foreach ((array) $parts as $part) {
        if ($part !== '') {
            $letters .= strtoupper(substr($part, 0, 1));
        }

        if (strlen($letters) >= 2) {
            break;
        }
    }

    return $letters !== '' ? $letters : 'U';
};

$providerLabel = static fn (string $provider): string => match ($provider) {
    'google' => 'Google',
    'apple' => 'Apple',
    default => 'Local',
};

$icon = static function (string $name): string {
    $paths = [
        'users' => '<path d="M16 19c0-2.2-1.8-4-4-4H8c-2.2 0-4 1.8-4 4m12-7a3 3 0 1 0 0-6m4 13c0-1.9-1.3-3.5-3-3.9M10 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'shield' => '<path d="M12 3 5 6v5c0 4.2 2.7 8 7 10 4.3-2 7-5.8 7-10V6l-7-3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'mail' => '<path d="M4 6h16v12H4V6Zm0 1 8 6 8-6" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'search' => '<path d="m21 21-4.3-4.3M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'eye' => '<path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="2"/>',
        'edit' => '<path d="M4 20h4l10.5-10.5a2.8 2.8 0 0 0-4-4L4 16v4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="m13.5 6.5 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'ban' => '<path d="M5 5 19 19M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'check' => '<path d="m5 13 4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'trash' => '<path d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'key' => '<path d="M15 7a5 5 0 1 0-3.8 4.85L13 13.65V16h2.35v2H18v-2.35l2.15-2.15-4.3-4.3A5 5 0 0 0 15 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 7h.01" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>',
    ];

    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . ($paths[$name] ?? $paths['users']) . '</svg>';
};
?>

<section class="space-y-5" data-users-page>
    <?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            <?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (is_string($flashError) && $flashError !== ''): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            <?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Administration</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Utilisateurs</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Vue centralisee des comptes disponibles dans la table users, avec roles, boutiques et statut de connexion.
            </p>
        </div>
        <a class="btn-primary h-11 w-full px-5 sm:w-auto" href="<?= $url('/users/create') ?>">
            Ajouter un employe
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="stat-card">
            <p class="text-sm text-slate-500">Total utilisateurs</p>
            <p class="mt-2 text-2xl font-bold"><?= (int) ($userStats['total'] ?? count($users)) ?></p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Comptes actifs</p>
            <p class="mt-2 text-2xl font-bold text-teal-700"><?= (int) ($userStats['active'] ?? 0) ?></p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Comptes inactifs</p>
            <p class="mt-2 text-2xl font-bold text-red-700"><?= (int) ($userStats['inactive'] ?? 0) ?></p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Boutiques liees</p>
            <p class="mt-2 text-2xl font-bold"><?= (int) ($userStats['shops'] ?? 0) ?></p>
        </article>
    </div>

    <section class="surface-panel">
        <div class="panel-header gap-4">
            <div>
                <h2 class="font-bold text-slate-950">Liste des utilisateurs</h2>
                <p class="mt-1 text-sm text-slate-500">Recherche visuelle cote interface, sans modifier la logique backend.</p>
            </div>
            <label class="relative w-full sm:max-w-xs">
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"><?= $icon('search') ?></span>
                <input class="field-control pl-11" type="search" data-users-search placeholder="Rechercher un utilisateur">
            </label>
        </div>

        <div class="mt-5 overflow-hidden rounded-lg border border-slate-200">
            <div class="hidden grid-cols-[1.15fr_1.05fr_.75fr_.95fr_.7fr_.85fr_.7fr] gap-4 border-b border-slate-200 bg-slate-50 px-4 py-3 text-xs font-bold uppercase tracking-[.16em] text-slate-400 lg:grid">
                <span>Utilisateur</span>
                <span>Contact</span>
                <span>Role</span>
                <span>Boutique</span>
                <span>Statut</span>
                <span>Derniere connexion</span>
                <span class="text-right">Actions</span>
            </div>

            <div class="divide-y divide-slate-200" data-users-list>
                <?php if ($users === []): ?>
                    <div class="px-4 py-10 text-center text-sm text-slate-500">
                        Aucun utilisateur trouve dans la table users.
                    </div>
                <?php endif; ?>

                <?php foreach ($users as $user): ?>
                    <?php
                    $name = trim((string) (($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')));
                    $name = $name !== '' ? $name : 'Utilisateur';
                    $userId = (int) ($user['id'] ?? 0);
                    $isCurrentUser = $userId === $currentUserId;
                    $email = (string) ($user['email'] ?? '');
                    $phone = (string) ($user['telephone'] ?? '');
                    $roleName = (string) ($user['role_name'] ?? $user['role_legacy'] ?? 'Agent');
                    $shopName = (string) ($user['shop_name'] ?? 'Toutes les boutiques');
                    $provider = (string) ($user['auth_provider'] ?? 'local');
                    $active = (int) ($user['actif'] ?? 0) === 1;
                    $searchText = strtolower($name . ' ' . $email . ' ' . $phone . ' ' . $roleName . ' ' . $shopName . ' ' . $provider);
                    ?>
                    <article class="grid gap-4 px-4 py-4 transition hover:bg-slate-50 lg:grid-cols-[1.15fr_1.05fr_.75fr_.95fr_.7fr_.85fr_.7fr] lg:items-center" data-user-row data-search="<?= $safe($searchText) ?>">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-slate-950 text-sm font-bold text-white"><?= $safe($initials($name)) ?></span>
                            <span class="min-w-0">
                                <span class="block truncate font-bold text-slate-950"><?= $safe($name) ?></span>
                                <span class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-slate-500">
                                    <?= $icon('shield') ?>
                                    ID <?= $userId ?>
                                </span>
                            </span>
                        </div>

                        <div class="min-w-0 text-sm">
                            <p class="flex min-w-0 items-center gap-2 text-slate-950">
                                <?= $icon('mail') ?>
                                <span class="truncate"><?= $safe($email, 'Email non renseigne') ?></span>
                            </p>
                            <p class="mt-1 truncate text-slate-500"><?= $safe($phone, 'Telephone non renseigne') ?></p>
                        </div>

                        <div>
                            <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700"><?= $safe($roleName) ?></span>
                            <p class="mt-1 text-xs text-slate-500"><?= $safe($providerLabel($provider)) ?></p>
                        </div>

                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-950"><?= $safe($shopName) ?></p>
                            <p class="mt-1 text-xs text-slate-500">Boutique assignee</p>
                        </div>

                        <div>
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold <?= $active ? 'bg-teal-50 text-teal-700' : 'bg-red-50 text-red-700' ?>">
                                <?= $active ? 'Actif' : 'Inactif' ?>
                            </span>
                        </div>

                        <div class="text-sm text-slate-600">
                            <?= $safe($dateLabel($user['derniere_connexion'] ?? null)) ?>
                        </div>

                        <div class="flex items-center gap-2 lg:justify-end">
                            <button
                                class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 focus:outline-none focus:ring-4 focus:ring-slate-200"
                                type="button"
                                title="Voir l'utilisateur"
                                aria-label="Voir l'utilisateur <?= $safe($name) ?>"
                                data-user-preview
                                data-user-initials="<?= $safe($initials($name)) ?>"
                                data-user-name="<?= $safe($name) ?>"
                                data-user-email="<?= $safe($email, 'Email non renseigne') ?>"
                                data-user-phone="<?= $safe($phone, 'Telephone non renseigne') ?>"
                                data-user-role="<?= $safe($roleName) ?>"
                                data-user-shop="<?= $safe($shopName) ?>"
                                data-user-status="<?= $active ? 'Actif' : 'Inactif' ?>"
                                data-user-provider="<?= $safe($providerLabel($provider)) ?>"
                                data-user-login="<?= $safe($dateLabel($user['derniere_connexion'] ?? null)) ?>"
                                data-user-edit-url="<?= $url('/admin/users/edit/' . $userId) ?>"
                            >
                                <?= $icon('eye') ?>
                            </button>
                            <a
                                class="grid h-9 w-9 place-items-center rounded-lg border border-blue-100 bg-blue-50 text-blue-700 transition hover:bg-blue-100 focus:outline-none focus:ring-4 focus:ring-blue-100"
                                href="<?= $url('/admin/users/edit/' . $userId) ?>"
                                title="Modifier l'utilisateur"
                                aria-label="Modifier l'utilisateur <?= $safe($name) ?>"
                            >
                                <?= $icon('edit') ?>
                            </a>
                            <?php if (!$isCurrentUser): ?>
                                <form method="post" action="<?= $url('/users/' . $userId . '/reset-password') ?>">
                                    <button
                                        class="grid h-9 w-9 place-items-center rounded-lg border border-violet-100 bg-violet-50 text-violet-700 transition hover:bg-violet-100 focus:outline-none focus:ring-4 focus:ring-violet-100"
                                        type="button"
                                        title="Réinitialiser le mot de passe"
                                        aria-label="Réinitialiser le mot de passe de <?= $safe($name) ?>"
                                        data-confirm
                                        data-confirm-title="Réinitialiser le mot de passe ?"
                                        data-confirm-message="Un nouveau mot de passe temporaire sera généré pour <?= $safe($name) ?>. L’ancien mot de passe cessera immédiatement de fonctionner."
                                        data-confirm-accept="Oui, réinitialiser"
                                        data-confirm-progress="Génération en cours..."
                                    >
                                        <?= $icon('key') ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if (!$active): ?>
                                <form method="post" action="<?= $url('/admin/users/activate/' . $userId) ?>">
                                    <button
                                        class="grid h-9 w-9 place-items-center rounded-lg border border-emerald-100 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100 focus:outline-none focus:ring-4 focus:ring-emerald-100"
                                        type="button"
                                        title="Activer l'utilisateur"
                                        aria-label="Activer l'utilisateur <?= $safe($name) ?>"
                                        data-confirm
                                        data-confirm-title="Activer cet utilisateur ?"
                                        data-confirm-message="<?= $safe($name) ?> pourra de nouveau se connecter et accéder à son espace utilisateur selon son rôle et ses permissions."
                                        data-confirm-accept="Oui, activer"
                                        data-confirm-progress="Activation en cours..."
                                    >
                                        <?= $icon('check') ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if ($active && !$isCurrentUser): ?>
                                <form method="post" action="<?= $url('/admin/users/deactivate/' . $userId) ?>">
                                    <button
                                        class="grid h-9 w-9 place-items-center rounded-lg border border-amber-100 bg-amber-50 text-amber-700 transition hover:bg-amber-100 focus:outline-none focus:ring-4 focus:ring-amber-100"
                                        type="button"
                                        title="Désactiver l'utilisateur"
                                        aria-label="Désactiver l'utilisateur <?= $safe($name) ?>"
                                        data-confirm
                                        data-confirm-title="Désactiver cet utilisateur ?"
                                        data-confirm-message="<?= $safe($name) ?> ne pourra plus se connecter ni accéder à son espace jusqu’à sa réactivation. Ses données et son historique seront conservés."
                                        data-confirm-accept="Oui, désactiver"
                                        data-confirm-progress="Désactivation en cours..."
                                    >
                                        <?= $icon('ban') ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if (!$isCurrentUser): ?>
                                <form method="post" action="<?= $url('/admin/users/delete/' . $userId) ?>">
                                    <button
                                        class="grid h-9 w-9 place-items-center rounded-lg border border-red-100 bg-red-50 text-red-700 transition hover:bg-red-100 focus:outline-none focus:ring-4 focus:ring-red-100"
                                        type="button"
                                        title="Supprimer définitivement l'utilisateur"
                                        aria-label="Supprimer définitivement l'utilisateur <?= $safe($name) ?>"
                                        data-confirm
                                        data-confirm-title="Supprimer définitivement ?"
                                        data-confirm-message="Le compte de <?= $safe($name) ?> sera supprimé définitivement. Cette opération est irréversible et peut être refusée si l’utilisateur possède déjà des opérations liées."
                                        data-confirm-accept="Oui, supprimer"
                                        data-confirm-progress="Suppression en cours..."
                                    >
                                        <?= $icon('trash') ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</section>

<div class="fixed inset-0 z-[75] hidden items-center justify-center overflow-y-auto bg-slate-950/60 px-4 py-6 backdrop-blur-sm" data-user-preview-modal role="dialog" aria-modal="true" aria-labelledby="user-preview-title">
    <div class="w-full max-w-2xl overflow-hidden rounded-2xl border border-white/80 bg-white shadow-2xl shadow-slate-950/25" data-user-preview-panel tabindex="-1">
        <div class="relative overflow-hidden bg-slate-950 px-5 py-6 text-white sm:px-7">
            <div class="absolute -right-16 -top-20 h-48 w-48 rounded-full bg-teal-400/20 blur-2xl"></div>
            <div class="absolute -bottom-24 left-24 h-44 w-44 rounded-full bg-blue-500/20 blur-2xl"></div>
            <button class="absolute right-4 top-4 z-10 grid h-10 w-10 place-items-center rounded-full border border-white/15 bg-white/10 text-white transition hover:bg-white/20 focus:outline-none focus:ring-4 focus:ring-white/10" type="button" data-user-preview-close aria-label="Fermer">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </button>

            <div class="relative flex items-center gap-4 pr-12 sm:gap-5">
                <span class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-teal-300 to-teal-500 text-xl font-black text-slate-950 shadow-lg shadow-teal-950/20" data-user-preview-initials>U</span>
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-[.18em] text-teal-300">Profil utilisateur</p>
                    <h2 class="mt-2 truncate text-2xl font-bold" id="user-preview-title" data-user-preview-name>Utilisateur</h2>
                    <p class="mt-1 truncate text-sm text-slate-300" data-user-preview-email></p>
                </div>
            </div>
        </div>

        <div class="p-5 sm:p-7">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Etat du compte</p>
                    <p class="mt-1 text-sm text-slate-600">Acces et rattachement actuels</p>
                </div>
                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold" data-user-preview-status-badge>
                    <span class="h-2 w-2 rounded-full bg-current"></span>
                    <span data-user-preview-status>-</span>
                </span>
            </div>

            <dl class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <dt class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[.12em] text-slate-400"><?= $icon('users') ?> Telephone</dt>
                    <dd class="mt-2 break-words font-bold text-slate-950" data-user-preview-phone>-</dd>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <dt class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[.12em] text-slate-400"><?= $icon('shield') ?> Role</dt>
                    <dd class="mt-2 font-bold text-slate-950" data-user-preview-role>-</dd>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[.12em] text-slate-400">Boutique assignee</dt>
                    <dd class="mt-2 font-bold text-slate-950" data-user-preview-shop>-</dd>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[.12em] text-slate-400">Mode de connexion</dt>
                    <dd class="mt-2 font-bold text-slate-950" data-user-preview-provider>-</dd>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-[.12em] text-slate-400">Derniere connexion</dt>
                    <dd class="mt-2 font-bold text-slate-950" data-user-preview-login>-</dd>
                </div>
            </dl>

            <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                <button class="btn-secondary w-full sm:w-auto" type="button" data-user-preview-close>Fermer</button>
                <a class="btn-primary w-full gap-2 sm:w-auto" href="#" data-user-preview-edit>
                    <?= $icon('edit') ?> Modifier l utilisateur
                </a>
            </div>
        </div>
    </div>
</div>

<?php if (is_array($resetCredentials)): ?>
    <?php
    $resetEmployee = trim((string) ($resetCredentials['employee'] ?? '')) ?: 'Utilisateur';
    $resetEmail = (string) ($resetCredentials['email'] ?? '');
    $resetPassword = (string) ($resetCredentials['password'] ?? '');
    $loginUrl = $url('/login');
    ?>
    <div class="fixed inset-0 z-[90] flex items-center justify-center overflow-y-auto bg-slate-950/65 px-4 py-6 backdrop-blur-sm" data-password-result-modal role="dialog" aria-modal="true" aria-labelledby="password-result-title">
        <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-white/80 bg-white shadow-2xl shadow-slate-950/30" data-password-result-panel tabindex="-1">
            <div class="bg-gradient-to-br from-violet-700 via-indigo-700 to-slate-950 px-6 py-6 text-white">
                <div class="flex items-start gap-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-white/15 text-violet-100"><?= $icon('key') ?></span>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[.18em] text-violet-200">Nouveaux identifiants</p>
                        <h2 class="mt-2 text-xl font-bold" id="password-result-title">Mot de passe réinitialisé</h2>
                        <p class="mt-1 text-sm text-violet-100"><?= $safe($resetEmployee) ?> peut maintenant utiliser ces identifiants.</p>
                    </div>
                </div>
            </div>

            <div class="space-y-4 p-6">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-[.14em] text-slate-400">Adresse email</p>
                    <div class="mt-2 flex items-center gap-3">
                        <code class="min-w-0 flex-1 break-all font-bold text-slate-950"><?= $safe($resetEmail) ?></code>
                        <button class="btn-secondary h-9 shrink-0 px-3 text-xs" type="button" data-copy-value="<?= $safe($resetEmail) ?>">Copier</button>
                    </div>
                </div>
                <div class="rounded-xl border border-violet-200 bg-violet-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-[.14em] text-violet-500">Nouveau mot de passe</p>
                    <div class="mt-2 flex items-center gap-3">
                        <code class="min-w-0 flex-1 break-all text-lg font-black tracking-wide text-violet-950"><?= $safe($resetPassword) ?></code>
                        <button class="btn-secondary h-9 shrink-0 border-violet-200 px-3 text-xs" type="button" data-copy-value="<?= $safe($resetPassword) ?>">Copier</button>
                    </div>
                </div>
                <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-800">
                    Ce mot de passe n’est affiché qu’une seule fois. Copiez-le avant de fermer cette fenêtre.
                </p>
                <div class="flex flex-col-reverse gap-3 pt-1 sm:flex-row sm:justify-end">
                    <button class="btn-secondary w-full sm:w-auto" type="button" data-password-result-close>Fermer</button>
                    <button
                        class="btn-primary w-full sm:w-auto"
                        type="button"
                        data-copy-value="<?= $safe("Connexion MadukaOne\nAdresse : " . $loginUrl . "\nEmail : " . $resetEmail . "\nMot de passe : " . $resetPassword) ?>"
                    >Copier tous les identifiants</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var page = document.querySelector('[data-users-page]');
    if (!page) {
        return;
    }

    var input = page.querySelector('[data-users-search]');
    var rows = Array.prototype.slice.call(page.querySelectorAll('[data-user-row]'));
    var previewModal = document.querySelector('[data-user-preview-modal]');
    var previewCloseButtons = document.querySelectorAll('[data-user-preview-close]');
    var previewPanel = document.querySelector('[data-user-preview-panel]');
    var previewEdit = document.querySelector('[data-user-preview-edit]');
    var previewStatusBadge = document.querySelector('[data-user-preview-status-badge]');
    var passwordModal = document.querySelector('[data-password-result-modal]');
    var passwordPanel = document.querySelector('[data-password-result-panel]');

    var setPreviewText = function (key, value) {
        var target = document.querySelector('[data-user-preview-' + key + ']');
        if (target) {
            target.textContent = value || '-';
        }
    };

    var closePreview = function () {
        if (!previewModal) {
            return;
        }

        previewModal.classList.add('hidden');
        previewModal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    };

    page.querySelectorAll('[data-user-preview]').forEach(function (button) {
        button.addEventListener('click', function () {
            setPreviewText('name', button.dataset.userName);
            setPreviewText('initials', button.dataset.userInitials);
            setPreviewText('email', button.dataset.userEmail);
            setPreviewText('phone', button.dataset.userPhone);
            setPreviewText('role', button.dataset.userRole);
            setPreviewText('shop', button.dataset.userShop);
            setPreviewText('status', button.dataset.userStatus);
            setPreviewText('provider', button.dataset.userProvider);
            setPreviewText('login', button.dataset.userLogin);

            if (previewEdit) {
                previewEdit.href = button.dataset.userEditUrl || '#';
            }

            if (previewStatusBadge) {
                var isActive = button.dataset.userStatus === 'Actif';
                previewStatusBadge.className = 'inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold ' + (isActive ? 'bg-teal-50 text-teal-700' : 'bg-red-50 text-red-700');
            }

            previewModal?.classList.remove('hidden');
            previewModal?.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            window.setTimeout(function () { previewPanel?.focus(); }, 0);
        });
    });

    previewCloseButtons.forEach(function (button) {
        button.addEventListener('click', closePreview);
    });
    previewModal?.addEventListener('click', function (event) {
        if (event.target === previewModal) {
            closePreview();
        }
    });

    var closePasswordModal = function () {
        passwordModal?.classList.add('hidden');
        passwordModal?.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    };

    document.querySelectorAll('[data-password-result-close]').forEach(function (button) {
        button.addEventListener('click', closePasswordModal);
    });
    passwordModal?.addEventListener('click', function (event) {
        if (event.target === passwordModal) {
            closePasswordModal();
        }
    });
    document.querySelectorAll('[data-copy-value]').forEach(function (button) {
        button.addEventListener('click', async function () {
            var value = button.getAttribute('data-copy-value') || '';
            try {
                await navigator.clipboard.writeText(value);
                var original = button.textContent;
                button.textContent = 'Copié';
                window.setTimeout(function () { button.textContent = original; }, 1600);
            } catch (error) {
                window.prompt('Copiez les identifiants :', value);
            }
        });
    });
    if (passwordModal) {
        document.body.classList.add('overflow-hidden');
        window.setTimeout(function () { passwordPanel?.focus(); }, 0);
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && previewModal && !previewModal.classList.contains('hidden')) {
            closePreview();
        }
        if (event.key === 'Escape' && passwordModal && !passwordModal.classList.contains('hidden')) {
            closePasswordModal();
        }
    });

    if (!input || rows.length === 0) {
        return;
    }

    input.addEventListener('input', function () {
        var query = input.value.trim().toLowerCase();

        rows.forEach(function (row) {
            var haystack = row.getAttribute('data-search') || '';
            row.hidden = query !== '' && haystack.indexOf(query) === -1;
        });
    });
});
</script>
