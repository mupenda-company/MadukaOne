<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';
require_once dirname(__DIR__) . '/Models/Sale.php';

class PosController extends AppController
{
    private Sale $sales;

    public function __construct()
    {
        $this->sales = new Sale();
    }

    public function index(array $params = []): void
    {
        $statement = Database::connection()->prepare(
            'SELECT id, nom, ref, prix_vente, quantite_stock
             FROM products
             WHERE shop_id = :shop_id AND actif = 1
             ORDER BY nom ASC'
        );
        $statement->execute(['shop_id' => $this->currentShopId()]);

        $this->render('pos/index', [
            'pageTitle' => 'Caisse POS',
            'activeMenu' => 'pos',
            'products' => $statement->fetchAll(),
            'pageScripts' => ['assets/js/pos.js'],
        ]);
    }

    public function sales(array $params = []): void
    {
        $shopId = $this->currentShopId();

        $this->render('pos/sales', [
            'pageTitle' => 'Ventes',
            'activeMenu' => 'sales',
            'sales' => $this->sales->allByShop($shopId),
            'salesSummary' => $this->sales->summaryByShop($shopId),
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
            ], 201);
        } catch (InvalidArgumentException $exception) {
            $this->json(['ok' => false, 'success' => false, 'message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            $this->json(['ok' => false, 'success' => false, 'message' => $exception->getMessage()], 400);
        }
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
}
