<?php
$displayName = $userName($user);
$displayRole = $roleName($user);
$userShopId = (int) ($user['shop_id'] ?? 0);
$userShopKey = $userShopId > 0 ? (string) $userShopId : 'global';
$isActiveUser = (int) ($user['actif'] ?? 0) === 1;
$emailOrCode = (string) (($user['email'] ?? '') !== '' ? $user['email'] : ($user['invitation_code'] ?? 'Compte non active'));
$activation = ($user['email'] ?? '') !== '' ? 'active' : 'pending';
$searchText = strtolower($displayName . ' ' . $emailOrCode . ' ' . ($user['shop_name'] ?? 'global') . ' ' . $displayRole . ' ' . $authLabel($user));
?>
<article
    class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-teal-200 hover:shadow-md"
    data-user-card
    data-shop-id="<?= $safe($userShopKey) ?>"
    data-role="<?= $safe(strtolower($displayRole)) ?>"
    data-status="<?= $isActiveUser ? 'active' : 'inactive' ?>"
    data-activation="<?= $safe($activation) ?>"
    data-search="<?= $safe($searchText) ?>"
>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex min-w-0 gap-3">
            <div class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-slate-900 text-sm font-bold text-white">
                <?= $safe(strtoupper(substr($displayName, 0, 1)), 'U') ?>
            </div>
            <div class="min-w-0">
                <p class="truncate font-bold text-slate-950"><?= $safe($displayName) ?></p>
                <p class="mt-1 truncate text-xs text-slate-500"><?= $safe($emailOrCode) ?></p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <span class="rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700"><?= $safe($displayRole) ?></span>
                    <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700"><?= $safe($authLabel($user)) ?></span>
                    <span class="rounded-lg px-2.5 py-1 text-xs font-bold <?= $isActiveUser ? 'bg-teal-50 text-teal-700' : 'bg-red-50 text-red-700' ?>"><?= $isActiveUser ? 'Actif' : 'Suspendu' ?></span>
                </div>
            </div>
        </div>
        <a class="btn-secondary h-10 w-full px-4 sm:w-auto" href="<?= $url('/saas-admin/utilisateurs/' . (int) $user['id'] . '/edit') ?>">Modifier</a>
    </div>
</article>
