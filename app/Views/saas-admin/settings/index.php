<?php
$settings = is_array($settings ?? null) ? $settings : [];
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$rawValue = static fn (string $key, string $fallback = ''): string => (string) ($settings[$key]['setting_value'] ?? $fallback);
$value = static fn (string $key, string $fallback = ''): string => htmlspecialchars($rawValue($key, $fallback), ENT_QUOTES, 'UTF-8');
$isEnabled = static fn (string $key): bool => (string) ($settings[$key]['setting_value'] ?? '0') === '1';
$trialDays = max(0, (int) $rawValue('default_trial_days', '0'));
$graceDays = max(0, (int) $rawValue('billing_grace_days', '0'));
$currency = $rawValue('default_currency', 'USD');
?>
<section class="space-y-5" data-saas-settings>
    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Configuration</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Parametres generaux du SaaS</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">Centralisez les informations de support, les valeurs par defaut de facturation et les controles globaux de la plateforme.</p>
        </div>
        <a class="btn-secondary" href="<?= $url('/saas-admin') ?>">Retour pilotage</a>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="stat-card">
            <p class="text-sm text-slate-500">Plateforme</p>
            <p class="mt-2 truncate text-2xl font-bold"><?= $safe($rawValue('platform_name', 'MadukaOne SaaS')) ?></p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Essai par defaut</p>
            <p class="mt-2 text-2xl font-bold text-blue-700"><?= $trialDays ?> jour(s)</p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Devise SaaS</p>
            <p class="mt-2 text-2xl font-bold text-teal-700"><?= $safe($currency) ?></p>
        </article>
        <article class="stat-card">
            <p class="text-sm text-slate-500">Maintenance</p>
            <p class="mt-2 text-2xl font-bold <?= $isEnabled('maintenance_mode') ? 'text-red-700' : 'text-teal-700' ?>"><?= $isEnabled('maintenance_mode') ? 'Active' : 'Inactive' ?></p>
        </article>
    </div>

    <form class="surface-panel space-y-6" method="post" action="<?= $url('/saas-admin/parametres') ?>" accept-charset="UTF-8">
        <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <h2 class="text-lg font-bold text-slate-950">Reglages principaux</h2>
                <p class="mt-1 text-sm text-slate-500">Les modifications sont appliquees a l espace de gestion SaaS.</p>
            </div>
            <button class="btn-primary w-full sm:w-auto" type="submit">Enregistrer les parametres</button>
        </div>

        <div class="grid gap-5 xl:grid-cols-3">
            <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-4">
                    <p class="text-xs font-semibold uppercase tracking-[.16em] text-slate-400">Plateforme</p>
                    <h3 class="mt-1 font-bold text-slate-950">Identite et support</h3>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700" for="platform_name">Nom de la plateforme</label>
                        <input class="field-control" id="platform_name" name="settings[platform_name]" maxlength="120" value="<?= $value('platform_name', 'MadukaOne SaaS') ?>" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700" for="support_email">Email support</label>
                        <input class="field-control" id="support_email" type="email" name="settings[support_email]" maxlength="160" value="<?= $value('support_email') ?>" placeholder="support@example.com">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700" for="support_phone">Telephone support</label>
                        <input class="field-control" id="support_phone" name="settings[support_phone]" maxlength="60" value="<?= $value('support_phone') ?>" placeholder="+243 ...">
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-4">
                    <p class="text-xs font-semibold uppercase tracking-[.16em] text-slate-400">Facturation</p>
                    <h3 class="mt-1 font-bold text-slate-950">Valeurs par defaut</h3>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700" for="default_currency">Devise par defaut</label>
                        <select class="field-control" id="default_currency" name="settings[default_currency]">
                            <option value="USD" <?= $currency === 'USD' ? 'selected' : '' ?>>USD</option>
                            <option value="CDF" <?= $currency === 'CDF' ? 'selected' : '' ?>>CDF</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700" for="default_trial_days">Jours d essai par defaut</label>
                        <input class="field-control" id="default_trial_days" type="number" min="0" step="1" name="settings[default_trial_days]" value="<?= $value('default_trial_days', '14') ?>">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700" for="billing_grace_days">Delai de grace paiement</label>
                        <input class="field-control" id="billing_grace_days" type="number" min="0" step="1" name="settings[billing_grace_days]" value="<?= $value('billing_grace_days', '7') ?>">
                        <p class="mt-2 text-xs text-slate-500"><?= $graceDays ?> jour(s) avant action apres echeance.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-4">
                    <p class="text-xs font-semibold uppercase tracking-[.16em] text-slate-400">Controle</p>
                    <h3 class="mt-1 font-bold text-slate-950">Acces globaux</h3>
                </div>
                <div class="space-y-3">
                    <input type="hidden" name="settings[allow_new_shops]" value="0">
                    <label class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <input class="mt-1 h-4 w-4" type="checkbox" name="settings[allow_new_shops]" value="1" <?= $isEnabled('allow_new_shops') ? 'checked' : '' ?>>
                        <span>
                            <span class="block text-sm font-bold text-slate-900">Creation de boutiques autorisee</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-500">Permet aux super admins d ajouter de nouvelles boutiques depuis le SaaS.</span>
                        </span>
                    </label>

                    <input type="hidden" name="settings[maintenance_mode]" value="0">
                    <label class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <input class="mt-1 h-4 w-4" type="checkbox" name="settings[maintenance_mode]" value="1" <?= $isEnabled('maintenance_mode') ? 'checked' : '' ?>>
                        <span>
                            <span class="block text-sm font-bold text-slate-900">Mode maintenance SaaS</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-500">Marque la plateforme comme en maintenance globale pour les operations internes.</span>
                        </span>
                    </label>
                </div>
            </section>
        </div>
    </form>
</section>
