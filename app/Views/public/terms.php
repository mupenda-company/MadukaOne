<?php

$sections = [
    [
        'title' => 'Accès à l’application',
        'text' => 'MadukaOne est réservé aux utilisateurs autorisés par l’administrateur de la boutique. Les identifiants sont personnels et ne doivent pas être partagés.',
    ],
    [
        'title' => 'Utilisation professionnelle',
        'text' => 'Les modules de caisse, stock, clients, fournisseurs, dépenses et rapports doivent être utilisés pour des opérations réelles, vérifiables et conformes aux règles internes de la boutique.',
    ],
    [
        'title' => 'Exactitude des données',
        'text' => 'Chaque utilisateur doit saisir des informations exactes. Les ventes, mouvements de stock, règlements et dépenses peuvent avoir un impact direct sur les rapports financiers.',
    ],
    [
        'title' => 'Opérations sensibles',
        'text' => 'Certaines actions sont limitées par rôle et peuvent demander une confirmation. Les journaux de vente et de stock doivent rester exploitables pour l’audit.',
    ],
    [
        'title' => 'Disponibilité et maintenance',
        'text' => 'Des interruptions peuvent intervenir pendant les opérations de maintenance, de sauvegarde ou de correction technique. Les responsables doivent organiser les contrôles nécessaires.',
    ],
];
?>

<section class="public-legal-hero">
    <div class="public-container" data-reveal>
        <p class="public-eyebrow text-teal-200">Regles d usage</p>
        <h1 class="max-w-3xl text-4xl font-black tracking-normal text-white sm:text-5xl">Conditions d’utilisation</h1>
        <p class="mt-5 max-w-2xl text-sm leading-7 text-white/70">
            Ces conditions encadrent l’utilisation de MadukaOne par les administrateurs, gérants, caissiers et agents autorisés.
        </p>
    </div>
</section>

<section class="public-section bg-white">
    <div class="public-container">
        <div class="grid gap-5 md:grid-cols-2">
            <?php foreach ($sections as $section): ?>
                <article class="public-legal-card" data-reveal>
                    <h2><?= htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p><?= htmlspecialchars($section['text'], ENT_QUOTES, 'UTF-8') ?></p>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="public-final-cta mt-8" data-reveal>
            <div>
                <p class="public-eyebrow">Connexion</p>
                <h2 class="public-section-title">En utilisant MadukaOne, vous acceptez ces conditions.</h2>
            </div>
            <a class="public-cta-large" href="<?= $url('/login') ?>">Continuer</a>
        </div>
    </div>
</section>
