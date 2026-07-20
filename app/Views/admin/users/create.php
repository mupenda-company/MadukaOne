<?php

$roles = is_array($roles ?? null) ? $roles : [];
$shops = is_array($shops ?? null) ? $shops : [];
$activeShop = is_array($activeShop ?? null) ? $activeShop : [];
$createdCredentials = [];

if (isset($_SESSION['flash']) && is_array($_SESSION['flash'])) {
    $createdCredentials = is_array($_SESSION['flash']['created_credentials'] ?? null) ? $_SESSION['flash']['created_credentials'] : [];
    unset($_SESSION['flash']['created_credentials']);

    if ($_SESSION['flash'] === []) {
        unset($_SESSION['flash']);
    }
}
?>

<section class="space-y-6">
    <?php if ($createdCredentials !== []): ?>
        <div class="overflow-hidden rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 via-white to-teal-50 shadow-sm" data-created-credentials>
            <div class="border-b border-emerald-100 px-5 py-4 sm:px-6">
                <p class="text-xs font-black uppercase tracking-[.16em] text-emerald-700">Compte créé avec succès</p>
                <h2 class="mt-2 text-xl font-bold text-slate-950">Identifiants de <?= htmlspecialchars((string) ($createdCredentials['employee'] ?? 'l’employé'), ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="mt-1 text-sm text-slate-600">Copiez ces informations maintenant : le mot de passe ne sera plus affiché après avoir quitté cette page.</p>
            </div>
            <div class="grid gap-3 p-5 sm:grid-cols-2 sm:p-6">
                <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Email de connexion</p><div class="mt-2 flex items-center gap-2"><code class="min-w-0 flex-1 select-all break-all font-bold text-slate-950" data-credential-email><?= htmlspecialchars((string) ($createdCredentials['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code><button class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-bold hover:bg-slate-200" type="button" data-copy-target="email">Copier</button></div></div>
                <div class="rounded-xl border border-emerald-200 bg-white p-4"><p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Mot de passe temporaire</p><div class="mt-2 flex items-center gap-2"><code class="min-w-0 flex-1 select-all break-all text-lg font-black tracking-wider text-slate-950" data-credential-password><?= htmlspecialchars((string) ($createdCredentials['password'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code><button class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-800" type="button" data-copy-target="password">Copier</button></div></div>
                <div class="sm:col-span-2 flex flex-col gap-3 rounded-xl bg-slate-950 p-4 text-white sm:flex-row sm:items-center sm:justify-between"><div><p class="text-sm font-bold">Prêt à transmettre à l’utilisateur</p><p class="mt-1 text-xs text-slate-300">Le message contient l’adresse de connexion, l’email et le mot de passe.</p></div><button class="rounded-lg bg-teal-400 px-4 py-3 text-sm font-black text-slate-950 hover:bg-teal-300" type="button" data-copy-all>Copier tous les identifiants</button></div>
                <p class="sm:col-span-2 text-xs font-semibold text-amber-700">Par sécurité, demandez à l’employé de modifier ce mot de passe depuis son profil après sa première connexion.</p>
            </div>
                </div>
    <?php endif; ?>

    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Administration</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Nouvel employe</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Créez un accès local sécurisé. Un mot de passe temporaire sera généré automatiquement après validation.
            </p>
        </div>
        <a class="btn-secondary h-10 px-4" href="<?= $url('/users') ?>">Retour aux utilisateurs</a>
    </div>

    <section class="surface-panel max-w-4xl">
        <div class="panel-header">
            <div>
                <h2 class="font-bold text-slate-950">Informations employe</h2>
                <p class="mt-1 text-sm text-slate-500">Les identifiants de connexion seront affichés une seule fois après la création.</p>
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

            <div class="grid gap-5 md:grid-cols-2">
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-800">Email</span>
                    <input class="field-control" name="email" type="email" autocomplete="email" placeholder="nom@entreprise.com" required>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-800">Contact</span>
                    <input class="field-control" name="telephone" type="tel" autocomplete="tel" placeholder="+243...">
                </label>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-800">Boutique</span>
                    <select class="field-control" name="shop_id" required>
                        <?php foreach ($shops as $shop): ?>
                            <?php $shopId = (int) ($shop['id'] ?? 0); ?>
                            <option value="<?= $shopId ?>" <?= $shopId === (int) ($activeShop['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($shop['nom'] ?? 'Boutique'), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-800">Role</span>
                    <select class="field-control" name="role_id" required>
                        <option value="">Selectionner un role</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= (int) ($role['id'] ?? 0) ?>">
                                <?= htmlspecialchars((string) ($role['nom'] ?? 'Role'), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600">
                Le mot de passe est généré de manière sécurisée puis stocké uniquement sous forme chiffrée. Vous pourrez copier les identifiants pour les transmettre à l’employé.
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a class="btn-secondary h-11 px-5" href="<?= $url('/users') ?>">Annuler</a>
                <button class="btn-primary sm:w-auto" type="submit">Créer l’utilisateur et générer le mot de passe</button>
            </div>
        </form>
    </section>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var email = document.querySelector('[data-credential-email]');
    var password = document.querySelector('[data-credential-password]');
    var copy = function (text, button) {
        if (!text) return;
        navigator.clipboard.writeText(text).then(function () {
            var original = button.textContent;
            button.textContent = 'Copié';
            window.setTimeout(function () { button.textContent = original; }, 1600);
        });
    };
    document.querySelectorAll('[data-copy-target]').forEach(function (button) {
        button.addEventListener('click', function () {
            copy(button.dataset.copyTarget === 'email' ? email?.textContent.trim() : password?.textContent.trim(), button);
        });
    });
    document.querySelector('[data-copy-all]')?.addEventListener('click', function () {
        var loginUrl = window.location.origin + <?= json_encode($url('/login'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        copy('Connexion MadukaOne\nAdresse : ' + loginUrl + '\nEmail : ' + email.textContent.trim() + '\nMot de passe : ' + password.textContent.trim(), this);
    });
});
</script>
