<?php

$customer = is_array($customer ?? null) ? $customer : [];
$phone = trim((string) ($customer['telephone'] ?? ''));
$email = trim((string) ($customer['email'] ?? ''));
$debt = (float) ($customer['dette_actuelle'] ?? 0);
$money = static fn ($value): string => number_format((float) $value, 2, ',', ' ') . ' USD';
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$icon = static function (string $name): string {
    $paths = [
        'arrow' => '<path d="M19 12H5m6-6-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'edit' => '<path d="M4 20h4l10.5-10.5a2.8 2.8 0 0 0-4-4L4 16v4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="m13.5 6.5 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'user' => '<path d="M20 19c0-2.8-2.2-5-5-5H9c-2.8 0-5 2.2-5 5m8-8a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'phone' => '<path d="M6.6 4h3l1.5 4-2 1.2a11 11 0 0 0 5.7 5.7l1.2-2 4 1.5v3a2 2 0 0 1-2.2 2A16 16 0 0 1 4.6 6.2 2 2 0 0 1 6.6 4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'mail' => '<path d="M4 6h16v12H4V6Zm0 1 8 6 8-6" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'cash' => '<path d="M4 7h16v10H4V7Zm4 5h.01M16 12h.01M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    ];
    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . ($paths[$name] ?? $paths['user']) . '</svg>';
};
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Client</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950"><?= $safe($customer['nom'] ?? 'Client') ?></h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Détail du client et de son crédit actuel.</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row">
            <a class="btn-secondary gap-2" href="<?= $url('/customers') ?>"><?= $icon('arrow') ?><span>Retour</span></a>
            <?php if ($debt > 0): ?>
                <form method="post" action="<?= $url('/customers/' . (int) $customer['id'] . '/settle-debt') ?>" data-confirm-form>
                    <input type="hidden" name="amount" value="<?= htmlspecialchars((string) $debt, ENT_QUOTES, 'UTF-8') ?>">
                    <button
                        class="btn-secondary w-full gap-2 border-teal-100 bg-teal-50 text-teal-700 hover:bg-teal-100 sm:w-auto"
                        type="button"
                        data-confirm
                        data-confirm-title="Régler la dette ?"
                        data-confirm-message="Cette action réglera <?= $money($debt) ?> et actualisera les factures liées à ce client."
                        data-confirm-accept="Régler"
                        data-confirm-progress="Règlement..."
                    ><?= $icon('cash') ?><span>Régler dette</span></button>
                </form>
            <?php endif; ?>
            <a class="btn-primary gap-2" href="<?= $url('/customers/' . (int) $customer['id'] . '/edit') ?>"><?= $icon('edit') ?><span>Modifier</span></a>
        </div>
    </div>

    <section class="surface-panel">
        <div class="grid gap-4 md:grid-cols-2">
            <article class="rounded-lg border border-slate-200 bg-slate-50 p-4"><div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-lg bg-teal-50 text-teal-700"><?= $icon('user') ?></span><div><p class="text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Nom</p><p class="mt-1 font-bold text-slate-950"><?= $safe($customer['nom'] ?? '') ?></p></div></div></article>
            <article class="rounded-lg border border-slate-200 bg-slate-50 p-4"><div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-lg bg-blue-50 text-blue-700"><?= $icon('phone') ?></span><div><p class="text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Téléphone</p><p class="mt-1 font-bold text-slate-950"><?= $safe($phone, 'Non renseigné') ?></p></div></div></article>
            <article class="rounded-lg border border-slate-200 bg-slate-50 p-4"><div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-lg bg-indigo-50 text-indigo-700"><?= $icon('mail') ?></span><div><p class="text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Email</p><p class="mt-1 font-bold text-slate-950"><?= $safe($email, 'Non renseigné') ?></p></div></div></article>
            <article class="rounded-lg border border-slate-200 bg-slate-50 p-4"><div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-lg bg-amber-50 text-amber-700"><?= $icon('cash') ?></span><div><p class="text-xs font-semibold uppercase tracking-[.14em] text-slate-400">Dette actuelle</p><p class="mt-1 font-bold <?= $debt > 0 ? 'text-amber-700' : 'text-slate-950' ?>"><?= $money($debt) ?></p></div></div></article>
        </div>
    </section>
</section>
