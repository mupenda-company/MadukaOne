<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';
require_once dirname(__DIR__) . '/Models/Customer.php';
require_once dirname(__DIR__) . '/Models/Sale.php';

class PosController extends AppController
{
    private Customer $customers;
    private Sale $sales;

    public function __construct()
    {
        $this->customers = new Customer();
        $this->sales = new Sale();
    }

    public function index(array $params = []): void
    {
        $shopId = $this->currentShopId();
        $statement = Database::connection()->prepare(
            'SELECT id, nom, ref, prix_vente, prix_vente_devise, prix_vente_montant, quantite_stock, alerte_stock_min, date_expiration
             FROM products
             WHERE shop_id = :shop_id AND actif = 1
             ORDER BY nom ASC'
        );
        $statement->execute(['shop_id' => $shopId]);

        $this->render('pos/index', [
            'pageTitle' => 'Caisse POS',
            'activeMenu' => 'pos',
            'products' => $statement->fetchAll(),
            'customers' => $this->customers->allByShop($shopId),
            'latestSales' => $this->sales->latestValidatedByShop($shopId, 10),
            'pageScripts' => ['assets/js/pos.js'],
        ]);
    }

    public function sales(array $params = []): void
    {
        $shopId = $this->currentShopId();
        $filters = $this->salesFiltersFromQuery();
        $isExportRequest = in_array(strtolower((string) ($_GET['export'] ?? '')), ['pdf', 'xlsx'], true)
            || in_array(strtolower((string) ($_GET['export_preview'] ?? '')), ['pdf', 'xlsx'], true);
        $sales = $this->sales->allByShop($shopId, $isExportRequest ? null : 1000, $filters);
        $summary = $this->sales->summaryByShopFiltered($shopId, $filters);
        $shops = $this->shops();
        $currentUser = $this->currentUser();
        $activeShop = $this->activeShop($shops, $currentUser);
        $export = strtolower((string) ($_GET['export'] ?? ''));
        $preview = strtolower((string) ($_GET['export_preview'] ?? ''));
        $confirmed = (string) ($_GET['confirm'] ?? '') === '1';
        $report = $this->salesHistoryReportData($sales, $summary, $activeShop, $filters);

        if (in_array($preview, ['pdf', 'xlsx'], true)) {
            $this->render('reports/export-preview', [
                'pageTitle' => 'Prévisualisation export',
                'activeMenu' => 'sales',
                'exportFormat' => $preview,
                'exportBasePath' => '/sales',
                'currentUser' => $currentUser,
                'shops' => $shops,
                'activeShop' => $activeShop,
            ] + $report);

            return;
        }

        if ($export === 'xlsx') {
            if (!$confirmed) {
                $this->render('reports/export-preview', [
                    'pageTitle' => 'Prévisualisation export',
                    'activeMenu' => 'sales',
                    'exportFormat' => 'xlsx',
                    'exportBasePath' => '/sales',
                    'currentUser' => $currentUser,
                    'shops' => $shops,
                    'activeShop' => $activeShop,
                ] + $report);

                return;
            }

            $this->exportSalesHistoryExcel($report);
        }

        if ($export === 'pdf') {
            if (!$confirmed) {
                $this->render('reports/export-preview', [
                    'pageTitle' => 'Prévisualisation export',
                    'activeMenu' => 'sales',
                    'exportFormat' => 'pdf',
                    'exportBasePath' => '/sales',
                    'currentUser' => $currentUser,
                    'shops' => $shops,
                    'activeShop' => $activeShop,
                ] + $report);

                return;
            }

            $this->exportSalesHistoryPdf($report);
        }

        $this->render('pos/sales', [
            'pageTitle' => 'Ventes',
            'activeMenu' => 'sales',
            'sales' => $sales,
            'salesSummary' => $summary,
            'salesFilters' => $filters,
        ]);
    }

    public function store(array $params = []): void
    {
        if (!$this->isJsonRequest()) {
            $this->json(['ok' => false, 'success' => false, 'message' => 'Requête JSON attendue.'], 415);
        }

        try {
            $payload = $this->normalizePayload($this->jsonPayload());
            $result = $this->sales->createFromPos($payload, $this->currentShopId(), $this->currentUserId());

            $this->json([
                'ok' => true,
                'success' => true,
                'message' => 'Vente enregistrée avec succès.',
                'invoice' => $result['numero_facture'] ?? null,
                'data' => $result,
                'latestSales' => $this->sales->latestValidatedByShop($this->currentShopId(), 10),
            ], 201);
        } catch (InvalidArgumentException $exception) {
            $this->json(['ok' => false, 'success' => false, 'message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            $this->json(['ok' => false, 'success' => false, 'message' => $exception->getMessage()], 400);
        }
    }

    public function storeCustomer(array $params = []): void
    {
        if (!$this->isJsonRequest()) {
            $this->json(['ok' => false, 'success' => false, 'message' => 'Requête JSON attendue.'], 415);
        }

        try {
            $payload = $this->jsonPayload();
            $name = trim((string) ($payload['nom'] ?? $payload['name'] ?? ''));

            if ($name === '') {
                $this->json(['ok' => false, 'success' => false, 'message' => 'Le nom du client est obligatoire.'], 422);
            }

            $customerId = $this->customers->create([
                'nom' => $name,
                'telephone' => $payload['telephone'] ?? null,
                'email' => $payload['email'] ?? null,
                'dette_actuelle' => 0,
            ], $this->currentShopId());
            $customer = $this->customers->findByShop($customerId, $this->currentShopId());

            $this->json([
                'ok' => true,
                'success' => true,
                'message' => 'Client ajouté avec succès.',
                'customer' => $customer,
                'customers' => $this->customers->allByShop($this->currentShopId()),
            ], 201);
        } catch (Throwable $exception) {
            $this->json(['ok' => false, 'success' => false, 'message' => $exception->getMessage()], 400);
        }
    }

    public function show(array $params = []): void
    {
        $sale = $this->findSaleFromParams($params);

        $this->render('pos/sale-show', [
            'pageTitle' => 'Détail vente',
            'activeMenu' => 'sales',
            'sale' => $sale,
            'saleDetails' => $this->sales->detailsBySale((int) $sale['id']),
        ]);
    }

    public function edit(array $params = []): void
    {
        $sale = $this->findSaleFromParams($params);

        $this->render('pos/sale-edit', [
            'pageTitle' => 'Modifier la vente',
            'activeMenu' => 'sales',
            'sale' => $sale,
            'saleDetails' => $this->sales->detailsBySale((int) $sale['id']),
            'customers' => $this->customersForCurrentShop(),
        ]);
    }

    public function update(array $params = []): void
    {
        $id = $this->saleIdFromParams($params);

        try {
            if (!$this->sales->updatePaymentByShop($id, $this->currentShopId(), [
                'customer_id' => $_POST['customer_id'] ?? null,
                'mode_paiement' => $_POST['mode_paiement'] ?? 'cash',
                'montant_recu' => $_POST['montant_recu'] ?? 0,
            ])) {
                $this->abort(404, 'Vente introuvable pour cette boutique.');
            }

            $this->flashSuccess('Vente mise à jour avec succès.');
            $this->redirect('/sales/' . $id);
        } catch (Throwable $exception) {
            $this->flashError('Impossible de modifier cette vente: ' . $exception->getMessage());
            $this->redirect('/sales/' . $id . '/edit');
        }
    }

    public function destroy(array $params = []): void
    {
        $id = $this->saleIdFromParams($params);

        try {
            if (!$this->sales->cancelByShop($id, $this->currentShopId(), $this->currentUserId(), (string) ($_POST['reason'] ?? ''))) {
                $this->abort(404, 'Vente introuvable pour cette boutique.');
            }

            $this->flashSuccess('Vente annulée avec succès. Tous les articles ont été retournés automatiquement au stock.');
        } catch (Throwable $exception) {
            $this->flashError('Impossible d’annuler cette vente : ' . $exception->getMessage());
        }

        $this->redirect('/sales');
    }

    public function invoice(array $params = []): void
    {
        $sale = $this->findSaleFromParams($params);

        $this->render('pos/sale-invoice', [
            'pageTitle' => 'Facture ' . (string) ($sale['numero_facture'] ?? ''),
            'activeMenu' => 'sales',
            'sale' => $sale,
            'saleDetails' => $this->sales->detailsBySale((int) $sale['id']),
        ]);
    }

    private function normalizePayload(array $payload): array
    {
        $payload['montant_recu'] = $payload['montant_recu'] ?? $payload['amount_received'] ?? 0;
        $payload['mode_paiement'] = $payload['mode_paiement'] ?? $payload['payment_method'] ?? 'cash';

        return $payload;
    }

    private function jsonPayload(): array
    {
        $raw = file_get_contents('php://input');

        if ($raw === false || trim($raw) === '') {
            throw new InvalidArgumentException('Corps JSON vide.');
        }

        $payload = json_decode($raw, true);

        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('JSON invalide.');
        }

        return $payload;
    }

    private function isJsonRequest(): bool
    {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));

        return str_contains($contentType, 'application/json') || str_contains($accept, 'application/json');
    }

    private function saleIdFromParams(array $params): int
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            $this->abort(404, 'Vente introuvable.');
        }

        return $id;
    }

    private function findSaleFromParams(array $params): array
    {
        $sale = $this->sales->findByShop($this->saleIdFromParams($params), $this->currentShopId());

        if ($sale === null) {
            $this->abort(404, 'Vente introuvable pour cette boutique.');
        }

        return $sale;
    }

    private function customersForCurrentShop(): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, nom, telephone, dette_actuelle
             FROM customers
             WHERE shop_id = :shop_id
             ORDER BY nom ASC'
        );
        $statement->execute(['shop_id' => $this->currentShopId()]);

        return $statement->fetchAll();
    }

    private function salesFiltersFromQuery(): array
    {
        $status = strtolower(trim((string) ($_GET['status'] ?? 'all')));
        $payment = strtolower(trim((string) ($_GET['payment'] ?? 'all')));
        $period = strtolower(trim((string) ($_GET['period'] ?? 'all')));
        $debt = strtolower(trim((string) ($_GET['debt'] ?? 'all')));

        return [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'status' => in_array($status, ['all', 'validee', 'annulee'], true) ? $status : 'all',
            'payment' => in_array($payment, ['all', 'cash', 'mobile_money', 'carte', 'virement', 'credit', 'mixte'], true) ? $payment : 'all',
            'period' => in_array($period, ['all', 'today', 'week', 'month'], true) ? $period : 'all',
            'debt' => in_array($debt, ['all', 'paid', 'debt'], true) ? $debt : 'all',
            'date_debut' => $this->dateFilterValue($_GET['date_debut'] ?? ''),
            'date_fin' => $this->dateFilterValue($_GET['date_fin'] ?? ''),
        ];
    }

    private function dateFilterValue(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10));

        return $date instanceof DateTimeImmutable ? $date->format('Y-m-d') : '';
    }

    private function salesPeriodDisplay(array $filters): string
    {
        $parts = [];
        $period = (string) ($filters['period'] ?? 'all');
        $status = (string) ($filters['status'] ?? 'all');
        $payment = (string) ($filters['payment'] ?? 'all');
        $debt = (string) ($filters['debt'] ?? 'all');
        $search = trim((string) ($filters['search'] ?? ''));
        $dateStart = trim((string) ($filters['date_debut'] ?? ''));
        $dateEnd = trim((string) ($filters['date_fin'] ?? ''));

        if ($dateStart !== '' || $dateEnd !== '') {
            $parts[] = 'Dates: ' . ($dateStart !== '' ? date('d/m/Y', strtotime($dateStart)) : 'début')
                . ' - ' . ($dateEnd !== '' ? date('d/m/Y', strtotime($dateEnd)) : 'fin');
        } else {
            $parts[] = match ($period) {
                'today' => 'Aujourd\'hui',
                'week' => '7 derniers jours',
                'month' => '30 derniers jours',
                default => 'Toutes les ventes',
            };
        }

        if ($status !== 'all') {
            $parts[] = $status === 'annulee' ? 'Annulées' : 'Validées';
        }

        if ($payment !== 'all') {
            $parts[] = 'Paiement: ' . $payment;
        }

        if ($debt !== 'all') {
            $parts[] = $debt === 'debt' ? 'Avec crédit' : 'Payées';
        }

        if ($search !== '') {
            $parts[] = 'Recherche: ' . $search;
        }

        return implode(' - ', $parts);
    }

    private function salesHistoryReportData(array $sales, array $summary, array $activeShop, array $filters = []): array
    {
        $payments = [];

        foreach ($sales as $sale) {
            if (($sale['statut'] ?? '') !== 'validee') {
                continue;
            }

            $mode = (string) ($sale['mode_paiement'] ?? 'cash');
            $payments[$mode] ??= [
                'mode_paiement' => $mode,
                'tickets' => 0,
                'revenue' => 0.0,
                'received' => 0.0,
                'debt' => 0.0,
            ];
            $payments[$mode]['tickets']++;
            $payments[$mode]['revenue'] += (float) ($sale['total_montant'] ?? 0);
            $payments[$mode]['received'] += (float) ($sale['montant_recu'] ?? 0);
            $payments[$mode]['debt'] += (float) ($sale['montant_dette'] ?? 0);
        }

        return [
            'activeShop' => $activeShop,
            'reportFilter' => [
                'period' => (string) ($filters['period'] ?? 'all'),
                'label' => $this->salesPeriodDisplay($filters),
                'start' => null,
                'end' => null,
                'search' => (string) ($filters['search'] ?? ''),
                'status' => (string) ($filters['status'] ?? 'all'),
                'payment' => (string) ($filters['payment'] ?? 'all'),
                'debt' => (string) ($filters['debt'] ?? 'all'),
                'date_debut' => (string) ($filters['date_debut'] ?? ''),
                'date_fin' => (string) ($filters['date_fin'] ?? ''),
            ],
            'periodDisplay' => $this->salesPeriodDisplay($filters),
            'overview' => [
                'validated_count' => (int) ($summary['sales_count'] ?? 0) - (int) ($summary['cancelled_count'] ?? 0),
                'cancelled_count' => (int) ($summary['cancelled_count'] ?? 0),
                'validated_revenue' => (float) ($summary['revenue'] ?? 0),
                'received_total' => (float) ($summary['received'] ?? 0),
                'debt_total' => (float) ($summary['debt'] ?? 0),
                'items_sold' => array_sum(array_map(static fn (array $sale): int => (int) ($sale['articles_count'] ?? 0), $sales)),
                'average_ticket' => ((int) ($summary['sales_count'] ?? 0) - (int) ($summary['cancelled_count'] ?? 0)) > 0
                    ? (float) ($summary['revenue'] ?? 0) / ((int) ($summary['sales_count'] ?? 0) - (int) ($summary['cancelled_count'] ?? 0))
                    : 0,
            ],
            'cards' => [
                ['label' => 'Tickets validés', 'value' => (string) max(0, (int) ($summary['sales_count'] ?? 0) - (int) ($summary['cancelled_count'] ?? 0)), 'detail' => 'Historique des ventes', 'tone' => 'teal'],
                ['label' => 'Chiffre d’affaires', 'value' => $this->money((float) ($summary['revenue'] ?? 0)), 'detail' => 'Ventes validées', 'tone' => 'blue'],
                ['label' => 'Crédit client', 'value' => $this->money((float) ($summary['debt'] ?? 0)), 'detail' => 'Reste à encaisser', 'tone' => 'amber'],
                ['label' => 'Ventes annulées', 'value' => (string) (int) ($summary['cancelled_count'] ?? 0), 'detail' => 'Tickets non validés', 'tone' => 'slate'],
            ],
            'paymentBreakdown' => array_values($payments),
            'topProducts' => [],
            'recentSales' => $sales,
        ];
    }

    private function exportSalesHistoryExcel(array $report): never
    {
        if (!class_exists(ZipArchive::class)) {
            $this->abort(500, 'Extension PHP Zip indisponible pour générer le fichier Excel.');
        }

        $activeShop = is_array($report['activeShop'] ?? null) ? $report['activeShop'] : [];
        $sales = is_array($report['recentSales'] ?? null) ? $report['recentSales'] : [];
        $overview = is_array($report['overview'] ?? null) ? $report['overview'] : [];
        $rows = [
            ['Historique des ventes', ''],
            ['Boutique', (string) ($activeShop['nom'] ?? 'Boutique active')],
            ['Période', (string) ($report['periodDisplay'] ?? 'Toutes les ventes disponibles')],
            ['Généré le', date('d/m/Y H:i')],
            ['', ''],
            ['Tickets validés', (int) ($overview['validated_count'] ?? 0)],
            ['Chiffre d’affaires', (float) ($overview['validated_revenue'] ?? 0)],
            ['Montant reçu', (float) ($overview['received_total'] ?? 0)],
            ['Crédit client', (float) ($overview['debt_total'] ?? 0)],
            ['', ''],
            ['Facture', 'Date', 'Client', 'Caissier', 'Articles', 'Paiement', 'Total', 'Reçu', 'Crédit', 'Statut'],
        ];

        foreach ($sales as $sale) {
            $rows[] = [
                (string) ($sale['numero_facture'] ?? ''),
                (string) ($sale['date_vente'] ?? ''),
                (string) ($sale['customer_name'] ?? 'Client comptant'),
                (string) ($sale['user_name'] ?? ''),
                (int) ($sale['articles_count'] ?? 0),
                (string) ($sale['mode_paiement'] ?? ''),
                (float) ($sale['total_montant'] ?? 0),
                (float) ($sale['montant_recu'] ?? 0),
                (float) ($sale['montant_dette'] ?? 0),
                (string) ($sale['statut'] ?? ''),
            ];
        }

        $tmp = tempnam(sys_get_temp_dir(), 'madukaone_sales_excel_');
        if ($tmp === false) {
            $this->abort(500, 'Impossible de préparer le fichier Excel.');
        }

        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            $this->abort(500, 'Impossible de créer le fichier Excel.');
        }

        $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypes());
        $zip->addFromString('_rels/.rels', $this->xlsxRootRels());
        $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRels());
        $zip->addFromString('xl/styles.xml', $this->xlsxStyles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->xlsxSheet($rows));
        $zip->close();

        $this->downloadFile($tmp, 'historique-ventes-' . date('Ymd-His') . '.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    private function exportSalesHistoryPdf(array $report): never
    {
        $activeShop = is_array($report['activeShop'] ?? null) ? $report['activeShop'] : [];
        $overview = is_array($report['overview'] ?? null) ? $report['overview'] : [];
        $sales = is_array($report['recentSales'] ?? null) ? $report['recentSales'] : [];
        $lines = [
            'MadukaOne - Historique des ventes',
            'Boutique: ' . (string) ($activeShop['nom'] ?? 'Boutique active'),
            'Période: ' . (string) ($report['periodDisplay'] ?? 'Toutes les ventes disponibles'),
            'Généré le: ' . date('d/m/Y H:i'),
            '',
            'Synthèse',
            'Tickets validés: ' . (int) ($overview['validated_count'] ?? 0),
            'Chiffre d’affaires: ' . $this->money((float) ($overview['validated_revenue'] ?? 0)),
            'Montant reçu: ' . $this->money((float) ($overview['received_total'] ?? 0)),
            'Crédit client: ' . $this->money((float) ($overview['debt_total'] ?? 0)),
            '',
            'Tickets',
        ];

        foreach (array_slice($sales, 0, 35) as $sale) {
            $lines[] = (string) ($sale['numero_facture'] ?? '-') . ' | ' . (string) ($sale['date_vente'] ?? '') . ' | ' . $this->money((float) ($sale['total_montant'] ?? 0)) . ' | ' . (string) ($sale['statut'] ?? '');
        }

        $pdf = $this->professionalSalesHistoryPdf($report);

        header_remove('Content-Type');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="historique-ventes-' . date('Ymd-His') . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    private function money(float $value): string
    {
        return number_format($value, 2, ',', ' ') . ' USD';
    }

    private function professionalSalesHistoryPdf(array $report): string
    {
        $activeShop = is_array($report['activeShop'] ?? null) ? $report['activeShop'] : [];
        $overview = is_array($report['overview'] ?? null) ? $report['overview'] : [];
        $sales = is_array($report['recentSales'] ?? null) ? $report['recentSales'] : [];
        $payments = is_array($report['paymentBreakdown'] ?? null) ? $report['paymentBreakdown'] : [];
        $periodDisplay = (string) ($report['periodDisplay'] ?? 'Toutes les ventes disponibles');

        $content = '';
        $pageWidth = 595.0;
        $pageHeight = 842.0;
        $left = 46.0;
        $right = 549.0;
        $contentWidth = $right - $left;
        $primary = [0.06, 0.46, 0.43];
        $primaryDark = [0.05, 0.30, 0.28];
        $primarySoft = [0.94, 0.99, 0.98];
        $dark = [0.07, 0.09, 0.15];
        $muted = [0.36, 0.42, 0.50];
        $light = [0.96, 0.97, 0.98];
        $border = [0.83, 0.86, 0.90];

        $content .= $this->pdfSetFill(1, 1, 1);
        $content .= $this->pdfRect(0, 0, $pageWidth, $pageHeight, 'f');

        $y = 780.0;
        $content .= $this->pdfSetFill($primary[0], $primary[1], $primary[2]);
        $content .= $this->pdfRect($left, $y - 11, 18, 18, 'f');
        $content .= $this->pdfTextAt('M1', $left + 4, $y - 5, 8, 'F2', [1, 1, 1]);
        $content .= $this->pdfTextAt('MadukaOne', $left + 25, $y - 2, 10, 'F2', $dark);
        $content .= $this->pdfTextAt((string) ($activeShop['nom'] ?? 'Boutique active'), $left, $y - 40, 18, 'F2', $dark);
        $content .= $this->pdfTextAt('HISTORIQUE DES VENTES', $left, $y - 63, 13, 'F2', $primary);
        $content .= $this->pdfTextAt('Rapport détaillé des tickets enregistrés dans la boutique active.', $left, $y - 83, 8.5, 'F1', $muted);

        $boxX = 360.0;
        $boxY = $y - 70;
        $content .= $this->pdfSetFill($primarySoft[0], $primarySoft[1], $primarySoft[2]);
        $content .= $this->pdfRect($boxX, $boxY, 150, 68, 'f');
        $content .= $this->pdfSetStroke($border[0], $border[1], $border[2]);
        $content .= $this->pdfRect($boxX, $boxY, 150, 68, 'S');
        $content .= $this->pdfTextAt('IDENTIFIANTS BOUTIQUE', $boxX + 50, $boxY + 52, 7.5, 'F2', $primaryDark);
        $content .= $this->pdfTextAt('ID boutique : ' . (string) ($activeShop['id'] ?? '-'), $boxX + 75, $boxY + 35, 7, 'F1', $dark);
        $content .= $this->pdfTextAt('Téléphone : ' . (string) ($activeShop['telephone'] ?? '-'), $boxX + 48, $boxY + 22, 7, 'F1', $dark);
        $content .= $this->pdfTextAt('Statut : Active', $boxX + 92, $boxY + 9, 7, 'F1', $dark);

        $metaY = 602.0;
        $content .= $this->pdfSetFill($light[0], $light[1], $light[2]);
        $content .= $this->pdfRect($left, $metaY, $contentWidth, 45, 'f');
        $content .= $this->pdfSetStroke($border[0], $border[1], $border[2]);
        $content .= $this->pdfRect($left, $metaY, $contentWidth, 45, 'S');
        $metaCols = [
            ['PÉRIODE', $periodDisplay],
            ['TYPE', 'Toutes les ventes'],
            ['GÉNÉRÉ LE', date('d/m/Y H:i')],
            ['BOUTIQUE', (string) ($activeShop['nom'] ?? '-')],
        ];
        $colW = $contentWidth / 4;
        foreach ($metaCols as $index => $meta) {
            $x = $left + ($index * $colW);
            if ($index > 0) {
                $content .= $this->pdfLine($x, $metaY + 8, $x, $metaY + 37, $border);
            }
            $content .= $this->pdfTextAt($meta[0], $x + 8, $metaY + 27, 6.5, 'F2', $muted);
            $content .= $this->pdfTextAt($this->truncatePdfText($meta[1], 26), $x + 8, $metaY + 15, 7, 'F2', $dark);
        }
        $content .= $this->pdfSetFill($primary[0], $primary[1], $primary[2]);
        $content .= $this->pdfRect($left, $metaY - 12, $contentWidth, 2, 'f');

        $y = 556.0;
        $content .= $this->pdfSectionTitle('1. SYNTHÈSE EXÉCUTIVE', $left, $y, $contentWidth);
        $y -= 50;
        $content .= $this->pdfSetFill(1, 1, 1);
        $content .= $this->pdfRect($left, $y, $contentWidth, 40, 'f');
        $content .= $this->pdfSetStroke($border[0], $border[1], $border[2]);
        $content .= $this->pdfRect($left, $y, $contentWidth, 40, 'S');
        $summaryText = 'Ce document présente une vue contrôlée des tickets, encaissements, crédits clients et statuts de vente disponibles dans l’historique.';
        foreach ($this->wrapPdfText($summaryText, 112) as $index => $line) {
            $content .= $this->pdfTextAt($line, $left + 10, $y + 25 - ($index * 11), 7.5, 'F1', $dark);
        }

        $y -= 70;
        $content .= $this->pdfSectionTitle('2. INDICATEURS CLÉS', $left, $y, $contentWidth);
        $y -= 55;
        $indicators = [
            ['TICKETS VALIDÉS', (string) (int) ($overview['validated_count'] ?? 0), 'Tickets actifs', $primary],
            ['CHIFFRE D’AFFAIRES', $this->money((float) ($overview['validated_revenue'] ?? 0)), 'Ventes validées', [0.07, 0.32, 0.70]],
            ['ENCAISSEMENTS', $this->money((float) ($overview['received_total'] ?? 0)), 'Montant reçu', [0.06, 0.46, 0.43]],
            ['CRÉDIT CLIENT', $this->money((float) ($overview['debt_total'] ?? 0)), 'Reste à encaisser', [0.82, 0.13, 0.25]],
        ];
        $cardW = $contentWidth / 4;
        foreach ($indicators as $index => $item) {
            $x = $left + ($index * $cardW);
            $content .= $this->pdfSetFill(1, 1, 1);
            $content .= $this->pdfRect($x, $y, $cardW, 45, 'f');
            $content .= $this->pdfSetStroke($border[0], $border[1], $border[2]);
            $content .= $this->pdfRect($x, $y, $cardW, 45, 'S');
            $content .= $this->pdfTextAt($item[0], $x + 8, $y + 30, 6.5, 'F2', $muted);
            $content .= $this->pdfTextAt($this->truncatePdfText($item[1], 18), $x + 8, $y + 17, 11, 'F2', $item[3]);
            $content .= $this->pdfTextAt($item[2], $x + 8, $y + 7, 6.5, 'F1', $dark);
        }

        $y -= 70;
        $content .= $this->pdfSectionTitle('3. RÉPARTITION DES PAIEMENTS', $left, $y, $contentWidth);
        $y -= 12;
        $paymentRows = [];
        foreach ($payments as $payment) {
            $paymentRows[] = [
                (string) ($payment['mode_paiement'] ?? 'cash'),
                (string) (int) ($payment['tickets'] ?? 0),
                $this->money((float) ($payment['revenue'] ?? 0)),
                $this->money((float) ($payment['debt'] ?? 0)),
            ];
        }
        if ($paymentRows === []) {
            $paymentRows[] = ['Aucun paiement', '0', '0,00 USD', '0,00 USD'];
        }
        $content .= $this->pdfTable($left, $y, $contentWidth, ['MODE', 'TICKETS', 'TOTAL', 'CRÉDIT'], $paymentRows, [0.34, 0.16, 0.25, 0.25], 4);
        $y -= 28 + (min(4, count($paymentRows)) * 20);

        $content .= $this->pdfSectionTitle('4. TICKETS EXPORTES', $left, $y, $contentWidth);
        $y -= 12;
        $ticketRows = [];
        foreach (array_slice($sales, 0, 7) as $sale) {
            $ticketRows[] = [
                (string) ($sale['numero_facture'] ?? '-'),
                (string) ($sale['customer_name'] ?? 'Client comptant'),
                $this->money((float) ($sale['total_montant'] ?? 0)),
                (string) ($sale['statut'] ?? '-'),
            ];
        }
        if ($ticketRows === []) {
            $ticketRows[] = ['Aucune vente', '-', '0,00 USD', '-'];
        }
        $content .= $this->pdfTable($left, $y, $contentWidth, ['FACTURE', 'CLIENT', 'TOTAL', 'STATUT'], $ticketRows, [0.30, 0.30, 0.22, 0.18], 7);

        $footerY = 34.0;
        $content .= $this->pdfLine($left, $footerY + 16, $right, $footerY + 16, $border);
        $content .= $this->pdfTextAt('MadukaOne - Historique des ventes généré automatiquement', $left, $footerY, 7, 'F1', $muted);
        $content .= $this->pdfTextAt('Page 1', $right - 35, $footerY, 7, 'F1', $muted);

        $streams = [$content];
        $remainingSales = array_slice($sales, 7);
        $pageNumber = 2;
        foreach (array_chunk($remainingSales, 24) as $salesPage) {
            $page = '';
            $page .= $this->pdfSetFill(1, 1, 1);
            $page .= $this->pdfRect(0, 0, $pageWidth, $pageHeight, 'f');
            $page .= $this->pdfSetFill($primary[0], $primary[1], $primary[2]);
            $page .= $this->pdfRect($left, 780, 18, 18, 'f');
            $page .= $this->pdfTextAt('M1', $left + 4, 786, 8, 'F2', [1, 1, 1]);
            $page .= $this->pdfTextAt('MadukaOne', $left + 25, 789, 10, 'F2', $dark);
            $page .= $this->pdfTextAt('Tickets exportes - suite', $left, 748, 16, 'F2', $dark);
            $page .= $this->pdfTextAt($periodDisplay, $left, 730, 8, 'F1', $muted);

            $ticketRows = [];
            foreach ($salesPage as $sale) {
                $ticketRows[] = [
                    (string) ($sale['numero_facture'] ?? '-'),
                    (string) ($sale['customer_name'] ?? 'Client comptant'),
                    $this->money((float) ($sale['total_montant'] ?? 0)),
                    (string) ($sale['statut'] ?? '-'),
                ];
            }
            $page .= $this->pdfTable($left, 700, $contentWidth, ['FACTURE', 'CLIENT', 'TOTAL', 'STATUT'], $ticketRows, [0.30, 0.30, 0.22, 0.18], 24);
            $page .= $this->pdfLine($left, $footerY + 16, $right, $footerY + 16, $border);
            $page .= $this->pdfTextAt('MadukaOne - Historique des ventes genere automatiquement', $left, $footerY, 7, 'F1', $muted);
            $page .= $this->pdfTextAt('Page ' . $pageNumber, $right - 35, $footerY, 7, 'F1', $muted);
            $streams[] = $page;
            $pageNumber++;
        }

        return $this->pdfFromContentStreams($streams);
    }

    private function xlsxContentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>';
    }

    private function xlsxRootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function xlsxWorkbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Ventes" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function xlsxWorkbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function xlsxStyles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            . '<borders count="1"><border/></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            . '</styleSheet>';
    }

    private function xlsxSheet(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        foreach (array_values($rows) as $rowIndex => $row) {
            $xml .= '<row r="' . ($rowIndex + 1) . '">';
            foreach (array_values($row) as $columnIndex => $value) {
                $cell = $this->columnName($columnIndex + 1) . ($rowIndex + 1);
                if (is_int($value) || is_float($value)) {
                    $xml .= '<c r="' . $cell . '"><v>' . $this->xml((string) $value) . '</v></c>';
                } else {
                    $xml .= '<c r="' . $cell . '" t="inlineStr"><is><t>' . $this->xml((string) $value) . '</t></is></c>';
                }
            }
            $xml .= '</row>';
        }

        return $xml . '</sheetData></worksheet>';
    }

    private function columnName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function pdfFromContentStreams(array $streams): string
    {
        $objects = [];
        $catalogId = 1;
        $pagesId = 2;
        $fontId = 3;
        $boldFontId = 4;
        $nextId = 5;
        $pageIds = [];

        $objects[$fontId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[$boldFontId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        foreach ($streams as $stream) {
            $pageId = $nextId++;
            $contentId = $nextId++;
            $pageIds[] = $pageId;
            $objects[$contentId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
            $objects[$pageId] = '<< /Type /Page /Parent ' . $pagesId . ' 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 ' . $fontId . ' 0 R /F2 ' . $boldFontId . ' 0 R >> >> /Contents ' . $contentId . ' 0 R >>';
        }

        $objects[$catalogId] = '<< /Type /Catalog /Pages ' . $pagesId . ' 0 R >>';
        $objects[$pagesId] = '<< /Type /Pages /Kids [' . implode(' ', array_map(static fn (int $id): string => $id . ' 0 R', $pageIds)) . '] /Count ' . count($pageIds) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";

        for ($id = 1; $id <= count($objects); $id++) {
            $pdf .= str_pad((string) ($offsets[$id] ?? 0), 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . ' /Root ' . $catalogId . " 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    private function pdfSectionTitle(string $title, float $x, float $y, float $width): string
    {
        $content = $this->pdfSetFill(0.94, 0.95, 0.97);
        $content .= $this->pdfRect($x, $y, $width, 22, 'f');
        $content .= $this->pdfSetFill(0.06, 0.46, 0.43);
        $content .= $this->pdfRect($x, $y, 3, 22, 'f');
        $content .= $this->pdfTextAt($title, $x + 10, $y + 8, 9, 'F2', [0.04, 0.07, 0.12]);

        return $content;
    }

    private function pdfTable(float $x, float $yTop, float $width, array $headers, array $rows, array $ratios, int $maxRows): string
    {
        $content = '';
        $headerHeight = 20.0;
        $rowHeight = 20.0;
        $y = $yTop - $headerHeight;
        $dark = [0.04, 0.07, 0.12];
        $border = [0.83, 0.86, 0.90];
        $content .= $this->pdfSetFill($dark[0], $dark[1], $dark[2]);
        $content .= $this->pdfRect($x, $y, $width, $headerHeight, 'f');

        $cursorX = $x;
        foreach ($headers as $index => $header) {
            $cellW = $width * (float) ($ratios[$index] ?? (1 / count($headers)));
            $content .= $this->pdfTextAt((string) $header, $cursorX + 6, $y + 7, 6.5, 'F2', [1, 1, 1]);
            $cursorX += $cellW;
        }

        foreach (array_slice($rows, 0, $maxRows) as $rowIndex => $row) {
            $rowY = $y - (($rowIndex + 1) * $rowHeight);
            $fill = $rowIndex % 2 === 0 ? [1, 1, 1] : [0.98, 0.99, 1.0];
            $content .= $this->pdfSetFill($fill[0], $fill[1], $fill[2]);
            $content .= $this->pdfRect($x, $rowY, $width, $rowHeight, 'f');
            $content .= $this->pdfSetStroke($border[0], $border[1], $border[2]);
            $content .= $this->pdfRect($x, $rowY, $width, $rowHeight, 'S');

            $cursorX = $x;
            foreach (array_values($row) as $index => $cell) {
                $cellW = $width * (float) ($ratios[$index] ?? (1 / count($headers)));
                $text = $this->truncatePdfText((string) $cell, $cellW > 160 ? 30 : 18);
                $content .= $this->pdfTextAt($text, $cursorX + 6, $rowY + 7, 7, 'F1', [0.04, 0.07, 0.12]);
                $cursorX += $cellW;
            }
        }

        return $content;
    }

    private function pdfTextAt(string $text, float $x, float $y, float $size, string $font = 'F1', array $rgb = [0, 0, 0]): string
    {
        return sprintf(
            "BT\n%.3F %.3F %.3F rg\n/%s %.2F Tf\n%.2F %.2F Td\n(%s) Tj\nET\n",
            $rgb[0],
            $rgb[1],
            $rgb[2],
            $font,
            $size,
            $x,
            $y,
            $this->pdfText($text)
        );
    }

    private function pdfRect(float $x, float $y, float $width, float $height, string $mode): string
    {
        return sprintf("%.2F %.2F %.2F %.2F re %s\n", $x, $y, $width, $height, $mode);
    }

    private function pdfLine(float $x1, float $y1, float $x2, float $y2, array $rgb = [0, 0, 0]): string
    {
        return $this->pdfSetStroke($rgb[0], $rgb[1], $rgb[2]) . sprintf("%.2F %.2F m %.2F %.2F l S\n", $x1, $y1, $x2, $y2);
    }

    private function pdfSetFill(float $r, float $g, float $b): string
    {
        return sprintf("%.3F %.3F %.3F rg\n", $r, $g, $b);
    }

    private function pdfSetStroke(float $r, float $g, float $b): string
    {
        return sprintf("%.3F %.3F %.3F RG\n", $r, $g, $b);
    }

    private function truncatePdfText(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, max(0, $max - 3)) . '...';
    }

    private function wrapPdfText(string $text, int $max): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $candidate = trim($line . ' ' . $word);
            if (mb_strlen($candidate) > $max && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $candidate;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }

    private function simplePdf(array $lines): string
    {
        $content = "BT\n/F1 10.5 Tf\n45 800 Td\n";
        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $content .= "0 -16 Td\n";
            }
            $content .= '(' . $this->pdfText($line) . ") Tj\n";
        }
        $content .= "ET\n";
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            5 => '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "endstream",
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        for ($id = 1; $id <= 5; $id++) {
            $pdf .= str_pad((string) ($offsets[$id] ?? 0), 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    private function pdfText(string $value): string
    {
        $value = $this->normalizePdfUtf8Text($value);
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', $value);
        $encoded = $encoded === false ? $value : $encoded;

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded);
    }

    private function normalizePdfUtf8Text(string $value): string
    {
        if (!str_contains($value, "\xC3\x83") && !str_contains($value, "\xC3\x82") && !str_contains($value, "\xC3\xA2")) {
            return $value;
        }

        $decoded = iconv('UTF-8', 'Windows-1252//IGNORE', $value);
        if (is_string($decoded) && $decoded !== '' && mb_check_encoding($decoded, 'UTF-8')) {
            return $decoded;
        }

        return str_replace("\xc2\xa0", ' ', $value);
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function downloadFile(string $path, string $filename, string $contentType): never
    {
        header_remove('Content-Type');
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        @unlink($path);
        exit;
    }
}
