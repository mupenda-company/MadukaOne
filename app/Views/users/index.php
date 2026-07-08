<?php

$users = is_array($users ?? null) ? $users : [];
$userStats = is_array($userStats ?? null) ? $userStats : [];
$currentUserId = (int) ($currentUser['id'] ?? 0);
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

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
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <a class="btn-primary h-11 w-full px-5 sm:w-auto" href="<?= $url('/users/create') ?>">
                Ajouter un employe
            </a>
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-[.16em] text-slate-400">Source</p>
                <p class="mt-1 font-bold text-slate-950">Table users</p>
            </div>
        </div>
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
                                data-user-name="<?= $safe($name) ?>"
                                data-user-email="<?= $safe($email, 'Email non renseigne') ?>"
                                data-user-phone="<?= $safe($phone, 'Telephone non renseigne') ?>"
                                data-user-role="<?= $safe($roleName) ?>"
                                data-user-shop="<?= $safe($shopName) ?>"
                                data-user-status="<?= $active ? 'Actif' : 'Inactif' ?>"
                                data-user-provider="<?= $safe($providerLabel($provider)) ?>"
                                data-user-login="<?= $safe($dateLabel($user['derniere_connexion'] ?? null)) ?>"
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
                            <?php if (!$active): ?>
                                <form method="post" action="<?= $url('/admin/users/activate/' . $userId) ?>" onsubmit="return confirm('Voulez-vous activer cet utilisateur ?')">
                                    <button
                                        class="grid h-9 w-9 place-items-center rounded-lg border border-emerald-100 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100 focus:outline-none focus:ring-4 focus:ring-emerald-100"
                                        type="submit"
                                        title="Activer l'utilisateur"
                                        aria-label="Activer l'utilisateur <?= $safe($name) ?>"
                                    >
                                        <?= $icon('check') ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if ($active && !$isCurrentUser): ?>
                                <form method="post" action="<?= $url('/admin/users/deactivate/' . $userId) ?>" onsubmit="return confirm('Voulez-vous désactiver cet utilisateur ?')">
                                    <button
                                        class="grid h-9 w-9 place-items-center rounded-lg border border-amber-100 bg-amber-50 text-amber-700 transition hover:bg-amber-100 focus:outline-none focus:ring-4 focus:ring-amber-100"
                                        type="submit"
                                        title="Désactiver l'utilisateur"
                                        aria-label="Désactiver l'utilisateur <?= $safe($name) ?>"
                                    >
                                        <?= $icon('ban') ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if (!$isCurrentUser): ?>
                                <form method="post" action="<?= $url('/admin/users/delete/' . $userId) ?>" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer définitivement cet utilisateur ?')">
                                    <button
                                        class="grid h-9 w-9 place-items-center rounded-lg border border-red-100 bg-red-50 text-red-700 transition hover:bg-red-100 focus:outline-none focus:ring-4 focus:ring-red-100"
                                        type="submit"
                                        title="Supprimer définitivement l'utilisateur"
                                        aria-label="Supprimer définitivement l'utilisateur <?= $safe($name) ?>"
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

<div class="fixed inset-0 z-[75] hidden items-center justify-center bg-slate-950/45 px-4 py-6 backdrop-blur-sm" data-user-preview-modal role="dialog" aria-modal="true" aria-labelledby="user-preview-title">
    <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.16em] text-teal-700">Apercu utilisateur</p>
                <h2 class="mt-2 text-xl font-bold text-slate-950" id="user-preview-title" data-user-preview-name>Utilisateur</h2>
                <p class="mt-1 text-sm text-slate-500" data-user-preview-email></p>
            </div>
            <button class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-950" type="button" data-user-preview-close aria-label="Fermer">
                <span aria-hidden="true">x</span>
            </button>
        </div>

        <dl class="mt-5 grid gap-3 sm:grid-cols-2">
            <div class="signal-row">
                <dt class="text-slate-500">Telephone</dt>
                <dd class="font-semibold text-slate-950" data-user-preview-phone>-</dd>
            </div>
            <div class="signal-row">
                <dt class="text-slate-500">Role</dt>
                <dd class="font-semibold text-slate-950" data-user-preview-role>-</dd>
            </div>
            <div class="signal-row">
                <dt class="text-slate-500">Boutique</dt>
                <dd class="font-semibold text-slate-950" data-user-preview-shop>-</dd>
            </div>
            <div class="signal-row">
                <dt class="text-slate-500">Statut</dt>
                <dd class="font-semibold text-slate-950" data-user-preview-status>-</dd>
            </div>
            <div class="signal-row">
                <dt class="text-slate-500">Connexion</dt>
                <dd class="font-semibold text-slate-950" data-user-preview-provider>-</dd>
            </div>
            <div class="signal-row">
                <dt class="text-slate-500">Derniere connexion</dt>
                <dd class="font-semibold text-slate-950" data-user-preview-login>-</dd>
            </div>
        </dl>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var page = document.querySelector('[data-users-page]');
    if (!page) {
        return;
    }

    var input = page.querySelector('[data-users-search]');
    var rows = Array.prototype.slice.call(page.querySelectorAll('[data-user-row]'));
    var previewModal = document.querySelector('[data-user-preview-modal]');
    var previewClose = document.querySelector('[data-user-preview-close]');

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
            setPreviewText('email', button.dataset.userEmail);
            setPreviewText('phone', button.dataset.userPhone);
            setPreviewText('role', button.dataset.userRole);
            setPreviewText('shop', button.dataset.userShop);
            setPreviewText('status', button.dataset.userStatus);
            setPreviewText('provider', button.dataset.userProvider);
            setPreviewText('login', button.dataset.userLogin);

            previewModal?.classList.remove('hidden');
            previewModal?.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        });
    });

    previewClose?.addEventListener('click', closePreview);
    previewModal?.addEventListener('click', function (event) {
        if (event.target === previewModal) {
            closePreview();
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
