<?php
$sections = is_array($sections ?? null) ? $sections : [];
$isPrivacy = ($legalType ?? 'privacy') === 'privacy';
$documentTitle = $isPrivacy ? 'Politique de confidentialité' : 'Conditions d’utilisation';
$adminPath = $isPrivacy ? '/saas-admin/confidentialite' : '/saas-admin/conditions';
$publicPath = $isPrivacy ? '/privacy' : '/terms';
$activeCount = count(array_filter($sections, static fn (array $section): bool => (int) ($section['actif'] ?? 0) === 1));
$safe = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
?>
<section class="space-y-5" data-legal-admin>
    <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-slate-950 via-slate-900 to-teal-900 p-6 text-white shadow-xl sm:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-xs font-black uppercase tracking-[.2em] text-teal-300">Documents publics</p>
                <h1 class="mt-3 text-3xl font-bold tracking-normal sm:text-4xl"><?= $safe($documentTitle) ?></h1>
                <p class="mt-3 text-sm leading-7 text-slate-300">Composez le document section par section. Toute modification active est immédiatement visible sur le site public.</p>
            </div>
            <a class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-white/10 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/20" href="<?= $url($publicPath) ?>" target="_blank" rel="noopener">Voir la page publique</a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <article class="stat-card"><p class="text-sm text-slate-500">Sections</p><p class="mt-2 text-3xl font-bold"><?= count($sections) ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Sections publiées</p><p class="mt-2 text-3xl font-bold text-teal-700"><?= $activeCount ?></p></article>
        <article class="stat-card"><p class="text-sm text-slate-500">Visibilité</p><p class="mt-2 text-lg font-bold text-blue-700">Page publique synchronisée</p></article>
    </div>

    <div class="grid items-start gap-5 2xl:grid-cols-[380px_minmax(0,1fr)]">
        <form class="surface-panel h-fit space-y-5 2xl:sticky 2xl:top-5" method="post" action="<?= $url($adminPath) ?>">
            <div>
                <p class="text-xs font-black uppercase tracking-[.16em] text-teal-700">Nouvelle section</p>
                <h2 class="mt-2 text-xl font-bold">Ajouter du contenu</h2>
                <p class="mt-1 text-sm leading-6 text-slate-500">Utilisez une ligne par élément lorsque le contenu doit apparaître sous forme de liste.</p>
            </div>
            <label class="form-field"><span>Titre</span><input name="titre" required maxlength="180" placeholder="Ex. Sécurité des données"></label>
            <label class="form-field"><span>Ordre d’affichage</span><input name="ordre" type="number" min="0" value="<?= (count($sections) + 1) * 10 ?>"></label>
            <label class="form-field"><span>Contenu</span><textarea name="contenu" required rows="9" placeholder="Rédigez le contenu public de la section..."></textarea></label>
            <label class="flex items-center gap-3 rounded-xl border border-teal-100 bg-teal-50 p-3 text-sm font-bold text-teal-900"><input name="actif" type="checkbox" value="1" checked> Publier cette section</label>
            <button class="btn-primary w-full" type="submit">Ajouter la section</button>
        </form>

        <div class="space-y-4">
            <div class="surface-panel flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div><h2 class="text-lg font-bold">Contenu du document</h2><p class="mt-1 text-sm text-slate-500">Modifiez, réordonnez, masquez ou supprimez chaque section.</p></div>
                <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600"><?= count($sections) ?> section(s)</span>
            </div>

            <?php if ($sections === []): ?>
                <div class="surface-panel border-dashed py-14 text-center"><p class="text-lg font-bold">Aucune section</p><p class="mt-2 text-sm text-slate-500">Ajoutez le premier contenu avec le formulaire.</p></div>
            <?php endif; ?>

            <?php foreach ($sections as $index => $section): ?>
                <article class="surface-panel overflow-hidden p-0">
                    <div class="flex items-center gap-4 border-b border-slate-100 bg-gradient-to-r <?= (int) $section['actif'] === 1 ? 'from-teal-50 to-white' : 'from-slate-100 to-white' ?> px-5 py-4">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-slate-950 text-sm font-black text-white"><?= $index + 1 ?></span>
                        <div class="min-w-0 flex-1"><h3 class="truncate font-bold"><?= $safe($section['titre']) ?></h3><p class="mt-1 text-xs text-slate-500">Position <?= (int) $section['ordre'] ?> · <?= (int) $section['actif'] === 1 ? 'Publiée' : 'Masquée' ?></p></div>
                        <span class="rounded-full px-3 py-1 text-xs font-bold <?= (int) $section['actif'] === 1 ? 'bg-teal-100 text-teal-800' : 'bg-slate-200 text-slate-600' ?>"><?= (int) $section['actif'] === 1 ? 'En ligne' : 'Brouillon' ?></span>
                    </div>
                    <form class="grid gap-4 p-5 md:grid-cols-[minmax(0,1fr)_9rem]" method="post" action="<?= $url($adminPath . '/' . (int) $section['id'] . '/update') ?>">
                        <label class="form-field"><span>Titre</span><input name="titre" required maxlength="180" value="<?= $safe($section['titre']) ?>"></label>
                        <label class="form-field"><span>Ordre</span><input name="ordre" type="number" min="0" value="<?= (int) $section['ordre'] ?>"></label>
                        <label class="form-field md:col-span-2"><span>Contenu public</span><textarea name="contenu" required rows="5"><?= $safe($section['contenu']) ?></textarea></label>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center md:col-span-2">
                            <label class="mr-auto flex items-center gap-2 text-sm font-bold text-slate-700"><input name="actif" type="checkbox" value="1" <?= (int) $section['actif'] === 1 ? 'checked' : '' ?>> Section publiée</label>
                            <button class="btn-primary sm:w-auto sm:px-8" type="submit">Enregistrer</button>
                        </div>
                    </form>
                    <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
                        <form method="post" action="<?= $url($adminPath . '/' . (int) $section['id'] . '/toggle') ?>"><button class="btn-secondary" type="submit"><?= (int) $section['actif'] === 1 ? 'Masquer' : 'Publier' ?></button></form>
                        <form method="post" action="<?= $url($adminPath . '/' . (int) $section['id'] . '/delete') ?>" onsubmit="return confirm('Supprimer définitivement cette section ?')"><button class="rounded-xl border border-red-200 bg-white px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-50" type="submit">Supprimer</button></form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
