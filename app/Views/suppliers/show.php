<?php

$supplier = is_array($supplier ?? null) ? $supplier : [];
$phone = trim((string) ($supplier['telephone'] ?? ''));
$email = trim((string) ($supplier['email'] ?? ''));
$contact = trim((string) ($supplier['contact_nom'] ?? ''));

$icon = static function (string $name): string {
    $paths = [
        'arrow' => '<path d="M19 12H5m6-6-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'edit' => '<path d="M4 20h4l10.5-10.5a2.8 2.8 0 0 0-4-4L4 16v4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="m13.5 6.5 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'truck' => '<path d="M3 7h11v9H3V7Zm11 3h4l3 3v3h-7v-6ZM7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'phone' => '<path d="M6.6 4h3l1.5 4-2 1.2a11 11 0 0 0 5.7 5.7l1.2-2 4 1.5v3a2 2 0 0 1-2.2 2A16 16 0 0 1 4.6 6.2 2 2 0 0 1 6.6 4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'mail' => '<path d="M4 6h16v12H4V6Zm0 1 8 6 8-6" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'user' => '<path d="M20 19c0-2.8-2.2-5-5-5H9c-2.8 0-5 2.2-5 5m8-8a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
    ];

    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . ($paths[$name] ?? $paths['truck']) . '</svg>';
};
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Fournisseur</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950"><?= htmlspecialchars((string) ($supplier['nom'] ?? 'Fournisseur'), ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Vue détaillée du contact fournisseur pour la boutique active.</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row">
            <a class="btn-secondary gap-2" href="<?= $url('/suppliers') ?>"><?= $icon('arrow') ?><span>Retour</span></a>
            <a class="btn-primary gap-2" href="<?= $url('/suppliers/' . (int) $supplier['id'] . '/edit') ?>"><?= $icon('edit') ?><span>Modifier</span></a>
        </div>
    </div>

    <section class="surface-panel">
        <div class="grid gap-4 md:grid-cols-2">
            <article class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-teal-50 text-teal-700"><?= $icon('truck') ?></span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Nom</p>
                        <p class="mt-1 font-bold text-slate-950"><?= htmlspecialchars((string) ($supplier['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
            </article>
            <article class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-blue-50 text-blue-700"><?= $icon('user') ?></span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Contact principal</p>
                        <p class="mt-1 font-bold text-slate-950"><?= htmlspecialchars($contact !== '' ? $contact : 'Non renseigné', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
            </article>
            <article class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-amber-50 text-amber-700"><?= $icon('phone') ?></span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Téléphone</p>
                        <p class="mt-1 font-bold text-slate-950"><?= htmlspecialchars($phone !== '' ? $phone : 'Non renseigné', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
            </article>
            <article class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-indigo-50 text-indigo-700"><?= $icon('mail') ?></span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Email</p>
                        <p class="mt-1 font-bold text-slate-950"><?= htmlspecialchars($email !== '' ? $email : 'Non renseigné', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
            </article>
        </div>
    </section>
</section>
