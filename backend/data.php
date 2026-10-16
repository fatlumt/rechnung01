<?php
declare(strict_types=1);
function data_get(string $owner):array {
 $p=one('SELECT * FROM kr_businesses WHERE owner=?',[$owner]);
 return ['company'=>$p?decode_row($p)['data']:null,'companyVersion'=>$p?(int)$p['version']:0,
 'customers'=>array_map('decode_row',query('SELECT * FROM kr_customers WHERE owner=? ORDER BY created DESC,id',[$owner])->fetchAll()),
 'documents'=>array_map('invoice_values',query('SELECT d.*,COALESCE(f.amount,0) AS fee FROM kr_documents d LEFT JOIN kr_dunning_fees f ON f.document_id=d.id AND f.owner=d.owner WHERE d.owner=? ORDER BY d.created DESC,d.number DESC',[$owner])->fetchAll()),
 'payments'=>array_map(fn($r)=>array_merge($r,['amount'=>(int)$r['amount']]),query('SELECT * FROM kr_payments WHERE owner=? ORDER BY date DESC,id',[$owner])->fetchAll())];
}
function customer_snapshot(string $owner,string $id):array {$r=one('SELECT data FROM kr_customers WHERE owner=? AND id=?',[$owner,$id]);if(!$r)fail('Kunde nicht gefunden.',404);$c=decode_row($r)['data'];if($c['country']!=='CH')fail('Diese Version unterstützt Schweizer Inlandsgeschäfte.');return $c;}
function check_vat(array $data,array $profile):void {if(!$profile['vat'])foreach(pricing_lines($data) as $line)if(($line['type']??'item')!=='section'&&$line['rate']!=0)fail('Ohne MWST-Pflicht müssen die Steuersätze 0 % sein.');}
function add_document(string $owner,string $kind,array $data,string $status='draft'):array {
 $sum=document_total($data);$id=id();$prefix=['invoice'=>'RE','offer'=>'OF','reminder'=>'ZE','dunning'=>'MA'][$kind];
 query('INSERT INTO kr_counters(owner,value) VALUES(?,1) ON DUPLICATE KEY UPDATE value=value+1',[$owner]);
 $number=$prefix.'-'.date('Y').'-'.str_pad((string)one('SELECT value FROM kr_counters WHERE owner=?',[$owner])['value'],5,'0',STR_PAD_LEFT);
 query('INSERT INTO kr_documents(id,owner,number,kind,status,data,total,source_offer,created) VALUES(?,?,?,?,?,?,?,?,?)',[$id,$owner,$number,$kind,$status,encode($data),$sum,$kind==='invoice'?($data['sourceId']??null):null,date('Y-m-d H:i:s')]);return ['id'=>$id];
}
function data_post(string $owner,array $b):array {
 db()->beginTransaction();
 // Serialise each business's mutations, including numbering and partial payments.
 one('SELECT id FROM kr_users WHERE id=? FOR UPDATE',[$owner]);
 if(!current_user())fail('Bitte erneut anmelden.',401);
 $result=data_change($owner,$b);db()->commit();return $result;
}
function data_change(string $owner,array $b):array {
 $action=$b['action']??'';
 if($action==='company'){$v=company($b['data']??null);$p=one('SELECT version FROM kr_businesses WHERE owner=?',[$owner]);if(integer($b['version']??null)!==($p?(int)$p['version']:0))fail('Firmendaten wurden inzwischen geändert. Bitte neu laden.',409);query('INSERT INTO kr_businesses(owner,data,version) VALUES(?,?,1) ON DUPLICATE KEY UPDATE data=VALUES(data),version=version+1',[$owner,encode($v)]);return ['ok'=>true];}
 if($action==='customer'){$v=address($b['data']??null);$id=$b['customerId']??null;if($id){$id=text($id,36,true);if(!one('SELECT id FROM kr_customers WHERE owner=? AND id=?',[$owner,$id]))fail('Kunde nicht gefunden.',404);query('UPDATE kr_customers SET data=? WHERE owner=? AND id=?',[encode($v),$owner,$id]);}else{$id=id();query('INSERT INTO kr_customers(id,owner,data,created) VALUES(?,?,?,?)',[$id,$owner,encode($v),date('Y-m-d H:i:s')]);}return ['id'=>$id];}
 if($action==='create'){$v=draft($b['data']??null);$p=one('SELECT data FROM kr_businesses WHERE owner=?',[$owner]);if(!$p)fail('Bitte zuerst Firmendaten speichern.');$profile=decode_row($p)['data'];check_vat($v,$profile);$v['company']=$profile;$v['customer']=customer_snapshot($owner,$v['customerId']);$v['currency']='CHF';return add_document($owner,$v['kind'],$v);}
 $id=text($b['id']??'',36,true);$r=one('SELECT d.*,COALESCE(f.amount,0) AS fee FROM kr_documents d LEFT JOIN kr_dunning_fees f ON f.document_id=d.id AND f.owner=d.owner WHERE d.owner=? AND d.id=?',[$owner,$id]);if(!$r)fail('Beleg nicht gefunden.',404);$d=invoice_values($r);if(integer($b['version']??null)!==$d['version'])fail('Beleg wurde inzwischen geändert. Bitte neu laden.',409);
 if($action==='edit'){if($d['status']!=='draft')fail('Ausgestellte Belege können nicht bearbeitet werden.');$v=draft($b['data']??null);if($v['kind']!==$d['kind'])fail('Belegtyp kann nicht geändert werden.');check_vat($v,$d['data']['company']);$v=array_merge($d['data'],$v,['customer'=>customer_snapshot($owner,$v['customerId'])]);query('UPDATE kr_documents SET data=?,total=?,version=version+1 WHERE owner=? AND id=?',[encode($v),document_total($v),$owner,$id]);return ['ok'=>true];}
 if($action==='issue'){if($d['status']!=='draft')fail('Beleg ist bereits ausgestellt.');query("UPDATE kr_documents SET status='issued',version=version+1 WHERE owner=? AND id=?",[$owner,$id]);return ['ok'=>true];}
 if($action==='delete'){if($d['status']!=='draft')fail('Nur Entwürfe können gelöscht werden.');query('DELETE FROM kr_documents WHERE owner=? AND id=?',[$owner,$id]);return ['ok'=>true];}
 if($action==='accept'){if($d['kind']!=='offer'||$d['status']!=='issued')fail('Nur ausgestellte Offerten können angenommen werden.');query("UPDATE kr_documents SET status='accepted',version=version+1 WHERE owner=? AND id=?",[$owner,$id]);return ['ok'=>true];}
 if($action==='convert'){
  if($d['kind']!=='offer')fail('Nur Offerten können in Rechnungen umgewandelt werden.');
  if(one('SELECT id FROM kr_documents WHERE owner=? AND source_offer=?',[$owner,$id]))fail('Für diese Offerte existiert bereits eine Rechnung.',409);
  $copy=array_merge($d['data'],['kind'=>'invoice','documentTitle'=>'Rechnung','date'=>date('Y-m-d'),'due'=>date('Y-m-d',strtotime('+'.$d['data']['company']['terms'].' days'))]);
  $v=draft($b['data']??$copy);if($v['kind']!=='invoice')fail('Zielbeleg muss eine Rechnung sein.');
  $v['company']=$d['data']['company'];check_vat($v,$v['company']);
  $v['customer']=$v['customerId']===$d['data']['customerId']?$d['data']['customer']:customer_snapshot($owner,$v['customerId']);
  $v['currency']='CHF';$v['sourceId']=$id;$v['sourceNumber']=$d['number'];
  return add_document($owner,'invoice',$v);
 }
 if($action==='status'){
  if($d['kind']!=='invoice')fail('Dieser Status ist nur für Rechnungen verfügbar.');
  $target=$b['status']??'';if(!in_array($target,['open','paid'],true))fail('Ungültiger Status.');
  if($d['status']==='draft'){
   if($target!=='open')fail('Bitte die Rechnung zuerst ausstellen.');
   query("UPDATE kr_documents SET status='issued',version=version+1 WHERE owner=? AND id=?",[$owner,$id]);return ['ok'=>true];
  }
  if($target==='paid'){$amount=$d['total']-$d['paid'];if($amount<=0)fail('Rechnung ist bereits bezahlt.',409);}
  else{
   if($d['paid']===0)return ['ok'=>true];
   if(($b['confirmReversal']??false)!==true)fail('Bitte die Rückbuchung der Zahlungen bestätigen.',409);
   $amount=-$d['paid'];
  }
  // Append a correction instead of deleting the original payment history.
  query('INSERT INTO kr_payments(id,owner,document_id,amount,date) VALUES(?,?,?,?,?)',[id(),$owner,$id,$amount,date('Y-m-d')]);
  query('UPDATE kr_documents SET paid=paid+?,version=version+1 WHERE owner=? AND id=?',[$amount,$owner,$id]);return ['ok'=>true];
 }
 if($action==='pay'){if($d['kind']!=='invoice'||$d['status']==='draft')fail('Nur ausgestellte Rechnungen können bezahlt werden.');$amount=integer($b['amount']??null,1,$d['total']-$d['paid']);$date=day($b['date']??'');if($date>date('Y-m-d'))fail('Zahlungsdatum darf nicht in der Zukunft liegen.');query('UPDATE kr_documents SET paid=paid+?,version=version+1 WHERE owner=? AND id=?',[$amount,$owner,$id]);query('INSERT INTO kr_payments(id,owner,document_id,amount,date) VALUES(?,?,?,?,?)',[id(),$owner,$id,$amount,$date]);return ['ok'=>true];}
 if(in_array($action,['reminder','quickReminder'],true)){
  if($d['kind']!=='invoice'||$d['status']==='draft'||$d['paid']>=$d['total'])fail('Erinnerungen sind für ausgestellte, offene Rechnungen verfügbar.');
  $kind=$b['kind']??'';if(!in_array($kind,['reminder','dunning'],true))fail('Ungültiger Belegtyp.');
  $due=$action==='quickReminder'?date('Y-m-d',strtotime('+10 days')):day($b['due']??'');if($due<=date('Y-m-d'))fail('Bitte ein zukünftiges Zahlungsziel wählen.');
  $newFee=$kind==='dunning'&&!$d['fee'];if($newFee){
   query('INSERT INTO kr_dunning_fees(document_id,owner,amount,created) VALUES(?,?,5000,?)',[$id,$owner,date('Y-m-d H:i:s')]);$d['fee']=5000;$d['total']+=5000;
  }
  $principal=max(0,$d['invoiceTotal']-$d['paid']);$feeRemaining=max(0,$d['fee']-max(0,$d['paid']-$d['invoiceTotal']));
  $source=$d;$source['total']=$d['invoiceTotal'];$source['fee']=0;$source['paid']=min($d['paid'],$d['invoiceTotal']);
  $v=array_merge($d['data'],['date'=>date('Y-m-d'),'due'=>$due,'sourceId'=>$id,'sourceNumber'=>$d['number'],'outstanding'=>$principal,'feeOutstanding'=>$feeRemaining,'amountDue'=>$principal+$feeRemaining,'paidAtCreation'=>$d['paid'],'sourceInvoice'=>$source,
   'notes'=>$action==='quickReminder'?($kind==='reminder'?'Freundliche Zahlungserinnerung: Bitte überweisen Sie den offenen Betrag bis zum angegebenen Zahlungsziel. Falls Sie bereits bezahlt haben, betrachten Sie dieses Schreiben bitte als gegenstandslos.':'Mahnung: Bitte überweisen Sie den offenen Betrag einschliesslich der ausgewiesenen Mahngebühr bis zum angegebenen Zahlungsziel. Falls Sie bereits bezahlt haben, kontaktieren Sie uns bitte.'):text($b['notes']??'',4000,true)]);
  // Fee creation and quick actions invalidate stale invoice requests.
  if($action==='quickReminder'||$newFee)query('UPDATE kr_documents SET version=version+1 WHERE owner=? AND id=?',[$owner,$id]);
  return add_document($owner,$kind,$v,'issued');
 }

 fail('Aktion nicht verfügbar.');
}

