<?php

$flashError = is_string($flashError ?? null) ? $flashError : '';
$basePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
$basePath = ($basePath === '' || $basePath === '.') ? '' : $basePath;
$action = htmlspecialchars($basePath . '/activate', ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Activation employe - MadukaOne</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-950 antialiased">
    <main class="grid min-h-screen place-items-center px-4 py-8">
        <section class="w-full max-w-md rounded-xl border border-white/80 bg-white p-6 shadow-xl shadow-slate-200/80 sm:p-8">
            <div class="mb-6 text-center">
                <div class="mx-auto grid h-12 w-12 place-items-center rounded-lg bg-teal-700 text-sm font-black text-white shadow-lg shadow-teal-700/20">
                    M1
                </div>
                <p class="mt-5 text-xs font-bold uppercase tracking-[.18em] text-teal-700">Activation employe</p>
                <h1 class="mt-3 text-2xl font-bold tracking-normal text-slate-950">Entrez votre Code d'Invitation</h1>
                <p class="mt-3 text-sm leading-6 text-slate-500">
                    Apres validation, vous serez redirige vers Google pour activer votre acces.
                </p>
            </div>

            <?php if ($flashError !== ''): ?>
                <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                    <?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form class="space-y-5" method="post" action="<?= $action ?>" accept-charset="UTF-8">
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-800">Code d'invitation</span>
                    <input
                        class="field-control h-14 text-center text-lg font-black uppercase tracking-[.16em]"
                        name="invitation_code"
                        type="text"
                        autocomplete="one-time-code"
                        placeholder="INV-ABC123"
                        required
                    >
                </label>

                <button class="btn-primary" type="submit">Continuer avec Google</button>
            </form>

            <p class="mt-5 text-center text-xs leading-5 text-slate-500">
                Si votre code ne fonctionne pas, demandez un nouveau code a votre administrateur.
            </p>
        </section>
    </main>
</body>
</html>
