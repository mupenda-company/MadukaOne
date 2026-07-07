<?php

$role = strtolower((string) ($currentUser['role'] ?? $currentUser['role_legacy'] ?? 'agent'));
$isAdmin = in_array($role, ['admin', 'super_admin', 'gerant'], true);

$navSections = [
    [
        'label' => 'Pilotage',
        'items' => [
            ['key' => 'dashboard', 'label' => 'Tableau de bord', 'href' => $url('/dashboard'), 'roles' => ['admin', 'super_admin', 'gerant'], 'icon' => 'dashboard'],
            ['key' => 'reports', 'label' => 'Rapports', 'href' => $url('/rapports/ventes'), 'roles' => ['admin', 'super_admin', 'gerant'], 'icon' => 'chart'],
        ],
    ],
    [
        'label' => 'Ventes et clients',
        'items' => [
            ['key' => 'pos', 'label' => 'Caisse POS', 'href' => $url('/pos'), 'roles' => ['admin', 'super_admin', 'gerant', 'agent'], 'icon' => 'pos'],
            ['key' => 'sales', 'label' => 'Historique ventes', 'href' => $url('/sales'), 'roles' => ['admin', 'super_admin', 'gerant', 'agent'], 'icon' => 'receipt'],
            ['key' => 'customers', 'label' => 'Clients et credits', 'href' => $url('/customers'), 'roles' => ['admin', 'super_admin', 'gerant', 'agent'], 'icon' => 'users'],
        ],
    ],
    [
        'label' => 'Stock et catalogue',
        'items' => [
            ['key' => 'products', 'label' => 'Catalogue produits', 'href' => $url('/products'), 'roles' => ['admin', 'super_admin', 'gerant'], 'icon' => 'box'],
            ['key' => 'stock', 'label' => 'Stock et inventaire', 'href' => $url('/stock/movements'), 'roles' => ['admin', 'super_admin', 'gerant'], 'icon' => 'stock'],
        ],
    ],
    [
        'label' => 'Achats et fournisseurs',
        'items' => [
            ['key' => 'supplies', 'label' => 'Approvisionnements', 'href' => $url('/supplies'), 'roles' => ['admin', 'super_admin', 'gerant'], 'icon' => 'truck'],
            ['key' => 'suppliers', 'label' => 'Fournisseurs', 'href' => $url('/suppliers'), 'roles' => ['admin', 'super_admin', 'gerant'], 'icon' => 'handshake'],
        ],
    ],
    [
        'label' => 'Finance',
        'items' => [
            ['key' => 'finances', 'label' => 'Depenses et finances', 'href' => $url('/finances'), 'roles' => ['admin', 'super_admin'], 'icon' => 'finance'],
        ],
    ],
    [
        'label' => 'Administration',
        'items' => [
            ['key' => 'users', 'label' => 'Utilisateurs', 'href' => $url('/users'), 'roles' => ['admin', 'super_admin'], 'icon' => 'shield'],
            ['key' => 'roles', 'label' => 'Roles et permissions', 'href' => $url('/roles'), 'roles' => ['admin', 'super_admin'], 'icon' => 'key'],
            ['key' => 'shop_settings', 'label' => 'Parametres boutique', 'href' => $url('/shops/settings'), 'roles' => ['admin', 'super_admin', 'gerant'], 'icon' => 'settings'],
        ],
    ],
];

$icon = static function (string $name): string {
    $paths = [
        'dashboard' => '<path d="M4 13h6V4H4v9Zm0 7h6v-4H4v4Zm10 0h6v-9h-6v9Zm0-16v4h6V4h-6Z" fill="currentColor"/>',
        'pos' => '<path d="M5 5h14v10H5V5Zm3 14h8M9 15v4m6-4v4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'receipt' => '<path d="M7 3h10l2 2v16l-3-2-2 2-2-2-2 2-2-2-3 2V5l2-2Zm2 6h6M9 13h6M9 17h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'box' => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Zm0 9 8-4.5M12 12 4 7.5M12 12v9" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'stock' => '<path d="M4 19V5m0 14h16M8 16V9m4 7V6m4 10v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'truck' => '<path d="M3 7h11v9H3V7Zm11 3h4l3 3v3h-7v-6ZM7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'handshake' => '<path d="M7 12 4.5 9.5a3 3 0 0 1 4.2-4.2L11 7.6l2.3-2.3a3 3 0 0 1 4.2 4.2L15 12m-8 0 5 5 5-5m-10 0 2.5-2.5M17 12l-2.5-2.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'users' => '<path d="M16 19c0-2.2-1.8-4-4-4H8c-2.2 0-4 1.8-4 4m12-7a3 3 0 1 0 0-6m4 13c0-1.9-1.3-3.5-3-3.9M10 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'finance' => '<path d="M4 18h16M7 15V9m5 6V6m5 9v-4M5 21h14M12 3l8 4H4l8-4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'chart' => '<path d="M4 19V5m0 14h16M8 15l3-4 3 2 5-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'shield' => '<path d="M12 3 5 6v5c0 4.2 2.7 8 7 10 4.3-2 7-5.8 7-10V6l-7-3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'key' => '<path d="M15 7a4 4 0 1 0-2.7 3.8L15 13.5V16h2.5v2.5H20V16l-5-5m-6-1h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'settings' => '<path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z" stroke="currentColor" stroke-width="2"/><path d="M4 12h2m12 0h2M12 4v2m0 12v2M6.3 6.3l1.4 1.4m8.6 8.6 1.4 1.4m0-11.4-1.4 1.4m-8.6 8.6-1.4 1.4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
    ];

    return '<svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . ($paths[$name] ?? $paths['dashboard']) . '</svg>';
};
?>
<div class="sidebar-overlay" data-sidebar-overlay></div>

<aside class="app-sidebar">
    <div class="flex h-16 items-center justify-between border-b border-slate-800 px-4">
        <a class="flex min-w-0 items-center gap-3" href="<?= $url('/') ?>">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-teal-500 text-sm font-black text-slate-950">M1</span>
            <span class="sidebar-label min-w-0">
                <span class="block truncate text-sm font-bold text-white">MadukaOne</span>
                <span class="block truncate text-xs text-slate-400">Commerce ERP</span>
            </span>
        </a>
        <button class="icon-btn-dark lg:hidden" type="button" data-sidebar-close aria-label="Fermer le menu">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>
    </div>

    <nav class="flex-1 space-y-5 overflow-y-auto px-3 py-4">
        <?php foreach ($navSections as $section): ?>
            <?php
            $visibleItems = array_values(array_filter($section['items'], static fn (array $item): bool => in_array($role, $item['roles'], true) || $isAdmin));
            ?>
            <?php if ($visibleItems === []): ?>
                <?php continue; ?>
            <?php endif; ?>
            <section class="space-y-1">
                <p class="sidebar-label px-3 pb-1 text-xs font-bold uppercase tracking-[.16em] text-slate-500">
                    <?= htmlspecialchars($section['label'], ENT_QUOTES, 'UTF-8') ?>
                </p>
                <?php foreach ($visibleItems as $item): ?>
                    <?php $isActive = $activeMenu === $item['key']; ?>
                    <a class="sidebar-link <?= $isActive ? 'is-active' : '' ?>" href="<?= $item['href'] ?>" title="<?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= $icon($item['icon']) ?>
                        <span class="sidebar-label truncate"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    </nav>

    <div class="border-t border-slate-800 p-3">
        <form method="post" action="<?= $url('/logout') ?>" accept-charset="UTF-8" data-confirm-form>
            <button
                class="sidebar-link w-full"
                type="button"
                data-confirm
                data-confirm-title="Se deconnecter de MadukaOne ?"
                data-confirm-message="Votre session administrateur va etre fermee et vous serez redirige vers la page de connexion."
                data-confirm-accept="Oui, me deconnecter"
                data-confirm-progress="Deconnexion en cours..."
            >
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M10 17 15 12l-5-5M15 12H3m9-8h6a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3h-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="sidebar-label">Deconnexion</span>
            </button>
        </form>
    </div>
</aside>
