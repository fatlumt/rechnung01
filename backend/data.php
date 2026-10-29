<?php
declare(strict_types=1);
require_once __DIR__.'/document-trash.php';
require_once __DIR__.'/trash-retention.php';
function document_archive_schema():void {
 document_trash_schema();trash_retention_schema();
 query('CREATE TABLE IF NOT EXISTS kr_customer_trash (owner CHAR(36) NOT NULL,customer_id CHAR(36) NOT NULL,created DATETIME NOT NULL,PRIMARY KEY(owner,customer_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
 query('CREATE TABLE IF NOT EXISTS kr_document_archive (owner CHAR(36) NOT NULL,document_id CHAR(36) NOT NULL,created DATETIME NOT NULL,PRIMARY KEY(owner,document_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}
function data_get(string $owner):array {
 document_archive_schema();
 features_schema();hours_schema();business_schema();projects_schema();mail_schema();
 trash_cleanup_owner($owner);
 $features=features_for($owner);
 $p=one('SELECT * FROM kr_businesses WHERE owner=?',[$owner]);
 return ['trashDeadlines'=>query('SELECT kind,item_id,expires FROM kr_trash_deadlines WHERE owner=?',[$owner])->fetchAll(),'trashedDocumentIds'=>array_column(query('SELECT document_id FROM kr_document_trash WHERE owner=?',[$owner])->fetchAll(),'document_id'),'archivedDocumentIds'=>array_column(query('SELECT document_id FROM kr_document_archive WHERE owner=?',[$owner])->fetchAll(),'document_id'),'projects'=>$features['projects']?projects_list($owner):[],'projectLinks'=>$features['projects']?query('SELECT document_id,project_id FROM kr_project_documents WHERE owner=?',[$owner])->fetchAll():[],'features'=>$features,'employees'=>$features['hours']?hours_list($owner,'kr_employees'):[],'hours'=>$features['hours']?hours_list($owner,'kr_hours'):[],'serverTime'=>time(),'expenses'=>$features['expenses']?array_map('decode_row',query('SELECT id,data,version,created FROM kr_expenses WHERE owner=? ORDER BY created DESC',[$owner])->fetchAll()):[],'depositLinks'=>$features['deposits']?query('SELECT deposit_id,final_id FROM kr_deposit_links WHERE owner=?',[$owner])->fetchAll():[],'mailHistory'=>$features['email']?query('SELECT document_id,status,created FROM kr_mail_log WHERE owner=? ORDER BY created DESC LIMIT 500',[$owner])->fetchAll():[],'company'=>$p?decode_row($p)['data']:null,'companyVersion'=>$p?(int)$p['version']:0,
 'customers'=>array_map('decode_row',query('SELECT c.* FROM kr_customers c WHERE c.owner=? AND NOT EXISTS (SELECT 1 FROM kr_customer_trash t WHERE t.owner=c.owner AND t.customer_id=c.id) ORDER BY c.created DESC,c.id',[$owner])->fetchAll()),
 'trashedCustomers'=>array_map('decode_row',query('SELECT c.* FROM kr_customers c INNER JOIN kr_customer_trash t ON t.owner=c.owner AND t.customer_id=c.id WHERE c.owner=? ORDER BY t.created DESC,c.id',[$owner])->fetchAll()),
 'documents'=>array_values(array_filter(array_map('invoice_values',query('SELECT d.*,COALESCE(f.amount,0) AS fee FROM kr_documents d LEFT JOIN kr_dunning_fees f ON f.document_id=d.id AND f.owner=d.owner WHERE d.owner=? ORDER BY d.created DESC,d.number DESC',[$owner])->fetchAll()),fn($d)=>$features[feature_kind($d['kind'])])),
 'payments'=>$features['invoices']?array_map(fn($r)=>array_merge($r,['amount'=>(int)$r['amount']]),query('SELECT * FROM kr_payments WHERE owner=? ORDER BY date DESC,id',[$owner])->fetchAll()):[]];
}
function customer_snapshot(string $owner,string $id):array {$r=one('SELECT data FROM kr_customers WHERE owner=? AND id=?',[$owner,$id]);if(!$r)fail('Kunde nicht gefunden.',404);$c=decode_row($r)['data'];return $c;}
function check_vat(array $data,array $profile):void {foreach(pricing_lines($data) as $line){if(($line['type']??'item')==='section')continue;$allowed=$profile['vat']?(($profile['country']??'CH')==='DE'?[7.0,19.0]:[0.0,2.6,3.8,8.1]):[0.0];if(!in_array((float)$line['rate'],$allowed,true))fail('Steuersatz passt nicht zum Länder- und Steuerprofil.');}}
function document_country(array &$v):void{if($v['customer']['country']!==$v['company']['country'])fail('Aktuell werden nur Inlandsgeschäfte im Firmenland unterstützt.');$v['currency']=$v['company']['country']==='DE'?'EUR':'CHF';$v['language']=document_language($v['language']??'de');if($v['currency']==='EUR'){foreach(['uid','number','serviceDate'] as $k)$v['visibility'][$k]=true;}}
function add_document(string $owner,string $kind,array $data,string $status='draft'):array {
 $sum=document_total($data);$id=id();$prefix=['invoice'=>'RE','offer'=>'OF','reminder'=>'ZE','dunning'=>'MA'][$kind];
 query('INSERT INTO kr_counters(owner,value) VALUES(?,1) ON DUPLICATE KEY UPDATE value=value+1',[$owner]);
 $number=$prefix.'-'.date('Y').'-'.str_pad((string)one('SELECT value FROM kr_counters WHERE owner=?',[$owner])['value'],5,'0',STR_PAD_LEFT);
 query('INSERT INTO kr_documents(id,owner,number,kind,status,data,total,source_offer,created) VALUES(?,?,?,?,?,?,?,?,?)',[$id,$owner,$number,$kind,$status,encode($data),$sum,$kind==='invoice'?($data['sourceId']??null):null,date('Y-m-d H:i:s')]);project_attach_new($owner,$id,$data);return ['id'=>$id];
}
function data_post(string $owner,array $b):array {
 document_archive_schema();
 features_schema();hours_schema();business_schema();projects_schema();mail_schema();
 db()->beginTransaction();
 // Serialise each business's mutations, including numbering and partial payments.
 one('SELECT id FROM kr_users WHERE id=? FOR UPDATE',[$owner]);
 if(!current_user())fail('Bitte erneut anmelden.',401);
 feature_guard($owner,$b);$result=data_change($owner,$b);
 // Small mutations return their authoritative result without reloading all documents.
 if(in_array($b['action']??'',['status','pay','delete','restore','archive','unarchive','issue','accept','refreshLogo'],true)){
  $id=text($b['id']??'',36,true);
  $row=one('SELECT d.*,COALESCE(f.amount,0) AS fee FROM kr_documents d LEFT JOIN kr_dunning_fees f ON f.document_id=d.id AND f.owner=d.owner WHERE d.owner=? AND d.id=?',[$owner,$id]);
  if($row){$doc=invoice_values($row);$doc['archived']=(bool)one('SELECT document_id FROM kr_document_archive WHERE owner=? AND document_id=?',[$owner,$id]);$doc['trashed']=document_is_trashed($owner,$id);$result['documentPatch']=$doc;$result['documentPayments']=array_map(fn($r)=>array_merge($r,['amount'=>(int)$r['amount']]),query('SELECT * FROM kr_payments WHERE owner=? AND document_id=? ORDER BY date DESC,id',[$owner,$id])->fetchAll());}
 }
 db()->commit();return $result;
}
function data_change(string $owner,array $b):array {
 $action=$b['action']??'';
 if(in_array($action,['trashPurge','trashEmpty'],true))return trash_action($owner,$b);
 if(str_starts_with($action,'project'))return projects_change($owner,$b);
 if(in_array($action,['employee','hoursSave','hoursDelete','hoursTimer'],true))return hours_change($owner,$b);
 if($action==='expense')return expense_save($owner,$b);
 if($action==='receipt'){$r=one('SELECT data,receipt FROM kr_expenses WHERE owner=? AND id=?',[$owner,text($b['expenseId']??'',36,true)]);if(!$r)fail('Beleg nicht gefunden.',404);return ['receipt'=>$r['receipt'],'name'=>decode_row($r)['data']['receiptName']??'Beleg'];}
 if($action==='company'){$v=company($b['data']??null);$p=one('SELECT version,data FROM kr_businesses WHERE owner=?',[$owner]);if($p&&decode_row($p)['data']['country']!==$v['country']&&one('SELECT id FROM kr_documents WHERE owner=? LIMIT 1',[$owner]))fail('Das Firmenland kann nach dem ersten Beleg nicht gewechselt werden. Bitte einen eigenen Firmenbereich verwenden.');if(integer($b['version']??null)!==($p?(int)$p['version']:0))fail('Firmendaten wurden inzwischen geändert. Bitte neu laden.',409);query('INSERT INTO kr_businesses(owner,data,version) VALUES(?,?,1) ON DUPLICATE KEY UPDATE data=VALUES(data),version=version+1',[$owner,encode($v)]);return ['ok'=>true];}
 if(in_array($action,['customerDelete','customerRestore'],true)){
  $id=text($b['customerId']??'',36,true);
  if(!one('SELECT id FROM kr_customers WHERE owner=? AND id=?',[$owner,$id]))fail('Kunde nicht gefunden.',404);
  if($action==='customerDelete')query('INSERT INTO kr_customer_trash(owner,customer_id,created) VALUES(?,?,?) ON DUPLICATE KEY UPDATE created=VALUES(created)',[$owner,$id,date('Y-m-d H:i:s')]);
  else query('DELETE FROM kr_customer_trash WHERE owner=? AND customer_id=?',[$owner,$id]);
  if($action==='customerDelete')trash_schedule($owner,'customer',$id);else trash_forget_deadline($owner,'customer',$id);
  return ['ok'=>true];
 }
 if($action==='customer'){$v=address($b['data']??null);$id=$b['customerId']??null;if($id){$id=text($id,36,true);if(!one('SELECT id FROM kr_customers WHERE owner=? AND id=?',[$owner,$id]))fail('Kunde nicht gefunden.',404);query('UPDATE kr_customers SET data=? WHERE owner=? AND id=?',[encode($v),$owner,$id]);}else{$id=id();query('INSERT INTO kr_customers(id,owner,data,created) VALUES(?,?,?,?)',[$id,$owner,encode($v),date('Y-m-d H:i:s')]);}return ['id'=>$id];}
 if($action==='create'){$v=draft($b['data']??null);$p=one('SELECT data FROM kr_businesses WHERE owner=?',[$owner]);if(!$p)fail('Bitte zuerst Firmendaten speichern.');$profile=decode_row($p)['data'];$v['language']=document_language($b['data']['language']??$profile['language']??'de');check_vat($v,$profile);$v['company']=$profile;$v['customer']=customer_snapshot($owner,$v['customerId']);$v['templateId']=template_id($b['data']['templateId']??$profile['templateId']??'classic');$v['visibility']=field_visibility($profile['visibility']??[]);document_country($v);apply_deposits($owner,$v);$result=add_document($owner,$v['kind'],$v);reserve_deposits($owner,$result['id'],$v);return $result;}
 $id=text($b['id']??'',36,true);$r=one('SELECT d.*,COALESCE(f.amount,0) AS fee FROM kr_documents d LEFT JOIN kr_dunning_fees f ON f.document_id=d.id AND f.owner=d.owner WHERE d.owner=? AND d.id=?',[$owner,$id]);if(!$r)fail('Beleg nicht gefunden.',404);$d=invoice_values($r);if(integer($b['version']??null)!==$d['version'])fail('Beleg wurde inzwischen geändert. Bitte neu laden.',409);
 if(in_array($action,['delete','restore'],true)){
  if($action==='delete')query('INSERT INTO kr_document_trash(owner,document_id,created) VALUES(?,?,?) ON DUPLICATE KEY UPDATE created=VALUES(created)',[$owner,$id,date('Y-m-d H:i:s')]);
  else query('DELETE FROM kr_document_trash WHERE owner=? AND document_id=?',[$owner,$id]);
  query('DELETE FROM kr_document_archive WHERE owner=? AND document_id=?',[$owner,$id]);
  query('UPDATE kr_documents SET version=version+1 WHERE owner=? AND id=?',[$owner,$id]);
  if($action==='delete')trash_schedule($owner,'document',$id);else trash_forget_deadline($owner,'document',$id);
  return ['ok'=>true];
 }
 document_require_active($owner,$id);
 if($action==='edit'){if($d['status']!=='draft')fail('Ausgestellte Belege können nicht bearbeitet werden.');$v=draft($b['data']??null);$v['language']=document_language($b['data']['language']??$d['data']['language']??'de');$v['templateId']=template_id($b['data']['templateId']??$d['data']['templateId']??'classic');if($v['kind']!==$d['kind'])fail('Belegtyp kann nicht geändert werden.');check_vat($v,$d['data']['company']);$v=array_merge($d['data'],$v,['customer'=>$v['customerId']===$d['data']['customerId']&&!one('SELECT id FROM kr_customers WHERE owner=? AND id=?',[$owner,$v['customerId']])?$d['data']['customer']:customer_snapshot($owner,$v['customerId'])]);document_country($v);apply_deposits($owner,$v,$id);reserve_deposits($owner,$id,$v);query('UPDATE kr_documents SET data=?,total=?,version=version+1 WHERE owner=? AND id=?',[encode($v),document_total($v),$owner,$id]);if(features_for($owner)['projects'])project_document_link($owner,$id,$v['projectId']??'',$v['customerId']);return ['ok'=>true];}
 if($action==='refreshLogo'){
  if($d['status']!=='draft')fail('Nur Entwürfe können ein neues Firmenlogo übernehmen.');
  $profile=one('SELECT data FROM kr_businesses WHERE owner=?',[$owner]);if(!$profile)fail('Bitte zuerst Firmendaten speichern.');
  $v=$d['data'];$v['company']['logoUrl']=decode_row($profile)['data']['logoUrl']??'';
  query('UPDATE kr_documents SET data=?,version=version+1 WHERE owner=? AND id=?',[encode($v),$owner,$id]);return ['ok'=>true];
 }
 if($action==='issue'){if($d['status']!=='draft')fail('Beleg ist bereits ausgestellt.');$checked=$d['data'];apply_deposits($owner,$checked,$id);query("UPDATE kr_documents SET status='issued',version=version+1 WHERE owner=? AND id=?",[$owner,$id]);return ['ok'=>true];}
 if(in_array($action,['archive','unarchive'],true)){
  if($d['status']==='draft')fail('Nur ausgestellte Belege können archiviert werden.');
  if($action==='archive')query('INSERT INTO kr_document_archive(owner,document_id,created) VALUES(?,?,?) ON DUPLICATE KEY UPDATE created=VALUES(created)',[$owner,$id,date('Y-m-d H:i:s')]);
  else query('DELETE FROM kr_document_archive WHERE owner=? AND document_id=?',[$owner,$id]);
  query('UPDATE kr_documents SET version=version+1 WHERE owner=? AND id=?',[$owner,$id]);
  return ['ok'=>true];
 }

 if($action==='accept'){if($d['kind']!=='offer'||$d['status']!=='issued')fail('Nur ausgestellte Offerten können angenommen werden.');query("UPDATE kr_documents SET status='accepted',version=version+1 WHERE owner=? AND id=?",[$owner,$id]);return ['ok'=>true];}
 if($action==='convert'){
  if($d['kind']!=='offer')fail('Nur Offerten können in Rechnungen umgewandelt werden.');
  if(one('SELECT id FROM kr_documents WHERE owner=? AND source_offer=?',[$owner,$id]))fail('Für diese Offerte existiert bereits eine Rechnung.',409);
  $copy=array_merge($d['data'],['kind'=>'invoice','documentTitle'=>'Rechnung','date'=>date('Y-m-d'),'due'=>date('Y-m-d',strtotime('+'.$d['data']['company']['terms'].' days'))]);
  $v=draft($b['data']??$copy);$v['language']=document_language($b['data']['language']??$d['data']['language']??'de');$v['templateId']=template_id($b['data']['templateId']??$d['data']['templateId']??'classic');if($v['kind']!=='invoice')fail('Zielbeleg muss eine Rechnung sein.');
  $v['company']=$d['data']['company'];check_vat($v,$v['company']);
  $v['customer']=$v['customerId']===$d['data']['customerId']?$d['data']['customer']:customer_snapshot($owner,$v['customerId']);
  document_country($v);$v['sourceId']=$id;$v['sourceNumber']=$d['number'];
  apply_deposits($owner,$v);$result=add_document($owner,'invoice',$v);reserve_deposits($owner,$result['id'],$v);return $result;
 }
 if($action==='status'){
  if($d['kind']!=='invoice')fail('Dieser Status ist nur für Rechnungen verfügbar.');
  $target=$b['status']??'';if(!in_array($target,['open','paid'],true))fail('Ungültiger Status.');
  if($d['status']==='draft'){
   if($target!=='open')fail('Bitte die Rechnung zuerst ausstellen.');$checked=$d['data'];apply_deposits($owner,$checked,$id);
   query("UPDATE kr_documents SET status='issued',version=version+1 WHERE owner=? AND id=?",[$owner,$id]);return ['ok'=>true];
  }
  if($target==='paid'){$amount=$d['total']-$d['paid'];if($amount<=0)fail('Rechnung ist bereits bezahlt.',409);}
  else{
   if($d['paid']===0)return ['ok'=>true];if(one('SELECT final_id FROM kr_deposit_links WHERE owner=? AND deposit_id=?',[$owner,$id]))fail('Akonto ist einer Schlussrechnung zugeordnet. Eine Rückbuchung ist gesperrt.');
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
  $newFee=$kind==='dunning'&&!$d['fee']&&($d['data']['currency']??'CHF')==='CHF';if($newFee){
   query('INSERT INTO kr_dunning_fees(document_id,owner,amount,created) VALUES(?,?,5000,?)',[$id,$owner,date('Y-m-d H:i:s')]);$d['fee']=5000;$d['total']+=5000;
  }
  $principal=max(0,$d['invoiceTotal']-$d['paid']);$feeRemaining=max(0,$d['fee']-max(0,$d['paid']-$d['invoiceTotal']));
  $source=$d;$source['total']=$d['invoiceTotal'];$source['fee']=0;$source['paid']=min($d['paid'],$d['invoiceTotal']);
  $v=array_merge($d['data'],['date'=>date('Y-m-d'),'due'=>$due,'sourceId'=>$id,'sourceNumber'=>$d['number'],'outstanding'=>$principal,'feeOutstanding'=>$feeRemaining,'amountDue'=>$principal+$feeRemaining,'paidAtCreation'=>$d['paid'],'sourceInvoice'=>$source,
   'notes'=>$action==='quickReminder'?translate_message(document_language($d['data']['language']??'de'),$kind==='reminder'?'Freundliche Zahlungserinnerung: Bitte überweisen Sie den offenen Betrag bis zum angegebenen Zahlungsziel. Falls Sie bereits bezahlt haben, betrachten Sie dieses Schreiben bitte als gegenstandslos.':'Mahnung: Bitte überweisen Sie den offenen Betrag gemäss der folgenden Aufstellung bis zum angegebenen Zahlungsziel. Falls Sie bereits bezahlt haben, kontaktieren Sie uns bitte.'):text($b['notes']??'',4000,true)]);
  // Fee creation and quick actions invalidate stale invoice requests.
  if($action==='quickReminder'||$newFee)query('UPDATE kr_documents SET version=version+1 WHERE owner=? AND id=?',[$owner,$id]);
  return add_document($owner,$kind,$v,'issued');
 }

 fail('Aktion nicht verfügbar.');
}

