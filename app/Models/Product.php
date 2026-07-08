<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/Model.php';

final class Product extends Model
{
    public function allByShop(int $shopId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, shop_id, code_barre, ref, nom, description, prix_achat, prix_vente,
                    prix_achat_devise, prix_vente_devise, prix_achat_montant, prix_vente_montant,
                    quantite_stock, alerte_stock_min, date_fabrication, date_expiration, actif, created_at, updated_at
             FROM products
             WHERE shop_id = :shop_id
             ORDER BY nom ASC'
        );
        $statement->execute(['shop_id' => $shopId]);

        return $statement->fetchAll();
    }

    public function findByShop(int $id, int $shopId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, shop_id, code_barre, ref, nom, description, prix_achat, prix_vente,
                    prix_achat_devise, prix_vente_devise, prix_achat_montant, prix_vente_montant,
                    quantite_stock, alerte_stock_min, date_fabrication, date_expiration, actif, created_at, updated_at
             FROM products
             WHERE id = :id AND shop_id = :shop_id
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
        $statement = Database::connection()->prepare(
            'INSERT INTO products (
                shop_id, code_barre, ref, nom, description, prix_achat, prix_vente,
                prix_achat_devise, prix_vente_devise, prix_achat_montant, prix_vente_montant,
                quantite_stock, alerte_stock_min, date_fabrication, date_expiration, actif, created_by, updated_by
             ) VALUES (
                :shop_id, :code_barre, :ref, :nom, :description, :prix_achat, :prix_vente,
                :prix_achat_devise, :prix_vente_devise, :prix_achat_montant, :prix_vente_montant,
                :quantite_stock, :alerte_stock_min, :date_fabrication, :date_expiration, :actif, :created_by, :updated_by
             )'
        );

        $statement->execute([
            'shop_id' => $shopId,
            'code_barre' => $this->nullableString($data['code_barre'] ?? null),
            'ref' => $this->nullableString($data['ref'] ?? null),
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

    private function currency(mixed $value): string
    {
        $currency = strtoupper(trim((string) ($value ?? 'USD')));

        return in_array($currency, ['USD', 'CDF'], true) ? $currency : 'USD';
    }
}
