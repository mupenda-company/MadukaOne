<?php

$userName = (string) ($currentUser['nom'] ?? 'Utilisateur');
$userEmail = (string) ($currentUser['email'] ?? 'Email non défini');
$userRole = ucfirst(str_replace('_', ' ', (string) ($currentUser['role'] ?? $currentUser['role_legacy'] ?? 'agent')));
$authProvider = ucfirst((string) ($currentUser['auth_provider'] ?? 'local'));
$activeShopName = (string) ($activeShop['nom'] ?? 'Boutique active');
$userInitial = strtoupper(substr($userName, 0, 1));
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div class="max-w-3xl">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.26em] text-teal-700">Compte utilisateur</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Paramètres du profil</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Consultez les informations principales du compte connecté et préparez les préférences personnelles.
            </p>
        </div>

        <div class="hero-action-panel">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-400">Session active</p>
            <p class="mt-2 font-semibold text-slate-950"><?= htmlspecialchars($activeShopName, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="mt-1 text-sm text-slate-500"><?= htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-[22rem_1fr]">
        <aside class="surface-panel">
            <div class="flex items-center gap-4">
                <div class="grid h-16 w-16 place-items-center rounded-xl bg-slate-950 text-xl font-bold text-white">
                    <?= htmlspecialchars($userInitial, ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div class="min-w-0">
                    <h2 class="truncate text-lg font-bold text-slate-950"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="truncate text-sm text-slate-500"><?= htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>

            <dl class="mt-6 space-y-3 text-sm">
                <div class="signal-row">
                    <dt class="text-slate-500">Rôle</dt>
                    <dd class="font-semibold text-slate-950"><?= htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8') ?></dd>
                </div>
                <div class="signal-row">
                    <dt class="text-slate-500">Connexion</dt>
                    <dd class="font-semibold text-slate-950"><?= htmlspecialchars($authProvider, ENT_QUOTES, 'UTF-8') ?></dd>
                </div>
                <div class="signal-row">
                    <dt class="text-slate-500">Boutique</dt>
                    <dd class="max-w-36 truncate text-right font-semibold text-slate-950"><?= htmlspecialchars($activeShopName, ENT_QUOTES, 'UTF-8') ?></dd>
                </div>
            </dl>
        </aside>

        <div class="grid gap-5">
            <section class="surface-panel">
                <div class="panel-header">
                    <div>
                        <h2 class="font-bold text-slate-950">Informations personnelles</h2>
                        <p class="mt-1 text-sm text-slate-500">Données visibles dans l’espace de travail.</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Nom complet</span>
                        <input class="field-control" type="text" value="<?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>" readonly>
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Email</span>
                        <input class="field-control" type="email" value="<?= htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') ?>" readonly>
                    </label>
                </div>
            </section>

            <section class="surface-panel">
                <div class="panel-header">
                    <div>
                        <h2 class="font-bold text-slate-950">Sécurité</h2>
                        <p class="mt-1 text-sm text-slate-500">Les actions sensibles seront connectées au module utilisateur.</p>
                    </div>
                    <button class="btn-secondary" type="button" disabled>À venir</button>
                </div>
            </section>
        </div>
    </div>
</section>
