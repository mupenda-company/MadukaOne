<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/Model.php';

final class Shop extends Model
{
    public function find(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT shops.id,
                    shops.nom,
                    shops.slug,
                    shops.adresse,
                    shops.telephone,
                    shops.email,
                    shops.logo_url,
                    shops.owner_user_id,
                    shops.devise_principale,
                    shops.taux_change_cdf,
                    shops.actif,
                    shops.created_at,
                    shops.updated_at,
                    shops.category_id,
                    categories.nom AS category_name,
                    categories.slug AS category_slug,
                    categories.description AS category_description
             FROM shops
             LEFT JOIN shop_categories categories ON categories.id = shops.category_id
             WHERE shops.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $shop = $statement->fetch();

        return is_array($shop) ? $shop : null;
    }

    public function findPublicBySlug(string $slug): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT shops.id, shops.nom, shops.slug, shops.adresse, shops.telephone, shops.email,
                    shops.logo_url, shops.devise_principale, shops.taux_change_cdf,
                    categories.nom AS category_name
             FROM shops
             LEFT JOIN shop_categories categories ON categories.id = shops.category_id
             WHERE shops.slug = :slug AND shops.actif = 1
             LIMIT 1'
        );
        $statement->execute(['slug' => $slug]);
        $shop = $statement->fetch();

        return is_array($shop) ? $shop : null;
    }

    public function publicProducts(int $shopId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT products.id, products.nom, products.description, products.prix_vente,
                    products.prix_vente_devise, products.prix_vente_montant,
                    products.quantite_stock, categories.nom AS category_name
             FROM products
             LEFT JOIN product_categories categories ON categories.id = products.category_id
             WHERE products.shop_id = :shop_id AND products.actif = 1
             ORDER BY categories.nom ASC, products.nom ASC'
        );
        $statement->execute(['shop_id' => $shopId]);

        return $statement->fetchAll();
    }

    public function updateSettings(int $id, array $data): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE shops
             SET nom = :nom,
                 adresse = :adresse,
                 telephone = :telephone,
                 email = :email,
                 logo_url = :logo_url,
                 devise_principale = :devise_principale,
                 taux_change_cdf = :taux_change_cdf
             WHERE id = :id'
        );

        $statement->execute([
            'nom' => trim((string) $data['nom']),
            'adresse' => $this->nullableString($data['adresse'] ?? null),
            'telephone' => $this->nullableString($data['telephone'] ?? null),
            'email' => $this->nullableString($data['email'] ?? null),
            'logo_url' => $this->nullableString($data['logo_url'] ?? null),
            'devise_principale' => in_array(($data['devise_principale'] ?? 'USD'), ['USD', 'CDF'], true) ? $data['devise_principale'] : 'USD',
            'taux_change_cdf' => (float) ($data['taux_change_cdf'] ?? 1),
            'id' => $id,
        ]);

        return $statement->rowCount() > 0;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}

