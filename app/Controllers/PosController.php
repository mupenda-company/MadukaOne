<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';

class PosController extends AppController
{
    public function index(array $params = []): void
    {
        $statement = Database::connection()->prepare(
            'SELECT id, nom, ref, prix_vente, quantite_stock
             FROM products
             WHERE shop_id = :shop_id AND actif = 1
             ORDER BY nom ASC'
        );
        $statement->execute(['shop_id' => $this->currentShopId()]);
        $products = $statement->fetchAll();

        $this->render('pos/index', [
            'pageTitle' => 'Caisse POS',
            'activeMenu' => 'pos',
            'products' => $products,
            'pageScripts' => ['assets/js/pos.js'],
        ]);
    }

    public function store(array $params = []): void
    {
        $payload = json_decode((string) file_get_contents('php://input'), true);

        if (!is_array($payload) || empty($payload['items']) || !is_array($payload['items'])) {
            $this->json(['ok' => false, 'message' => 'Panier vide ou commande invalide.'], 422);
        }

        $shopId = $this->currentShopId();
        $userId = $this->currentUserId();
        $items = $this->normalizeItems($payload['items']);

        if ($items === []) {
            $this->json(['ok' => false, 'message' => 'Aucun article valide dans le panier.'], 422);
        }

        try {
            $invoice = '';
            $db = Database::getInstance();
            $db->transaction(function (PDO $pdo, Database $database) use ($shopId, $userId, $items, $payload, &$invoice): void {
                $selectProduct = $pdo->prepare(
                    'SELECT id, nom, prix_vente, prix_achat, quantite_stock
                     FROM products
                     WHERE id = :id AND shop_id = :shop_id AND actif = 1
                     FOR UPDATE'
                );
                $products = [];
                $total = 0.0;

                foreach ($items as $item) {
                    $selectProduct->execute(['id' => $item['product_id'], 'shop_id' => $shopId]);
                    $product = $selectProduct->fetch();

                    if (!is_array($product)) {
                        throw new RuntimeException('Un produit du panier est introuvable.');
                    }

                    if ((int) $product['quantite_stock'] < $item['quantity']) {
                        throw new RuntimeException('Stock insuffisant pour ' . (string) $product['nom'] . '.');
                    }

                    $product['quantity'] = $item['quantity'];
                    $product['unit_price'] = (float) $product['prix_vente'];
                    $product['line_total'] = $product['unit_price'] * $item['quantity'];
                    $products[] = $product;
                    $total += $product['line_total'];
                }

                $received = max(0, (float) ($payload['amount_received'] ?? 0));
                $paymentMethod = $this->paymentMethod((string) ($payload['payment_method'] ?? 'cash'));
                $debt = max(0, $total - $received);
                $invoice = 'FAC-' . date('Ymd-His') . '-' . random_int(100, 999);

                $sale = $pdo->prepare(
                    'INSERT INTO sales (shop_id, customer_id, user_id, numero_facture, total_montant, montant_recu, montant_dette, mode_paiement, statut)
                     VALUES (:shop_id, NULL, :user_id, :numero_facture, :total_montant, :montant_recu, :montant_dette, :mode_paiement, :statut)'
                );
                $sale->execute([
                    'shop_id' => $shopId,
                    'user_id' => $userId,
                    'numero_facture' => $invoice,
                    'total_montant' => $total,
                    'montant_recu' => $received,
                    'montant_dette' => $debt,
                    'mode_paiement' => $paymentMethod,
                    'statut' => 'validee',
                ]);
                $saleId = (int) $pdo->lastInsertId();

                $detail = $pdo->prepare(
                    'INSERT INTO sale_details (sale_id, product_id, quantite, prix_unitaire_vendu, prix_achat_unitaire, total_ligne)
                     VALUES (:sale_id, :product_id, :quantite, :prix_unitaire_vendu, :prix_achat_unitaire, :total_ligne)'
                );
                $updateProduct = $pdo->prepare(
                    'UPDATE products SET quantite_stock = :stock, updated_by = :updated_by WHERE id = :id AND shop_id = :shop_id'
                );
                $movement = $pdo->prepare(
                    'INSERT INTO stock_movements (shop_id, product_id, user_id, sale_id, type_mouvement, quantite, stock_avant, stock_apres, motif)
                     VALUES (:shop_id, :product_id, :user_id, :sale_id, :type_mouvement, :quantite, :stock_avant, :stock_apres, :motif)'
                );

                $database->enableStockUpdate();

                foreach ($products as $product) {
                    $before = (int) $product['quantite_stock'];
                    $after = $before - (int) $product['quantity'];

                    $detail->execute([
                        'sale_id' => $saleId,
                        'product_id' => (int) $product['id'],
                        'quantite' => (int) $product['quantity'],
                        'prix_unitaire_vendu' => (float) $product['unit_price'],
                        'prix_achat_unitaire' => (float) $product['prix_achat'],
                        'total_ligne' => (float) $product['line_total'],
                    ]);
                    $updateProduct->execute([
                        'stock' => $after,
                        'updated_by' => $userId,
                        'id' => (int) $product['id'],
                        'shop_id' => $shopId,
                    ]);
                    $movement->execute([
                        'shop_id' => $shopId,
                        'product_id' => (int) $product['id'],
                        'user_id' => $userId,
                        'sale_id' => $saleId,
                        'type_mouvement' => 'sortie',
                        'quantite' => (int) $product['quantity'],
                        'stock_avant' => $before,
                        'stock_apres' => $after,
                        'motif' => 'Vente POS ' . $invoice,
                    ]);
                }
            });

            $this->json([
                'ok' => true,
                'message' => 'Vente enregistrée avec succès.',
                'invoice' => $invoice,
            ]);
        } catch (Throwable $exception) {
            $this->json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = max(0, (int) ($item['quantity'] ?? 0));

            if ($productId > 0 && $quantity > 0) {
                $normalized[$productId] = [
                    'product_id' => $productId,
                    'quantity' => ($normalized[$productId]['quantity'] ?? 0) + $quantity,
                ];
            }
        }

        return array_values($normalized);
    }

    private function paymentMethod(string $method): string
    {
        return in_array($method, ['cash', 'mobile_money', 'carte', 'virement', 'credit', 'mixte'], true)
            ? $method
            : 'cash';
    }
}

