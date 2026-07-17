<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';
require_once dirname(__DIR__) . '/Core/SubscriptionGate.php';
require_once dirname(__DIR__) . '/Core/Validator.php';
require_once dirname(__DIR__) . '/Models/Product.php';

class ProductController extends AppController
{
    private Product $products;

    public function __construct()
    {
        $this->products = new Product();
    }

    public function index(array $params = []): void
    {
        $shop = $this->activeShop($this->shops(), $this->currentUser());
        $categorySlug = (string) ($shop['category_slug'] ?? '');
        $catalogProfile = $this->productCatalogProfile($categorySlug);
        $this->render('products/index', [
            'pageTitle' => $catalogProfile['page_title'],
            'activeMenu' => 'products',
            'products' => $this->products->allByShop($this->currentShopId()),
            'productCategories' => $this->productCategories(),
            'productCatalogProfile' => $catalogProfile,
            'catalogReturnPath' => $this->catalogRouteForCategory($categorySlug),
        ]);
    }

    public function create(array $params = []): void
    {
        $shop = $this->activeShop($this->shops(), $this->currentUser());
        $categorySlug = (string) ($shop['category_slug'] ?? '');
        $catalogProfile = $this->productCatalogProfile($categorySlug);
        $this->render('products/create', [
            'pageTitle' => $catalogProfile['create_title'],
            'activeMenu' => 'products',
            'productCategories' => $this->productCategories(),
            'nextReference' => $this->products->nextReference($this->currentShopId()),
            'productCatalogProfile' => $catalogProfile,
            'catalogReturnPath' => $this->catalogRouteForCategory($categorySlug),
        ]);
    }

    public function store(array $params = []): void
    {
        $data = $this->productPayload(allowInitialStock: true);
        $validator = $this->validateProduct($data, allowInitialStock: true);

        if ($validator->fails()) {
            $this->flashError($this->firstError($validator->errors()));
            $this->redirect('/products/create');
        }

        $dateError = $this->dateValidationError($data);
        if ($dateError !== null) {
            $this->flashError($dateError);
            $this->redirect('/products/create');
        }

        if (!$this->products->categoryBelongsToShop($this->nullableCategoryId($data['category_id'] ?? null), $this->currentShopId())) {
            $this->flashError('La categorie selectionnee est invalide pour cette boutique.');
            $this->redirect('/products/create');
        }

        $limitError = (new SubscriptionGate())->creationError($this->currentShopId(), 'products');
        if ($limitError !== null) {
            $this->flashError($limitError);
            $this->redirect('/products/create');
        }

        try {
            $productId = $this->products->create($data, $this->currentShopId(), $this->currentUserId());
            $stock = (int) ($data['quantite_stock'] ?? 0);

            if ($stock > 0) {
                $this->insertInitialStockMovement($productId, $stock);
            }

            $shop = $this->activeShop($this->shops(), $this->currentUser());
            $catalogProfile = $this->productCatalogProfile((string) ($shop['category_slug'] ?? ''));
            $this->flashSuccess($catalogProfile['success_message']);
            $this->redirect($this->catalogRouteForCategory((string) ($shop['category_slug'] ?? '')));
        } catch (Throwable $exception) {
            $this->flashError('Impossible d’enregistrer le produit: ' . $exception->getMessage());
            $this->redirect('/products/create');
        }
    }

    public function show(array $params = []): void
    {
        $this->render('products/show', [
            'pageTitle' => 'Détail produit',
            'activeMenu' => 'products',
            'product' => $this->findProductFromParams($params),
            'productCategories' => $this->productCategories(),
        ]);
    }

    public function edit(array $params = []): void
    {
        $this->render('products/edit', [
            'pageTitle' => 'Modifier le produit',
            'activeMenu' => 'products',
            'product' => $this->findProductFromParams($params),
            'productCategories' => $this->productCategories(),
        ]);
    }

    public function update(array $params = []): void
    {
        $id = $this->productIdFromParams($params);
        $data = $this->productPayload(allowInitialStock: false);
        $validator = $this->validateProduct($data, allowInitialStock: false);

        if ($validator->fails()) {
            $this->flashError($this->firstError($validator->errors()));
            $this->redirect('/products/' . $id . '/edit');
        }

        $dateError = $this->dateValidationError($data);
        if ($dateError !== null) {
            $this->flashError($dateError);
            $this->redirect('/products/' . $id . '/edit');
        }

        if (!$this->products->categoryBelongsToShop($this->nullableCategoryId($data['category_id'] ?? null), $this->currentShopId())) {
            $this->flashError('La categorie selectionnee est invalide pour cette boutique.');
            $this->redirect('/products/' . $id . '/edit');
        }

        if (!$this->products->updateByShop($id, $this->currentShopId(), $data, $this->currentUserId())) {
            $this->abort(404, 'Produit introuvable pour cette boutique.');
        }

        $this->flashSuccess('Produit mis à jour avec succès.');
        $this->redirect('/products');
    }

    public function destroy(array $params = []): void
    {
        if (!$this->products->deleteByShop($this->productIdFromParams($params), $this->currentShopId())) {
            $this->abort(404, 'Produit introuvable pour cette boutique.');
        }

        $this->flashSuccess('Produit désactivé avec succès.');
        $this->redirect('/products');
    }

    public function storeCategory(array $params = []): void
    {
        try {
            $payload = $this->isJsonRequest() ? $this->jsonPayload() : $_POST;
            $category = $this->products->createCategory($this->currentShopId(), (string) ($payload['nom'] ?? $payload['name'] ?? ''));

            $this->json([
                'ok' => true,
                'success' => true,
                'message' => 'Categorie ajoutee avec succes.',
                'category' => $category,
                'categories' => $this->productCategories(),
            ], 201);
        } catch (Throwable $exception) {
            $this->json(['ok' => false, 'success' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    private function validateProduct(array $data, bool $allowInitialStock): Validator
    {
        $validator = Validator::make($data)
            ->required('nom', 'Nom du produit')
            ->maxLength('nom', 190, 'Nom du produit')
            ->maxLength('code_barre', 80, 'Code-barres')
            ->maxLength('ref', 80, 'Référence')
            ->numeric('prix_achat', 'Prix d’achat')
            ->numeric('prix_vente', 'Prix de vente')
            ->numeric('prix_achat_montant', 'Montant du prix d achat')
            ->numeric('prix_vente_montant', 'Montant du prix de vente')
            ->positiveOrZero('prix_achat', 'Prix d’achat')
            ->positiveOrZero('prix_vente', 'Prix de vente')
            ->positiveOrZero('prix_achat_montant', 'Montant du prix d achat')
            ->positiveOrZero('prix_vente_montant', 'Montant du prix de vente')
            ->integerPositiveOrZero('alerte_stock_min', 'Alerte stock minimum');

        if ($allowInitialStock) {
            $validator->integerPositiveOrZero('quantite_stock', 'Stock initial');
        }

        return $validator;
    }

    private function productPayload(bool $allowInitialStock): array
    {
        $purchaseCurrency = $this->currencyFromInput($_POST['prix_achat_devise'] ?? null);
        $saleCurrency = $this->currencyFromInput($_POST['prix_vente_devise'] ?? null);
        $purchaseAmount = $_POST['prix_achat_montant'] ?? $_POST['prix_achat'] ?? 0;
        $saleAmount = $_POST['prix_vente_montant'] ?? $_POST['prix_vente'] ?? 0;
        $exchangeRate = $this->currentExchangeRate();

        $payload = [
            'code_barre' => $_POST['code_barre'] ?? null,
            'ref' => $_POST['ref'] ?? null,
            'category_id' => $_POST['category_id'] ?? null,
            'nom' => $_POST['nom'] ?? '',
            'description' => $_POST['description'] ?? null,
            'prix_achat' => $this->amountToUsd($purchaseAmount, $purchaseCurrency, $exchangeRate),
            'prix_vente' => $this->amountToUsd($saleAmount, $saleCurrency, $exchangeRate),
            'prix_achat_devise' => $purchaseCurrency,
            'prix_vente_devise' => $saleCurrency,
            'prix_achat_montant' => $purchaseAmount,
            'prix_vente_montant' => $saleAmount,
            'alerte_stock_min' => $_POST['alerte_stock_min'] ?? 0,
            'date_fabrication' => $_POST['date_fabrication'] ?? null,
            'date_expiration' => $_POST['date_expiration'] ?? null,
            'actif' => $_POST['actif'] ?? '1',
        ];

        if ($allowInitialStock) {
            $payload['quantite_stock'] = $_POST['quantite_stock'] ?? 0;
        }

        return $payload;
    }

    private function currencyFromInput(mixed $value): string
    {
        $currency = strtoupper(trim((string) ($value ?? 'USD')));

        return in_array($currency, ['USD', 'CDF'], true) ? $currency : 'USD';
    }

    private function amountToUsd(mixed $amount, string $currency, float $exchangeRate): float
    {
        $value = is_numeric($amount) ? (float) $amount : 0.0;

        if ($currency === 'CDF') {
            return round($value / max($exchangeRate, 0.0001), 2);
        }

        return round($value, 2);
    }

    private function currentExchangeRate(): float
    {
        $shops = $this->shops();
        $activeShop = $this->activeShop($shops, $this->currentUser());
        $rate = (float) ($activeShop['taux_change_cdf'] ?? 2800);

        return $rate > 0 ? $rate : 2800;
    }

    private function dateValidationError(array $data): ?string
    {
        $manufacturedValue = trim((string) ($data['date_fabrication'] ?? ''));
        $expirationValue = trim((string) ($data['date_expiration'] ?? ''));
        $manufacturedAt = $this->dateFromInput($data['date_fabrication'] ?? null);
        $expiresAt = $this->dateFromInput($data['date_expiration'] ?? null);

        if ($manufacturedValue !== '' && $manufacturedAt === null) {
            return 'La date de fabrication est invalide.';
        }

        if ($expirationValue !== '' && $expiresAt === null) {
            return 'La date d expiration est invalide.';
        }

        if ($manufacturedAt !== null && $expiresAt !== null && $manufacturedAt > $expiresAt) {
            return 'La date de fabrication ne peut pas etre apres la date d expiration.';
        }

        return null;
    }

    private function dateFromInput(mixed $value): ?DateTimeImmutable
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();

        if (!$date instanceof DateTimeImmutable || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        return $date;
    }

    private function productCategories(): array
    {
        $categories = $this->products->categoriesByShop($this->currentShopId());

        if ($categories === []) {
            $this->products->createCategory($this->currentShopId(), 'General');
            $categories = $this->products->categoriesByShop($this->currentShopId());
        }

        return $categories;
    }

    private function productCatalogProfile(string $slug): array
    {
        $default = [
            'page_title' => 'Produits', 'title' => 'Liste des produits', 'eyebrow' => 'Catalogue',
            'unit' => 'produit', 'plural' => 'produits', 'create_title' => 'Ajouter un produit',
            'create_button' => 'Ajouter un produit', 'active_label' => 'Produits actifs',
            'description' => 'Suivez les prix, les références et les seuils d’alerte stock par boutique.',
            'create_description' => 'Créez une fiche produit complète avec prix, stock initial et seuil d’alerte minimal.',
            'item_example' => 'Ex. Produit principal', 'category_example' => 'Ex. Général',
            'section_title' => 'Catalogue boutique', 'success_message' => 'Produit créé avec succès.',
        ];

        $profiles = [
            'pharmacies' => ['page_title'=>'Médicaments','title'=>'Catalogue des médicaments','eyebrow'=>'Officine · Catalogue thérapeutique','unit'=>'médicament','plural'=>'médicaments','create_title'=>'Ajouter un médicament','create_button'=>'Ajouter un médicament','active_label'=>'Médicaments actifs','description'=>'Gérez les références, prix, stocks et dates de péremption de votre officine.','create_description'=>'Référencez un médicament avec ses prix, son stock initial et ses dates de fabrication et de péremption.','item_example'=>'Ex. Paracétamol 500 mg','category_example'=>'Ex. Antibiotiques','section_title'=>'Référentiel de l’officine','success_message'=>'Médicament créé avec succès.'],
            'quincailleries' => ['page_title'=>'Articles de quincaillerie','title'=>'Catalogue de la quincaillerie','eyebrow'=>'Quincaillerie · Références techniques','unit'=>'article','plural'=>'articles','create_title'=>'Ajouter un article technique','create_button'=>'Ajouter un article','active_label'=>'Articles disponibles','description'=>'Gérez les outils, matériaux, références techniques et niveaux de stock.','create_description'=>'Ajoutez un article avec sa référence, ses prix et son stock initial.','item_example'=>'Ex. Marteau professionnel 500 g','category_example'=>'Ex. Outillage','section_title'=>'Rayons de la quincaillerie','success_message'=>'Article de quincaillerie créé avec succès.'],
            'supermarches' => ['page_title'=>'Rayons et produits','title'=>'Catalogue du supermarché','eyebrow'=>'Supermarché · Rayons','unit'=>'produit','plural'=>'produits','create_title'=>'Ajouter un produit en rayon','create_button'=>'Ajouter un produit','active_label'=>'Produits en rayon','description'=>'Pilotez les produits, rayons, prix, codes-barres et stocks du magasin.','create_description'=>'Référencez un produit avec son rayon, son prix et son stock initial.','item_example'=>'Ex. Lait entier 1 L','category_example'=>'Ex. Produits laitiers','section_title'=>'Catalogue des rayons','success_message'=>'Produit de supermarché créé avec succès.'],
            'depots' => ['page_title'=>'Stocks du dépôt','title'=>'Catalogue du dépôt','eyebrow'=>'Dépôt · Stock central','unit'=>'article','plural'=>'articles','create_title'=>'Ajouter un article au dépôt','create_button'=>'Ajouter un article','active_label'=>'Références en stock','description'=>'Contrôlez les références, volumes, seuils et disponibilités du dépôt.','create_description'=>'Enregistrez une référence avec son coût, son stock initial et son seuil de contrôle.','item_example'=>'Ex. Carton eau 24 bouteilles','category_example'=>'Ex. Boissons en gros','section_title'=>'Référentiel du dépôt','success_message'=>'Article de dépôt créé avec succès.'],
            'papeteries' => ['page_title'=>'Articles de papeterie','title'=>'Catalogue de la papeterie','eyebrow'=>'Papeterie · Fournitures','unit'=>'article','plural'=>'articles','create_title'=>'Ajouter une fourniture','create_button'=>'Ajouter une fourniture','active_label'=>'Fournitures disponibles','description'=>'Gérez les fournitures scolaires, bureautiques et leurs niveaux de stock.','create_description'=>'Ajoutez une fourniture avec sa référence, son prix et son stock initial.','item_example'=>'Ex. Cahier quadrillé 96 pages','category_example'=>'Ex. Cahiers et blocs','section_title'=>'Catalogue de la papeterie','success_message'=>'Fourniture créée avec succès.'],
            'librairies' => ['page_title'=>'Livres et ouvrages','title'=>'Catalogue de la librairie','eyebrow'=>'Librairie · Ouvrages','unit'=>'ouvrage','plural'=>'ouvrages','create_title'=>'Ajouter un ouvrage','create_button'=>'Ajouter un ouvrage','active_label'=>'Ouvrages disponibles','description'=>'Organisez les livres, références, catégories, prix et disponibilités.','create_description'=>'Référencez un livre ou ouvrage avec son prix et son stock initial.','item_example'=>'Ex. L’Alchimiste','category_example'=>'Ex. Romans','section_title'=>'Fonds de la librairie','success_message'=>'Ouvrage créé avec succès.'],
            'boulangeries' => ['page_title'=>'Produits de boulangerie','title'=>'Vitrine de la boulangerie','eyebrow'=>'Boulangerie · Production fraîche','unit'=>'produit frais','plural'=>'produits frais','create_title'=>'Ajouter un produit frais','create_button'=>'Ajouter un produit','active_label'=>'Produits du jour','description'=>'Suivez pains, pâtisseries, prix, production et disponibilités quotidiennes.','create_description'=>'Ajoutez un produit de boulangerie avec son prix et sa quantité initiale.','item_example'=>'Ex. Baguette tradition','category_example'=>'Ex. Pains','section_title'=>'Vitrine des produits','success_message'=>'Produit de boulangerie créé avec succès.'],
            'restaurants' => ['page_title'=>'Menu du restaurant','title'=>'Carte et menu','eyebrow'=>'Restaurant · Menu','unit'=>'plat','plural'=>'plats','create_title'=>'Ajouter un plat','create_button'=>'Ajouter un plat','active_label'=>'Plats disponibles','description'=>'Composez le menu, gérez les prix et la disponibilité des plats et boissons.','create_description'=>'Ajoutez un plat ou une boisson à la carte avec son prix de vente.','item_example'=>'Ex. Poulet grillé et frites','category_example'=>'Ex. Plats principaux','section_title'=>'Carte du restaurant','success_message'=>'Plat ajouté au menu avec succès.'],
            'bars' => ['page_title'=>'Boissons et consommations','title'=>'Carte du bar','eyebrow'=>'Bar · Boissons','unit'=>'boisson','plural'=>'boissons','create_title'=>'Ajouter une boisson','create_button'=>'Ajouter une boisson','active_label'=>'Boissons disponibles','description'=>'Gérez les boissons, consommations, prix et stocks de bouteilles.','create_description'=>'Ajoutez une boisson avec son prix et son stock initial.','item_example'=>'Ex. Eau minérale 50 cl','category_example'=>'Ex. Boissons sans alcool','section_title'=>'Carte des boissons','success_message'=>'Boisson créée avec succès.'],
            'hotels' => ['page_title'=>'Chambres et services','title'=>'Catalogue hôtelier','eyebrow'=>'Hôtel · Hébergement et services','unit'=>'service','plural'=>'services','create_title'=>'Ajouter une chambre ou un service','create_button'=>'Ajouter une offre','active_label'=>'Offres disponibles','description'=>'Organisez les chambres, prestations, tarifs et disponibilités de l’établissement.','create_description'=>'Ajoutez une chambre ou un service avec son tarif de vente.','item_example'=>'Ex. Chambre double standard','category_example'=>'Ex. Chambres','section_title'=>'Offres de l’hôtel','success_message'=>'Offre hôtelière créée avec succès.'],
            'magasins-de-vetements' => ['page_title'=>'Articles de mode','title'=>'Collection et articles de mode','eyebrow'=>'Fashion boutique · Collection','unit'=>'article de mode','plural'=>'articles','create_title'=>'Ajouter un article de mode','create_button'=>'Ajouter un article','active_label'=>'Articles disponibles','description'=>'Pilotez vos collections, articles, prix, références et niveaux de stock.','create_description'=>'Ajoutez une nouvelle pièce à la collection avec ses prix, sa référence et son stock initial.','item_example'=>'Ex. Chemise oversize en lin','category_example'=>'Ex. Robes et ensembles','section_title'=>'Catalogue de la boutique mode','success_message'=>'Article de mode créé avec succès.'],
            'magasins-d-electronique' => ['page_title'=>'Appareils électroniques','title'=>'Catalogue électronique','eyebrow'=>'Électronique · Appareils et accessoires','unit'=>'appareil','plural'=>'appareils','create_title'=>'Ajouter un appareil','create_button'=>'Ajouter un appareil','active_label'=>'Appareils disponibles','description'=>'Gérez appareils, accessoires, références techniques, prix et stocks à valeur élevée.','create_description'=>'Ajoutez un appareil ou accessoire avec sa référence, ses prix et son stock.','item_example'=>'Ex. Smartphone 128 Go','category_example'=>'Ex. Téléphones','section_title'=>'Catalogue électronique','success_message'=>'Appareil créé avec succès.'],
            'grossistes' => ['page_title'=>'Catalogue de gros','title'=>'Lots et produits de gros','eyebrow'=>'Grossiste · Volumes','unit'=>'lot','plural'=>'lots','create_title'=>'Ajouter un lot','create_button'=>'Ajouter un lot','active_label'=>'Lots disponibles','description'=>'Pilotez les lots, prix de volume, grands stocks et références professionnelles.','create_description'=>'Ajoutez un lot commercial avec son prix et sa quantité initiale.','item_example'=>'Ex. Lot de 12 cartons','category_example'=>'Ex. Produits alimentaires','section_title'=>'Catalogue de gros','success_message'=>'Lot créé avec succès.'],
            'distributeurs' => ['page_title'=>'Produits distribués','title'=>'Catalogue de distribution','eyebrow'=>'Distribution · Réseau','unit'=>'article distribué','plural'=>'articles distribués','create_title'=>'Ajouter un article distribué','create_button'=>'Ajouter un article','active_label'=>'Articles distribués','description'=>'Gérez les références distribuées, leurs prix et disponibilités pour le réseau.','create_description'=>'Ajoutez une référence à distribuer avec ses prix et son stock initial.','item_example'=>'Ex. Pack promotionnel 6 unités','category_example'=>'Ex. Produits distribués','section_title'=>'Référentiel de distribution','success_message'=>'Article distribué créé avec succès.'],
            'entreprises-commerciales' => ['page_title'=>'Offre commerciale','title'=>'Catalogue commercial','eyebrow'=>'Entreprise · Offre commerciale','unit'=>'article commercial','plural'=>'articles commerciaux','create_title'=>'Ajouter un article commercial','create_button'=>'Ajouter un article','active_label'=>'Articles actifs','description'=>'Centralisez les articles, tarifs, références et disponibilités de l’entreprise.','create_description'=>'Ajoutez un article commercial avec ses prix et son stock initial.','item_example'=>'Ex. Produit ou service principal','category_example'=>'Ex. Offre principale','section_title'=>'Offre de l’entreprise','success_message'=>'Article commercial créé avec succès.'],
            'vendeur-forfait-mobile-unites' => ['page_title'=>'Forfaits et unités','title'=>'Catalogue mobile money','eyebrow'=>'Télécom · Forfaits et recharges','unit'=>'forfait','plural'=>'forfaits','create_title'=>'Ajouter un forfait ou une recharge','create_button'=>'Ajouter une offre','active_label'=>'Offres disponibles','description'=>'Gérez les forfaits, unités, recharges, tarifs et soldes disponibles.','create_description'=>'Ajoutez un forfait ou une recharge avec son tarif et son solde initial.','item_example'=>'Ex. Forfait Internet 5 Go','category_example'=>'Ex. Forfaits Internet','section_title'=>'Offres et recharges','success_message'=>'Offre mobile créée avec succès.'],
        ];

        return array_replace($default, $profiles[$slug] ?? []);
    }

    private function catalogRouteForCategory(string $slug): string
    {
        return [
            'quincailleries'=>'/quincaillerie', 'supermarches'=>'/supermarche', 'depots'=>'/depot',
            'papeteries'=>'/papeterie', 'librairies'=>'/librairie', 'boulangeries'=>'/boulangerie',
            'restaurants'=>'/restaurant', 'bars'=>'/bar', 'hotels'=>'/hotel',
            'magasins-d-electronique'=>'/electronique', 'grossistes'=>'/grossiste',
            'distributeurs'=>'/distribution', 'entreprises-commerciales'=>'/entreprise-commerciale',
            'vendeur-forfait-mobile-unites'=>'/forfaits-unites',
        ][$slug] ?? '/products';
    }

    private function nullableCategoryId(mixed $value): ?int
    {
        $id = (int) ($value ?? 0);

        return $id > 0 ? $id : null;
    }

    private function jsonPayload(): array
    {
        $raw = file_get_contents('php://input');

        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $payload = json_decode($raw, true);

        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('JSON invalide.');
        }

        return $payload;
    }

    private function isJsonRequest(): bool
    {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));

        return str_contains($contentType, 'application/json') || str_contains($accept, 'application/json');
    }

    private function findProductFromParams(array $params): array
    {
        $product = $this->products->findByShop($this->productIdFromParams($params), $this->currentShopId());

        if ($product === null) {
            $this->abort(404, 'Produit introuvable pour cette boutique.');
        }

        return $product;
    }

    private function productIdFromParams(array $params): int
    {
        $id = (int) ($params['id'] ?? $_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->abort(404, 'Produit introuvable.');
        }

        return $id;
    }

    private function insertInitialStockMovement(int $productId, int $stock): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO stock_movements (shop_id, product_id, user_id, type_mouvement, quantite, stock_avant, stock_apres, motif)
             VALUES (:shop_id, :product_id, :user_id, :type_mouvement, :quantite, 0, :stock_apres, :motif)'
        );
        $statement->execute([
            'shop_id' => $this->currentShopId(),
            'product_id' => $productId,
            'user_id' => $this->currentUserId(),
            'type_mouvement' => 'entree',
            'quantite' => $stock,
            'stock_apres' => $stock,
            'motif' => 'Stock initial à la création du produit',
        ]);
    }

    private function firstError(array $errors): string
    {
        foreach ($errors as $messages) {
            return (string) ($messages[0] ?? 'Données invalides.');
        }

        return 'Données invalides.';
    }
}
