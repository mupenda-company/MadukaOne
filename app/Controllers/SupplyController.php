<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';

class SupplyController extends AppController
{
    public function index(array $params = []): void
    {
        $this->create($params);
    }

    public function create(array $params = []): void
    {
        $shopId = $this->currentShopId();
        $suppliers = $this->suppliers($shopId);
        $products = $this->products($shopId);

        $this->render('supplies/create', [
            'pageTitle' => 'Nouvel arrivage',
            'activeMenu' => 'supplies',
            'suppliers' => $suppliers,
            'products' => $products,
        ]);
    }

    public function store(array $params = []): void
    {
        $shopId = $this->currentShopId();
        $userId = $this->currentUserId();
        $supplierId = (int) ($_POST['supplier_id'] ?? 0);
        $number = trim((string) ($_POST['numero_arrivage'] ?? ''));
        $date = trim((string) ($_POST['date_approvisionnement'] ?? ''));
        $productIds = $_POST['product_id'] ?? [];
        $quantities = $_POST['quantite'] ?? [];
        $prices = $_POST['prix_achat_facture'] ?? [];

        if ($supplierId <= 0 || $number === '' || !is_array($productIds)) {
            $this->flashError('Fournisseur, numéro d’arrivage et articles sont obligatoires.');
            $this->redirect('/supplies/create');
        }

        $lines = [];
        foreach ($productIds as $index => $productId) {
            $quantity = max(0, (int) ($quantities[$index] ?? 0));
            $price = max(0, (float) ($prices[$index] ?? 0));

            if ((int) $productId > 0 && $quantity > 0) {
                $lines[] = [
                    'product_id' => (int) $productId,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $quantity * $price,
                ];
            }
        }

        if ($lines === []) {
            $this->flashError('Ajoutez au moins une ligne valide.');
            $this->redirect('/supplies/create');
        }

        try {
            $db = Database::getInstance();
            $db->transaction(function (PDO $pdo, Database $database) use ($shopId, $userId, $supplierId, $number, $date, $lines): void {
                $total = array_sum(array_column($lines, 'total'));
                $supply = $pdo->prepare(
                    'INSERT INTO supplies (shop_id, supplier_id, user_id, numero_arrivage, date_approvisionnement, total_facture, statut)
                     VALUES (:shop_id, :supplier_id, :user_id, :numero_arrivage, :date_approvisionnement, :total_facture, :statut)'
                );
                $supply->execute([
                    'shop_id' => $shopId,
                    'supplier_id' => $supplierId,
                    'user_id' => $userId,
                    'numero_arrivage' => $number,
                    'date_approvisionnement' => $date !== '' ? str_replace('T', ' ', $date) : date('Y-m-d H:i:s'),
                    'total_facture' => $total,
                    'statut' => 'reçu',
                ]);
                $supplyId = (int) $pdo->lastInsertId();

                $detail = $pdo->prepare(
                    'INSERT INTO supply_details (supply_id, product_id, quantite, prix_achat_facture, total_ligne)
                     VALUES (:supply_id, :product_id, :quantite, :prix_achat_facture, :total_ligne)'
                );
                $movement = $pdo->prepare(
                    'INSERT INTO stock_movements (shop_id, product_id, user_id, supply_id, type_mouvement, quantite, stock_avant, stock_apres, motif)
                     VALUES (:shop_id, :product_id, :user_id, :supply_id, :type_mouvement, :quantite, :stock_avant, :stock_apres, :motif)'
                );
                $selectProduct = $pdo->prepare('SELECT id, quantite_stock FROM products WHERE id = :id AND shop_id = :shop_id FOR UPDATE');
                $updateProduct = $pdo->prepare(
                    'UPDATE products
                     SET quantite_stock = :stock, prix_achat = :prix_achat, updated_by = :updated_by
                     WHERE id = :id AND shop_id = :shop_id'
                );

                $database->enableStockUpdate();

                foreach ($lines as $line) {
                    $selectProduct->execute(['id' => $line['product_id'], 'shop_id' => $shopId]);
                    $product = $selectProduct->fetch();

                    if (!is_array($product)) {
                        throw new RuntimeException('Produit introuvable dans la boutique active.');
                    }

                    $before = (int) $product['quantite_stock'];
                    $after = $before + $line['quantity'];

                    $detail->execute([
                        'supply_id' => $supplyId,
                        'product_id' => $line['product_id'],
                        'quantite' => $line['quantity'],
                        'prix_achat_facture' => $line['price'],
                        'total_ligne' => $line['total'],
                    ]);
                    $updateProduct->execute([
                        'stock' => $after,
                        'prix_achat' => $line['price'],
                        'updated_by' => $userId,
                        'id' => $line['product_id'],
                        'shop_id' => $shopId,
                    ]);
                    $movement->execute([
                        'shop_id' => $shopId,
                        'product_id' => $line['product_id'],
                        'user_id' => $userId,
                        'supply_id' => $supplyId,
                        'type_mouvement' => 'entree',
                        'quantite' => $line['quantity'],
                        'stock_avant' => $before,
                        'stock_apres' => $after,
                        'motif' => 'Arrivage fournisseur ' . $number,
                    ]);
                }
            });

            $this->flashSuccess('Arrivage enregistré et stock actualisé.');
            $this->redirect('/supplies/create');
        } catch (Throwable $exception) {
            $this->flashError('Impossible d’enregistrer l’arrivage: ' . $exception->getMessage());
            $this->redirect('/supplies/create');
        }
    }

    private function suppliers(int $shopId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, nom FROM suppliers WHERE shop_id = :shop_id ORDER BY nom ASC'
        );
        $statement->execute(['shop_id' => $shopId]);

        return $statement->fetchAll();
    }

    private function products(int $shopId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, nom, ref, prix_achat FROM products WHERE shop_id = :shop_id AND actif = 1 ORDER BY nom ASC'
        );
        $statement->execute(['shop_id' => $shopId]);

        return $statement->fetchAll();
    }
}

