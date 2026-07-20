<?php
$safe = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Créer votre boutique - MadukaOne</title>
    <link rel="stylesheet" href="<?= $safe($basePath) ?>/assets/css/app.css">
</head>
<body class="min-h-screen font-sans antialiased">
    <main class="auth-shell grid min-h-screen grid-cols-1 overflow-hidden lg:grid-cols-[.9fr_1.1fr]">
        <section class="relative hidden bg-slate-950 p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <a class="flex items-center gap-3" href="<?= $safe($basePath) ?>/">
                <span class="grid h-11 w-11 place-items-center rounded-lg bg-teal-600 font-black">M1</span>
                <span><strong class="block">MadukaOne</strong><small class="text-white/60">Création de boutique</small></span>
            </a>
            <div>
                <p class="text-sm font-bold uppercase tracking-[.18em] text-teal-300">Votre espace en quelques secondes</p>
                <h1 class="mt-5 text-5xl font-black leading-tight">Créez le compte, la boutique et son catalogue public.</h1>
                <p class="mt-6 max-w-lg leading-7 text-white/65">L’adresse publique sera générée automatiquement sous la forme MadukaOne.muoenda.cd/nom-boutique.</p>
            </div>
            <p class="text-sm text-white/45">Catalogue vitrine uniquement : aucune passerelle de paiement client n’est activée.</p>
        </section>

        <section class="flex items-center justify-center px-5 py-10 sm:px-8">
            <div class="w-full max-w-xl rounded-2xl border border-white bg-white/95 p-6 shadow-xl sm:p-9">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[.15em] text-teal-700">Création d’espace</p>
                        <h2 class="mt-2 text-3xl font-black text-slate-950">Votre boutique</h2>
                    </div>
                    <a class="text-sm font-semibold text-teal-700" href="<?= $safe($basePath) ?>/pricing">Changer de forfait</a>
                </div>

                <div class="mt-5 rounded-lg border border-teal-100 bg-teal-50 px-4 py-3 text-sm text-teal-900">
                    <?php if (is_array($selectedPlan)): ?>
                        Forfait choisi : <strong><?= $safe($selectedPlan['nom']) ?></strong> — essai gratuit de <?= (int) $trialDays ?> jour(s).
                    <?php else: ?>
                        <strong>Choisissez d’abord un forfait disponible.</strong>
                    <?php endif; ?>
                </div>

                <?php if (is_string($flashError) && $flashError !== ''): ?>
                    <div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"><?= $safe($flashError) ?></div>
                <?php endif; ?>

                <form class="mt-6 space-y-5" method="post" action="<?= $safe($basePath) ?>/register-store" novalidate>
                    <input type="hidden" name="_token" value="<?= $safe($csrfToken) ?>">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-800" for="name">Votre nom</label>
                            <input class="field-control" id="name" name="name" value="<?= $safe($old['name'] ?? '') ?>" maxlength="120" autocomplete="name" required>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-800" for="email">Email</label>
                            <input class="field-control" id="email" name="email" type="email" value="<?= $safe($old['email'] ?? '') ?>" maxlength="190" autocomplete="email" required>
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-800" for="shop_name">Nom de la boutique</label>
                        <input class="field-control" id="shop_name" name="shop_name" value="<?= $safe($old['shop_name'] ?? '') ?>" maxlength="120" placeholder="Ex. Ma Boutique" required>
                        <p class="mt-2 text-xs text-slate-500">Le slug unique sera généré automatiquement, par exemple <strong>ma-boutique</strong>.</p>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-800" for="password">Mot de passe</label>
                            <input class="field-control" id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-800" for="password_confirmation">Confirmation</label>
                            <input class="field-control" id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
                        </div>
                    </div>
                    <button class="btn-primary" type="submit">Créer ma boutique</button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-500">Déjà inscrit ? <a class="font-semibold text-teal-700" href="<?= $safe($basePath) ?>/login">Se connecter</a></p>
            </div>
        </section>
    </main>
</body>
</html>
