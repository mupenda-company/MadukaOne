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
        float $purchasePrice,
        float $enteredSalePrice = 0.0,
        string $enteredCurrency = 'USD',
        float $exchangeRate = 2800.0
    ): void {
        $enteredCurrency = in_array($enteredCurrency, ['USD', 'CDF'], true) ? $enteredCurrency : 'USD';
        $enteredSalePrice = $enteredSalePrice > 0 ? $enteredSalePrice : $salePrice;
        $exchangeRate = $exchangeRate > 0 ? $exchangeRate : 2800.0;
        $statement = $db->prepare(
            'INSERT INTO sale_details (
                sale_id, product_id, quantite, prix_unitaire_vendu, prix_unitaire_vendu_saisi,
                devise_saisie, taux_change_saisie, prix_achat_unitaire, total_ligne, total_ligne_saisi
             ) VALUES (
                :sale_id, :product_id, :quantite, :prix_unitaire_vendu, :prix_unitaire_vendu_saisi,
                :devise_saisie, :taux_change_saisie, :prix_achat_unitaire, :total_ligne, :total_ligne_saisi
             )'
        );

        $statement->execute([
            'sale_id' => $saleId,
            'product_id' => $productId,
            'quantite' => $quantity,
            'prix_unitaire_vendu' => $salePrice,
            'prix_unitaire_vendu_saisi' => $enteredSalePrice,
            'devise_saisie' => $enteredCurrency,
            'taux_change_saisie' => $exchangeRate,
            'prix_achat_unitaire' => $purchasePrice,
            'total_ligne' => $quantity * $salePrice,
            'total_ligne_saisi' => $quantity * $enteredSalePrice,
        ]);
    }
}
