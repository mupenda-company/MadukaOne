<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Core/AppController.php';
require_once dirname(__DIR__, 2) . '/Models/MobileUnits/MobileUnitsRepository.php';

final class MobileUnitsController extends AppController
{
    private MobileUnitsRepository $repo;
    public function __construct(){ $this->repo=new MobileUnitsRepository(); }
    public function index(array $p=[]): void{$this->page('dashboard');}
    public function operators(array $p=[]): void{$this->page('operators');}
    public function products(array $p=[]): void{$this->page('products');}
    public function stock(array $p=[]): void{$this->page('stock');}
    public function supplies(array $p=[]): void{$this->page('supplies');}
    public function sales(array $p=[]): void
    {
        $format=strtolower((string)($_GET['format']??''));if(in_array($format,['excel','pdf'],true)){$this->exportSales($format);}$this->page('sales');
    }
    public function history(array $p=[]): void
    { $format=strtolower((string)($_GET['format']??''));if(in_array($format,['excel','pdf'],true)){$this->exportSales($format);}$this->page('history'); }
    public function receipt(array $p=[]): void
    {
        $this->guard();$sale=$this->repo->sale($this->currentShopId(),(int)($p['id']??0));if(!$sale){$this->abort(404,'Vente mobile introuvable.');}$this->render('mobile-units/receipt',['pageTitle'=>'Reçu vente mobile','activeMenu'=>'mobile-sales','sale'=>$sale]);
    }
    public function storeOperator(array $p=[]): void{$this->action(fn()=> $this->repo->saveOperator($this->currentShopId(),$_POST),'Opérateur enregistré.','/forfaits-unites/operateurs');}
    public function updateOperator(array $p=[]): void{$id=(int)($p['id']??0);$this->action(fn()=> $this->repo->saveOperator($this->currentShopId(),$_POST,$id),'Opérateur mis à jour.','/forfaits-unites/operateurs');}
    public function toggleOperator(array $p=[]): void{$id=(int)($p['id']??0);$this->action(fn()=> $this->repo->toggleOperator($this->currentShopId(),$id),'Statut de l’opérateur modifié.','/forfaits-unites/operateurs');}
    public function storeProduct(array $p=[]): void{$this->action(fn()=> $this->repo->saveProduct($this->currentShopId(),$_POST),'Produit mobile enregistré.','/forfaits-unites/produits');}
    public function updateProduct(array $p=[]): void{$id=(int)($p['id']??0);$this->action(fn()=> $this->repo->saveProduct($this->currentShopId(),$_POST,$id),'Produit mobile mis à jour.','/forfaits-unites/produits');}
    public function toggleProduct(array $p=[]): void{$id=(int)($p['id']??0);$this->action(fn()=> $this->repo->toggleProduct($this->currentShopId(),$id),'Statut du produit modifié.','/forfaits-unites/produits');}
    public function deleteProduct(array $p=[]): void{$id=(int)($p['id']??0);$this->action(fn()=> $this->repo->deleteProduct($this->currentShopId(),$id),'Produit supprimé.','/forfaits-unites/produits');}
    public function storeSupply(array $p=[]): void{$this->action(fn()=> $this->repo->addSupply($this->currentShopId(),$_POST),'Approvisionnement enregistré.','/forfaits-unites/approvisionnements');}
    public function storeSale(array $p=[]): never
    {
        $this->guard();try{$id=$this->repo->addSale($this->currentShopId(),$this->currentUserId(),$_POST);$this->flashSuccess('Vente enregistrée, bénéfice calculé et stock actualisé.');$this->redirect('/forfaits-unites/ventes/'.$id.'/recu');}catch(Throwable $e){$this->flashError($e->getMessage());$this->redirect('/forfaits-unites/ventes');}
    }

    private function page(string $section): void
    {
        $this->guard();$shop=$this->currentShopId();$operators=$this->repo->operators($shop);$products=$this->repo->products($shop);$supplies=$this->repo->supplies($shop);$filters=$this->salesFilters();$sales=$this->repo->sales($shop,$filters);
        $menuKeys=['dashboard'=>'mobile-dashboard','operators'=>'mobile-operators','products'=>'mobile-products','stock'=>'mobile-stock','supplies'=>'mobile-supplies','sales'=>'mobile-sales','history'=>'mobile-sales-history'];
        $titles=['dashboard'=>'Vue générale mobile','operators'=>'Gestion des opérateurs','products'=>'Produits et forfaits','stock'=>'Stock virtuel','supplies'=>'Approvisionnements mobiles','sales'=>'Ventes d’unités','history'=>'Historique des ventes mobiles'];
        $this->render('mobile-units/index',['pageTitle'=>$titles[$section]??'Forfaits et unités','activeMenu'=>$menuKeys[$section]??'mobile-dashboard','mobileSection'=>$section,'operators'=>$operators,'mobileProducts'=>$products,'mobileSupplies'=>$supplies,'mobileSales'=>$sales,'mobileEmployees'=>$this->repo->employees($shop),'mobileCustomers'=>$this->repo->customers($shop),'salesFilters'=>$filters]);
    }
    private function guard(): void
    { $shop=$this->activeShop($this->shops(),$this->currentUser());if((string)($shop['category_slug']??'')!=='vendeur-forfait-mobile-unites'){$this->flashError('Cet espace est réservé aux vendeurs de forfaits et unités.');$this->redirect('/products');} }
    private function action(callable $callback,string $success,string $path): never
    { $this->guard();try{$callback();$this->flashSuccess($success);}catch(Throwable $e){$this->flashError($e->getMessage());}$this->redirect($path); }

    private function salesFilters(): array
    { return ['date_from'=>trim((string)($_GET['date_from']??'')),'date_to'=>trim((string)($_GET['date_to']??'')),'operator_id'=>(int)($_GET['operator_id']??0),'user_id'=>(int)($_GET['user_id']??0),'client'=>trim((string)($_GET['client']??''))]; }

    private function exportSales(string $format): never
    {
        $this->guard();$rows=$this->repo->sales($this->currentShopId(),$this->salesFilters());
        if($format==='excel'){header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="ventes-mobiles-'.date('Ymd-His').'.csv"');echo "\xEF\xBB\xBF";$out=fopen('php://output','wb');fputcsv($out,['Date','Boutique','Opérateur','Produit','Employé','Client','Numéro bénéficiaire','Quantité','Unité','Montant','Commission','Bénéfice','Référence'],';');foreach($rows as $r){fputcsv($out,[$r['date_operation'],$r['shop_name'],$r['operator_name'],$r['product_name'],$r['employee_name'],$r['client_name'],$r['numero_client'],$r['quantite'],$r['unite'],$r['montant'],$r['commission'],$r['benefice'],$r['reference']],';');}fclose($out);exit;}
        header('Content-Type: text/html; charset=UTF-8');$safe=static fn($v):string=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Historique ventes mobiles</title><style>body{font:13px Arial;padding:28px;color:#0f172a}h1{margin:0 0 6px}table{width:100%;border-collapse:collapse;margin-top:22px}th,td{border:1px solid #cbd5e1;padding:8px;text-align:left}th{background:#0f172a;color:white}.summary{color:#64748b}@media print{button{display:none}}</style></head><body><button onclick="print()">Imprimer / Enregistrer en PDF</button><h1>Historique des ventes mobiles</h1><p class="summary">Export du '.date('d/m/Y H:i').' · '.count($rows).' vente(s)</p><table><thead><tr><th>Date</th><th>Boutique</th><th>Opérateur</th><th>Produit</th><th>Employé</th><th>Client / bénéficiaire</th><th>Montant</th><th>Bénéfice</th><th>Référence</th></tr></thead><tbody>';foreach($rows as $r){echo '<tr><td>'.$safe($r['date_operation']).'</td><td>'.$safe($r['shop_name']).'</td><td>'.$safe($r['operator_name']).'</td><td>'.$safe($r['product_name']).'</td><td>'.$safe($r['employee_name']).'</td><td>'.$safe(($r['client_name']?:'—').' · '.($r['numero_client']?:'—')).'</td><td>'.$safe($r['montant']).'</td><td>'.$safe($r['benefice']).'</td><td>'.$safe($r['reference']).'</td></tr>';}echo '</tbody></table><script>window.addEventListener("load",()=>window.print())</script></body></html>';exit;
    }
}
