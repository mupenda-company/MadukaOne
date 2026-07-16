<?php

declare(strict_types=1);

final class ModuleCatalog
{
    public static function all(): array
    {
        return [
            'pos' => [
                'label' => 'Caisse POS et ventes', 'group' => 'ventes',
                'capabilities' => ['Catalogue de vente et panier', 'Recherche et creation rapide de client', 'Paiement comptant, partiel ou a credit', 'Saisie dans la devise de la vente', 'Confirmation detaillee avant validation', 'Mise a jour transactionnelle du stock', 'Facture imprimable', 'Historique, detail, modification et annulation des ventes'],
            ],
            'customers' => [
                'label' => 'Clients et credits', 'group' => 'ventes',
                'capabilities' => ['Creation, consultation, modification et suppression', 'Recherche et filtres clients', 'Synthese des comptes clients', 'Suivi de la dette actuelle', 'Historique des ventes a credit', 'Reglement total ou partiel des dettes', 'Synchronisation de la dette client avec les ventes'],
            ],
            'stock' => [
                'label' => 'Catalogue, stock et inventaire', 'group' => 'stock',
                'capabilities' => ['Creation et gestion des produits', 'Categories de produits', 'Prix achat, prix vente et devise', 'Quantite disponible et seuil d alerte', 'Mouvements d entree et de sortie', 'Ajustements manuels controles', 'Inventaire standard ou complet', 'Alertes de rupture et d expiration'],
            ],
            'supplies' => [
                'label' => 'Approvisionnements et fournisseurs', 'group' => 'achats',
                'capabilities' => ['Gestion complete des fournisseurs', 'Creation d arrivages multi-produits', 'Prix d achat et devise de saisie', 'Details et total d approvisionnement', 'Mise a jour transactionnelle du stock', 'Modification avec recalcul des ecarts', 'Annulation controlee et restauration du stock', 'Historique des approvisionnements'],
            ],
            'reports' => [
                'label' => 'Rapports et sauvegardes', 'group' => 'pilotage',
                'capabilities' => ['Rapport des ventes', 'Rapport financier', 'Rapport des mouvements de stock', 'Filtres par periode et criteres metier', 'Indicateurs, totaux, marges et charges', 'Historique filtre des ventes', 'Apercu avant export et impression', 'Sauvegarde manuelle des donnees'],
            ],
            'multi_currency' => [
                'label' => 'Gestion multi-devise', 'group' => 'finance',
                'capabilities' => ['Devise principale par boutique', 'Configuration du taux USD vers CDF', 'Affichage de la devise principale en premier', 'Conversion USD et CDF cote a cote', 'Saisie de devise sur ventes, achats et depenses', 'Conservation du montant et de la devise saisis', 'Conversions coherentes dans les rapports et apercus'],
            ],
            'finance' => [
                'label' => 'Depenses et finances', 'group' => 'finance',
                'capabilities' => ['Enregistrement des charges et depenses', 'Saisie du montant et de la devise d origine', 'Consultation detaillee des operations', 'Modification des depenses', 'Annulation controlee avec conservation de la trace', 'Filtres et syntheses financieres', 'Prise en compte dans la marge et le benefice net'],
            ],
            'pharmacy' => [
                'label' => 'Gestion pharmacie', 'group' => 'metier',
                'capabilities' => ['Informations pharmaceutiques par produit', 'Dosage et forme pharmaceutique', 'Numero de lot', 'Fabricant', 'Exigence d ordonnance', 'Dates de fabrication et d expiration', 'Alertes sur les produits proches de l expiration'],
            ],
            'fashion' => [
                'label' => 'Gestion vetements', 'group' => 'metier',
                'capabilities' => ['Informations textiles par produit', 'Tailles et couleurs', 'Marques', 'Collections', 'Saisons', 'Matiere ou composition', 'Variantes et references de vetements'],
            ],
        ];
    }
}
