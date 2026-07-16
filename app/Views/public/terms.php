<?php
$sections = is_array($legalSections ?? null) ? $legalSections : [];
$safe = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$lastUpdate = $sections !== [] ? max(array_map(static fn (array $row): string => (string) ($row['updated_at'] ?? ''), $sections)) : '';
$formattedDate = $lastUpdate !== '' ? (new DateTimeImmutable($lastUpdate))->format('d/m/Y') : 'Document en préparation';
?>
<section class="relative overflow-hidden bg-slate-950 py-20 text-white sm:py-28">
    <div class="absolute inset-0 bg-gradient-to-br from-slate-950 via-blue-950 to-teal-900"></div>
    <div class="absolute -left-24 bottom-0 h-80 w-80 rounded-full bg-blue-500/20 blur-3xl"></div>
    <div class="public-container relative grid items-end gap-10 lg:grid-cols-[1fr_320px]" data-reveal>
        <div><p class="public-eyebrow text-teal-300">Règles d’utilisation</p><h1 class="mt-4 max-w-4xl text-4xl font-black tracking-tight sm:text-6xl">Un cadre clair pour travailler sereinement.</h1><p class="mt-6 max-w-2xl text-base leading-8 text-slate-300">Ces conditions précisent les responsabilités des administrateurs, gérants, caissiers et agents autorisés à utiliser MadukaOne.</p></div>
        <div class="rounded-2xl border border-white/10 bg-white/10 p-5 backdrop-blur"><p class="text-xs font-black uppercase tracking-[.18em] text-teal-300">Version publiée</p><p class="mt-2 text-xl font-bold"><?= $safe($formattedDate) ?></p><p class="mt-3 text-sm leading-6 text-slate-300"><?= count($sections) ?> règle(s) structurante(s)</p></div>
    </div>
</section>

<section class="public-section bg-gradient-to-b from-slate-50 to-white">
    <div class="public-container">
        <div class="grid gap-5 md:grid-cols-2">
            <?php if ($sections === []): ?><article class="public-legal-card"><h2>Document en préparation</h2><p>Les conditions d’utilisation seront publiées prochainement.</p></article><?php endif; ?>
            <?php foreach ($sections as $index => $section): ?>
                <article class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-7 shadow-sm transition hover:-translate-y-1 hover:shadow-xl" data-reveal>
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-teal-500 via-cyan-500 to-blue-600"></div>
                    <div class="flex items-start gap-4"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-slate-950 text-sm font-black text-white transition group-hover:bg-teal-700"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><div><h2 class="text-xl font-bold text-slate-950"><?= $safe($section['titre']) ?></h2><p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-600"><?= $safe($section['contenu']) ?></p></div></div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="mt-10 overflow-hidden rounded-2xl bg-gradient-to-r from-slate-950 to-teal-900 p-7 text-white shadow-xl sm:flex sm:items-center sm:justify-between sm:p-9" data-reveal>
            <div><p class="text-xs font-black uppercase tracking-[.18em] text-teal-300">Acceptation</p><h2 class="mt-3 text-2xl font-bold">En utilisant MadukaOne, vous acceptez ces conditions.</h2></div>
            <a class="mt-6 inline-flex rounded-xl bg-teal-400 px-6 py-3 text-sm font-black text-slate-950 transition hover:bg-teal-300 sm:mt-0" href="<?= $url($isAuthenticated ? '/dashboard' : '/login') ?>"><?= $isAuthenticated ? 'Accéder à la boutique' : 'Continuer vers la connexion' ?></a>
        </div>
    </div>
</section>
