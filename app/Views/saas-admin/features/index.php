<?php
$features = is_array($features ?? null) ? $features : [];
$safe = static fn ($value, string $fallback = ''): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
?>
<section class="space-y-5">
    <div class="dashboard-hero">
        <div><p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Catalogue SaaS</p><h1 class="text-3xl font-bold tracking-normal text-slate-950">Fonctionnalites</h1><p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Activez ou desactivez les modules commercialisables, puis associez-les aux boutiques depuis les abonnements.</p></div>
        <a class="btn-secondary" href="<?= $url('/saas-admin/abonnements') ?>">Affecter aux boutiques</a>
    </div>
    <div class="grid gap-5 xl:grid-cols-[.8fr_1.2fr]">
        <form class="surface-panel space-y-4" method="post" action="<?= $url('/saas-admin/fonctionnalites') ?>">
            <div><h2 class="font-bold text-slate-950">Ajouter</h2><p class="mt-1 text-sm text-slate-500">Code stable et libelle visible.</p></div>
            <div><label class="mb-2 block text-sm font-semibold" for="code">Code</label><input class="field-control" id="code" name="code" required placeholder="ex: pos_advanced"></div>
            <div><label class="mb-2 block text-sm font-semibold" for="nom">Nom</label><input class="field-control" id="nom" name="nom" required maxlength="120"></div>
            <div><label class="mb-2 block text-sm font-semibold" for="categorie">Categorie</label><input class="field-control" id="categorie" name="categorie" value="general" maxlength="80"></div>
            <div><label class="mb-2 block text-sm font-semibold" for="description">Description</label><textarea class="field-control min-h-28" id="description" name="description"></textarea></div>
            <label class="inline-flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold"><input class="h-4 w-4" type="checkbox" name="actif" value="1" checked> Active</label>
            <button class="btn-primary" type="submit">Ajouter la fonctionnalite</button>
        </form>
        <section class="surface-panel">
            <div class="panel-header"><div><h2 class="font-bold text-slate-950">Modules disponibles</h2><p class="mt-1 text-sm text-slate-500"><?= count($features) ?> fonctionnalite(s).</p></div></div>
            <div class="mt-5 space-y-4">
                <?php foreach ($features as $feature): ?>
                    <form class="rounded-lg border border-slate-200 p-4" method="post" action="<?= $url('/saas-admin/fonctionnalites/' . (int) $feature['id'] . '/update') ?>">
                        <div class="grid gap-3 md:grid-cols-2">
                            <input class="field-control" name="code" value="<?= $safe($feature['code'] ?? '') ?>" required>
                            <input class="field-control" name="nom" value="<?= $safe($feature['nom'] ?? '') ?>" required>
                            <input class="field-control" name="categorie" value="<?= $safe($feature['categorie'] ?? 'general') ?>">
                            <label class="inline-flex h-12 items-center gap-3 rounded-lg border border-slate-200 px-4 text-sm font-semibold"><input class="h-4 w-4" type="checkbox" name="actif" value="1" <?= (int) ($feature['actif'] ?? 0) === 1 ? 'checked' : '' ?>> Active</label>
                        </div>
                        <textarea class="field-control mt-3 min-h-20" name="description"><?= $safe($feature['description'] ?? '') ?></textarea>
                        <div class="mt-3 flex flex-wrap gap-2"><button class="btn-secondary" type="submit">Enregistrer</button></div>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</section>
