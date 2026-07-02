<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Model.php';

final class SupplyDetail extends Model
{
    public function insert(PDO $db, int $supplyId, int $productId, int $quantity, float $purchasePrice): void
    {
        $statement = $db->prepare(
            'INSERT INTO supply_details (
                supply_id, product_id, quantite, prix_achat_facture, total_ligne
             ) VALUES (
                :supply_id, :product_id, :quantite, :prix_achat_facture, :total_ligne
             )'
        );

        $statement->execute([
            'supply_id' => $supplyId,
            'product_id' => $productId,
            'quantite' => $quantity,
            'prix_achat_facture' => $purchasePrice,
            'total_ligne' => $quantity * $purchasePrice,
        ]);
    }
}
