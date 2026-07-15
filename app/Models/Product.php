<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/Model.php';

final class Product extends Model
{
    public function allByShop(int $shopId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT products.id, products.shop_id, products.category_id, product_categories.nom AS category_name,
                    products.code_barre, products.ref, products.nom, products.description, products.prix_achat, products.prix_vente,
                    prix_achat_devise, prix_vente_devise, prix_achat_montant, prix_vente_montant,
                    quantite_stock, alerte_stock_min, date_fabrication, date_expiration, products.actif, products.created_at, products.updated_at
             FROM products
             LEFT JOIN product_categories ON product_categories.id = products.category_id
             WHERE products.shop_id = :shop_id
             ORDER BY products.nom ASC'
        );
        $statement->execute(['shop_id' => $shopId]);

        return $statement->fetchAll();
    }

    public function findByShop(int $id, int $shopId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT products.id, products.shop_id, products.category_id, product_categories.nom AS category_name,
                    products.code_barre, products.ref, products.nom, products.description, products.prix_achat, products.prix_vente,
                    prix_achat_devise, prix_vente_devise, prix_achat_montant, prix_vente_montant,
                    quantite_stock, alerte_stock_min, date_fabrication, date_expiration, products.actif, products.created_at, products.updated_at
             FROM products
             LEFT JOIN product_categories ON product_categories.id = products.category_id
             WHERE products.id = :id AND products.shop_id = :shop_id
             LIMIT 1'
        );
        $statement->execute([
            'id' => $id,
            'shop_id' => $shopId,
        ]);

        $product = $statement->fetch();

        return is_array($product) ? $product : null;
    }

    public function create(array $data, int $shopId, int $userId): int
    {
        $reference = $this->nullableString($data['ref'] ?? null);
        if ($reference === null || $this->referenceExists($shopId, $reference)) {
            $reference = $this->nextReference($shopId);
        }

        $statement = Database::connection()->prepare(
            'INSERT INTO products (
                shop_id, category_id, code_barre, ref, nom, description, prix_achat, prix_vente,
                prix_achat_devise, prix_vente_devise, prix_achat_montant, prix_vente_montant,
                quantite_stock, alerte_stock_min, date_fabrication, date_expiration, actif, created_by, updated_by
             ) VALUES (
                :shop_id, :category_id, :code_barre, :ref, :nom, :description, :prix_achat, :prix_vente,
                :prix_achat_devise, :prix_vente_devise, :prix_achat_montant, :prix_vente_montant,
                :quantite_stock, :alerte_stock_min, :date_fabrication, :date_expiration, :actif, :created_by, :updated_by
             )'
        );

        $statement->execute([
            'shop_id' => $shopId,
            'category_id' => $this->categoryId($data['category_id'] ?? null),
            'code_barre' => $this->nullableString($data['code_barre'] ?? null),
            'ref' => $reference,
            'nom' => trim((string) $data['nom']),
            'description' => $this->nullableString($data['description'] ?? null),
            'prix_achat' => (float) ($data['prix_achat'] ?? 0),
            'prix_vente' => (float) ($data['prix_vente'] ?? 0),
            'prix_achat_devise' => $this->currency($data['prix_achat_devise'] ?? 'USD'),
            'prix_vente_devise' => $this->currency($data['prix_vente_devise'] ?? 'USD'),
            'prix_achat_montant' => (float) ($data['prix_achat_montant'] ?? $data['prix_achat'] ?? 0),
            'prix_vente_montant' => (float) ($data['prix_vente_montant'] ?? $data['prix_vente'] ?? 0),
            'quantite_stock' => (int) ($data['quantite_stock'] ?? 0),
            'alerte_stock_min' => (int) ($data['alerte_stock_min'] ?? 0),
            'date_fabrication' => $this->nullableString($data['date_fabrication'] ?? null),
            'date_expiration' => $this->nullableString($data['date_expiration'] ?? null),
            'actif' => isset($data['actif']) ? 1 : 0,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function updateByShop(int $id, int $shopId, array $data, int $userId): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE products
             SET code_barre = :code_barre,
                 category_id = :category_id,
                 ref = :ref,
                 nom = :nom,
                 description = :description,
                 prix_achat = :prix_achat,
                 prix_vente = :prix_vente,
                 prix_achat_devise = :prix_achat_devise,
                 prix_vente_devise = :prix_vente_devise,
                 prix_achat_montant = :prix_achat_montant,
                 prix_vente_montant = :prix_vente_montant,
                 alerte_stock_min = :alerte_stock_min,
                 date_fabrication = :date_fabrication,
                 date_expiration = :date_expiration,
                 actif = :actif,
                 updated_by = :updated_by
             WHERE id = :id AND shop_id = :shop_id'
        );

        $statement->execute([
            'code_barre' => $this->nullableString($data['code_barre'] ?? null),
            'category_id' => $this->categoryId($data['category_id'] ?? null),
            'ref' => $this->nullableString($data['ref'] ?? null),
            'nom' => trim((string) $data['nom']),
            'description' => $this->nullableString($data['description'] ?? null),
            'prix_achat' => (float) ($data['prix_achat'] ?? 0),
            'prix_vente' => (float) ($data['prix_vente'] ?? 0),
            'prix_achat_devise' => $this->currency($data['prix_achat_devise'] ?? 'USD'),
            'prix_vente_devise' => $this->currency($data['prix_vente_devise'] ?? 'USD'),
            'prix_achat_montant' => (float) ($data['prix_achat_montant'] ?? $data['prix_achat'] ?? 0),
            'prix_vente_montant' => (float) ($data['prix_vente_montant'] ?? $data['prix_vente'] ?? 0),
            'alerte_stock_min' => (int) ($data['alerte_stock_min'] ?? 0),
            'date_fabrication' => $this->nullableString($data['date_fabrication'] ?? null),
            'date_expiration' => $this->nullableString($data['date_expiration'] ?? null),
            'actif' => isset($data['actif']) ? 1 : 0,
            'updated_by' => $userId,
            'id' => $id,
            'shop_id' => $shopId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function categoriesByShop(int $shopId, bool $activeOnly = true): array
    {
        $activeClause = $activeOnly ? ' AND actif = 1' : '';
        $statement = Database::connection()->prepare(
            'SELECT id, shop_id, nom, slug, actif, created_at, updated_at
             FROM product_categories
             WHERE shop_id = :shop_id' . $activeClause . '
             ORDER BY nom ASC'
        );
        $statement->execute(['shop_id' => $shopId]);

        return $statement->fetchAll();
    }

    public function createCategory(int $shopId, string $name): array
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('Le nom de la categorie est obligatoire.');
        }

        if (mb_strlen($name) > 150) {
            throw new InvalidArgumentException('Le nom de la categorie ne peut pas depasser 150 caracteres.');
        }

        $slug = $this->slug($name);
        $statement = Database::connection()->prepare(
            'INSERT INTO product_categories (shop_id, nom, slug, actif)
             VALUES (:shop_id, :nom, :slug, 1)
             ON DUPLICATE KEY UPDATE actif = 1, updated_at = CURRENT_TIMESTAMP'
        );
        $statement->execute([
            'shop_id' => $shopId,
            'nom' => $name,
            'slug' => $slug,
        ]);

        $find = Database::connection()->prepare(
            'SELECT id, shop_id, nom, slug, actif
             FROM product_categories
             WHERE shop_id = :shop_id AND slug = :slug
             LIMIT 1'
        );
        $find->execute([
            'shop_id' => $shopId,
            'slug' => $slug,
        ]);
        $category = $find->fetch();

        if (!is_array($category)) {
            throw new RuntimeException('Categorie introuvable apres creation.');
        }

        return $category;
    }

    public function categoryBelongsToShop(?int $categoryId, int $shopId): bool
    {
        if ($categoryId === null) {
            return true;
        }

        $statement = Database::connection()->prepare(
            'SELECT id FROM product_categories WHERE id = :id AND shop_id = :shop_id AND actif = 1 LIMIT 1'
        );
        $statement->execute([
            'id' => $categoryId,
            'shop_id' => $shopId,
        ]);

        return $statement->fetch() !== false;
    }

    public function nextReference(int $shopId): string
    {
        $statement = Database::connection()->prepare(
            'SELECT ref
             FROM products
             WHERE shop_id = :shop_id AND ref LIKE "PRD-%"
             ORDER BY id DESC
             LIMIT 50'
        );
        $statement->execute(['shop_id' => $shopId]);
        $next = 1;

        foreach ($statement->fetchAll() as $row) {
            if (preg_match('/^PRD-(\d+)$/', (string) ($row['ref'] ?? ''), $matches) === 1) {
                $next = max($next, (int) $matches[1] + 1);
                break;
            }
        }

        do {
            $reference = 'PRD-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while ($this->referenceExists($shopId, $reference));

        return $reference;
    }

    public function deleteByShop(int $id, int $shopId): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE products
             SET actif = 0
             WHERE id = :id AND shop_id = :shop_id'
        );
        $statement->execute([
            'id' => $id,
            'shop_id' => $shopId,
        ]);

        return $statement->rowCount() > 0;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function categoryId(mixed $value): ?int
    {
        $id = (int) ($value ?? 0);

        return $id > 0 ? $id : null;
    }

    private function referenceExists(int $shopId, string $reference): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT id FROM products WHERE shop_id = :shop_id AND ref = :ref LIMIT 1'
        );
        $statement->execute([
            'shop_id' => $shopId,
            'ref' => $reference,
        ]);

        return $statement->fetch() !== false;
    }

    private function slug(string $value): string
    {
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $slug = strtolower((string) $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'categorie';
    }

    private function currency(mixed $value): string
    {
        $currency = strtoupper(trim((string) ($value ?? 'USD')));

        return in_array($currency, ['USD', 'CDF'], true) ? $currency : 'USD';
    }
}
