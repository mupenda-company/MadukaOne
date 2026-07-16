<?php
$sections = is_array($legalSections ?? null) ? $legalSections : [];
$safe = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$lastUpdate = $sections !== [] ? max(array_map(static fn (array $row): string => (string) ($row['updated_at'] ?? ''), $sections)) : '';
$formattedDate = $lastUpdate !== '' ? (new DateTimeImmutable($lastUpdate))->format('d/m/Y') : 'Document en préparation';
?>
<section class="relative overflow-hidden bg-slate-950 py-20 text-white sm:py-28">
    <div class="absolute inset-0 bg-gradient-to-br from-slate-950 via-slate-900 to-teal-950"></div>
    <div class="absolute -right-24 -top-24 h-80 w-80 rounded-full bg-teal-400/20 blur-3xl"></div>
    <div class="public-container relative grid items-end gap-10 lg:grid-cols-[1fr_320px]" data-reveal>
        <div>
            <p class="public-eyebrow text-teal-300">Confiance et transparence</p>
            <h1 class="mt-4 max-w-4xl text-4xl font-black tracking-tight sm:text-6xl">Vos données professionnelles restent sous contrôle.</h1>
            <p class="mt-6 max-w-2xl text-base leading-8 text-slate-300">Découvrez les informations traitées par MadukaOne, leur utilité et les mesures qui encadrent leur protection.</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/10 p-5 backdrop-blur">
            <p class="text-xs font-black uppercase tracking-[.18em] text-teal-300">Dernière mise à jour</p>
            <p class="mt-2 text-xl font-bold"><?= $safe($formattedDate) ?></p>
            <p class="mt-3 text-sm leading-6 text-slate-300"><?= count($sections) ?> section(s) publiée(s)</p>
        </div>
    </div>
</section>

<section class="public-section bg-gradient-to-b from-slate-50 to-white">
    <div class="public-container grid gap-8 lg:grid-cols-[280px_minmax(0,1fr)]">
        <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:sticky lg:top-24" data-reveal>
            <p class="text-xs font-black uppercase tracking-[.16em] text-teal-700">Dans ce document</p>
            <nav class="mt-4 space-y-2">
                <?php foreach ($sections as $index => $section): ?>
                    <a class="flex gap-3 rounded-xl px-3 py-2 text-sm font-bold text-slate-600 transition hover:bg-teal-50 hover:text-teal-800" href="#section-<?= (int) $section['id'] ?>"><span class="text-teal-600"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><?= $safe($section['titre']) ?></a>
                <?php endforeach; ?>
            </nav>
            <a class="btn-primary mt-6 w-full" href="<?= $url($isAuthenticated ? '/dashboard' : '/login') ?>"><?= $isAuthenticated ? 'Accéder à la boutique' : 'Se connecter' ?></a>
        </aside>

        <div class="space-y-5">
            <?php if ($sections === []): ?><article class="public-legal-card"><h2>Document en préparation</h2><p>La politique de confidentialité sera publiée prochainement.</p></article><?php endif; ?>
            <?php foreach ($sections as $index => $section): ?>
                <?php $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $section['contenu']) ?: []))); ?>
                <article id="section-<?= (int) $section['id'] ?>" class="scroll-mt-28 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" data-reveal>
                    <div class="flex items-center gap-4 border-b border-slate-100 bg-gradient-to-r from-teal-50 to-white p-6"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-teal-700 text-sm font-black text-white"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><h2 class="text-xl font-bold text-slate-950"><?= $safe($section['titre']) ?></h2></div>
                    <div class="p-6 text-sm leading-7 text-slate-600">
                        <?php if (count($lines) > 1): ?><ul class="space-y-3"><?php foreach ($lines as $line): ?><li class="flex gap-3"><span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-teal-500"></span><span><?= $safe($line) ?></span></li><?php endforeach; ?></ul>
                        <?php else: ?><p><?= $safe($lines[0] ?? '') ?></p><?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
