<?php
$user = is_array($user ?? null) ? $user : [];
$shops = is_array($shops ?? null) ? $shops : [];
$roles = is_array($roles ?? null) ? $roles : [];
$safe = static fn ($value, string $fallback = ''): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
?>
<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Acces utilisateur</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950"><?= $safe(trim((string) ($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''))) ?></h1>
            <p class="mt-3 text-sm leading-6 text-slate-600"><?= $safe($user['email'] ?? $user['invitation_code'] ?? 'Compte non active') ?></p>
        </div>
        <a class="btn-secondary" href="<?= $url('/saas-admin/utilisateurs') ?>">Retour</a>
    </div>
    <form class="surface-panel max-w-3xl space-y-4" method="post" action="<?= $url('/saas-admin/utilisateurs/' . (int) ($user['id'] ?? 0) . '/update') ?>">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-semibold" for="shop_id">Boutique</label>
                <select class="field-control" id="shop_id" name="shop_id">
                    <option value="">Toutes les boutiques / administration SaaS</option>
                    <?php foreach ($shops as $shop): ?><option value="<?= (int) $shop['id'] ?>" <?= (int) ($user['shop_id'] ?? 0) === (int) $shop['id'] ? 'selected' : '' ?>><?= $safe($shop['nom'] ?? '') ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold" for="role_id">Role</label>
                <select class="field-control" id="role_id" name="role_id">
                    <option value="">Aucun role</option>
                    <?php foreach ($roles as $role): ?><option value="<?= (int) $role['id'] ?>" <?= (int) ($user['role_id'] ?? 0) === (int) $role['id'] ? 'selected' : '' ?>><?= $safe($role['nom'] ?? '') ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold" for="role_legacy">Profil operationnel</label>
                <select class="field-control" id="role_legacy" name="role_legacy"><option value="admin" <?= ($user['role_legacy'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrateur</option><option value="agent" <?= ($user['role_legacy'] ?? 'agent') === 'agent' ? 'selected' : '' ?>>Agent</option></select>
            </div>
            <label class="mt-7 inline-flex h-12 items-center gap-3 rounded-lg border border-slate-200 px-4 text-sm font-semibold"><input class="h-4 w-4" type="checkbox" name="actif" value="1" <?= (int) ($user['actif'] ?? 0) === 1 ? 'checked' : '' ?>> Acces actif</label>
        </div>
        <button class="btn-primary sm:w-auto" type="submit">Enregistrer les acces</button>
    </form>
</section>
