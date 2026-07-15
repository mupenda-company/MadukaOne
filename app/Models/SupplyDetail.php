<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Model.php';

final class SupplyDetail extends Model
{
    public function insert(
        PDO $db,
        int $supplyId,
        int $productId,
        int $quantity,
        float $purchasePrice,
        ?float $enteredPrice = null,
        string $enteredCurrency = 'USD',
        float $enteredRate = 2800.0
    ): void
    {
        $enteredPrice = round((float) ($enteredPrice ?? $purchasePrice), 2);
        $enteredCurrency = in_array($enteredCurrency, ['USD', 'CDF'], true) ? $enteredCurrency : 'USD';
        $enteredRate = max(0.0001, $enteredRate);

        $statement = $db->prepare(
            'INSERT INTO supply_details (
                supply_id, product_id, quantite, prix_achat_facture, prix_achat_saisi, devise_saisie, taux_change_saisie, total_ligne, total_ligne_saisi
             ) VALUES (
                :supply_id, :product_id, :quantite, :prix_achat_facture, :prix_achat_saisi, :devise_saisie, :taux_change_saisie, :total_ligne, :total_ligne_saisi
             )'
        );

        $statement->execute([
            'supply_id' => $supplyId,
            'product_id' => $productId,
            'quantite' => $quantity,
            'prix_achat_facture' => $purchasePrice,
            'prix_achat_saisi' => $enteredPrice,
            'devise_saisie' => $enteredCurrency,
            'taux_change_saisie' => $enteredRate,
            'total_ligne' => $quantity * $purchasePrice,
            'total_ligne_saisi' => $quantity * $enteredPrice,
        ]);
    }
}
