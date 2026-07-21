<?php

$shop = is_array($shop ?? null) ? $shop : [];
$value = static fn (string $field, string $fallback = ''): string => htmlspecialchars((string) (($shop[$field] ?? '') !== '' ? $shop[$field] : $fallback), ENT_QUOTES, 'UTF-8');
$exchangeRate = (float) ($shop['taux_change_cdf'] ?? 2800);
$primaryCurrency = (string) ($shop['devise_principale'] ?? 'USD');
$shopCategory = (string) ($shop['category_name'] ?? 'Sans categorie');

$icon = static function (string $name): string {
    $paths = [
        'save' => '<path d="M5 4h12l2 2v14H5V4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M8 4v6h8V4M8 20v-6h8v6" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'store' => '<path d="M4 10h16l-1-5H5l-1 5Zm2 0v10h12V10M9 20v-6h6v6" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'currency' => '<path d="M12 3v18M17 7.5c0-1.7-2.1-3-5-3s-5 1.3-5 3 2.1 3 5 3 5 1.3 5 3-2.1 3-5 3-5-1.3-5-3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
    ];

    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . ($paths[$name] ?? $paths['store']) . '</svg>';
};
?>

<section class="space-y-5">
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Administration</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Parametres de la boutique</h1>
            <div class="mt-3 inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                Categorie : <?= htmlspecialchars($shopCategory, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Configurez les informations generales, la devise principale et le taux utilise pour convertir les prix du catalogue.
            </p>
        </div>
        <a class="btn-secondary" href="<?= $url('/dashboard') ?>">Tableau de bord</a>
    </div>

    <form class="grid gap-5 xl:grid-cols-[1fr_22rem]" method="post" action="<?= $url('/shops/settings') ?>" enctype="multipart/form-data" accept-charset="UTF-8">
        <section class="surface-panel">
            <div class="panel-header">
                <div>
                    <h2 class="font-bold text-slate-950">Informations generales</h2>
                    <p class="mt-1 text-sm text-slate-500">Ces donnees identifient la boutique active dans l'application.</p>
                </div>
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-teal-50 text-teal-700"><?= $icon('store') ?></span>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <label class="space-y-2 sm:col-span-2">
                    <span class="text-sm font-semibold text-slate-700">Nom de la boutique</span>
                    <input class="field-control" name="nom" type="text" value="<?= $value('nom') ?>" required>
                </label>
                <label class="space-y-2 sm:col-span-2">
                    <span class="text-sm font-semibold text-slate-700">Adresse</span>
                    <input class="field-control" name="adresse" type="text" value="<?= $value('adresse') ?>">
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-semibold text-slate-700">Telephone</span>
                    <input class="field-control" name="telephone" type="text" value="<?= $value('telephone') ?>">
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-semibold text-slate-700">Email</span>
                    <input class="field-control" name="email" type="email" value="<?= $value('email') ?>">
                </label>
                <div class="space-y-3 sm:col-span-2">
                    <span class="text-sm font-semibold text-slate-700">Logo de la boutique</span>
                    <div class="flex flex-col gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center">
                        <div class="grid h-24 w-24 shrink-0 place-items-center overflow-hidden rounded-2xl border border-slate-200 bg-white text-2xl font-black text-teal-700">
                            <img class="<?= empty($shop['logo_url']) ? 'hidden ' : '' ?>h-full w-full object-contain p-2" src="<?= $value('logo_url') ?>" alt="Aperçu du logo" data-logo-preview>
                            <span class="<?= !empty($shop['logo_url']) ? 'hidden ' : '' ?>" data-logo-placeholder><?= htmlspecialchars(strtoupper(substr((string) ($shop['nom'] ?? 'B'), 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="min-w-0 flex-1 space-y-2">
                            <input class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" name="logo_file" type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-logo-input>
                            <p class="text-xs leading-5 text-slate-500">JPG, PNG, WEBP ou GIF, jusqu’à 1 Mo. Le logo apparaîtra dans l’en-tête du catalogue public.</p>
                            <?php if (!empty($shop['logo_url'])): ?>
                                <label class="inline-flex items-center gap-2 text-sm font-semibold text-red-600"><input name="remove_logo" type="checkbox" value="1"> Supprimer le logo actuel</label>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <aside class="space-y-5">
            <section class="surface-panel">
                <div class="flex items-start gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-blue-50 text-blue-700"><?= $icon('currency') ?></span>
                    <div>
                        <h2 class="font-bold text-slate-950">Devise et taux</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">Le catalogue convertit les prix CDF vers USD avec ce taux.</p>
                    </div>
                </div>

                <div class="mt-5 space-y-4">
                    <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[.14em] text-blue-500">Categorie de la boutique</p>
                        <p class="mt-2 font-bold text-blue-950"><?= htmlspecialchars($shopCategory, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <label class="space-y-2">
                        <span class="text-sm font-semibold text-slate-700">Devise principale</span>
                        <select class="field-control" name="devise_principale" required>
                            <option value="USD" <?= $primaryCurrency === 'USD' ? 'selected' : '' ?>>USD</option>
                            <option value="CDF" <?= $primaryCurrency === 'CDF' ? 'selected' : '' ?>>CDF</option>
                        </select>
                    </label>
                    <label class="space-y-2">
                        <span class="text-sm font-semibold text-slate-700">Taux USD vers CDF</span>
                        <input class="field-control" name="taux_change_cdf" type="number" min="0.0001" step="0.0001" value="<?= htmlspecialchars(number_format($exchangeRate, 4, '.', ''), ENT_QUOTES, 'UTF-8') ?>" required>
                    </label>
                </div>

                <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    1 USD = <strong class="text-slate-950"><?= number_format($exchangeRate, 2, ',', ' ') ?> CDF</strong>
                </div>
            </section>

            <button class="btn-primary w-full gap-2" type="submit">
                <?= $icon('save') ?>
                <span>Enregistrer</span>
            </button>
        </aside>
    </form>
</section>
<script>
document.querySelector('[data-logo-input]')?.addEventListener('change', function () {
    const file = this.files?.[0];
    if (!file) return;
    const preview = document.querySelector('[data-logo-preview]');
    if (preview) {
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('hidden');
    }
    document.querySelector('[data-logo-placeholder]')?.classList.add('hidden');
});
</script>
