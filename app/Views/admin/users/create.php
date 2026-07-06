<?php

$roles = is_array($roles ?? null) ? $roles : [];
$inviteSuccess = is_array($inviteSuccess ?? null) ? $inviteSuccess : null;
$inviteCode = (string) ($inviteSuccess['code'] ?? '');
$inviteMessage = (string) ($inviteSuccess['message'] ?? '');

$roleLabel = static function (string $name): string {
    $normalized = strtolower(trim($name));

    return match ($normalized) {
        'caissier', 'agent', 'vendeur' => 'Agent de caisse',
        'gerant', 'gérant', 'manager' => 'Gerant',
        default => $name,
    };
};
?>

<section class="space-y-6">
    <?php if ($inviteSuccess !== null && $inviteCode !== ''): ?>
        <div class="rounded-lg border border-teal-200 bg-teal-50 p-4 text-teal-900 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[.14em] text-teal-700">Invitation generee</p>
                    <p class="mt-2 text-sm font-semibold"><?= htmlspecialchars($inviteMessage, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="flex min-w-0 items-center gap-2 rounded-lg border border-teal-200 bg-white px-3 py-2">
                    <code class="select-all truncate text-lg font-black tracking-[.12em] text-slate-950" data-invite-code><?= htmlspecialchars($inviteCode, ENT_QUOTES, 'UTF-8') ?></code>
                    <button class="rounded-md bg-slate-950 px-3 py-2 text-xs font-bold text-white transition hover:bg-slate-800" type="button" data-copy-code>
                        Copier
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Administration</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Nouvel employe</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Creez un acces sans saisir d'e-mail. L'employe utilisera son code d'invitation puis Google pour activer son compte.
            </p>
        </div>
        <a class="btn-secondary h-10 px-4" href="<?= $url('/users') ?>">Retour aux utilisateurs</a>
    </div>

    <section class="surface-panel max-w-3xl">
        <div class="panel-header">
            <div>
                <h2 class="font-bold text-slate-950">Informations employe</h2>
                <p class="mt-1 text-sm text-slate-500">Le code sera genere automatiquement apres validation.</p>
            </div>
        </div>

        <form class="mt-6 grid gap-5" method="post" action="<?= $url('/users') ?>" accept-charset="UTF-8">
            <div class="grid gap-5 md:grid-cols-2">
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-800">Prenom</span>
                    <input class="field-control" name="prenom" type="text" autocomplete="given-name" placeholder="Ex: Grace" required>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-800">Nom</span>
                    <input class="field-control" name="nom" type="text" autocomplete="family-name" placeholder="Ex: Mukendi" required>
                </label>
            </div>

            <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-800">Role</span>
                <select class="field-control" name="role_id" required>
                    <option value="">Selectionner un role</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= (int) ($role['id'] ?? 0) ?>">
                            <?= htmlspecialchars($roleLabel((string) ($role['nom'] ?? 'Role')), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600">
                Aucun mot de passe n'est cree ici. L'employe active son compte avec le code d'invitation et son compte Google.
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a class="btn-secondary h-11 px-5" href="<?= $url('/users') ?>">Annuler</a>
                <button class="btn-primary sm:w-auto" type="submit">Generer le code d'invitation</button>
            </div>
        </form>
    </section>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var button = document.querySelector('[data-copy-code]');
    var code = document.querySelector('[data-invite-code]');

    if (!button || !code) {
        return;
    }

    button.addEventListener('click', function () {
        navigator.clipboard?.writeText(code.textContent.trim());
        button.textContent = 'Copie';
        window.setTimeout(function () {
            button.textContent = 'Copier';
        }, 1600);
    });
});
</script>
