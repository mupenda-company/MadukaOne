<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';
require_once dirname(__DIR__) . '/Models/Product.php';

final class CategoryWorkspaceController extends AppController
{
    private const WORKSPACES = [
        'hardware' => ['slug'=>'quincailleries','view'=>'hardware/index','title'=>'Espace quincaillerie','eyebrow'=>'Quincaillerie moderne','unit'=>'Article technique','icon'=>'🔧','gradient'=>'from-slate-950 via-orange-950 to-amber-700','focus'=>['Outillage et matériaux','Références techniques','Seuils de stock','Fournisseurs']],
        'supermarket' => ['slug'=>'supermarches','view'=>'supermarket/index','title'=>'Espace supermarché','eyebrow'=>'Commerce grande consommation','unit'=>'Produit en rayon','icon'=>'🛒','gradient'=>'from-emerald-950 via-teal-900 to-lime-700','focus'=>['Rayons organisés','Rotation rapide','Codes-barres','Péremptions']],
        'depot' => ['slug'=>'depots','view'=>'depot/index','title'=>'Espace dépôt','eyebrow'=>'Stock central et logistique','unit'=>'Référence de stock','icon'=>'🏭','gradient'=>'from-slate-950 via-blue-950 to-cyan-700','focus'=>['Entrées de stock','Sorties contrôlées','Volumes','Traçabilité']],
        'stationery' => ['slug'=>'papeteries','view'=>'stationery/index','title'=>'Espace papeterie','eyebrow'=>'Fournitures et scolaire','unit'=>'Fourniture','icon'=>'✏️','gradient'=>'from-indigo-950 via-violet-900 to-fuchsia-700','focus'=>['Fournitures','Kits scolaires','Prix unitaires','Stock rapide']],
        'bookstore' => ['slug'=>'librairies','view'=>'bookstore/index','title'=>'Espace librairie','eyebrow'=>'Livres et connaissances','unit'=>'Ouvrage','icon'=>'📚','gradient'=>'from-stone-950 via-amber-950 to-orange-700','focus'=>['Ouvrages','Auteurs et genres','Références','Disponibilités']],
        'bakery' => ['slug'=>'boulangeries','view'=>'bakery/index','title'=>'Espace boulangerie','eyebrow'=>'Production fraîche','unit'=>'Produit frais','icon'=>'🥖','gradient'=>'from-amber-950 via-orange-900 to-yellow-600','focus'=>['Production du jour','Vitrine','Invendus','Pertes']],
        'restaurant' => ['slug'=>'restaurants','view'=>'restaurant/index','title'=>'Espace restaurant','eyebrow'=>'Carte et restauration','unit'=>'Plat','icon'=>'🍽️','gradient'=>'from-red-950 via-rose-900 to-orange-700','focus'=>['Menu','Plats disponibles','Boissons','Recettes']],
        'bar' => ['slug'=>'bars','view'=>'bar/index','title'=>'Espace bar','eyebrow'=>'Boissons et consommations','unit'=>'Boisson','icon'=>'🍹','gradient'=>'from-purple-950 via-indigo-950 to-cyan-700','focus'=>['Bouteilles','Consommations','Recettes','Pertes']],
        'hotel' => ['slug'=>'hotels','view'=>'hotel/index','title'=>'Espace hôtel','eyebrow'=>'Hébergement et services','unit'=>'Chambre ou service','icon'=>'🏨','gradient'=>'from-slate-950 via-indigo-950 to-blue-700','focus'=>['Chambres','Services','Tarification','Suivi client']],
        'electronics' => ['slug'=>'magasins-d-electronique','view'=>'electronics/index','title'=>'Espace électronique','eyebrow'=>'Appareils et technologies','unit'=>'Appareil','icon'=>'📱','gradient'=>'from-zinc-950 via-blue-950 to-violet-700','focus'=>['Références techniques','Accessoires','Garanties','Stock à valeur']],
        'wholesale' => ['slug'=>'grossistes','view'=>'wholesale/index','title'=>'Espace grossiste','eyebrow'=>'Vente en gros et volumes','unit'=>'Lot','icon'=>'📦','gradient'=>'from-slate-950 via-emerald-950 to-teal-700','focus'=>['Lots','Prix volume','Clients professionnels','Grands stocks']],
        'distribution' => ['slug'=>'distributeurs','view'=>'distribution/index','title'=>'Espace distribution','eyebrow'=>'Flux et réseau','unit'=>'Article distribué','icon'=>'🚚','gradient'=>'from-blue-950 via-cyan-900 to-teal-700','focus'=>['Flux sortants','Réseau clients','Références','Rapports']],
        'commercial' => ['slug'=>'entreprises-commerciales','view'=>'commercial/index','title'=>'Espace commercial','eyebrow'=>'Pilotage des offres','unit'=>'Article commercial','icon'=>'💼','gradient'=>'from-slate-950 via-teal-950 to-emerald-700','focus'=>['Offres','Ventes','Achats','Pilotage']],
        'mobile' => ['slug'=>'vendeur-forfait-mobile-unites','view'=>'mobile-units/index','title'=>'Espace forfaits et unités','eyebrow'=>'Télécom et recharges','unit'=>'Forfait ou recharge','icon'=>'📶','gradient'=>'from-blue-950 via-violet-950 to-fuchsia-700','focus'=>['Forfaits','Unités','Recharges','Opérations rapides']],
    ];

    public function hardware(array $params = []): void { $this->show('hardware'); }
    public function supermarket(array $params = []): void { $this->show('supermarket'); }
    public function depot(array $params = []): void { $this->show('depot'); }
    public function stationery(array $params = []): void { $this->show('stationery'); }
    public function bookstore(array $params = []): void { $this->show('bookstore'); }
    public function bakery(array $params = []): void { $this->show('bakery'); }
    public function restaurant(array $params = []): void { $this->show('restaurant'); }
    public function bar(array $params = []): void { $this->show('bar'); }
    public function hotel(array $params = []): void { $this->show('hotel'); }
    public function electronics(array $params = []): void { $this->show('electronics'); }
    public function wholesale(array $params = []): void { $this->show('wholesale'); }
    public function distribution(array $params = []): void { $this->show('distribution'); }
    public function commercial(array $params = []): void { $this->show('commercial'); }
    public function mobile(array $params = []): void { $this->show('mobile'); }

    private function show(string $key): void
    {
        $profile = self::WORKSPACES[$key] ?? null;
        if (!is_array($profile)) {
            $this->abort(404, 'Espace métier introuvable.');
        }
        $shop = $this->activeShop($this->shops(), $this->currentUser());
        if ((string) ($shop['category_slug'] ?? '') !== $profile['slug']) {
            $this->flashError('Cet espace est réservé aux boutiques de la catégorie correspondante.');
            $this->redirect('/products');
        }
        $products = (new Product())->allByShop($this->currentShopId());
        $active = array_filter($products, static fn (array $p): bool => (int) ($p['actif'] ?? 1) === 1);
        $low = array_filter($active, static fn (array $p): bool => (int) ($p['quantite_stock'] ?? 0) <= (int) ($p['alerte_stock_min'] ?? 0));
        $out = array_filter($active, static fn (array $p): bool => (int) ($p['quantite_stock'] ?? 0) === 0);
        $this->render($profile['view'], [
            'pageTitle' => $profile['title'], 'activeMenu' => 'products',
            'workspace' => $profile, 'products' => $products,
            'workspaceStats' => ['total'=>count($products),'active'=>count($active),'low'=>count($low),'out'=>count($out)],
        ]);
    }
}
