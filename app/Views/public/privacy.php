<?php

$sections = [
    [
        'title' => 'Données collectées',
        'items' => [
            'Informations de compte : nom, email, rôle, boutique rattachée et état du compte.',
            'Données commerciales saisies dans l’application : ventes, produits, stock, clients, fournisseurs, dépenses et rapports.',
            'Données techniques utiles à la sécurité : dernière connexion, fournisseur d’authentification et journal applicatif.',
        ],
    ],
    [
        'title' => 'Utilisation des données',
        'items' => [
            'Permettre l’accès aux modules autorisés selon le profil utilisateur.',
            'Assurer la traçabilité des ventes, mouvements de stock et opérations sensibles.',
            'Produire des rapports de gestion pour la boutique active.',
        ],
    ],
    [
        'title' => 'Protection et conservation',
        'items' => [
            'Les mots de passe sont stockés sous forme de hash et ne sont pas affichés par l’application.',
            'Les données opérationnelles sont conservées pour les besoins d’audit, de comptabilité et de suivi commercial.',
            'Les accès doivent rester personnels ; chaque utilisateur est responsable de la confidentialité de son compte.',
        ],
    ],
];
?>

<section class="public-legal-hero">
    <div class="public-container" data-reveal>
        <p class="public-eyebrow text-teal-200">Cadre de confiance</p>
        <h1 class="max-w-3xl text-4xl font-black tracking-normal text-white sm:text-5xl">Politique de confidentialité</h1>
        <p class="mt-5 max-w-2xl text-sm leading-7 text-white/70">
            Cette page explique comment MadukaOne traite les données nécessaires à la gestion commerciale, au contrôle des accès et à la traçabilité des opérations.
        </p>
    </div>
</section>

<section class="public-section bg-white">
    <div class="public-container grid gap-6 lg:grid-cols-[.7fr_1.3fr]">
        <aside class="public-legal-aside" data-reveal>
            <p class="text-sm font-black text-slate-950">Dernière mise à jour</p>
            <p class="mt-2 text-sm text-slate-600">6 juillet 2026</p>
            <a class="btn-secondary mt-5 w-full" href="<?= $url('/login') ?>">Aller a la connexion</a>
        </aside>

        <div class="space-y-5">
            <?php foreach ($sections as $section): ?>
                <article class="public-legal-card" data-reveal>
                    <h2><?= htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <ul>
                        <?php foreach ($section['items'] as $item): ?>
                            <li><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </article>
            <?php endforeach; ?>

            <article class="public-legal-card" data-reveal>
                <h2>Contact et demandes</h2>
                <p>
                    Toute demande relative aux données doit être adressée à l’administrateur de la boutique ou au responsable technique de l’installation MadukaOne concernée.
                </p>
            </article>
        </div>
    </div>
</section>
