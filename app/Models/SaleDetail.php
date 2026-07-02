<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Model.php';

final class SaleDetail extends Model
{
    public function insert(
        PDO $db,
        int $saleId,
        int $productId,
        int $quantity,
        float $salePrice,
        float $purchasePrice
    ): void {
        $statement = $db->prepare(
            'INSERT INTO sale_details (
                sale_id, product_id, quantite, prix_unitaire_vendu, prix_achat_unitaire, total_ligne
             ) VALUES (
                :sale_id, :product_id, :quantite, :prix_unitaire_vendu, :prix_achat_unitaire, :total_ligne
             )'
        );

        $statement->execute([
            'sale_id' => $saleId,
            'product_id' => $productId,
            'quantite' => $quantity,
            'prix_unitaire_vendu' => $salePrice,
            'prix_achat_unitaire' => $purchasePrice,
            'total_ligne' => $quantity * $salePrice,
        ]);
    }
}
