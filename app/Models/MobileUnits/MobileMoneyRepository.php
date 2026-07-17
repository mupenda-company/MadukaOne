<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Core/Database.php';

final class MobileMoneyRepository
{
    public const TYPES = ['depot','retrait','envoi','reception','paiement_facture','achat_credit','achat_forfait','cash_in','cash_out'];

    public function accounts(int $shopId): array
    {
        $s=Database::connection()->prepare('SELECT a.*,o.nom operator_name,o.couleur operator_color FROM mobile_money_accounts a JOIN mobile_operators o ON o.id=a.operator_id WHERE a.shop_id=? ORDER BY a.actif DESC,o.nom,a.nom');$s->execute([$shopId]);return $s->fetchAll();
    }
    public function commissions(int $shopId): array
    {
        $s=Database::connection()->prepare('SELECT c.*,o.nom operator_name,o.couleur operator_color FROM mobile_money_commissions c JOIN mobile_operators o ON o.id=c.operator_id WHERE c.shop_id=? ORDER BY o.nom,c.type_transaction');$s->execute([$shopId]);return $s->fetchAll();
    }
    public function transactions(int $shopId,array $f=[]): array
    {
        $sql='SELECT t.*,a.nom account_name,o.nom operator_name,COALESCE(u.nom,u.email,"Système") agent_name FROM mobile_money_transactions t JOIN mobile_money_accounts a ON a.id=t.account_id JOIN mobile_operators o ON o.id=t.operator_id LEFT JOIN users u ON u.id=t.user_id WHERE t.shop_id=?';$args=[$shopId];
        if(($f['date_from']??'')!==''){$sql.=' AND DATE(t.date_operation)>=?';$args[]=$f['date_from'];}if(($f['date_to']??'')!==''){$sql.=' AND DATE(t.date_operation)<=?';$args[]=$f['date_to'];}if((int)($f['operator_id']??0)>0){$sql.=' AND t.operator_id=?';$args[]=(int)$f['operator_id'];}if(in_array(($f['type']??''),self::TYPES,true)){$sql.=' AND t.type_transaction=?';$args[]=$f['type'];}$sql.=' ORDER BY t.date_operation DESC,t.id DESC';$s=Database::connection()->prepare($sql);$s->execute($args);return $s->fetchAll();
    }
    public function saveAccount(int $shopId,array $d): void
    {
        $operator=(int)($d['operator_id']??0);$nom=trim((string)($d['nom']??''));$numero=trim((string)($d['numero']??''));$owner=trim((string)($d['proprietaire']??''));if($operator<1||$nom===''||$numero===''||$owner==='')throw new InvalidArgumentException('Opérateur, compte, numéro et propriétaire sont obligatoires.');
        $s=Database::connection()->prepare('INSERT INTO mobile_money_accounts(shop_id,operator_id,nom,numero,proprietaire,solde,actif) SELECT ?,id,?,?,?,?,? FROM mobile_operators WHERE id=? AND shop_id=?');$s->execute([$shopId,$nom,$numero,$owner,max(0,(float)($d['solde']??0)),isset($d['actif'])?1:0,$operator,$shopId]);if($s->rowCount()===0)throw new InvalidArgumentException('Opérateur invalide.');
    }
    public function updateAccount(int $shopId,int $id,array $d): void
    {
        $numero=trim((string)($d['numero']??''));$owner=trim((string)($d['proprietaire']??''));if($numero===''||$owner==='')throw new InvalidArgumentException('Numéro et propriétaire sont obligatoires.');$s=Database::connection()->prepare('UPDATE mobile_money_accounts SET numero=?,proprietaire=?,solde=?,actif=? WHERE id=? AND shop_id=?');$s->execute([$numero,$owner,max(0,(float)($d['solde']??0)),isset($d['actif'])?1:0,$id,$shopId]);
    }
    public function saveCommission(int $shopId,array $d): void
    {
        $operator=(int)($d['operator_id']??0);$type=(string)($d['type_transaction']??'');$rate=max(0,(float)($d['taux']??0));if($operator<1||!in_array($type,self::TYPES,true))throw new InvalidArgumentException('Barème de commission invalide.');$s=Database::connection()->prepare('INSERT INTO mobile_money_commissions(shop_id,operator_id,type_transaction,taux,actif) VALUES(?,?,?,?,1) ON DUPLICATE KEY UPDATE taux=VALUES(taux),actif=1');$s->execute([$shopId,$operator,$type,$rate]);
    }
    public function addTransaction(int $shopId,int $userId,array $d): int
    {
        $type=(string)($d['type_transaction']??'');$accountId=(int)($d['account_id']??0);$amount=max(0,(float)($d['montant']??0));$fees=max(0,(float)($d['frais']??0));$number=trim((string)($d['numero']??''));$reference=trim((string)($d['reference']??''));if(!in_array($type,self::TYPES,true)||$amount<=0||$number===''||$reference==='')throw new InvalidArgumentException('Type, numéro, montant et référence sont obligatoires.');
        $db=Database::connection();$db->beginTransaction();try{$s=$db->prepare('SELECT a.*,o.id operator_id FROM mobile_money_accounts a JOIN mobile_operators o ON o.id=a.operator_id WHERE a.id=? AND a.shop_id=? AND a.actif=1 FOR UPDATE');$s->execute([$accountId,$shopId]);$a=$s->fetch();if(!$a)throw new InvalidArgumentException('Compte Mobile Money invalide.');$walletIncoming=in_array($type,['retrait','reception','cash_in'],true);$cashIncoming=in_array($type,['depot','envoi','paiement_facture','achat_credit','achat_forfait','cash_out'],true);$direction=$cashIncoming?'entree':'sortie';$before=(float)$a['solde'];$after=$walletIncoming?$before+$amount:$before-$amount;if($after<0)throw new RuntimeException('Solde électronique insuffisant pour cette opération.');$rateS=$db->prepare('SELECT taux FROM mobile_money_commissions WHERE shop_id=? AND operator_id=? AND type_transaction=? AND actif=1');$rateS->execute([$shopId,$a['operator_id'],$type]);$rate=(float)($rateS->fetchColumn()?:0);$commission=round($amount*$rate/100,2);$insert=$db->prepare('INSERT INTO mobile_money_transactions(shop_id,account_id,operator_id,user_id,type_transaction,numero,client,montant,frais,taux_commission,commission,sens_caisse,solde_avant,solde_apres,reference) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');$insert->execute([$shopId,$accountId,$a['operator_id'],$userId,$type,$number,$this->nullable($d['client']??null),$amount,$fees,$rate,$commission,$direction,$before,$after,$reference]);$db->prepare('UPDATE mobile_money_accounts SET solde=? WHERE id=?')->execute([$after,$accountId]);$id=(int)$db->lastInsertId();$db->commit();return$id;}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw$e;}
    }
    public function cashSummary(int $shopId): array
    {
        $s=Database::connection()->prepare("SELECT COALESCE(SUM(CASE WHEN sens_caisse='entree' THEN montant+frais ELSE 0 END),0) entrees,COALESCE(SUM(CASE WHEN sens_caisse='sortie' THEN montant ELSE 0 END),0) sorties,COALESCE(SUM(commission),0) commissions FROM mobile_money_transactions WHERE shop_id=?");$s->execute([$shopId]);$r=$s->fetch()?:[];$session=$this->openSession($shopId);$opening=(float)($session['solde_ouverture']??0);return array_merge($r,['opening'=>$opening,'balance'=>$opening+(float)($r['entrees']??0)-(float)($r['sorties']??0),'session'=>$session]);
    }
    public function openCash(int $shopId,int $userId,float $amount): void
    { if($this->openSession($shopId))throw new RuntimeException('Une caisse est déjà ouverte.');$s=Database::connection()->prepare("INSERT INTO mobile_cash_sessions(shop_id,user_id,solde_ouverture,statut) VALUES(?,?,?,'ouverte')");$s->execute([$shopId,$userId,max(0,$amount)]); }
    public function closeCash(int $shopId,float $real): void
    { $summary=$this->cashSummary($shopId);$session=$summary['session']??null;if(!$session)throw new RuntimeException('Aucune caisse ouverte.');$s=Database::connection()->prepare("UPDATE mobile_cash_sessions SET solde_fermeture_theorique=?,solde_fermeture_reel=?,closed_at=NOW(),statut='fermee' WHERE id=? AND shop_id=?");$s->execute([$summary['balance'],max(0,$real),$session['id'],$shopId]); }
    public function cashSessions(int $shopId): array
    { $s=Database::connection()->prepare('SELECT c.*,COALESCE(u.nom,u.email,"Système") agent_name FROM mobile_cash_sessions c LEFT JOIN users u ON u.id=c.user_id WHERE c.shop_id=? ORDER BY c.opened_at DESC');$s->execute([$shopId]);return$s->fetchAll(); }
    public function accounting(int $shopId): array
    {
        $sql="SELECT (SELECT COALESCE(SUM(montant+frais),0) FROM mobile_money_transactions WHERE shop_id=:s1 AND sens_caisse='entree') recettes_money,(SELECT COALESCE(SUM(commission),0) FROM mobile_money_transactions WHERE shop_id=:s2) commissions_money,(SELECT COALESCE(SUM(montant),0) FROM mobile_sales WHERE shop_id=:s3) ventes_unites,(SELECT COALESCE(SUM(benefice),0) FROM mobile_sales WHERE shop_id=:s4) benefices_unites,(SELECT COALESCE(SUM(montant*taux_change_saisie),0) FROM expenses WHERE shop_id=:s5 AND statut='active') charges,(SELECT COALESCE(SUM(montant*taux_change_saisie),0) FROM expenses WHERE shop_id=:s6 AND statut='active' AND categorie='salaire') salaires";$s=Database::connection()->prepare($sql);$s->execute(['s1'=>$shopId,'s2'=>$shopId,'s3'=>$shopId,'s4'=>$shopId,'s5'=>$shopId,'s6'=>$shopId]);$r=$s->fetch()?:[];$r['benefice']=(float)($r['benefices_unites']??0)+(float)($r['commissions_money']??0)-(float)($r['charges']??0);return$r;
    }
    private function openSession(int $shopId): ?array{$s=Database::connection()->prepare("SELECT * FROM mobile_cash_sessions WHERE shop_id=? AND statut='ouverte' ORDER BY id DESC LIMIT 1");$s->execute([$shopId]);$r=$s->fetch();return is_array($r)?$r:null;}
    private function nullable(mixed $v): ?string{$v=trim((string)($v??''));return$v===''?null:$v;}
}
