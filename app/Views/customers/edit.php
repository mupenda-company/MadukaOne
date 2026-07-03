<?php

$customer = is_array($customer ?? null) ? $customer : [];
$icon = static function (string $name): string {
    $paths = [
        'arrow' => '<path d="M19 12H5m6-6-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'save' => '<path d="M5 4h12l2 2v14H5V4Zm4 0v6h6V4M8 20v-6h8v6" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
    ];
    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . ($paths[$name] ?? $paths['save']) . '</svg>';
};
$value = static fn (string $key, string $fallback = ''): string => htmlspecialchars((string) ($customer[$key] ?? $fallback), ENT_QUOTES, 'UTF-8');
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Client</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Modifier le client</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Mettez à jour les coordonnées et la dette actuelle.</p>
        </div>
        <a class="btn-secondary gap-2" href="<?= $url('/customers') ?>"><?= $icon('arrow') ?><span>Retour</span></a>
    </div>

    <section class="surface-panel max-w-3xl">
        <form class="space-y-5" method="post" action="<?= $url('/customers/' . (int) $customer['id'] . '/update') ?>" accept-charset="UTF-8">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-800" for="customer_name">Nom du client</label>
                <input class="field-control" id="customer_name" name="nom" type="text" value="<?= $value('nom') ?>" maxlength="120" required>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-800" for="customer_phone">Téléphone</label>
                    <input class="field-control" id="customer_phone" name="telephone" type="tel" value="<?= $value('telephone') ?>" maxlength="30" inputmode="tel">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-800" for="customer_email">Email</label>
                    <input class="field-control" id="customer_email" name="email" type="email" value="<?= $value('email') ?>" maxlength="190">
                </div>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-800" for="customer_debt">Dette actuelle</label>
                <input class="field-control" id="customer_debt" name="dette_actuelle" type="number" min="0" step="0.01" value="<?= $value('dette_actuelle', '0') ?>">
            </div>
            <button class="btn-primary gap-2" type="submit"><?= $icon('save') ?><span>Enregistrer les modifications</span></button>
        </form>
    </section>
</section>
