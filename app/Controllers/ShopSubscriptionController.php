<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';
require_once dirname(__DIR__) . '/Models/ShopSubscription.php';
require_once dirname(__DIR__) . '/Core/ModuleCatalog.php';

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
        $subscription = $this->subscriptions->currentForShop($shopId);
        $plans = $this->subscriptions->availablePlansForShop($shopId);

        $this->render('shops/subscription', [
            'pageTitle' => 'Abonnement boutique',
            'activeMenu' => 'shop_subscription',
            'subscription' => $subscription,
            'payments' => $this->subscriptions->paymentsForShop($shopId),
            'planFeatures' => $this->subscriptions->featuresForPlan((int) ($subscription['plan_id'] ?? 0)),
            'availablePlans' => $plans,
            'moduleCatalog' => ModuleCatalog::all(),
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

    public function changePlan(array $params = []): void
    {
        try {
            $this->subscriptions->changePlan($this->currentShopId(), (int) ($_POST['plan_id'] ?? 0));
            $this->flashSuccess('Votre plan d abonnement a ete change. Les modules du nouveau plan sont maintenant appliques.');
        } catch (Throwable $exception) {
            $this->flashError('Changement de plan impossible: ' . $exception->getMessage());
        }

        $this->redirect('/shops/subscription');
    }
}
