<?php

$userName = (string) ($currentUser['nom'] ?? 'Utilisateur');
$userEmail = (string) ($currentUser['email'] ?? '');
$userRole = ucfirst(str_replace('_', ' ', (string) ($currentUser['role'] ?? $currentUser['role_legacy'] ?? 'agent')));
$activeShopName = (string) ($activeShop['nom'] ?? 'Boutique active');
$userInitial = strtoupper(substr($userName, 0, 1));
$shopAllowance = is_array($shopAllowance ?? null) ? $shopAllowance : [];
$canCreateShop = (bool) ($shopAllowance['can_create'] ?? false);
$remainingShops = $shopAllowance['remaining'] ?? 0;
$shopLimit = $shopAllowance['limit'] ?? null;
$nextPlan = is_array($shopAllowance['next_plan'] ?? null) ? $shopAllowance['next_plan'] : null;
$canManageShops = (bool) ($canManageShops ?? false);
?>
<header class="app-topbar sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur">
    <div class="topbar-row flex min-h-16 items-center justify-between gap-3 px-4 py-2 sm:px-6 lg:px-8">
        <div class="topbar-title flex min-w-0 items-center gap-3">
            <button class="icon-btn lg:hidden" type="button" data-sidebar-toggle aria-label="Ouvrir le menu">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>

            <button class="icon-btn hidden lg:inline-flex" type="button" data-sidebar-toggle aria-label="Réduire le menu">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 6h16M4 12h10M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>

            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-slate-950"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></p>
                <p class="hidden truncate text-xs text-slate-500 sm:block"><?= htmlspecialchars($activeShopName, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>

        <div class="topbar-actions flex min-w-0 items-center justify-end gap-2 sm:gap-3">
            <?php if ($canManageShops): ?>
            <div class="shop-menu relative" data-shop-menu>
                <button class="shop-switcher" type="button" aria-label="Changer de boutique" aria-expanded="false" aria-haspopup="menu" data-shop-menu-toggle>
                    <span class="hidden max-w-44 truncate sm:inline"><?= htmlspecialchars($activeShopName, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="sm:hidden">Boutique</span>
                    <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>

                <div class="dropdown-panel right-0" data-shop-menu-panel role="menu" aria-label="Liste des boutiques">
                    <div class="p-2">
                        <p class="px-3 py-2 text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Boutiques</p>
                        <?php foreach ($shops as $shop): ?>
                            <?php $isActiveShop = (int) ($shop['id'] ?? 0) === (int) ($activeShop['id'] ?? 0); ?>
                            <a
                                class="dropdown-item <?= $isActiveShop ? 'is-active' : '' ?>"
                                href="<?= $url('/dashboard', ['shop_id' => (int) $shop['id']]) ?>"
                                role="menuitem"
                                <?php if (!$isActiveShop): ?>
                                    data-confirm
                                    data-confirm-title="Changer de boutique ?"
                                    data-confirm-message="Voulez-vous quitter <?= htmlspecialchars($activeShopName, ENT_QUOTES, 'UTF-8') ?> et passer vers <?= htmlspecialchars((string) ($shop['nom'] ?? 'cette boutique'), ENT_QUOTES, 'UTF-8') ?> ?"
                                    data-confirm-accept="Oui, changer"
                                    data-confirm-progress="Changement en cours..."
                                    data-confirm-delay="2000"
                                <?php endif; ?>
                            >
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-teal-50 text-xs font-bold text-teal-700">
                                    <?= htmlspecialchars(strtoupper(substr((string) $shop['nom'], 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold"><?= htmlspecialchars((string) $shop['nom'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="block truncate text-xs text-slate-500"><?= htmlspecialchars((string) ($shop['adresse'] ?? 'Adresse non définie'), ENT_QUOTES, 'UTF-8') ?></span>
                                </span>
                            </a>
                        <?php endforeach; ?>

                        <div class="mt-2 border-t border-slate-100 px-3 pb-2 pt-3">
                            <?php if ($shopLimit === null && ($shopAllowance['active'] ?? false)): ?>
                                <p class="mb-3 text-xs font-semibold text-teal-700">Boutiques illimitees avec le plan actuel.</p>
                            <?php else: ?>
                                <p class="mb-1 text-xs font-semibold text-slate-600">
                                    <?= (int) $remainingShops ?> boutique(s) restante(s) sur <?= (int) $shopLimit ?>.
                                </p>
                                <p class="mb-3 text-xs text-slate-400"><?= (int) ($shopAllowance['used'] ?? count($shops)) ?> boutique(s) utilisee(s).</p>
                            <?php endif; ?>

                            <?php if ($canCreateShop): ?>
                                <a class="btn-primary h-10 w-full px-3 text-sm" href="<?= $url('/shops/create') ?>" role="menuitem">Ajouter une nouvelle boutique</a>
                            <?php else: ?>
                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                                    <p class="text-xs font-bold text-amber-800">Limite du plan atteinte</p>
                                    <p class="mt-1 text-xs leading-5 text-amber-700">
                                        <?php if ($nextPlan !== null): ?>
                                            Passez au plan <?= htmlspecialchars((string) ($nextPlan['nom'] ?? 'suivant'), ENT_QUOTES, 'UTF-8') ?> pour creer davantage de boutiques.
                                        <?php else: ?>
                                            Renouvelez votre abonnement ou contactez l administration SaaS.
                                        <?php endif; ?>
                                    </p>
                                    <a class="btn-secondary mt-3 h-9 w-full px-3 text-xs" href="<?= $url('/shops/subscription') ?>" role="menuitem">Voir les plans d abonnement</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($canManageShops): ?>
                <div class="hidden h-9 w-px bg-slate-200 sm:block"></div>
            <?php endif; ?>

            <div class="user-menu relative" data-user-menu>
                <button class="user-menu-trigger" type="button" data-user-menu-toggle aria-expanded="false" aria-haspopup="menu">
                    <span class="hidden min-w-0 text-right sm:block">
                        <span class="block max-w-36 truncate text-sm font-semibold text-slate-900"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="block text-xs text-slate-500"><?= htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-slate-900 text-sm font-bold text-white">
                        <?= htmlspecialchars($userInitial, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <svg class="hidden h-4 w-4 text-slate-400 sm:block" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>

                <div class="dropdown-panel right-0 w-72" data-user-menu-panel role="menu" aria-label="Menu utilisateur">
                    <div class="border-b border-slate-100 p-4">
                        <p class="truncate text-sm font-semibold text-slate-950"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="mt-1 truncate text-xs text-slate-500"><?= htmlspecialchars($userEmail !== '' ? $userEmail : $userRole, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>

                    <div class="p-2">
                        <a class="dropdown-item" href="<?= $url('/profil') ?>" role="menuitem">
                            <span class="dropdown-item-icon">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 20a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold">Paramètres du profil</span>
                                <span class="block truncate text-xs text-slate-500">Compte, sécurité et préférences</span>
                            </span>
                        </a>

                        <form method="post" action="<?= $url('/logout') ?>" accept-charset="UTF-8" data-confirm-form>
                            <button
                                class="dropdown-item dropdown-item-danger w-full text-left"
                                type="button"
                                role="menuitem"
                                data-confirm
                                data-confirm-title="Se déconnecter de MadukaOne ?"
                                data-confirm-message="Votre session va être fermée immédiatement après confirmation."
                                data-confirm-accept="Oui, me déconnecter"
                                data-confirm-progress="Déconnexion en cours..."
                            >
                                <span class="dropdown-item-icon">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold">Déconnexion</span>
                                    <span class="block truncate text-xs text-red-500">Fermer la session active</span>
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
