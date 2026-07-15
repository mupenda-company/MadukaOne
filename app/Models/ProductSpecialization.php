<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/Model.php';

final class ProductSpecialization extends Model
{
    public function pharmacyProducts(int $shopId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT
                products.id,
                products.nom,
                products.ref,
                products.quantite_stock,
                products.alerte_stock_min,
                products.date_expiration,
                products.actif,
                product_categories.nom AS category_name,
                details.dosage,
                details.forme,
                details.fabricant,
                details.numero_lot,
                details.ordonnance_requise,
                details.alerte_expiration_jours,
                details.emplacement
             FROM products
             LEFT JOIN product_categories ON product_categories.id = products.category_id
             LEFT JOIN product_pharmacy_details details ON details.product_id = products.id
             WHERE products.shop_id = :shop_id
             ORDER BY products.actif DESC, products.nom ASC'
        );
        $statement->execute(['shop_id' => $shopId]);

        return $statement->fetchAll();
    }

    public function fashionProducts(int $shopId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT
                products.id,
                products.nom,
                products.ref,
                products.quantite_stock,
                products.alerte_stock_min,
                products.actif,
                product_categories.nom AS category_name,
                details.taille,
                details.couleur,
                details.marque,
                details.collection,
                details.sexe,
                details.matiere,
                details.saison,
                details.code_modele
             FROM products
             LEFT JOIN product_categories ON product_categories.id = products.category_id
             LEFT JOIN product_fashion_details details ON details.product_id = products.id
             WHERE products.shop_id = :shop_id
             ORDER BY products.actif DESC, products.nom ASC'
        );
        $statement->execute(['shop_id' => $shopId]);

        return $statement->fetchAll();
    }

    public function pharmacyDetails(int $productId, int $shopId): array
    {
        return $this->details('product_pharmacy_details', $productId, $shopId);
    }

    public function fashionDetails(int $productId, int $shopId): array
    {
        return $this->details('product_fashion_details', $productId, $shopId);
    }

    public function savePharmacy(int $productId, int $shopId, array $data): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO product_pharmacy_details (
                shop_id, product_id, dosage, forme, fabricant, numero_lot,
                ordonnance_requise, alerte_expiration_jours, emplacement, notes
             ) VALUES (
                :shop_id, :product_id, :dosage, :forme, :fabricant, :numero_lot,
                :ordonnance_requise, :alerte_expiration_jours, :emplacement, :notes
             )
             ON DUPLICATE KEY UPDATE
                dosage = VALUES(dosage),
                forme = VALUES(forme),
                fabricant = VALUES(fabricant),
                numero_lot = VALUES(numero_lot),
                ordonnance_requise = VALUES(ordonnance_requise),
                alerte_expiration_jours = VALUES(alerte_expiration_jours),
                emplacement = VALUES(emplacement),
                notes = VALUES(notes)'
        );
        $statement->execute([
            'shop_id' => $shopId,
            'product_id' => $productId,
            'dosage' => $this->nullableString($data['dosage'] ?? null),
            'forme' => $this->nullableString($data['forme'] ?? null),
            'fabricant' => $this->nullableString($data['fabricant'] ?? null),
            'numero_lot' => $this->nullableString($data['numero_lot'] ?? null),
            'ordonnance_requise' => isset($data['ordonnance_requise']) ? 1 : 0,
            'alerte_expiration_jours' => max(1, (int) ($data['alerte_expiration_jours'] ?? 30)),
            'emplacement' => $this->nullableString($data['emplacement'] ?? null),
            'notes' => $this->nullableString($data['notes'] ?? null),
        ]);
    }

    public function saveFashion(int $productId, int $shopId, array $data): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO product_fashion_details (
                shop_id, product_id, taille, couleur, marque, collection,
                sexe, matiere, saison, code_modele, notes
             ) VALUES (
                :shop_id, :product_id, :taille, :couleur, :marque, :collection,
                :sexe, :matiere, :saison, :code_modele, :notes
             )
             ON DUPLICATE KEY UPDATE
                taille = VALUES(taille),
                couleur = VALUES(couleur),
                marque = VALUES(marque),
                collection = VALUES(collection),
                sexe = VALUES(sexe),
                matiere = VALUES(matiere),
                saison = VALUES(saison),
                code_modele = VALUES(code_modele),
                notes = VALUES(notes)'
        );
        $statement->execute([
            'shop_id' => $shopId,
            'product_id' => $productId,
            'taille' => $this->nullableString($data['taille'] ?? null),
            'couleur' => $this->nullableString($data['couleur'] ?? null),
            'marque' => $this->nullableString($data['marque'] ?? null),
            'collection' => $this->nullableString($data['collection'] ?? null),
            'sexe' => $this->fashionGender($data['sexe'] ?? 'mixte'),
            'matiere' => $this->nullableString($data['matiere'] ?? null),
            'saison' => $this->nullableString($data['saison'] ?? null),
            'code_modele' => $this->nullableString($data['code_modele'] ?? null),
            'notes' => $this->nullableString($data['notes'] ?? null),
        ]);
    }

    public function pharmacyStats(array $products): array
    {
        $configured = 0;
        $expiring = 0;
        $expired = 0;
        $prescription = 0;
        $today = date('Y-m-d');

        foreach ($products as $product) {
            if (trim((string) ($product['dosage'] ?? $product['forme'] ?? $product['numero_lot'] ?? '')) !== '') {
                $configured++;
            }

            $expiration = trim((string) ($product['date_expiration'] ?? ''));
            if ($expiration !== '') {
                if ($expiration < $today) {
                    $expired++;
                } elseif ($expiration <= date('Y-m-d', strtotime('+' . max(1, (int) ($product['alerte_expiration_jours'] ?? 30)) . ' days'))) {
                    $expiring++;
                }
            }

            if ((int) ($product['ordonnance_requise'] ?? 0) === 1) {
                $prescription++;
            }
        }

        return compact('configured', 'expiring', 'expired', 'prescription') + ['total' => count($products)];
    }

    public function fashionStats(array $products): array
    {
        $configured = 0;
        $variants = [];
        $collections = [];
        $brands = [];

        foreach ($products as $product) {
            if (trim((string) ($product['taille'] ?? $product['couleur'] ?? $product['marque'] ?? '')) !== '') {
                $configured++;
            }

            $variant = trim((string) ($product['taille'] ?? '')) . '|' . trim((string) ($product['couleur'] ?? ''));
            if ($variant !== '|') {
                $variants[$variant] = true;
            }

            if (trim((string) ($product['collection'] ?? '')) !== '') {
                $collections[(string) $product['collection']] = true;
            }

            if (trim((string) ($product['marque'] ?? '')) !== '') {
                $brands[(string) $product['marque']] = true;
            }
        }

        return [
            'total' => count($products),
            'configured' => $configured,
            'variants' => count($variants),
            'collections' => count($collections),
            'brands' => count($brands),
        ];
    }

    private function details(string $table, int $productId, int $shopId): array
    {
        $statement = Database::connection()->prepare(
            "SELECT * FROM {$table} WHERE product_id = :product_id AND shop_id = :shop_id LIMIT 1"
        );
        $statement->execute([
            'product_id' => $productId,
            'shop_id' => $shopId,
        ]);
        $details = $statement->fetch();

        return is_array($details) ? $details : [];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function fashionGender(mixed $value): string
    {
        $value = strtolower(trim((string) ($value ?? 'mixte')));

        return in_array($value, ['mixte', 'femme', 'homme', 'enfant'], true) ? $value : 'mixte';
    }
}
