<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

$basePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
$basePath = ($basePath === '' || $basePath === '.') ? '' : $basePath;
$loginUrl = htmlspecialchars($basePath . '/login', ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Créer une boutique - MadukaOne</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/css/app.css">
</head>
<body class="min-h-screen font-sans antialiased">
    <main class="auth-shell grid min-h-screen grid-cols-1 overflow-hidden lg:grid-cols-[1.05fr_.95fr]">
        <section class="relative hidden min-h-screen bg-slate-950 px-10 py-10 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(15,118,110,.92),rgba(15,23,42,.94)_54%,rgba(37,99,235,.78))]"></div>
            <div class="absolute inset-x-10 top-24 h-px bg-white/15"></div>
            <div class="absolute bottom-0 left-0 right-0 h-48 bg-[linear-gradient(0deg,rgba(15,23,42,.9),transparent)]"></div>

            <div class="relative z-10 flex items-center gap-3">
                <div class="grid h-11 w-11 place-items-center rounded-lg bg-white text-base font-black text-teal-700 shadow-xl shadow-black/10">M1</div>
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[.18em] text-white/70">MadukaOne</p>
                    <p class="text-sm text-white/70">ERP commerce et logistique</p>
                </div>
            </div>

            <div class="relative z-10 max-w-xl">
                <div class="mb-6 inline-flex items-center rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-white/85 backdrop-blur">
                    Création de boutique
                </div>
                <h1 class="max-w-lg text-5xl font-semibold leading-[1.02] tracking-normal">
                    Démarrez une boutique avec une base claire.
                </h1>
                <p class="mt-6 max-w-md text-base leading-7 text-white/75">
                    Préparez les informations principales de la boutique avant de connecter les utilisateurs, produits et stocks.
                </p>
            </div>

            <div class="relative z-10 grid grid-cols-3 gap-3">
                <div class="metric-chip"><p class="text-2xl font-semibold">Boutique</p><p class="mt-1 text-xs text-white/65">Point de vente</p></div>
                <div class="metric-chip"><p class="text-2xl font-semibold">Équipe</p><p class="mt-1 text-xs text-white/65">Rôles et accès</p></div>
                <div class="metric-chip"><p class="text-2xl font-semibold">Stock</p><p class="mt-1 text-xs text-white/65">Inventaire initial</p></div>
            </div>
        </section>

        <section class="flex min-h-screen items-center justify-center px-5 py-8 sm:px-8 lg:px-12">
            <div class="w-full max-w-md">
                <div class="mb-8 flex items-center justify-between lg:hidden">
                    <div class="flex items-center gap-3">
                        <div class="grid h-10 w-10 place-items-center rounded-lg bg-teal-700 text-sm font-black text-white shadow-lg shadow-teal-700/20">M1</div>
                        <div>
                            <p class="text-sm font-bold text-slate-950">MadukaOne</p>
                            <p class="text-xs text-slate-500">ERP commerce</p>
                        </div>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-teal-700 shadow-sm ring-1 ring-slate-200">SaaS POS</span>
                </div>

                <div class="auth-panel rounded-[1.25rem] border border-white/80 bg-white/90 p-5 backdrop-blur sm:p-8">
                    <div class="mb-5 flex justify-end">
                        <a class="btn-secondary h-10 w-full gap-2 px-4 sm:w-auto" href="<?= $loginUrl ?>">
                            <span>Retour à la connexion</span>
                        </a>
                    </div>

                    <div class="mb-8">
                        <p class="text-sm font-semibold uppercase tracking-[.16em] text-teal-700">Nouvelle boutique</p>
                        <h2 class="mt-3 text-3xl font-semibold tracking-normal text-slate-950">Créer une boutique</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-500">
                            Renseignez les informations de base. Le raccordement d'enregistrement sera ajouté au backend de gestion des boutiques.
                        </p>
                    </div>

                    <form class="space-y-5" method="post" action="#" accept-charset="UTF-8">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-800" for="shop_name">Nom de la boutique</label>
                            <input class="field-control" id="shop_name" name="nom" type="text" placeholder="Ex: Boutique Centre Ville" required>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-800" for="shop_phone">Téléphone</label>
                            <input class="field-control" id="shop_phone" name="telephone" type="tel" placeholder="+243..." inputmode="tel">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-800" for="shop_address">Adresse</label>
                            <textarea class="field-control min-h-28" id="shop_address" name="adresse" placeholder="Avenue, commune, ville"></textarea>
                        </div>

                        <button class="btn-primary" type="button">
                            Préparer la boutique
                        </button>
                    </form>

                    <div class="mt-6 rounded-lg bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-500 ring-1 ring-slate-200">
                        Cette page est accessible depuis la connexion pour faciliter l'ouverture d'une nouvelle boutique.
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
