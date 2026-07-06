<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

$oldEmail = htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8');
$basePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
$basePath = ($basePath === '' || $basePath === '.') ? '' : $basePath;
$createShopUrl = htmlspecialchars($basePath . '/shops/create', ENT_QUOTES, 'UTF-8');
$homeUrl = htmlspecialchars($basePath . '/', ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Connexion - MadukaOne</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/css/app.css">
</head>
<body class="min-h-screen font-sans antialiased">
    <main class="auth-shell grid min-h-screen grid-cols-1 overflow-hidden lg:grid-cols-[1.05fr_.95fr]">
        <section class="relative hidden min-h-screen bg-slate-950 px-10 py-10 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(15,118,110,.92),rgba(15,23,42,.94)_54%,rgba(37,99,235,.78))]"></div>
            <div class="absolute inset-x-10 top-24 h-px bg-white/15"></div>
            <div class="absolute bottom-0 left-0 right-0 h-48 bg-[linear-gradient(0deg,rgba(15,23,42,.9),transparent)]"></div>

            <div class="relative z-10 flex items-center gap-3">
                <div class="grid h-11 w-11 place-items-center rounded-lg bg-white text-base font-black text-teal-700 shadow-xl shadow-black/10">
                    M1
                </div>
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[.18em] text-white/70">MadukaOne</p>
                    <p class="text-sm text-white/70">ERP commerce et logistique</p>
                </div>
            </div>

            <div class="relative z-10 max-w-xl">
                <div class="mb-6 inline-flex items-center rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-white/85 backdrop-blur">
                    Pilotage multi-boutiques en temps réel
                </div>
                <h1 class="max-w-lg text-5xl font-semibold leading-[1.02] tracking-normal">
                    Une caisse pro, un stock clair, des ventes sous contrôle.
                </h1>
                <p class="mt-6 max-w-md text-base leading-7 text-white/75">
                    Connectez-vous pour suivre les ventes, limiter les erreurs terrain et garder une lecture nette de chaque boutique.
                </p>
            </div>

            <div class="relative z-10 grid grid-cols-3 gap-3">
                <div class="metric-chip">
                    <p class="text-2xl font-semibold">POS</p>
                    <p class="mt-1 text-xs text-white/65">Caisse rapide</p>
                </div>
                <div class="metric-chip">
                    <p class="text-2xl font-semibold">Stock</p>
                    <p class="mt-1 text-xs text-white/65">Mouvements tracés</p>
                </div>
                <div class="metric-chip">
                    <p class="text-2xl font-semibold">ERP</p>
                    <p class="mt-1 text-xs text-white/65">Rapports fiables</p>
                </div>
            </div>
        </section>

        <section class="flex min-h-screen items-center justify-center px-5 py-8 sm:px-8 lg:px-12">
            <div class="w-full max-w-md">
                <div class="mb-8 flex items-center justify-between lg:hidden">
                    <div class="flex items-center gap-3">
                        <div class="grid h-10 w-10 place-items-center rounded-lg bg-teal-700 text-sm font-black text-white shadow-lg shadow-teal-700/20">
                            M1
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-950">MadukaOne</p>
                            <p class="text-xs text-slate-500">ERP commerce</p>
                        </div>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-teal-700 shadow-sm ring-1 ring-slate-200">
                        SaaS POS
                    </span>
                </div>

                <div class="auth-panel rounded-[1.25rem] border border-white/80 bg-white/90 p-5 backdrop-blur sm:p-8">
                    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <a class="btn-secondary h-10 w-full gap-2 px-4 sm:w-auto" href="<?= $homeUrl ?>">
                            <span>Accueil</span>
                        </a>
                        <a class="btn-secondary h-10 w-full gap-2 px-4 sm:w-auto" href="<?= $createShopUrl ?>">
                            <span>Créer une boutique</span>
                        </a>
                    </div>

                    <div class="mb-8">
                        <p class="text-sm font-semibold uppercase tracking-[.16em] text-teal-700">Connexion</p>
                        <h2 class="mt-3 text-3xl font-semibold tracking-normal text-slate-950">Accéder à votre espace</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-500">
                            Utilisez votre compte professionnel pour ouvrir le tableau de bord ou la caisse.
                        </p>
                    </div>

                    <?php if (is_string($flashError) && $flashError !== ''): ?>
                        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                            <?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <form class="mb-5 space-y-3 rounded-lg border border-teal-100 bg-teal-50/70 p-4" method="post" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/activate-account" accept-charset="UTF-8">
                        <label class="block text-sm font-semibold text-slate-800" for="invitation_code">Code d'invitation agent</label>
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <input
                                class="field-control sm:flex-1"
                                id="invitation_code"
                                name="invitation_code"
                                type="text"
                                autocomplete="one-time-code"
                                placeholder="Ex: AGT-2026"
                                required
                            >
                            <button class="btn-primary sm:w-auto" type="submit">
                                Activer
                            </button>
                        </div>
                    </form>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <a class="btn-social" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/auth/google" aria-label="Connexion avec Google">
                            <span class="grid h-6 w-6 place-items-center rounded-full border border-slate-200 text-sm font-black text-blue-600">G</span>
                            <span>Google</span>
                        </a>
                        <a class="btn-social" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/auth/apple" aria-label="Connexion avec Apple">
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-slate-950 text-[11px] font-black text-white">A</span>
                            <span>Apple</span>
                        </a>
                    </div>

                    <div class="my-6 flex items-center gap-4">
                        <div class="h-px flex-1 bg-slate-200"></div>
                        <span class="text-xs font-semibold uppercase tracking-[.14em] text-slate-400">ou email</span>
                        <div class="h-px flex-1 bg-slate-200"></div>
                    </div>

                    <form class="space-y-5" method="post" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/login" accept-charset="UTF-8" novalidate>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-800" for="email">Email</label>
                            <input
                                class="field-control"
                                id="email"
                                name="email"
                                type="email"
                                value="<?= $oldEmail ?>"
                                autocomplete="email"
                                inputmode="email"
                                placeholder="nom@entreprise.com"
                                required
                            >
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <label class="block text-sm font-semibold text-slate-800" for="password">Mot de passe</label>
                                <button class="text-xs font-semibold text-teal-700 transition hover:text-teal-900" type="button" data-password-toggle>
                                    Afficher
                                </button>
                            </div>
                            <input
                                class="field-control pr-12"
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                placeholder="Votre mot de passe"
                                required
                            >
                        </div>

                        <button class="btn-primary" type="submit">
                            Se connecter
                        </button>
                    </form>

                    <div class="mt-6 rounded-lg bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-500 ring-1 ring-slate-200">
                        Les accès sont contrôlés par rôle. Les opérations sensibles restent tracées pour protéger la caisse, le stock et les rapports.
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        const toggle = document.querySelector('[data-password-toggle]');
        const password = document.querySelector('#password');

        if (toggle && password) {
            toggle.addEventListener('click', () => {
                const visible = password.type === 'text';
                password.type = visible ? 'password' : 'text';
                toggle.textContent = visible ? 'Afficher' : 'Masquer';
            });
        }
    </script>
</body>
</html>
