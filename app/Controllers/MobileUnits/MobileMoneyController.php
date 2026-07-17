<?php

declare(strict_types=1);

require_once dirname(__DIR__,2).'/Core/AppController.php';
require_once dirname(__DIR__,2).'/Models/MobileUnits/MobileUnitsRepository.php';
require_once dirname(__DIR__,2).'/Models/MobileUnits/MobileMoneyRepository.php';

final class MobileMoneyController extends AppController
{
    private MobileMoneyRepository $repo;
    private MobileUnitsRepository $units;
    public function __construct(){ $this->repo=new MobileMoneyRepository();$this->units=new MobileUnitsRepository(); }
    public function accounts(array $p=[]):void{$this->page('accounts');}
    public function transactions(array $p=[]):void{$this->page('transactions');}
    public function commissions(array $p=[]):void{$this->page('commissions');}
    public function cash(array $p=[]):void{$this->page('cash');}
    public function accounting(array $p=[]):void{$this->page('accounting');}
    public function storeAccount(array $p=[]):never{$this->action(fn()=> $this->repo->saveAccount($this->currentShopId(),$_POST),'Compte Mobile Money créé.','/forfaits-unites/mobile-money/comptes');}
    public function updateAccount(array $p=[]):never{$this->action(fn()=> $this->repo->updateAccount($this->currentShopId(),(int)($p['id']??0),$_POST),'Compte Mobile Money actualisé.','/forfaits-unites/mobile-money/comptes');}
    public function storeCommission(array $p=[]):never{$this->action(fn()=> $this->repo->saveCommission($this->currentShopId(),$_POST),'Taux de commission enregistré.','/forfaits-unites/mobile-money/commissions');}
    public function storeTransaction(array $p=[]):never{$this->action(fn()=> $this->repo->addTransaction($this->currentShopId(),$this->currentUserId(),$_POST),'Transaction enregistrée et soldes actualisés.','/forfaits-unites/mobile-money/transactions');}
    public function openCash(array $p=[]):never{$this->action(fn()=> $this->repo->openCash($this->currentShopId(),$this->currentUserId(),(float)($_POST['solde_ouverture']??0)),'Caisse ouverte.','/forfaits-unites/mobile-money/caisse');}
    public function closeCash(array $p=[]):never{$this->action(fn()=> $this->repo->closeCash($this->currentShopId(),(float)($_POST['solde_reel']??0)),'Caisse fermée et journal enregistrée.','/forfaits-unites/mobile-money/caisse');}
    private function page(string $section):void
    {
        $this->guard();$shop=$this->currentShopId();$filters=['date_from'=>trim((string)($_GET['date_from']??'')),'date_to'=>trim((string)($_GET['date_to']??'')),'operator_id'=>(int)($_GET['operator_id']??0),'type'=>(string)($_GET['type']??'')];
        $titles=['accounts'=>'Comptes Mobile Money','transactions'=>'Transactions Mobile Money','commissions'=>'Commissions Mobile Money','cash'=>'Caisse Mobile Money','accounting'=>'Comptabilité Mobile Money'];$menus=['accounts'=>'money-accounts','transactions'=>'money-transactions','commissions'=>'money-commissions','cash'=>'money-cash','accounting'=>'money-accounting'];
        $this->render('mobile-money/index',['pageTitle'=>$titles[$section],'activeMenu'=>$menus[$section],'moneySection'=>$section,'operators'=>$this->units->operators($shop),'moneyAccounts'=>$this->repo->accounts($shop),'moneyCommissions'=>$this->repo->commissions($shop),'moneyTransactions'=>$this->repo->transactions($shop,$filters),'moneyFilters'=>$filters,'cashSummary'=>$this->repo->cashSummary($shop),'cashSessions'=>$this->repo->cashSessions($shop),'accounting'=>$this->repo->accounting($shop),'transactionTypes'=>MobileMoneyRepository::TYPES]);
    }
    private function guard():void{$shop=$this->activeShop($this->shops(),$this->currentUser());if(($shop['category_slug']??'')!=='vendeur-forfait-mobile-unites'){$this->flashError('Espace réservé à la catégorie forfaits et unités.');$this->redirect('/dashboard');}}
    private function action(callable $fn,string $message,string $path):never{$this->guard();try{$fn();$this->flashSuccess($message);}catch(Throwable $e){$this->flashError($e->getMessage());}$this->redirect($path);}
}
