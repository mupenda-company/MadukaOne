<?php

$appConfig = require dirname(__DIR__, 4) . '/config/app.php';
$appName = (string) ($appConfig['name'] ?? 'MadukaOne');
$currentUser = is_array($currentUser ?? null) ? $currentUser : [];
$userName = (string) ($currentUser['nom'] ?? 'Super Admin');
$userEmail = (string) ($currentUser['email'] ?? '');
$activeMenu = (string) ($activeMenu ?? 'saas-dashboard');
$safe = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$navGroups = [
    [
        'label' => 'Pilotage',
        'items' => [
            ['key' => 'saas-dashboard', 'label' => 'Tableau de bord', 'href' => $url('/saas-admin'), 'icon' => 'M4 13h6V4H4v9Zm0 7h6v-4H4v4Zm10 0h6v-9h-6v9Zm0-16v4h6V4h-6Z'],
            ['key' => 'saas-shops', 'label' => 'Boutiques', 'href' => $url('/saas-admin/boutiques'), 'icon' => 'M4 10 12 4l8 6v10H4V10Zm5 10v-6h6v6'],
            ['key' => 'saas-categories', 'label' => 'Categories', 'href' => $url('/saas-admin/categories'), 'icon' => 'M4 6h16M4 12h16M4 18h16'],
        ],
    ],
    [
        'label' => 'Acces et droits',
        'items' => [
            ['key' => 'saas-users', 'label' => 'Utilisateurs', 'href' => $url('/saas-admin/utilisateurs'), 'icon' => 'M16 19c0-2.2-1.8-4-4-4H8c-2.2 0-4 1.8-4 4m12-7a3 3 0 1 0 0-6m4 13c0-1.9-1.3-3.5-3-3.9M10 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z'],
            ['key' => 'saas-rights', 'label' => 'Roles et droits', 'href' => $url('/saas-admin/droits'), 'icon' => 'M15 7a4 4 0 1 0-2.7 3.8L15 13.5V16h2.5v2.5H20V16l-5-5'],
        ],
    ],
    [
        'label' => 'Offre SaaS',
        'items' => [
            ['key' => 'saas-subscriptions', 'label' => 'Abonnements', 'href' => $url('/saas-admin/abonnements'), 'icon' => 'M4 7h16v10H4V7Zm3 4h4m-4 3h7'],
            ['key' => 'saas-features', 'label' => 'Fonctionnalites', 'href' => $url('/saas-admin/fonctionnalites'), 'icon' => 'M12 3v18M3 12h18M5 5l14 14M19 5 5 19'],
        ],
    ],
    [
        'label' => 'Administration',
        'items' => [
            ['key' => 'saas-activities', 'label' => 'Administration activite', 'href' => $url('/saas-admin/activites'), 'icon' => 'M4 12h4l2-6 4 12 2-6h4'],
            ['key' => 'saas-settings', 'label' => 'Parametres generaux', 'href' => $url('/saas-admin/parametres'), 'icon' => 'M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5ZM19 13v-2l-2.1-.4a7 7 0 0 0-.7-1.7l1.2-1.8-1.4-1.4-1.8 1.2a7 7 0 0 0-1.7-.7L12 4H10l-.4 2.1a7 7 0 0 0-1.7.7L6.1 5.6 4.7 7l1.2 1.8a7 7 0 0 0-.7 1.7L3 11v2l2.1.4a7 7 0 0 0 .7 1.7l-1.2 1.8L6 18.3l1.8-1.2a7 7 0 0 0 1.7.7L10 20h2l.4-2.1a7 7 0 0 0 1.7-.7l1.8 1.2 1.4-1.4-1.2-1.8a7 7 0 0 0 .7-1.7L19 13Z'],
            ['key' => 'saas-privacy', 'label' => 'Politique de confidentialite', 'href' => $url('/saas-admin/confidentialite'), 'icon' => 'M12 3 5 6v5c0 4.6 2.8 8 7 10 4.2-2 7-5.4 7-10V6l-7-3Zm-3 9 2 2 4-5'],
            ['key' => 'saas-terms', 'label' => 'Conditions d utilisation', 'href' => $url('/saas-admin/conditions'), 'icon' => 'M6 3h9l3 3v15H6V3Zm8 0v4h4M9 11h6M9 15h6'],
        ],
    ],
];
$icon = static function (string $path): string {
    return '<svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="' . $path . '" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
};
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= $safe($pageTitle . ' - Administration SaaS - ' . $appName) ?></title>
    <link rel="stylesheet" href="<?= $asset('assets/css/app.css') ?>?v=<?= (int) filemtime(dirname(__DIR__, 4) . '/public/assets/css/app.css') ?>">
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-950 antialiased">
    <div class="app-shell" data-app-shell>
        <div class="sidebar-overlay" data-sidebar-overlay></div>
        <aside class="app-sidebar">
            <div class="flex h-16 items-center justify-between border-b border-slate-800 px-4">
                <a class="flex min-w-0 items-center gap-3" href="<?= $url('/saas-admin') ?>">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-amber-400 text-sm font-black text-slate-950">M1</span>
                    <span class="sidebar-label min-w-0">
                        <span class="block truncate text-sm font-bold text-white">MadukaOne SaaS</span>
                        <span class="block truncate text-xs text-slate-400">Administration generale</span>
                    </span>
                </a>
                <button class="icon-btn-dark lg:hidden" type="button" data-sidebar-close aria-label="Fermer le menu">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
            </div>

            <nav class="flex-1 space-y-5 overflow-y-auto px-3 py-4">
                <?php foreach ($navGroups as $group): ?>
                    <div class="space-y-2">
                        <p class="sidebar-label px-3 text-[11px] font-black uppercase tracking-[.18em] text-slate-500"><?= $safe($group['label']) ?></p>
                        <?php foreach ($group['items'] as $item): ?>
                            <a class="sidebar-link <?= $activeMenu === $item['key'] ? 'is-active' : '' ?>" href="<?= $item['href'] ?>">
                                <?= $icon($item['icon']) ?>
                                <span class="sidebar-label truncate"><?= $safe($item['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <div class="my-4 border-t border-slate-800"></div>
                <a class="sidebar-link <?= $activeMenu === 'saas-shop-space' ? 'is-active' : '' ?>" href="<?= $url('/saas-admin/espace-boutique') ?>">
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span class="sidebar-label truncate">Espace boutique</span>
                </a>
            </nav>

            <div class="border-t border-slate-800 p-3">
                <form method="post" action="<?= $url('/logout') ?>" accept-charset="UTF-8" data-confirm-form>
                    <button class="sidebar-link w-full" type="button" data-confirm data-confirm-title="Se deconnecter ?" data-confirm-message="Votre session sera fermee." data-confirm-accept="Oui, deconnecter" data-confirm-progress="Deconnexion...">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 17 15 12l-5-5M15 12H3m9-8h6a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3h-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span class="sidebar-label">Deconnexion</span>
                    </button>
                </form>
            </div>
        </aside>

        <div class="app-main">
            <header class="app-topbar sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur">
                <div class="topbar-row flex min-h-16 items-center justify-between gap-3 px-4 py-2 sm:px-6 lg:px-8">
                    <div class="topbar-title flex min-w-0 items-center gap-3">
                        <button class="icon-btn lg:hidden" type="button" data-sidebar-toggle aria-label="Ouvrir le menu"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
                        <button class="icon-btn hidden lg:inline-flex" type="button" data-sidebar-toggle aria-label="Reduire le menu"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h10M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-950"><?= $safe($pageTitle) ?></p>
                            <p class="hidden truncate text-xs text-slate-500 sm:block">Pilotage global de toutes les boutiques</p>
                        </div>
                    </div>
                    <div class="relative" data-user-menu>
                        <button class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2 text-left transition hover:bg-slate-50" type="button" data-user-menu-toggle aria-expanded="false">
                            <span class="hidden text-right sm:block">
                                <span class="block max-w-44 truncate text-sm font-semibold text-slate-900"><?= $safe($userName) ?></span>
                                <span class="block max-w-44 truncate text-xs text-slate-500"><?= $safe($userEmail) ?></span>
                            </span>
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-slate-950 text-xs font-black uppercase text-white"><?= $safe(substr($userName, 0, 1) ?: 'U') ?></span>
                            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <div class="absolute right-0 top-full z-50 mt-2 hidden w-64 rounded-lg border border-slate-200 bg-white p-2 shadow-xl" data-user-menu-panel>
                            <div class="border-b border-slate-100 px-3 py-2">
                                <p class="truncate text-sm font-bold text-slate-950"><?= $safe($userName) ?></p>
                                <p class="truncate text-xs text-slate-500"><?= $safe($userEmail) ?></p>
                            </div>
                            <a class="mt-2 flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" href="<?= $url('/saas-admin/profil') ?>">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 21c0-3.3-2.7-6-6-6h-4c-3.3 0-6 2.7-6 6M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                Mon profil
                            </a>
                            <a class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" href="<?= $url('/saas-admin/espace-boutique') ?>">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                Espace boutique
                            </a>
                            <form class="mt-2 border-t border-slate-100 pt-2" method="post" action="<?= $url('/logout') ?>" accept-charset="UTF-8" data-confirm-form>
                                <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-semibold text-red-700 hover:bg-red-50" type="button" data-confirm data-confirm-title="Se deconnecter ?" data-confirm-message="Votre session sera fermee." data-confirm-accept="Oui, deconnecter" data-confirm-progress="Deconnexion...">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 17 15 12l-5-5M15 12H3m9-8h6a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3h-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    Deconnexion
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="px-4 py-5 sm:px-6 lg:px-8">
                <?php require dirname(__DIR__, 2) . '/layouts/partials/flash.php'; ?>
                <?= $content ?? '' ?>
            </main>
        </div>
    </div>

    <?php require dirname(__DIR__, 2) . '/layouts/partials/confirm-modal.php'; ?>
    <script>
        const shell = document.querySelector('[data-app-shell]');
        const sidebarToggles = document.querySelectorAll('[data-sidebar-toggle]');
        const sidebarClose = document.querySelector('[data-sidebar-close]');
        const sidebarOverlay = document.querySelector('[data-sidebar-overlay]');
        const confirmModal = document.querySelector('[data-confirm-modal]');
        const userMenu = document.querySelector('[data-user-menu]');
        const userMenuToggle = document.querySelector('[data-user-menu-toggle]');
        const userMenuPanel = document.querySelector('[data-user-menu-panel]');
        const confirmPanel = confirmModal?.querySelector('[data-confirm-panel]');
        const confirmTitle = confirmModal?.querySelector('[data-confirm-title]');
        const confirmMessage = confirmModal?.querySelector('[data-confirm-message]');
        const confirmStatus = confirmModal?.querySelector('[data-confirm-status]');
        const confirmCancel = confirmModal?.querySelector('[data-confirm-cancel]');
        const confirmAccept = confirmModal?.querySelector('[data-confirm-accept]');
        let pendingConfirmTarget = null;
        const closeConfirmModal = () => {
            pendingConfirmTarget = null;
            confirmModal?.classList.add('hidden');
            confirmModal?.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
            if (confirmStatus) confirmStatus.classList.add('hidden');
            if (confirmAccept) confirmAccept.disabled = false;
        };
        sidebarToggles.forEach((toggle) => toggle.addEventListener('click', () => {
            if (window.matchMedia('(min-width: 1024px)').matches) {
                shell?.classList.toggle('is-sidebar-collapsed');
                return;
            }
            shell?.classList.toggle('is-sidebar-open');
        }));
        sidebarClose?.addEventListener('click', () => shell?.classList.remove('is-sidebar-open'));
        sidebarOverlay?.addEventListener('click', () => shell?.classList.remove('is-sidebar-open'));
        userMenuToggle?.addEventListener('click', (event) => {
            event.stopPropagation();
            userMenuPanel?.classList.toggle('hidden');
            userMenuToggle.setAttribute('aria-expanded', userMenuPanel?.classList.contains('hidden') ? 'false' : 'true');
        });
        document.addEventListener('click', (event) => {
            if (userMenu && !userMenu.contains(event.target)) {
                userMenuPanel?.classList.add('hidden');
                userMenuToggle?.setAttribute('aria-expanded', 'false');
            }
        });
        document.addEventListener('click', (event) => {
            const trigger = event.target?.closest?.('[data-confirm]');
            if (!trigger) return;
            event.preventDefault();
            const form = trigger.closest('form');
            pendingConfirmTarget = form ? { type: 'form', form } : null;
            if (confirmTitle) confirmTitle.textContent = trigger.getAttribute('data-confirm-title') || 'Confirmation';
            if (confirmMessage) confirmMessage.textContent = trigger.getAttribute('data-confirm-message') || 'Voulez-vous continuer ?';
            if (confirmAccept) {
                confirmAccept.textContent = trigger.getAttribute('data-confirm-accept') || 'Confirmer';
                confirmAccept.dataset.progressText = trigger.getAttribute('data-confirm-progress') || 'Action en cours...';
            }
            confirmModal?.classList.remove('hidden');
            confirmModal?.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            window.setTimeout(() => confirmPanel?.focus(), 0);
        });
        confirmCancel?.addEventListener('click', closeConfirmModal);
        confirmModal?.addEventListener('click', (event) => { if (event.target === confirmModal) closeConfirmModal(); });
        confirmAccept?.addEventListener('click', () => {
            if (confirmAccept) {
                confirmAccept.disabled = true;
                confirmAccept.textContent = confirmAccept.dataset.progressText || 'Action en cours...';
            }
            if (pendingConfirmTarget?.type === 'form') pendingConfirmTarget.form.submit();
        });
    </script>
</body>
</html>
