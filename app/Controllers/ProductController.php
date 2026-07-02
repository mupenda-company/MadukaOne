<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';

class ProductController extends AppController
{
    public function index(array $params = []): void
    {
        $statement = Database::connection()->prepare(
            'SELECT id, ref, nom, code_barre, prix_achat, prix_vente, quantite_stock, alerte_stock_min, actif
             FROM products
             WHERE shop_id = :shop_id
             ORDER BY nom ASC'
        );
        $statement->execute(['shop_id' => $this->currentShopId()]);
        $products = $statement->fetchAll();

        $this->render('products/index', [
            'pageTitle' => 'Produits',
            'activeMenu' => 'products',
            'products' => $products,
        ]);
    }

    public function create(array $params = []): void
    {
        $this->render('products/create', [
            'pageTitle' => 'Ajouter un produit',
            'activeMenu' => 'products',
        ]);
    }

    public function store(array $params = []): void
    {
        $shopId = $this->currentShopId();
        $userId = $this->currentUserId();
        $name = trim((string) ($_POST['nom'] ?? ''));
        $purchasePrice = max(0, (float) ($_POST['prix_achat'] ?? 0));
        $salePrice = max(0, (float) ($_POST['prix_vente'] ?? 0));
        $stock = max(0, (int) ($_POST['quantite_stock'] ?? 0));
        $alert = max(0, (int) ($_POST['alerte_stock_min'] ?? 0));

        if ($name === '') {
            $this->flashError('Le nom du produit est obligatoire.');
            $this->redirect('/products/create');
        }

        try {
            $db = Database::getInstance();
            $db->transaction(function (PDO $pdo) use ($shopId, $userId, $name, $purchasePrice, $salePrice, $stock, $alert): void {
                $statement = $pdo->prepare(
                    'INSERT INTO products (shop_id, code_barre, ref, nom, description, prix_achat, prix_vente, quantite_stock, alerte_stock_min, created_by, updated_by)
                     VALUES (:shop_id, :code_barre, :ref, :nom, :description, :prix_achat, :prix_vente, :quantite_stock, :alerte_stock_min, :created_by, :updated_by)'
                );
                $statement->execute([
                    'shop_id' => $shopId,
                    'code_barre' => $this->nullableString($_POST['code_barre'] ?? null),
                    'ref' => $this->nullableString($_POST['ref'] ?? null),
                    'nom' => $name,
                    'description' => $this->nullableString($_POST['description'] ?? null),
                    'prix_achat' => $purchasePrice,
                    'prix_vente' => $salePrice,
                    'quantite_stock' => $stock,
                    'alerte_stock_min' => $alert,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                if ($stock > 0) {
                    $productId = (int) $pdo->lastInsertId();
                    $movement = $pdo->prepare(
                        'INSERT INTO stock_movements (shop_id, product_id, user_id, type_mouvement, quantite, stock_avant, stock_apres, motif)
                         VALUES (:shop_id, :product_id, :user_id, :type_mouvement, :quantite, 0, :stock_apres, :motif)'
                    );
                    $movement->execute([
                        'shop_id' => $shopId,
                        'product_id' => $productId,
                        'user_id' => $userId,
                        'type_mouvement' => 'entree',
                        'quantite' => $stock,
                        'stock_apres' => $stock,
                        'motif' => 'Stock initial à la création du produit',
                    ]);
                }
            });

            $this->flashSuccess('Produit enregistré avec succès.');
            $this->redirect('/products');
        } catch (Throwable $exception) {
            $this->flashError('Impossible d’enregistrer le produit: ' . $exception->getMessage());
            $this->redirect('/products/create');
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

