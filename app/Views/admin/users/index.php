<?php

$users = is_array($users ?? null) ? $users : [];
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');

$roleLabel = static function ($user): string {
    $role = strtolower(trim((string) ($user['role_name'] ?? $user['role_legacy'] ?? 'agent')));

    return match ($role) {
        'gerant', 'gérant', 'manager' => 'Gérant',
        'super admin', 'super_admin', 'admin', 'administrateur' => 'Administrateur',
        default => 'Agent de caisse',
    };
};

$employeeName = static function (array $user): string {
    $prenom = trim((string) ($user['prenom'] ?? ''));
    $nom = trim((string) ($user['nom'] ?? ''));
    $fullName = trim($prenom . ' ' . $nom);

    return $fullName !== '' ? $fullName : 'Employé';
};
?>

<section class="space-y-6">
    <?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            <?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (is_string($flashError) && $flashError !== ''): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            <?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-hero">
        <div class="min-w-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-teal-700">Administration</p>
            <h1 class="text-3xl font-bold tracking-normal text-slate-950">Gestion des utilisateurs</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Gérez les employés de la boutique, leurs rôles et leurs codes d'activation Google.
            </p>
        </div>
        <a class="btn-primary h-11 w-full px-5 sm:w-auto" href="<?= $url('/admin/users/create') ?>">
            Ajouter un employé
        </a>
    </div>

    <section class="surface-panel overflow-hidden">
        <div class="panel-header">
            <div>
                <h2 class="font-bold text-slate-950">Employés de la boutique</h2>
                <p class="mt-1 text-sm text-slate-500">Les comptes en attente affichent leur code d'invitation.</p>
            </div>
        </div>

        <div class="mt-5 overflow-x-auto rounded-lg border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 bg-white text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-[.14em] text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Employé</th>
                        <th class="px-4 py-3">Rôle actuel</th>
                        <th class="px-4 py-3">Statut</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if ($users === []): ?>
                        <tr>
                            <td class="px-4 py-10 text-center text-slate-500" colspan="4">Aucun utilisateur trouvé pour cette boutique.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($users as $user): ?>
                        <?php
                        $id = (int) ($user['id'] ?? 0);
                        $isGoogleActive = trim((string) ($user['google_id'] ?? '')) !== '';
                        $invitationCode = trim((string) ($user['invitation_code'] ?? ''));
                        ?>
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-4 py-4">
                                <div class="font-bold text-slate-950"><?= $safe($employeeName($user)) ?></div>
                                <div class="mt-1 text-xs text-slate-500"><?= $safe($user['email'] ?? null, 'Email non activé') ?></div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                                    <?= $safe($roleLabel($user)) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <?php if ($isGoogleActive): ?>
                                    <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Actif via Google</span>
                                <?php else: ?>
                                    <div class="flex flex-col gap-1">
                                        <span class="inline-flex w-fit rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">En attente</span>
                                        <code class="w-fit rounded-md border border-amber-200 bg-amber-50 px-2 py-1 text-xs font-black tracking-[.08em] text-slate-900"><?= $safe($invitationCode, 'Code manquant') ?></code>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <a class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-100" href="<?= $url('/admin/users/edit/' . $id) ?>">
                                        Modifier
                                    </a>
                                    <form method="post" action="<?= $url('/admin/users/delete/' . $id) ?>" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                        <button class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-700 transition hover:bg-red-100" type="submit">
                                            Supprimer
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
