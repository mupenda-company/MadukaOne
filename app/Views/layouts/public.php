<?php

$appName = (string) ($appName ?? 'MadukaOne');
$pageTitle = (string) ($pageTitle ?? $appName);
$pageDescription = (string) ($pageDescription ?? '');
$activePublicPage = (string) ($activePublicPage ?? '');
$publicNav = [
    ['key' => 'home', 'label' => 'Accueil', 'href' => $url('/')],
    ['key' => 'modules', 'label' => 'Modules', 'href' => $url('/') . '#modules'],
    ['key' => 'workflow', 'label' => 'Fonctionnement', 'href' => $url('/') . '#workflow'],
    ['key' => 'plans', 'label' => 'Abonnements', 'href' => $url('/') . '#plans'],
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
    <div class="public-shell" data-public-shell>
        <header class="public-nav">
            <a class="public-brand" href="<?= $url('/') ?>" aria-label="MadukaOne accueil" data-public-nav-link="home">
                <span class="public-brand-mark">M1</span>
                <span>
                    <span class="block text-sm font-black leading-5 text-white">MadukaOne</span>
                    <span class="block text-xs font-semibold text-white/60">ERP commerce</span>
                </span>
            </a>

            <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigation publique">
                <?php foreach ($publicNav as $item): ?>
                    <a class="public-nav-link <?= $activePublicPage === $item['key'] ? 'is-active' : '' ?>" href="<?= $item['href'] ?>" data-public-nav-link="<?= htmlspecialchars($item['key'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="flex items-center gap-2">
                <?php if (!$isAuthenticated): ?>
                    <a class="public-login-link" href="<?= $url('/login') ?>">Connexion</a>
                    <a class="public-cta hidden sm:inline-flex" href="<?= $url('/login') ?>">Accéder</a>
                <?php else: ?>
                    <a class="public-cta" href="<?= $url('/dashboard') ?>">Accéder à la boutique</a>
                <?php endif; ?>
                <button class="public-menu-button" type="button" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="public-mobile-menu" data-public-menu-toggle>
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </header>

        <div class="public-mobile-overlay" data-public-menu-close></div>
        <nav id="public-mobile-menu" class="public-mobile-menu" aria-label="Navigation mobile">
            <div class="public-mobile-menu-head">
                <div>
                    <span>MadukaOne</span>
                    <strong>Menu</strong>
                </div>
                <button type="button" aria-label="Fermer le menu" data-public-menu-close>×</button>
            </div>

            <div class="public-mobile-menu-links">
                <?php foreach ($publicNav as $item): ?>
                    <a class="public-mobile-link <?= $activePublicPage === $item['key'] ? 'is-active' : '' ?>" href="<?= $item['href'] ?>" data-public-nav-link="<?= htmlspecialchars($item['key'], ENT_QUOTES, 'UTF-8') ?>" data-public-menu-close>
                        <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (!$isAuthenticated): ?>
                <a class="public-mobile-cta" href="<?= $url('/login') ?>">Se connecter</a>
            <?php else: ?>
                <a class="public-mobile-cta" href="<?= $url('/dashboard') ?>">Accéder à la boutique</a>
            <?php endif; ?>
        </nav>

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
                    <a class="transition hover:text-white" href="<?= $url('/terms') ?>">Conditions d'utilisation</a>
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

        const publicShell = document.querySelector('[data-public-shell]');
        const publicMenuToggle = document.querySelector('[data-public-menu-toggle]');
        const publicMenuClosers = document.querySelectorAll('[data-public-menu-close]');

        const setPublicMenuOpen = (isOpen) => {
            publicShell?.classList.toggle('is-public-menu-open', isOpen);
            publicMenuToggle?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            document.body.classList.toggle('overflow-hidden', isOpen);
        };

        publicMenuToggle?.addEventListener('click', () => {
            setPublicMenuOpen(!publicShell?.classList.contains('is-public-menu-open'));
        });

        publicMenuClosers.forEach((item) => {
            item.addEventListener('click', () => setPublicMenuOpen(false));
        });

        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                setPublicMenuOpen(false);
            }
        });

        const sectionLinks = document.querySelectorAll('[data-public-nav-link]');
        const trackedSections = ['modules', 'workflow', 'plans']
            .map((id) => document.getElementById(id))
            .filter(Boolean);

        const setActivePublicLink = (key) => {
            sectionLinks.forEach((link) => {
                link.classList.toggle('is-active', link.dataset.publicNavLink === key);
            });
        };

        const updateActivePublicLink = () => {
            let activeKey = 'home';
            const navHeight = document.querySelector('.public-nav')?.getBoundingClientRect().height || 0;
            const triggerLine = window.scrollY + navHeight + 32;

            trackedSections.forEach((section) => {
                if (section.offsetTop <= triggerLine) {
                    activeKey = section.id;
                }
            });

            setActivePublicLink(activeKey);
        };

        const scrollToPublicSection = (key, updateHash = true) => {
            if (trackedSections.length === 0) {
                return false;
            }

            const target = key === 'home' ? document.body : document.getElementById(key);

            if (!target) {
                return false;
            }

            const navHeight = document.querySelector('.public-nav')?.getBoundingClientRect().height || 0;
            const targetTop = key === 'home'
                ? 0
                : Math.max(0, window.scrollY + target.getBoundingClientRect().top - navHeight - 16);
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            setPublicMenuOpen(false);
            setActivePublicLink(key);
            window.scrollTo({ top: targetTop, behavior: reduceMotion ? 'auto' : 'smooth' });

            if (updateHash) {
                const nextUrl = key === 'home'
                    ? window.location.pathname + window.location.search
                    : '#' + encodeURIComponent(key);
                window.history.pushState({ publicSection: key }, '', nextUrl);
            }

            return true;
        };

        sectionLinks.forEach((link) => {
            link.addEventListener('click', (event) => {
                const key = link.dataset.publicNavLink || 'home';

                if (scrollToPublicSection(key)) {
                    event.preventDefault();
                }
            });
        });

        if (sectionLinks.length > 0 && trackedSections.length > 0) {
            updateActivePublicLink();
            window.addEventListener('scroll', updateActivePublicLink, { passive: true });
            window.addEventListener('popstate', () => {
                const key = decodeURIComponent(window.location.hash.replace(/^#/, '')) || 'home';
                scrollToPublicSection(key, false);
            });

            const initialKey = decodeURIComponent(window.location.hash.replace(/^#/, ''));
            if (initialKey) {
                window.requestAnimationFrame(() => scrollToPublicSection(initialKey, false));
            }
        }
    </script>
</body>
</html>
