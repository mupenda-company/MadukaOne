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
        require dirname(__DIR__) . '/Views/shops/create.php';
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

    public function updateSettings(array $params = []): void
    {
        $shopId = $this->currentShopId();
        $data = [
            'nom' => $_POST['nom'] ?? '',
            'adresse' => $_POST['adresse'] ?? null,
            'telephone' => $_POST['telephone'] ?? null,
            'email' => $_POST['email'] ?? null,
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

        return null;
    }
}
