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

    public function activeCategories(): array
    {
        $statement = Database::connection()->query(
            'SELECT id, nom FROM shop_categories WHERE actif = 1 ORDER BY nom ASC'
        );

        return $statement->fetchAll();
    }

    public function createForOwner(array $data, int $ownerUserId, int $sourceShopId): int
    {
        return Database::getInstance()->transaction(function (PDO $pdo) use ($data, $ownerUserId, $sourceShopId): int {
            $pdo->prepare(
                'UPDATE shops SET owner_user_id = :owner_user_id WHERE id = :shop_id AND owner_user_id IS NULL'
            )->execute(['owner_user_id' => $ownerUserId, 'shop_id' => $sourceShopId]);

            $slug = $this->uniqueSlug($pdo, (string) ($data['nom'] ?? 'boutique'));
            $statement = $pdo->prepare(
                'INSERT INTO shops (
                    category_id, owner_user_id, nom, slug, adresse, telephone, email,
                    devise_principale, taux_change_cdf, actif
                 ) VALUES (
                    :category_id, :owner_user_id, :nom, :slug, :adresse, :telephone, :email,
                    :devise_principale, :taux_change_cdf, 1
                 )'
            );
            $statement->execute([
                'category_id' => (int) ($data['category_id'] ?? 0) ?: null,
                'owner_user_id' => $ownerUserId,
                'nom' => trim((string) ($data['nom'] ?? '')),
                'slug' => $slug,
                'adresse' => $this->nullableString($data['adresse'] ?? null),
                'telephone' => $this->nullableString($data['telephone'] ?? null),
                'email' => $this->nullableString($data['email'] ?? null),
                'devise_principale' => in_array(($data['devise_principale'] ?? 'USD'), ['USD', 'CDF'], true) ? $data['devise_principale'] : 'USD',
                'taux_change_cdf' => max(0.01, (float) ($data['taux_change_cdf'] ?? 2800)),
            ]);
            $shopId = (int) $pdo->lastInsertId();

            $copySubscription = $pdo->prepare(
                'INSERT INTO saas_subscriptions (
                    shop_id, plan_id, statut, date_debut, date_fin, renouvellement_auto, notes
                 )
                 SELECT :new_shop_id, plan_id, statut, date_debut, date_fin, renouvellement_auto,
                        CONCAT("Plan partage depuis la boutique #", shop_id)
                 FROM saas_subscriptions
                 WHERE shop_id = :source_shop_id
                 LIMIT 1'
            );
            $copySubscription->execute(['new_shop_id' => $shopId, 'source_shop_id' => $sourceShopId]);

            return $shopId;
        });
    }

    private function uniqueSlug(PDO $pdo, string $name): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', trim($name));
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $ascii)), '-');
        $base = $base !== '' ? $base : 'boutique';

        do {
            $slug = $base . '-' . strtolower(bin2hex(random_bytes(3)));
            $statement = $pdo->prepare('SELECT COUNT(*) FROM shops WHERE slug = :slug');
            $statement->execute(['slug' => $slug]);
        } while ((int) $statement->fetchColumn() > 0);

        return $slug;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}

