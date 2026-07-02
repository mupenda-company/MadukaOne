<?php

$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$appName = (string) ($appConfig['name'] ?? 'MadukaOne');
$pageTitle = (string) ($pageTitle ?? $appName);
$currentUser = is_array($currentUser ?? null) ? $currentUser : [];
$shops = is_array($shops ?? null) ? $shops : [];
$activeShop = is_array($activeShop ?? null) ? $activeShop : ($shops[0] ?? []);
$activeMenu = (string) ($activeMenu ?? '');
$basePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
$basePath = ($basePath === '' || $basePath === '.') ? '' : $basePath;

$asset = static function (string $path) use ($basePath): string {
    return htmlspecialchars($basePath . '/' . ltrim($path, '/'), ENT_QUOTES, 'UTF-8');
};

$url = static function (string $path, array $query = []) use ($basePath): string {
    $href = $basePath . '/' . ltrim($path, '/');

    if ($path === '/') {
        $href = $basePath === '' ? '/' : $basePath . '/';
    }

    if ($query !== []) {
        $href .= (str_contains($href, '?') ? '&' : '?') . http_build_query($query);
    }

    return htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
};
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= htmlspecialchars($pageTitle . ' - ' . $appName, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= $asset('assets/css/app.css') ?>?v=<?= (int) filemtime(dirname(__DIR__, 3) . '/public/assets/css/app.css') ?>">
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-950 antialiased">
    <div class="app-shell" data-app-shell>
        <?php require __DIR__ . '/partials/sidebar.php'; ?>

        <div class="app-main">
            <?php require __DIR__ . '/partials/navbar.php'; ?>

            <main class="px-4 py-5 sm:px-6 lg:px-8">
                <?php require __DIR__ . '/partials/flash.php'; ?>
                <?= $content ?? '' ?>
            </main>
        </div>
    </div>

    <?php require __DIR__ . '/partials/confirm-modal.php'; ?>

    <script>
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                window.location.reload();
            }
        });

        const shell = document.querySelector('[data-app-shell]');
        const sidebarToggles = document.querySelectorAll('[data-sidebar-toggle]');
        const sidebarClose = document.querySelector('[data-sidebar-close]');
        const sidebarOverlay = document.querySelector('[data-sidebar-overlay]');
        const userMenu = document.querySelector('[data-user-menu]');
        const userMenuToggle = document.querySelector('[data-user-menu-toggle]');
        const userMenuPanel = document.querySelector('[data-user-menu-panel]');
        const confirmModal = document.querySelector('[data-confirm-modal]');
        const confirmPanel = confirmModal?.querySelector('[data-confirm-panel]');
        const confirmTitle = confirmModal?.querySelector('[data-confirm-title]');
        const confirmMessage = confirmModal?.querySelector('[data-confirm-message]');
        const confirmStatus = confirmModal?.querySelector('[data-confirm-status]');
        const confirmCancel = confirmModal?.querySelector('[data-confirm-cancel]');
        const confirmAccept = confirmModal?.querySelector('[data-confirm-accept]');
        let pendingConfirmTarget = null;

        const closeMobileSidebar = () => shell?.classList.remove('is-sidebar-open');
        const closeUserMenu = () => {
            userMenu?.classList.remove('is-open');
            userMenuToggle?.setAttribute('aria-expanded', 'false');
        };

        const toggleUserMenu = () => {
            const willOpen = !userMenu?.classList.contains('is-open');

            userMenu?.classList.toggle('is-open', willOpen);
            userMenuToggle?.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        };

        const closeConfirmModal = () => {
            pendingConfirmTarget = null;

            confirmModal?.classList.add('hidden');
            confirmModal?.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');

            if (confirmStatus) {
                confirmStatus.textContent = '';
                confirmStatus.classList.add('hidden');
            }

            if (confirmAccept) {
                confirmAccept.disabled = false;
            }
        };

        const openConfirmModal = (trigger, target) => {
            pendingConfirmTarget = target;

            if (confirmTitle) {
                confirmTitle.textContent = trigger.getAttribute('data-confirm-title') || 'Confirmation requise';
            }

            if (confirmMessage) {
                confirmMessage.textContent = trigger.getAttribute('data-confirm-message') || 'Voulez-vous continuer ?';
            }

            if (confirmAccept) {
                confirmAccept.textContent = trigger.getAttribute('data-confirm-accept') || 'Confirmer';
                confirmAccept.dataset.progressText = trigger.getAttribute('data-confirm-progress') || 'Action en cours...';
                confirmAccept.disabled = false;
            }

            if (confirmStatus) {
                confirmStatus.textContent = '';
                confirmStatus.classList.add('hidden');
            }

            confirmModal?.classList.remove('hidden');
            confirmModal?.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            window.setTimeout(() => confirmPanel?.focus(), 0);
        };

        const setSidebarCollapsed = (collapsed) => {
            shell?.classList.toggle('is-sidebar-collapsed', collapsed);
            sidebarToggles.forEach((toggle) => {
                toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                toggle.setAttribute('aria-label', collapsed ? 'Développer le menu' : 'Réduire le menu');
            });
            localStorage.setItem('madukaone.sidebarCollapsed', collapsed ? '1' : '0');
        };

        if (localStorage.getItem('madukaone.sidebarCollapsed') === '1') {
            setSidebarCollapsed(true);
        }

        sidebarToggles.forEach((toggle) => toggle.addEventListener('click', () => {
            if (window.matchMedia('(min-width: 1024px)').matches) {
                setSidebarCollapsed(!shell?.classList.contains('is-sidebar-collapsed'));
                return;
            }

            shell?.classList.toggle('is-sidebar-open');
        }));

        sidebarClose?.addEventListener('click', closeMobileSidebar);
        sidebarOverlay?.addEventListener('click', closeMobileSidebar);
        userMenuToggle?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            toggleUserMenu();
        });

        document.addEventListener('click', (event) => {
            if (userMenu && !userMenu.contains(event.target)) {
                closeUserMenu();
            }

            const trigger = event.target?.closest?.('[data-confirm]');

            if (!trigger) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            closeUserMenu();

            const form = trigger.closest('form');

            if (form) {
                openConfirmModal(trigger, { type: 'form', form });
                return;
            }

            if (trigger instanceof HTMLAnchorElement) {
                openConfirmModal(trigger, { type: 'href', href: trigger.href });
            }
        });

        confirmCancel?.addEventListener('click', closeConfirmModal);
        confirmModal?.addEventListener('click', (event) => {
            if (event.target === confirmModal) {
                closeConfirmModal();
            }
        });
        confirmAccept?.addEventListener('click', () => {
            const target = pendingConfirmTarget;

            if (!target) {
                closeConfirmModal();
                return;
            }

            const progressText = confirmAccept.dataset.progressText || 'Action en cours...';
            confirmAccept.disabled = true;
            confirmAccept.textContent = progressText;

            if (confirmStatus) {
                confirmStatus.textContent = progressText;
                confirmStatus.classList.remove('hidden');
            }

            if (target.type === 'form' && target.form instanceof HTMLFormElement) {
                target.form.submit();
                return;
            }

            if (target.type === 'href' && target.href) {
                window.location.href = target.href;
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && userMenu?.classList.contains('is-open')) {
                closeUserMenu();
            }

            if (event.key === 'Escape' && confirmModal && !confirmModal.classList.contains('hidden')) {
                closeConfirmModal();
            }
        });
    </script>
    <?php foreach ((array) ($pageScripts ?? []) as $script): ?>
        <script src="<?= $asset((string) $script) ?>?v=<?= (int) filemtime(dirname(__DIR__, 3) . '/public/' . ltrim((string) $script, '/')) ?>" defer></script>
    <?php endforeach; ?>
</body>
</html>
