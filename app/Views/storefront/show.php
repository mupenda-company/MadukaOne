<?php
$safe = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$currency = in_array(($shop['devise_principale'] ?? 'USD'), ['USD', 'CDF'], true) ? (string) $shop['devise_principale'] : 'USD';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Catalogue public de <?= $safe($shop['nom']) ?>">
    <title><?= $safe($shop['nom']) ?> - Catalogue</title>
    <link rel="stylesheet" href="<?= $safe($basePath) ?>/assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-950 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-5 px-5 py-5 sm:px-8">
            <div class="flex min-w-0 items-center gap-4">
                <?php if (!empty($shop['logo_url'])): ?>
                    <img class="h-12 w-12 rounded-xl object-cover" src="<?= $safe($shop['logo_url']) ?>" alt="Logo <?= $safe($shop['nom']) ?>">
                <?php else: ?>
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-teal-700 font-black text-white"><?= $safe(mb_strtoupper(mb_substr((string) $shop['nom'], 0, 1))) ?></span>
                <?php endif; ?>
                <div class="min-w-0">
                    <h1 class="truncate text-xl font-black"><?= $safe($shop['nom']) ?></h1>
                    <p class="text-sm text-slate-500"><?= $safe($shop['category_name'] ?? 'Boutique en ligne') ?></p>
                </div>
            </div>
            <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-teal-700">Catalogue vitrine</span>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
        <section class="rounded-2xl bg-slate-950 px-6 py-10 text-white sm:px-10">
            <p class="text-sm font-bold uppercase tracking-[.16em] text-teal-300">Bienvenue</p>
            <h2 class="mt-3 text-3xl font-black sm:text-4xl">Découvrez le catalogue de <?= $safe($shop['nom']) ?>.</h2>
            <p class="mt-4 max-w-2xl text-white/65">Consultez les articles disponibles puis contactez directement la boutique. La commande et le paiement en ligne ne sont pas encore activés.</p>
            <div class="mt-6 flex flex-wrap gap-3 text-sm text-white/70">
                <?php if (!empty($shop['telephone'])): ?><span>📞 <?= $safe($shop['telephone']) ?></span><?php endif; ?>
                <?php if (!empty($shop['email'])): ?><span>✉ <?= $safe($shop['email']) ?></span><?php endif; ?>
                <?php if (!empty($shop['adresse'])): ?><span>📍 <?= $safe($shop['adresse']) ?></span><?php endif; ?>
            </div>
        </section>

        <section class="py-10">
            <div class="mb-6 flex items-end justify-between gap-4">
                <div><p class="text-sm font-bold uppercase tracking-[.14em] text-teal-700">Produits</p><h2 class="mt-2 text-2xl font-black">Notre catalogue</h2></div>
                <span class="text-sm text-slate-500"><?= count($products) ?> article(s)</span>
            </div>

            <?php if ($products === []): ?>
                <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center text-slate-500">Le catalogue sera bientôt disponible.</div>
            <?php else: ?>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <?php foreach ($products as $product): ?>
                        <?php
                        $displayCurrency = in_array(($product['prix_vente_devise'] ?? $currency), ['USD', 'CDF'], true) ? (string) $product['prix_vente_devise'] : $currency;
                        $displayAmount = (float) ($product['prix_vente_montant'] ?? 0) > 0 ? (float) $product['prix_vente_montant'] : (float) $product['prix_vente'];
                        ?>
                        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-[.12em] text-teal-700"><?= $safe($product['category_name'] ?? 'Général') ?></p>
                            <h3 class="mt-3 text-lg font-black"><?= $safe($product['nom']) ?></h3>
                            <p class="mt-2 line-clamp-3 min-h-12 text-sm leading-6 text-slate-500"><?= $safe($product['description'] ?? 'Article disponible dans notre boutique.') ?></p>
                            <div class="mt-5 flex items-center justify-between gap-3">
                                <strong class="text-lg"><?= number_format($displayAmount, 2, ',', ' ') ?> <?= $safe($displayCurrency) ?></strong>
                                <span class="text-xs font-semibold <?= (int) $product['quantite_stock'] > 0 ? 'text-emerald-700' : 'text-slate-400' ?>"><?= (int) $product['quantite_stock'] > 0 ? 'Disponible' : 'Nous contacter' ?></span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
