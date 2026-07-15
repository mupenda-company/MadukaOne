<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';
require_once dirname(__DIR__) . '/Models/ShopSubscription.php';

final class ShopSubscriptionController extends AppController
{
    private ShopSubscription $subscriptions;

    public function __construct()
    {
        $this->subscriptions = new ShopSubscription();
    }

    public function show(array $params = []): void
    {
        $shopId = $this->currentShopId();

        $this->render('shops/subscription', [
            'pageTitle' => 'Abonnement boutique',
            'activeMenu' => 'shop_subscription',
            'subscription' => $this->subscriptions->currentForShop($shopId),
            'payments' => $this->subscriptions->paymentsForShop($shopId),
        ]);
    }

    public function renew(array $params = []): void
    {
        try {
            $this->subscriptions->requestRenewal($this->currentShopId());
            $this->flashSuccess('Demande de renouvellement enregistree. Votre paiement apparait maintenant dans l historique.');
        } catch (Throwable $exception) {
            $this->flashError('Renouvellement impossible: ' . $exception->getMessage());
        }

        $this->redirect('/shops/subscription');
    }

    public function autoRenew(array $params = []): void
    {
        try {
            if (!$this->subscriptions->updateAutoRenew($this->currentShopId(), isset($_POST['renouvellement_auto']))) {
                $this->flashError('Aucun abonnement n est configure pour cette boutique.');
                $this->redirect('/shops/subscription');
            }

            $this->flashSuccess('Preference de renouvellement mise a jour.');
        } catch (Throwable $exception) {
            $this->flashError('Mise a jour impossible: ' . $exception->getMessage());
        }

        $this->redirect('/shops/subscription');
    }
}
