<?php

$sale = is_array($sale ?? null) ? $sale : [];
$saleDetails = is_array($saleDetails ?? null) ? $saleDetails : [];
$activeShop = is_array($activeShop ?? null) ? $activeShop : [];
$money = static fn ($value): string => number_format((float) $value, 2, ',', ' ') . ' USD';
$safe = static fn ($value, string $fallback = '-'): string => htmlspecialchars((string) (($value ?? '') !== '' ? $value : $fallback), ENT_QUOTES, 'UTF-8');
$dateLabel = static function ($value): string {
    $timestamp = strtotime((string) ($value ?? ''));
    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : '-';
};
$modeLabel = static fn (string $mode): string => match ($mode) {
    'mobile_money' => 'Mobile money',
    'carte' => 'Carte',
    'virement' => 'Virement',
    'credit' => 'Crédit',
    'mixte' => 'Mixte',
    default => 'Cash',
};
?>

<style>
    @page { size: A4; margin: 14mm; }
    @media screen {
        .invoice-page-shell {
            max-width: 100%;
            overflow-x: hidden;
        }

        .invoice-page-frame {
            margin-inline: auto;
            max-width: min(100%, 920px);
        }
    }

    @media screen and (max-width: 639px) {
        .invoice-page-shell {
            margin-inline: -1rem;
        }

        .invoice-scroll {
            overflow-x: auto;
            padding-inline: 1rem;
            -webkit-overflow-scrolling: touch;
        }

        .invoice-a4 {
            min-width: 42rem;
        }
    }

    @media print {
        body { background: #fff !important; }
        .app-shell > aside,
        .app-shell > div > header,
        .print-toolbar { display: none !important; }
        .app-shell,
        .app-shell > div,
        main { display: block !important; margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .invoice-a4 { box-shadow: none !important; border: 0 !important; margin: 0 !important; width: 100% !important; }
    }
</style>

<section class="invoice-page-shell space-y-5">
    <div class="print-toolbar invoice-page-frame flex flex-col gap-3 rounded-xl bg-slate-900 p-4 sm:flex-row sm:items-center sm:justify-center">
        <a class="btn-secondary w-full border-slate-700 bg-slate-800 text-white hover:bg-slate-700 sm:w-auto" href="<?= $url('/sales') ?>">Retour</a>
        <button class="btn-primary w-full sm:w-auto" type="button" onclick="window.print()">Imprimer la facture</button>
    </div>

    <div class="invoice-scroll invoice-page-frame">
    <article class="invoice-a4 mx-auto w-full max-w-[794px] border border-slate-200 bg-white p-5 shadow-sm sm:p-8 lg:p-10">
        <header class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between sm:gap-8">
            <div>
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-teal-600 text-sm font-black text-white">M1</span>
                    <span class="text-sm font-bold text-slate-950">MadukaOne</span>
                </div>
                <h1 class="mt-6 text-2xl font-black tracking-normal text-slate-950 sm:text-3xl">Facture de vente</h1>
                <p class="mt-2 text-sm font-bold uppercase tracking-[.14em] text-teal-700"><?= $safe($sale['numero_facture'] ?? '-') ?></p>
            </div>
            <div class="w-full rounded-lg border border-teal-100 bg-teal-50 p-4 text-left text-sm sm:w-56 sm:text-right">
                <p class="font-bold text-teal-900"><?= $safe($activeShop['nom'] ?? 'Boutique active') ?></p>
                <p class="mt-2 text-slate-600"><?= $safe($activeShop['adresse'] ?? '') ?></p>
                <p class="text-slate-600"><?= $safe($activeShop['telephone'] ?? '') ?></p>
            </div>
        </header>

        <section class="mt-8 grid gap-3 border border-slate-200 bg-slate-50 p-4 text-sm sm:grid-cols-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.12em] text-slate-400">Date</p>
                <p class="mt-1 font-bold text-slate-950"><?= $safe($dateLabel($sale['date_vente'] ?? null)) ?></p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-[.12em] text-slate-400">Client</p>
                <p class="mt-1 font-bold text-slate-950"><?= $safe($sale['customer_name'] ?? 'Client comptant') ?></p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-[.12em] text-slate-400">Caissier</p>
                <p class="mt-1 font-bold text-slate-950"><?= $safe($sale['user_name'] ?? '-') ?></p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-[.12em] text-slate-400">Paiement</p>
                <p class="mt-1 font-bold text-slate-950"><?= $safe($modeLabel((string) ($sale['mode_paiement'] ?? 'cash'))) ?></p>
            </div>
        </section>

        <section class="mt-8">
            <h2 class="border-l-4 border-teal-600 bg-slate-100 px-3 py-2 text-sm font-black uppercase tracking-[.08em] text-slate-950">Articles</h2>
            <table class="mt-3 w-full min-w-[34rem] border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-950 text-left text-xs uppercase tracking-[.12em] text-white">
                        <th class="px-3 py-3">Produit</th>
                        <th class="px-3 py-3 text-right">Qté</th>
                        <th class="px-3 py-3 text-right">Prix</th>
                        <th class="px-3 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($saleDetails as $detail): ?>
                        <tr class="border-b border-slate-200">
                            <td class="px-3 py-3">
                                <p class="font-bold text-slate-950"><?= $safe($detail['product_name'] ?? 'Produit') ?></p>
                                <p class="mt-1 text-xs text-slate-500"><?= $safe($detail['product_ref'] ?? '') ?></p>
                            </td>
                            <td class="px-3 py-3 text-right"><?= (int) ($detail['quantite'] ?? 0) ?></td>
                            <td class="px-3 py-3 text-right"><?= $money($detail['prix_unitaire_vendu'] ?? 0) ?></td>
                            <td class="px-3 py-3 text-right font-bold"><?= $money($detail['total_ligne'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="mt-8 ml-auto w-full max-w-sm space-y-2 text-sm">
            <div class="flex justify-between border-b border-slate-200 py-2">
                <span class="text-slate-500">Total</span>
                <strong><?= $money($sale['total_montant'] ?? 0) ?></strong>
            </div>
            <div class="flex justify-between border-b border-slate-200 py-2">
                <span class="text-slate-500">Montant reçu</span>
                <strong class="text-teal-700"><?= $money($sale['montant_recu'] ?? 0) ?></strong>
            </div>
            <div class="flex justify-between bg-teal-50 px-3 py-3">
                <span class="font-bold text-teal-900">Reste / crédit</span>
                <strong class="text-teal-900"><?= $money($sale['montant_dette'] ?? 0) ?></strong>
            </div>
        </section>

        <footer class="mt-10 border-t border-slate-200 pt-4 text-xs text-slate-500">
            <p>Facture générée automatiquement par MadukaOne. Merci pour votre achat.</p>
            <p class="mt-1">Imprimée le <?= date('d/m/Y H:i') ?>.</p>
        </footer>
    </article>
    </div>
</section>
