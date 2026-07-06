<?php

$heroImage = $asset('assets/img/landing-hero.png');
$features = [
    ['title' => 'Caisse rapide', 'text' => 'Vendez, sélectionnez le client, confirmez le panier et gardez une trace claire des tickets.'],
    ['title' => 'Stock surveillé', 'text' => 'Suivez les mouvements, les seuils minimums et les dates d’expiration des produits sensibles.'],
    ['title' => 'Clients et créances', 'text' => 'Gardez la dette client visible, réglable et reliée aux ventes concernées.'],
    ['title' => 'Rapports fiables', 'text' => 'Analysez ventes, marges, charges et signaux critiques sans multiplier les fichiers.'],
];
$workflow = [
    'Connecter les agents à la caisse',
    'Enregistrer ventes et approvisionnements',
    'Vérifier stock, alertes et créances',
    'Piloter la boutique depuis les rapports',
];
?>

<section class="public-hero" style="--hero-image: url('<?= $heroImage ?>');">
    <div class="public-hero-overlay"></div>
    <div class="public-container relative z-10 flex min-h-[82svh] flex-col justify-end pb-12 pt-28 sm:pb-16 lg:pt-32">
        <div class="max-w-3xl" data-reveal>
            <p class="mb-5 inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-black uppercase text-teal-100 backdrop-blur">
                ERP commercial pour boutiques modernes
            </p>
            <h1 class="text-4xl font-black leading-[1.02] tracking-normal text-white sm:text-6xl lg:text-7xl">
                Pilotez vos ventes, votre stock et vos finances depuis un seul espace.
            </h1>
            <p class="mt-6 max-w-2xl text-base leading-7 text-white/78 sm:text-lg">
                MadukaOne transforme la gestion quotidienne d’une boutique en un flux clair : caisse, catalogue, stock, clients, créances et rapports restent synchronisés.
            </p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a class="public-hero-cta" href="<?= $url('/login') ?>">Se connecter</a>
                <a class="public-hero-secondary" href="#modules">Voir les modules</a>
            </div>
        </div>

        <div class="mt-10 grid gap-3 sm:grid-cols-3" data-reveal>
            <div class="public-hero-metric"><strong>POS</strong><span>Caisse et tickets</span></div>
            <div class="public-hero-metric"><strong>Stock</strong><span>Alertes et audit</span></div>
            <div class="public-hero-metric"><strong>Rapports</strong><span>Marges et charges</span></div>
        </div>
    </div>
</section>

<section id="modules" class="public-section bg-white">
    <div class="public-container">
        <div class="max-w-2xl" data-reveal>
            <p class="public-eyebrow">Modules réutilisables</p>
            <h2 class="public-section-title">Des pages conçues pour travailler vite, sans perdre le contrôle.</h2>
        </div>

        <div class="mt-10 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <?php foreach ($features as $feature): ?>
                <article class="public-feature-card" data-reveal>
                    <span class="public-feature-icon"></span>
                    <h3><?= htmlspecialchars($feature['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p><?= htmlspecialchars($feature['text'], ENT_QUOTES, 'UTF-8') ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="public-section bg-slate-50">
    <div class="public-container grid gap-10 lg:grid-cols-[.9fr_1.1fr] lg:items-center">
        <div data-reveal>
            <p class="public-eyebrow">Flux opérationnel</p>
            <h2 class="public-section-title">Une progression simple pour l’équipe terrain et le gérant.</h2>
            <p class="mt-5 text-sm leading-7 text-slate-600">
                La navigation reprend le design de l’application : interfaces denses mais lisibles, actions visibles, confirmations sur les opérations sensibles et transitions sobres.
            </p>
            <a class="btn-primary mt-7 w-full sm:w-auto" href="<?= $url('/login') ?>">Ouvrir la connexion</a>
        </div>

        <div class="public-workflow" data-reveal>
            <?php foreach ($workflow as $index => $step): ?>
                <div class="public-workflow-step">
                    <span><?= $index + 1 ?></span>
                    <p><?= htmlspecialchars($step, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="public-section bg-white">
    <div class="public-container">
        <div class="public-final-cta" data-reveal>
            <div>
                <p class="public-eyebrow">Accès sécurisé</p>
                <h2 class="public-section-title">La page d’accueil reste publique. Les données restent derrière la connexion.</h2>
            </div>
            <a class="public-cta-large" href="<?= $url('/login') ?>">Connexion</a>
        </div>
    </div>
</section>
