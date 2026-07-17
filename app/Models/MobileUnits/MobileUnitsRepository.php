<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Core/Database.php';

final class MobileUnitsRepository
{
    public function operators(int $shopId): array
    {
        $s=Database::connection()->prepare('SELECT * FROM mobile_operators WHERE shop_id=? ORDER BY actif DESC, nom');$s->execute([$shopId]);return $s->fetchAll();
    }

    public function products(int $shopId): array
    {
        $s=Database::connection()->prepare(
            'SELECT p.*, o.nom operator_name, o.couleur operator_color,
             p.stock_initial + COALESCE((SELECT SUM(quantite) FROM mobile_supplies WHERE product_id=p.id),0) - COALESCE((SELECT SUM(quantite) FROM mobile_sales WHERE product_id=p.id),0) stock_actuel,
             COALESCE((SELECT SUM(quantite) FROM mobile_supplies WHERE product_id=p.id),0) total_approvisionne,
             COALESCE((SELECT SUM(quantite) FROM mobile_sales WHERE product_id=p.id),0) total_vendu
             FROM mobile_products p JOIN mobile_operators o ON o.id=p.operator_id WHERE p.shop_id=? ORDER BY o.nom,p.categorie,p.nom'
        );$s->execute([$shopId]);return $s->fetchAll();
    }

    public function supplies(int $shopId): array
    {
        $s=Database::connection()->prepare('SELECT a.*,o.nom operator_name,p.nom product_name,p.unite FROM mobile_supplies a JOIN mobile_operators o ON o.id=a.operator_id JOIN mobile_products p ON p.id=a.product_id WHERE a.shop_id=? ORDER BY a.date_operation DESC,a.id DESC');$s->execute([$shopId]);return $s->fetchAll();
    }

    public function sales(int $shopId, array $filters = []): array
    {
        $sql='SELECT v.*,o.nom operator_name,o.couleur operator_color,p.nom product_name,p.unite,p.prix_achat,p.prix_vente,COALESCE(u.nom,u.email,"Système") employee_name,s.nom shop_name FROM mobile_sales v JOIN mobile_operators o ON o.id=v.operator_id JOIN mobile_products p ON p.id=v.product_id JOIN shops s ON s.id=v.shop_id LEFT JOIN users u ON u.id=v.user_id WHERE v.shop_id=?';$args=[$shopId];
        if(($filters['date_from']??'')!==''){$sql.=' AND DATE(v.date_operation)>=?';$args[]=$filters['date_from'];}if(($filters['date_to']??'')!==''){$sql.=' AND DATE(v.date_operation)<=?';$args[]=$filters['date_to'];}if((int)($filters['operator_id']??0)>0){$sql.=' AND v.operator_id=?';$args[]=(int)$filters['operator_id'];}if((int)($filters['user_id']??0)>0){$sql.=' AND v.user_id=?';$args[]=(int)$filters['user_id'];}if(trim((string)($filters['client']??''))!==''){$sql.=' AND (v.client_name LIKE ? OR v.numero_client LIKE ?)';$term='%'.trim((string)$filters['client']).'%';$args[]=$term;$args[]=$term;}$sql.=' ORDER BY v.date_operation DESC,v.id DESC';
        $s=Database::connection()->prepare($sql);$s->execute($args);return $s->fetchAll();
    }

    public function employees(int $shopId): array
    { $s=Database::connection()->prepare('SELECT id,nom,email FROM users WHERE shop_id=? AND actif=1 ORDER BY nom,email');$s->execute([$shopId]);return $s->fetchAll(); }

    public function customers(int $shopId): array
    { $s=Database::connection()->prepare('SELECT id,nom,telephone,email FROM customers WHERE shop_id=? ORDER BY nom');$s->execute([$shopId]);return $s->fetchAll(); }

    public function sale(int $shopId,int $id): ?array
    { $rows=$this->sales($shopId);foreach($rows as $row){if((int)$row['id']===$id)return$row;}return null; }

    public function saveOperator(int $shopId,array $d,?int $id=null): void
    {
        $nom=trim((string)($d['nom']??''));if($nom==='')throw new InvalidArgumentException('Le nom de l’opérateur est obligatoire.');
        $values=[$nom,$this->nullable($d['logo_url']??null),$this->nullable($d['code_ussd']??null),$this->nullable($d['numero_principal']??null),$this->color($d['couleur']??''),isset($d['actif'])?1:0];
        if($id){$s=Database::connection()->prepare('UPDATE mobile_operators SET nom=?,logo_url=?,code_ussd=?,numero_principal=?,couleur=?,actif=? WHERE id=? AND shop_id=?');$s->execute([...$values,$id,$shopId]);return;}
        $s=Database::connection()->prepare('INSERT INTO mobile_operators(nom,logo_url,code_ussd,numero_principal,couleur,actif,shop_id) VALUES(?,?,?,?,?,?,?)');$s->execute([...$values,$shopId]);
    }

    public function toggleOperator(int $shopId,int $id): void
    { $s=Database::connection()->prepare('UPDATE mobile_operators SET actif=IF(actif=1,0,1) WHERE id=? AND shop_id=?');$s->execute([$id,$shopId]); }

    public function saveProduct(int $shopId,array $d,?int $id=null): void
    {
        $operator=$this->operatorId($shopId,(int)($d['operator_id']??0));$nom=trim((string)($d['nom']??''));$cat=trim((string)($d['categorie']??''));
        if($nom===''||$cat==='')throw new InvalidArgumentException('Le nom et la catégorie sont obligatoires.');
        $unit=in_array(($d['unite']??''),['CDF','GO','MINUTES','SMS','UNITES'],true)?$d['unite']:'CDF';
        $currency=in_array(($d['devise_prix']??'CDF'),['CDF','USD'],true)?(string)$d['devise_prix']:'CDF';$rateStatement=Database::connection()->prepare('SELECT taux_change_cdf FROM shops WHERE id=?');$rateStatement->execute([$shopId]);$rate=max((float)($rateStatement->fetchColumn()?:2800),0.0001);$purchaseEntered=max(0,(float)($d['prix_achat']??0));$saleEntered=max(0,(float)($d['prix_vente']??0));$purchase=$currency==='USD'?round($purchaseEntered*$rate,2):$purchaseEntered;$sale=$currency==='USD'?round($saleEntered*$rate,2):$saleEntered;
        $values=[$operator,$nom,$cat,$unit,$currency,$purchaseEntered,$purchase,$saleEntered,$sale,$rate,max(0,(float)($d['commission']??0)),max(0,(float)($d['stock_initial']??0)),$this->nullable($d['description']??null),isset($d['actif'])?1:0];
        if($id){$s=Database::connection()->prepare('UPDATE mobile_products SET operator_id=?,nom=?,categorie=?,unite=?,devise_prix=?,prix_achat_saisi=?,prix_achat=?,prix_vente_saisi=?,prix_vente=?,taux_change_prix=?,commission=?,stock_initial=?,description=?,actif=? WHERE id=? AND shop_id=?');$s->execute([...$values,$id,$shopId]);return;}
        $s=Database::connection()->prepare('INSERT INTO mobile_products(operator_id,nom,categorie,unite,devise_prix,prix_achat_saisi,prix_achat,prix_vente_saisi,prix_vente,taux_change_prix,commission,stock_initial,description,actif,shop_id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');$s->execute([...$values,$shopId]);
    }

    public function toggleProduct(int $shopId,int $id): void
    { $s=Database::connection()->prepare('UPDATE mobile_products SET actif=IF(actif=1,0,1) WHERE id=? AND shop_id=?');$s->execute([$id,$shopId]);if($s->rowCount()===0)throw new RuntimeException('Produit mobile introuvable.'); }

    public function deleteProduct(int $shopId,int $id): void
    { $s=Database::connection()->prepare('SELECT (SELECT COUNT(*) FROM mobile_sales WHERE product_id=p.id)+(SELECT COUNT(*) FROM mobile_supplies WHERE product_id=p.id) operations FROM mobile_products p WHERE p.id=? AND p.shop_id=?');$s->execute([$id,$shopId]);$operations=$s->fetchColumn();if($operations===false)throw new RuntimeException('Produit mobile introuvable.');if((int)$operations>0)throw new RuntimeException('Suppression impossible : ce produit possède déjà des opérations. Désactivez-le plutôt.');$d=Database::connection()->prepare('DELETE FROM mobile_products WHERE id=? AND shop_id=?');$d->execute([$id,$shopId]); }

    public function addSupply(int $shopId,array $d): void
    {
        [$operator,$product]=$this->linkedProduct($shopId,$d);$qty=(float)($d['quantite']??0);if($qty<=0)throw new InvalidArgumentException('La quantité doit être supérieure à zéro.');
        $supplier=trim((string)($d['fournisseur']??''));$ref=trim((string)($d['reference']??''));if($supplier===''||$ref==='')throw new InvalidArgumentException('Le fournisseur et la référence sont obligatoires.');
        $currency=in_array(($d['devise']??'CDF'),['CDF','USD'],true)?(string)$d['devise']:'CDF';$rateStatement=Database::connection()->prepare('SELECT taux_change_cdf FROM shops WHERE id=?');$rateStatement->execute([$shopId]);$rate=max((float)($rateStatement->fetchColumn()?:2800),0.0001);$enteredAmount=max(0,(float)($d['montant']??0));$enteredCommission=max(0,(float)($d['commission']??0));$amount=$currency==='USD'?round($enteredAmount*$rate,2):$enteredAmount;$commission=$currency==='USD'?round($enteredCommission*$rate,2):$enteredCommission;
        $s=Database::connection()->prepare('INSERT INTO mobile_supplies(shop_id,operator_id,product_id,fournisseur,montant,montant_saisi,quantite,commission,commission_saisie,devise_saisie,taux_change_saisie,date_operation,reference) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)');$s->execute([$shopId,$operator,$product,$supplier,$amount,$enteredAmount,$qty,$commission,$enteredCommission,$currency,$rate,(string)($d['date_operation']??date('Y-m-d')),$ref]);
    }

    public function addSale(int $shopId,int $userId,array $d): int
    {
        [$operator,$product]=$this->linkedProduct($shopId,$d);$enteredAmount=max(0,(float)($d['montant']??0));$currency=in_array(($d['devise']??'CDF'),['CDF','USD'],true)?(string)$d['devise']:'CDF';$rateStatement=Database::connection()->prepare('SELECT taux_change_cdf FROM shops WHERE id=?');$rateStatement->execute([$shopId]);$rate=max((float)($rateStatement->fetchColumn()?:2800),0.0001);$amount=$currency==='USD'?round($enteredAmount*$rate,2):$enteredAmount;$qty=isset($d['quick_sale'])?$amount:(float)($d['quantite']??0);if($qty<=0)throw new InvalidArgumentException('La quantité doit être supérieure à zéro.');
        $stock=$this->stock($product);if($qty>$stock)throw new RuntimeException('Stock virtuel insuffisant. Disponible : '.number_format($stock,2,',',' '));
        $ref=trim((string)($d['reference']??''));if($ref==='')throw new InvalidArgumentException('La référence est obligatoire.');
        $s=Database::connection()->prepare('SELECT prix_achat,prix_vente,commission FROM mobile_products WHERE id=? AND shop_id=?');$s->execute([$product,$shopId]);$pricing=$s->fetch()?:[];$commission=array_key_exists('commission',$d)?max(0,(float)$d['commission']):max(0,(float)($pricing['commission']??0));$purchase=(float)($pricing['prix_achat']??0);$selling=(float)($pricing['prix_vente']??0);$cost=$selling>0?$amount*($purchase/$selling):$qty*$purchase;$profit=max(0,$amount-$cost+$commission);
        $customerId=(int)($d['customer_id']??0);if($customerId>0){$c=Database::connection()->prepare('SELECT id,nom FROM customers WHERE id=? AND shop_id=?');$c->execute([$customerId,$shopId]);$customer=$c->fetch();if(!$customer)throw new InvalidArgumentException('Client invalide pour cette boutique.');$clientName=(string)$customer['nom'];}else{$customerId=null;$clientName=$this->nullable($d['client_name']??null);}
        $s=Database::connection()->prepare('INSERT INTO mobile_sales(shop_id,user_id,customer_id,operator_id,product_id,client_name,numero_client,quantite,montant,montant_saisi,devise_saisie,taux_change_saisie,commission,benefice,date_operation,reference) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');$s->execute([$shopId,$userId,$customerId,$operator,$product,$clientName,$this->nullable($d['numero_client']??null),$qty,$amount,$enteredAmount,$currency,$rate,$commission,$profit,date('Y-m-d H:i:s'),$ref]);return(int)Database::connection()->lastInsertId();
    }

    private function linkedProduct(int $shopId,array $d): array
    { $operator=$this->operatorId($shopId,(int)($d['operator_id']??0));$product=(int)($d['product_id']??0);$s=Database::connection()->prepare('SELECT id FROM mobile_products WHERE id=? AND operator_id=? AND shop_id=? AND actif=1');$s->execute([$product,$operator,$shopId]);if(!$s->fetch())throw new InvalidArgumentException('Produit invalide pour cet opérateur.');return[$operator,$product]; }
    private function operatorId(int $shopId,int $id): int
    { $s=Database::connection()->prepare('SELECT id FROM mobile_operators WHERE id=? AND shop_id=?');$s->execute([$id,$shopId]);if(!$s->fetch())throw new InvalidArgumentException('Opérateur invalide.');return$id; }
    private function stock(int $id): float
    { $s=Database::connection()->prepare('SELECT stock_initial+COALESCE((SELECT SUM(quantite) FROM mobile_supplies WHERE product_id=?),0)-COALESCE((SELECT SUM(quantite) FROM mobile_sales WHERE product_id=?),0) stock FROM mobile_products WHERE id=?');$s->execute([$id,$id,$id]);return(float)($s->fetchColumn()?:0); }
    private function nullable(mixed $v): ?string { $v=trim((string)($v??''));return$v===''?null:$v; }
    private function color(mixed $v): string { $v=trim((string)$v);return preg_match('/^#[0-9a-fA-F]{6}$/',$v)?$v:'#0f766e'; }
}
