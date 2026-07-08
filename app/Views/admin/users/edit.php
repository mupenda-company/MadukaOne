<?php

$user = is_array($user ?? null) ? $user : [];
$roles = is_array($roles ?? null) ? $roles : [];
$shops = is_array($shops ?? null) ? $shops : [];
$userId = (int) ($user['id'] ?? 0);

$safe = static fn ($value, string $fallback = ''): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
?>

<section class="space-y-6">
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Administration</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Modifier un employe</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Mettez a jour l'email, la boutique, le role, le contact et les informations d'identite.
            </p>
        </div>
        <a class="btn-secondary h-10 px-4" href="<?= $url('/users') ?>">Annuler</a>
    </div>

    <section class="surface-panel max-w-4xl">
        <div class="panel-header">
            <div>
                <h2 class="font-bold text-slate-950"><?= $safe(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''), 'Employe') ?></h2>
                <p class="mt-1 text-sm text-slate-500">ID utilisateur <?= $userId ?></p>
            </div>
        </div>

        <form class="mt-6 grid gap-5" method="post" action="<?= $url('/admin/users/update/' . $userId) ?>" accept-charset="UTF-8">
            <div class="grid gap-5 md:grid-cols-2">
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-800">Prenom</span>
                    <input class="field-control" name="prenom" type="text" value="<?= $safe($user['prenom'] ?? '') ?>" autocomplete="given-name" required>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-800">Nom</span>
                    <input class="field-control" name="nom" type="text" value="<?= $safe($user['nom'] ?? '') ?>" autocomplete="family-name" required>
                </label>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-800">Email</span>
                    <input class="field-control" name="email" type="email" value="<?= $safe($user['email'] ?? '') ?>" autocomplete="email">
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-800">Contact</span>
                    <input class="field-control" name="telephone" type="tel" value="<?= $safe($user['telephone'] ?? '') ?>" autocomplete="tel">
                </label>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-800">Boutique</span>
                    <select class="field-control" name="shop_id" required>
                        <?php foreach ($shops as $shop): ?>
                            <?php $shopId = (int) ($shop['id'] ?? 0); ?>
                            <option value="<?= $shopId ?>" <?= $shopId === (int) ($user['shop_id'] ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($shop['nom'] ?? 'Boutique'), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-800">Role</span>
                    <select class="field-control" name="role_id" required>
                        <?php foreach ($roles as $role): ?>
                            <?php $roleId = (int) ($role['id'] ?? 0); ?>
                            <option value="<?= $roleId ?>" <?= $roleId === (int) ($user['role_id'] ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($role['nom'] ?? 'Role'), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a class="btn-secondary h-11 px-5" href="<?= $url('/users') ?>">Annuler</a>
                <button class="btn-primary sm:w-auto" type="submit">Enregistrer les modifications</button>
            </div>
        </form>
    </section>
</section>