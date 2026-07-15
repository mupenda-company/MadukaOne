<?php
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);
$safe = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Abonnements - MadukaOne</title>
    <link rel="stylesheet" href="<?= $safe($basePath) ?>/assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-950 font-sans text-white antialiased">
    <main class="mx-auto min-h-screen max-w-7xl px-5 py-10 sm:px-8 lg:px-12">
        <header class="flex items-center justify-between gap-4">
            <a class="flex items-center gap-3" href="<?= $safe($basePath) ?>/">
                <span class="grid h-11 w-11 place-items-center rounded-lg bg-teal-600 font-black">M1</span>
                <span><strong class="block">MadukaOne</strong><small class="text-white/60">Boutiques en ligne</small></span>
            </a>
            <a class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold" href="<?= $safe($basePath) ?>/login">Connexion</a>
        </header>

        <section class="py-16 text-center">
            <p class="text-sm font-bold uppercase tracking-[.18em] text-teal-300">Abonnements</p>
            <h1 class="mx-auto mt-4 max-w-3xl text-4xl font-black sm:text-5xl">Choisissez le forfait adapté à votre boutique.</h1>
            <p class="mx-auto mt-5 max-w-2xl text-white/65">Votre espace démarre avec 14 jours d’essai gratuit. Aucun paiement client n’est activé dans le catalogue vitrine.</p>
        </section>

        <?php if (is_string($flashError) && $flashError !== ''): ?>
            <div class="mx-auto mb-6 max-w-2xl rounded-lg border border-red-400/30 bg-red-500/10 px-4 py-3 text-sm text-red-100"><?= $safe($flashError) ?></div>
        <?php endif; ?>

        <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            <?php foreach ($plans as $plan): ?>
                <?php
                $features = preg_split('/\R+/', trim((string) ($plan['description'] ?? ''))) ?: [];
                $isSelected = $selectedPlanId === (int) $plan['id'];
                ?>
                <article class="flex flex-col rounded-2xl border <?= $isSelected ? 'border-teal-400 bg-teal-400/10' : 'border-white/10 bg-white/5' ?> p-6">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[.14em] text-teal-300"><?= $safe($plan['code']) ?></p>
                        <h2 class="mt-2 text-2xl font-black"><?= $safe($plan['nom']) ?></h2>
                        <p class="mt-4 text-4xl font-black"><?= number_format((float) $plan['prix_mensuel_usd'], 0, ',', ' ') ?> $ <span class="text-sm font-medium text-white/50">/ mois</span></p>
                    </div>
                    <ul class="my-6 flex-1 space-y-2 text-sm text-white/70">
                        <?php foreach (array_slice(array_filter($features), 0, 8) as $feature): ?>
                            <li>✓ <?= $safe($feature) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <form method="post" action="<?= $safe($basePath) ?>/pricing/select">
                        <input type="hidden" name="_token" value="<?= $safe($csrfToken) ?>">
                        <input type="hidden" name="plan_id" value="<?= (int) $plan['id'] ?>">
                        <button class="btn-primary w-full" type="submit"><?= $isSelected ? 'Continuer avec ce forfait' : 'Choisir ce forfait' ?></button>
                    </form>
                </article>
            <?php endforeach; ?>
        </section>

        <div class="mt-8 text-center">
            <a class="text-sm font-semibold text-white/65 underline hover:text-white" href="<?= $safe($basePath) ?>/register-store">Continuer sans forfait — essai gratuit</a>
        </div>
    </main>
</body>
</html>
