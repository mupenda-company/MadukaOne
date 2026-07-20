<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';
require_once dirname(__DIR__) . '/Models/Shop.php';

class ShopController extends AppController
{
    private Shop $shops;

    public function __construct()
    {
        $this->shops = new Shop();
    }

    public function create(array $params = []): void
    {
        $this->guardShopManagement();
        $shopId = $this->currentShopId();
        $allowance = (new SubscriptionGate())->shopAllowanceForUser($this->currentUserId(), $shopId);

        if (!$allowance['can_create']) {
            $this->flashError((new SubscriptionGate())->shopCreationError($this->currentUserId(), $shopId) ?? 'Creation impossible.');
            $this->redirect('/shops/subscription');
        }

        $this->render('shops/create', [
            'pageTitle' => 'Nouvelle boutique',
            'activeMenu' => '',
            'categories' => $this->shops->activeCategories(),
            'shopAllowance' => $allowance,
        ]);
    }

    public function store(array $params = []): void
    {
        $this->guardShopManagement();
        $shopId = $this->currentShopId();
        $userId = $this->currentUserId();
        $gate = new SubscriptionGate();
        $error = $gate->shopCreationError($userId, $shopId);

        if ($error !== null) {
            $this->flashError($error);
            $this->redirect('/shops/subscription');
        }

        $name = trim((string) ($_POST['nom'] ?? ''));
        $rate = (float) ($_POST['taux_change_cdf'] ?? 0);

        if ($name === '' || $rate <= 0) {
            $this->flashError('Le nom et un taux de change valide sont obligatoires.');
            $this->redirect('/shops/create');
        }

        try {
            $newShopId = $this->shops->createForOwner($_POST, $userId, $shopId);
            $_SESSION['current_shop_id'] = $newShopId;
            $this->flashSuccess('Nouvelle boutique creee avec le plan actif.');
            $this->redirect('/dashboard?shop_id=' . $newShopId);
        } catch (Throwable $exception) {
            $this->flashError('Creation impossible: ' . $exception->getMessage());
            $this->redirect('/shops/create');
        }
    }

    public function settings(array $params = []): void
    {
        $shop = $this->shops->find($this->currentShopId());

        if ($shop === null) {
            $this->abort(404, 'Boutique introuvable.');
        }

        $this->render('shops/settings', [
            'pageTitle' => 'Parametres de la boutique',
            'activeMenu' => 'shop_settings',
            'shop' => $shop,
        ]);
    }

    public function activity(array $params = []): void
    {
        $shop = $this->shops->find($this->currentShopId());

        if ($shop === null) {
            $this->abort(404, 'Boutique introuvable.');
        }

        $this->render('shops/activity', [
            'pageTitle' => 'Administration par activite',
            'activeMenu' => 'shop_activity',
            'shop' => $shop,
            'shopCategoryProfile' => $this->shopCategoryProfile($shop),
        ]);
    }

    public function updateSettings(array $params = []): void
    {
        $shopId = $this->currentShopId();
        $data = [
            'nom' => $_POST['nom'] ?? '',
            'adresse' => $_POST['adresse'] ?? null,
            'telephone' => $_POST['telephone'] ?? null,
            'email' => $_POST['email'] ?? null,
            'logo_url' => $_POST['logo_url'] ?? null,
            'devise_principale' => $_POST['devise_principale'] ?? 'USD',
            'taux_change_cdf' => $_POST['taux_change_cdf'] ?? 0,
        ];

        $error = $this->settingsValidationError($data);

        if ($error !== null) {
            $this->flashError($error);
            $this->redirect('/shops/settings');
        }

        if (!$this->shops->updateSettings($shopId, $data)) {
            $shop = $this->shops->find($shopId);

            if ($shop === null) {
                $this->abort(404, 'Boutique introuvable.');
            }
        }

        $this->flashSuccess('Parametres de la boutique mis a jour.');
        $this->redirect('/shops/settings');
    }

    private function settingsValidationError(array $data): ?string
    {
        if (trim((string) ($data['nom'] ?? '')) === '') {
            return 'Le nom de la boutique est obligatoire.';
        }

        if (!in_array(($data['devise_principale'] ?? ''), ['USD', 'CDF'], true)) {
            return 'La devise principale doit etre USD ou CDF.';
        }

        if (!is_numeric($data['taux_change_cdf'] ?? null) || (float) $data['taux_change_cdf'] <= 0) {
            return 'Le taux de change CDF doit etre superieur a zero.';
        }

        $email = trim((string) ($data['email'] ?? ''));

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return 'L adresse email de la boutique est invalide.';
        }

        $logoUrl = trim((string) ($data['logo_url'] ?? ''));

        if ($logoUrl !== '') {
            $scheme = strtolower((string) parse_url($logoUrl, PHP_URL_SCHEME));

            if (filter_var($logoUrl, FILTER_VALIDATE_URL) === false || !in_array($scheme, ['http', 'https'], true)) {
                return 'L URL du logo doit etre une adresse HTTP ou HTTPS valide.';
            }
        }

        return null;
    }

    private function guardShopManagement(): void
    {
        if ($this->shopContext($this->currentUser())->canManageShops()) {
            return;
        }

        $this->flashError('Accès refusé : seuls le gérant ou l’administrateur peuvent gérer les boutiques.');
        $this->redirect('/dashboard');
    }
}
