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
                    shops.adresse,
                    shops.telephone,
                    shops.email,
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

    public function updateSettings(int $id, array $data): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE shops
             SET nom = :nom,
                 adresse = :adresse,
                 telephone = :telephone,
                 email = :email,
                 devise_principale = :devise_principale,
                 taux_change_cdf = :taux_change_cdf
             WHERE id = :id'
        );

        $statement->execute([
            'nom' => trim((string) $data['nom']),
            'adresse' => $this->nullableString($data['adresse'] ?? null),
            'telephone' => $this->nullableString($data['telephone'] ?? null),
            'email' => $this->nullableString($data['email'] ?? null),
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

