<?php
$permissions = is_array($permissions ?? null) ? $permissions : [];
$safe = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
?>
<section class="space-y-5">
    <div class="dashboard-hero">
        <div><p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Nouveau role</p><h1 class="text-3xl font-bold tracking-normal text-slate-950">Creer un role</h1><p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Selectionnez les droits qui seront visibles et utilisables par les comptes rattaches.</p></div>
        <a class="btn-secondary" href="<?= $url('/saas-admin/droits') ?>">Retour</a>
    </div>
    <form class="surface-panel max-w-4xl space-y-5" method="post" action="<?= $url('/saas-admin/droits') ?>">
        <div><label class="mb-2 block text-sm font-semibold" for="nom">Nom du role</label><input class="field-control" id="nom" name="nom" required maxlength="50" placeholder="Ex: Superviseur boutique"></div>
        <div class="grid gap-3 sm:grid-cols-2">
            <?php foreach ($permissions as $code => $label): ?>
                <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold"><input class="h-4 w-4" type="checkbox" name="permissions[]" value="<?= $safe($code) ?>"> <?= $safe($label) ?></label>
            <?php endforeach; ?>
        </div>
        <button class="btn-primary sm:w-auto" type="submit">Creer le role</button>
    </form>
</section>
