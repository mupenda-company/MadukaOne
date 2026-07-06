<?php

$appName = (string) ($appName ?? 'MadukaOne');
$pageTitle = (string) ($pageTitle ?? $appName);
$pageDescription = (string) ($pageDescription ?? '');
$activePublicPage = (string) ($activePublicPage ?? '');
$publicNav = [
    ['key' => 'home', 'label' => 'Accueil', 'href' => $url('/')],
    ['key' => 'privacy', 'label' => 'Confidentialité', 'href' => $url('/privacy')],
    ['key' => 'terms', 'label' => 'Conditions', 'href' => $url('/terms')],
];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($pageTitle . ' - ' . $appName, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= $asset('assets/css/app.css') ?>?v=<?= (int) filemtime(dirname(__DIR__, 3) . '/public/assets/css/app.css') ?>">
</head>
<body class="min-h-screen bg-slate-950 font-sans text-slate-950 antialiased">
    <div class="public-shell">
        <header class="public-nav">
            <a class="public-brand" href="<?= $url('/') ?>" aria-label="MadukaOne accueil">
                <span class="public-brand-mark">M1</span>
                <span>
                    <span class="block text-sm font-black leading-5 text-white">MadukaOne</span>
                    <span class="block text-xs font-semibold text-white/60">ERP commerce</span>
                </span>
            </a>

            <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigation publique">
                <?php foreach ($publicNav as $item): ?>
                    <a class="public-nav-link <?= $activePublicPage === $item['key'] ? 'is-active' : '' ?>" href="<?= $item['href'] ?>">
                        <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="flex items-center gap-2">
                <a class="public-login-link" href="<?= $url('/login') ?>">Connexion</a>
                <a class="public-cta" href="<?= $url('/login') ?>">Accéder</a>
            </div>
        </header>

        <main>
            <?= $content ?? '' ?>
        </main>

        <footer class="public-footer">
            <div class="public-footer-grid">
                <div>
                    <a class="public-brand" href="<?= $url('/') ?>" aria-label="MadukaOne accueil">
                        <span class="public-brand-mark">M1</span>
                        <span>
                            <span class="block text-sm font-black leading-5 text-white">MadukaOne</span>
                            <span class="block text-xs font-semibold text-white/60">ERP commerce et logistique</span>
                        </span>
                    </a>
                    <p class="mt-4 max-w-md text-sm leading-6 text-white/60">
                        Une interface claire pour vendre, suivre le stock, surveiller les créances et piloter les boutiques avec des données fiables.
                    </p>
                </div>

                <div class="grid gap-3 text-sm font-semibold text-white/70 sm:grid-cols-3 lg:justify-items-end">
                    <a class="transition hover:text-white" href="<?= $url('/') ?>">Accueil</a>
                    <a class="transition hover:text-white" href="<?= $url('/privacy') ?>">Politique de confidentialité</a>
                    <a class="transition hover:text-white" href="<?= $url('/terms') ?>">Conditions d’utilisation</a>
                </div>
            </div>
            <div class="mt-8 border-t border-white/10 pt-5 text-xs font-semibold text-white/45">
                &copy; <?= date('Y') ?> MadukaOne. Tous droits réservés.
            </div>
        </footer>
    </div>

    <script>
        const revealItems = document.querySelectorAll('[data-reveal]');

        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.16 });

            revealItems.forEach((item) => observer.observe(item));
        } else {
            revealItems.forEach((item) => item.classList.add('is-visible'));
        }
    </script>
</body>
</html>
