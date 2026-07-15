<?php

$heroImage = $asset('assets/img/landing-hero.png');
$heroStats = [
    ['value' => 'POS', 'label' => 'Caisse rapide avec ticket'],
    ['value' => 'Stock', 'label' => 'Alertes et inventaire'],
    ['value' => 'Finance', 'label' => 'Créances, charges et marges'],
];
$painPoints = [
    ['title' => 'Ventes dispersées', 'text' => 'Chaque vente alimente automatiquement les clients, le stock et les rapports.'],
    ['title' => 'Stock difficile à suivre', 'text' => 'Les seuils, mouvements et inventaires gardent les ruptures visibles avant qu’elles ne bloquent la caisse.'],
    ['title' => 'Créances oubliées', 'text' => 'Les dettes clients restent liées aux ventes et peuvent être suivies sans cahier parallèle.'],
];
$features = [
    ['title' => 'Caisse et factures', 'text' => 'Vente rapide, sélection client, confirmation du panier, facture imprimable et historique détaillé.'],
    ['title' => 'Catalogue produits', 'text' => 'Prix, seuils d’alerte, dates d’expiration, fournisseurs et mouvements restent centralisés.'],
    ['title' => 'Clients et créances', 'text' => 'Suivi des clients, règlement des dettes et visibilité sur les ventes partiellement payées.'],
    ['title' => 'Approvisionnements', 'text' => 'Entrées fournisseur, quantités, prix d’achat et annulation contrôlée des arrivages.'],
    ['title' => 'Charges et finances', 'text' => 'Enregistrement des dépenses, marges, totaux vendus et lecture claire de la performance.'],
    ['title' => 'Rapports', 'text' => 'Filtres, exports et synthèses pour piloter une ou plusieurs boutiques avec les mêmes données.'],
];
$workflow = [
    ['step' => '01', 'title' => 'Créer la boutique', 'text' => 'Configurez la devise, les informations de vente et les utilisateurs.'],
    ['step' => '02', 'title' => 'Charger le catalogue', 'text' => 'Ajoutez produits, seuils, fournisseurs et stock initial.'],
    ['step' => '03', 'title' => 'Vendre au comptoir', 'text' => 'L’agent encaisse, confirme le panier et garde la facture disponible.'],
    ['step' => '04', 'title' => 'Piloter les résultats', 'text' => 'Le gérant suit ventes, dettes, mouvements, charges et marges.'],
];
$plans = [
    [
        'name' => 'Starter',
        'price' => '3 $',
        'period' => '/mois',
        'tag' => 'Démarrage',
        'features' => [
            '1 boutique',
            '2 utilisateurs',
            'Caisse et tickets',
            'Gestion des produits',
            'Stock initial et seuils',
            'Clients simples',
            'Créances clients',
            'Rapports essentiels',
            'Devise boutique',
            'Assistance standard',
        ],
        'featured' => false,
    ],
    [
        'name' => 'Business',
        'price' => '7 $',
        'period' => '/mois',
        'tag' => 'Boutique active',
        'features' => [
            '3 boutiques',
            '8 utilisateurs',
            'Toutes les fonctions Starter',
            'Approvisionnements fournisseurs',
            'Ajustements de stock',
            'Inventaire complet',
            'Charges et dépenses',
            'Historique des ventes',
            'Rapports complets',
            'Exports et impressions',
        ],
        'featured' => false,
    ],
    [
        'name' => 'Pro',
        'price' => '12 $',
        'period' => '/mois',
        'tag' => 'Croissance',
        'features' => [
            '8 boutiques',
            '20 utilisateurs',
            'Toutes les fonctions Business',
            'Gestion avancée des rôles',
            'Suivi multi-boutiques',
            'Alertes stock renforcées',
            'Analyse marges et charges',
            'Suivi avancé des créances',
            'Rapports financiers détaillés',
            'Assistance prioritaire',
        ],
        'featured' => true,
    ],
    [
        'name' => 'Réseau',
        'price' => '20 $',
        'period' => '/mois',
        'tag' => 'Multi-boutiques',
        'features' => [
            'Boutiques illimitées',
            'Utilisateurs illimités',
            'Toutes les fonctions Pro',
            'Pilotage réseau',
            'Centralisation des rapports',
            'Gestion étendue des équipes',
            'Suivi global stock et ventes',
            'Paramétrage personnalisé',
            'Accompagnement de déploiement',
            'Assistance prioritaire renforcée',
        ],
        'featured' => false,
    ],
];
$faqs = [
    ['question' => 'MadukaOne fonctionne pour une seule boutique ?', 'answer' => 'Oui. Le plan Starter couvre une petite boutique avec les fonctions essentielles de caisse, stock et rapports.'],
    ['question' => 'Les employés voient-ils toutes les données ?', 'answer' => 'Non. L’accès dépend du rôle et des permissions configurés pour chaque utilisateur de la boutique.'],
    ['question' => 'Puis-je suivre les dettes clients ?', 'answer' => 'Oui. Les créances restent visibles dans les ventes, les clients et les rapports afin de garder une trace fiable.'],
];
?>

<section class="public-home" style="--hero-image: url('<?= $heroImage ?>');">
    <div class="public-home-shade"></div>
    <div class="public-container relative z-10 grid min-h-[96svh] gap-8 pb-12 pt-32 lg:grid-cols-[1fr_.82fr] lg:items-center lg:gap-12 lg:pt-36">
        <div class="public-home-copy" data-reveal>
            <p class="public-home-kicker">ERP commercial pour boutiques</p>
            <h1>Une seule application pour vendre, gérer le stock et piloter la boutique.</h1>
            <p>
                MadukaOne rassemble la caisse, les produits, les clients, les créances, les approvisionnements, les charges et les rapports dans un espace simple à utiliser au quotidien.
            </p>

            <div class="public-home-actions">
                <a class="public-hero-cta" href="<?= $url('/login') ?>">Se connecter</a>
                <a class="public-hero-secondary" href="#plans">Voir les abonnements</a>
            </div>
        </div>

        <div class="public-home-card" data-reveal>
            <div class="public-home-card-head">
                <span>Vue gérant</span>
                <strong>Aujourd’hui</strong>
            </div>
            <div class="public-home-card-total">
                <span>Activité boutique</span>
                <strong>Ventes, stock et créances synchronisés</strong>
            </div>
            <div class="public-home-modules" aria-label="Résumé des modules">
                <?php foreach ($heroStats as $stat): ?>
                    <div class="public-home-module">
                        <strong><?= htmlspecialchars($stat['value'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars($stat['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section id="solutions" class="public-section bg-white">
    <div class="public-container">
        <div class="public-section-header" data-reveal>
            <p class="public-eyebrow">Pourquoi MadukaOne</p>
            <h2 class="public-section-title">Les tâches sensibles restent visibles au lieu de se perdre dans plusieurs cahiers.</h2>
        </div>

        <div class="public-problem-grid">
            <?php foreach ($painPoints as $point): ?>
                <article class="public-problem-card" data-reveal>
                    <h3><?= htmlspecialchars($point['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p><?= htmlspecialchars($point['text'], ENT_QUOTES, 'UTF-8') ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="modules" class="public-section bg-slate-50">
    <div class="public-container">
        <div class="public-section-header" data-reveal>
            <p class="public-eyebrow">Modules inclus</p>
            <h2 class="public-section-title">Tout ce qu’une boutique doit manipuler, regroupé dans des écrans lisibles.</h2>
        </div>

        <div class="public-feature-grid">
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

<section id="workflow" class="public-section bg-white">
    <div class="public-container grid gap-10 lg:grid-cols-[.82fr_1fr] lg:items-center">
        <div data-reveal>
            <p class="public-eyebrow">Utilisation quotidienne</p>
            <h2 class="public-section-title">Un flux clair pour l’agent de caisse et le gérant.</h2>
            <p class="public-section-copy">
                La page d’accueil présente le produit, mais les données de travail restent derrière la connexion. Chaque action sensible garde un contexte clair avant validation.
            </p>
            <a class="btn-primary mt-7 w-full sm:w-auto" href="<?= $url('/login') ?>">Ouvrir la connexion</a>
        </div>

        <div class="public-workflow public-workflow-grid" data-reveal>
            <?php foreach ($workflow as $item): ?>
                <article class="public-workflow-step">
                    <span><?= htmlspecialchars($item['step'], ENT_QUOTES, 'UTF-8') ?></span>
                    <div>
                        <h3><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p><?= htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="plans" class="public-section bg-slate-950">
    <div class="public-container">
        <div class="public-section-header" data-reveal>
            <p class="public-eyebrow public-eyebrow-light">Plans d’abonnement</p>
            <h2 class="public-section-title public-section-title-light">Choisissez selon la taille et le rythme de votre boutique.</h2>
        </div>

        <div class="public-plan-panel public-plan-panel-section" data-reveal>
            <div class="public-plan-heading">
                <div>
                    <p class="public-eyebrow">Activation boutique</p>
                    <h2>Commencez avec le plan adapté, puis évoluez quand l’activité grandit.</h2>
                </div>
                <span>Mensuel</span>
            </div>

            <div class="public-plan-grid">
                <?php foreach ($plans as $plan): ?>
                    <article class="public-plan-card <?= $plan['featured'] ? 'is-featured' : '' ?>">
                        <div class="public-plan-card-head">
                            <div>
                                <h3><?= htmlspecialchars($plan['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <p><?= htmlspecialchars($plan['tag'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <?php if ($plan['featured']): ?>
                                <span>Populaire</span>
                            <?php endif; ?>
                        </div>

                        <div class="public-plan-price">
                            <strong><?= htmlspecialchars($plan['price'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span><?= htmlspecialchars($plan['period'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>

                        <ul>
                            <?php foreach ($plan['features'] as $feature): ?>
                                <li><?= htmlspecialchars($feature, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>

                        <a href="<?= $url('/pricing') ?>">Commencer</a>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section id="faq" class="public-section bg-white">
    <div class="public-container grid gap-10 lg:grid-cols-[.75fr_1fr]">
        <div data-reveal>
            <p class="public-eyebrow">Questions fréquentes</p>
            <h2 class="public-section-title">Les réponses essentielles avant d’activer une boutique.</h2>
        </div>

        <div class="public-faq-list" data-reveal>
            <?php foreach ($faqs as $faq): ?>
                <article class="public-faq-item">
                    <h3><?= htmlspecialchars($faq['question'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p><?= htmlspecialchars($faq['answer'], ENT_QUOTES, 'UTF-8') ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="public-section bg-slate-50">
    <div class="public-container">
        <div class="public-final-cta" data-reveal>
            <div>
                <p class="public-eyebrow">Accès sécurisé</p>
                <h2 class="public-section-title">Connectez-vous pour gérer les ventes, le stock, les clients et les rapports de votre boutique.</h2>
            </div>
            <a class="public-cta-large" href="<?= $url('/login') ?>">Connexion</a>
        </div>
    </div>
</section>
