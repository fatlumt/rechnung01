<?php
declare(strict_types=1);
function data_get(string $owner):array {
 $p=one('SELECT * FROM kr_businesses WHERE owner=?',[$owner]);
 return ['company'=>$p?decode_row($p)['data']:null,'companyVersion'=>$p?(int)$p['version']:0,
 'customers'=>array_map('decode_row',query('SELECT * FROM kr_customers WHERE owner=? ORDER BY created DESC,id',[$owner])->fetchAll()),
 'documents'=>array_map('decode_row',query('SELECT * FROM kr_documents WHERE owner=? ORDER BY created DESC,number DESC',[$owner])->fetchAll()),
 'payments'=>array_map(fn($r)=>array_merge($r,['amount'=>(int)$r['amount']]),query('SELECT * FROM kr_payments WHERE owner=? ORDER BY date DESC,id',[$owner])->fetchAll())];
}
function customer_snapshot(string $owner,string $id):array {$r=one('SELECT data FROM kr_customers WHERE owner=? AND id=?',[$owner,$id]);if(!$r)fail('Kunde nicht gefunden.',404);$c=decode_row($r)['data'];if($c['country']!=='CH')fail('Diese Version unterstützt Schweizer Inlandsgeschäfte.');return $c;}
function check_vat(array $data,array $profile):void {if(!$profile['vat'])foreach($data['lines'] as $line)if($line['rate']!=0)fail('Ohne MWST-Pflicht müssen die Steuersätze 0 % sein.');}
function add_document(string $owner,string $kind,array $data,string $status='draft'):array {
 $sum=total($data['lines']);$id=id();$prefix=['invoice'=>'RE','offer'=>'OF','reminder'=>'ZE','dunning'=>'MA'][$kind];
 query('INSERT INTO kr_counters(owner,value) VALUES(?,1) ON DUPLICATE KEY UPDATE value=value+1',[$owner]);
 $number=$prefix.'-'.date('Y').'-'.str_pad((string)one('SELECT value FROM kr_counters WHERE owner=?',[$owner])['value'],5,'0',STR_PAD_LEFT);
 query('INSERT INTO kr_documents(id,owner,number,kind,status,data,total,source_offer,created) VALUES(?,?,?,?,?,?,?,?,?)',[$id,$owner,$number,$kind,$status,encode($data),$sum,$kind==='invoice'?($data['sourceId']??null):null,date('Y-m-d H:i:s')]);return ['id'=>$id];
}
function data_post(string $owner,array $b):array {
 db()->beginTransaction();
 // Serialise each business's mutations, including numbering and partial payments.
 one('SELECT id FROM kr_users WHERE id=? FOR UPDATE',[$owner]);
 $result=data_change($owner,$b);db()->commit();return $result;
}
function data_change(string $owner,array $b):array {
 $action=$b['action']??'';
 if($action==='company'){$v=company($b['data']??null);$p=one('SELECT version FROM kr_businesses WHERE owner=?',[$owner]);if(integer($b['version']??null)!==($p?(int)$p['version']:0))fail('Firmendaten wurden inzwischen geändert. Bitte neu laden.',409);query('INSERT INTO kr_businesses(owner,data,version) VALUES(?,?,1) ON DUPLICATE KEY UPDATE data=VALUES(data),version=version+1',[$owner,encode($v)]);return ['ok'=>true];}
 if($action==='customer'){$v=address($b['data']??null);$id=$b['customerId']??null;if($id){$id=text($id,36,true);if(!one('SELECT id FROM kr_customers WHERE owner=? AND id=?',[$owner,$id]))fail('Kunde nicht gefunden.',404);query('UPDATE kr_customers SET data=? WHERE owner=? AND id=?',[encode($v),$owner,$id]);}else{$id=id();query('INSERT INTO kr_customers(id,owner,data,created) VALUES(?,?,?,?)',[$id,$owner,encode($v),date('Y-m-d H:i:s')]);}return ['id'=>$id];}
 if($action==='create'){$v=draft($b['data']??null);$p=one('SELECT data FROM kr_businesses WHERE owner=?',[$owner]);if(!$p)fail('Bitte zuerst Firmendaten speichern.');$profile=decode_row($p)['data'];check_vat($v,$profile);$v['company']=$profile;$v['customer']=customer_snapshot($owner,$v['customerId']);$v['currency']='CHF';return add_document($owner,$v['kind'],$v);}
 $id=text($b['id']??'',36,true);$r=one('SELECT * FROM kr_documents WHERE owner=? AND id=?',[$owner,$id]);if(!$r)fail('Beleg nicht gefunden.',404);$d=decode_row($r);if(integer($b['version']??null)!==$d['version'])fail('Beleg wurde inzwischen geändert. Bitte neu laden.',409);
 if($action==='edit'){if($d['status']!=='draft')fail('Ausgestellte Belege können nicht bearbeitet werden.');$v=draft($b['data']??null);if($v['kind']!==$d['kind'])fail('Belegtyp kann nicht geändert werden.');check_vat($v,$d['data']['company']);$v=array_merge($d['data'],$v,['customer'=>customer_snapshot($owner,$v['customerId'])]);query('UPDATE kr_documents SET data=?,total=?,version=version+1 WHERE owner=? AND id=?',[encode($v),total($v['lines']),$owner,$id]);return ['ok'=>true];}
 if($action==='issue'){if($d['status']!=='draft')fail('Beleg ist bereits ausgestellt.');query("UPDATE kr_documents SET status='issued',version=version+1 WHERE owner=? AND id=?",[$owner,$id]);return ['ok'=>true];}
 if($action==='delete'){if($d['status']!=='draft')fail('Nur Entwürfe können gelöscht werden.');query('DELETE FROM kr_documents WHERE owner=? AND id=?',[$owner,$id]);return ['ok'=>true];}
 if($action==='accept'){if($d['kind']!=='offer'||$d['status']!=='issued')fail('Nur ausgestellte Offerten können angenommen werden.');query("UPDATE kr_documents SET status='accepted',version=version+1 WHERE owner=? AND id=?",[$owner,$id]);return ['ok'=>true];}
 if($action==='convert'){if($d['kind']!=='offer'||$d['status']!=='accepted')fail('Bitte die Offerte zuerst annehmen.');if(one('SELECT id FROM kr_documents WHERE owner=? AND source_offer=?',[$owner,$id]))fail('Für diese Offerte existiert bereits eine Rechnung.',409);$v=array_merge($d['data'],['kind'=>'invoice','documentTitle'=>'Rechnung','sourceId'=>$id,'sourceNumber'=>$d['number'],'date'=>date('Y-m-d'),'due'=>date('Y-m-d',strtotime('+'.$d['data']['company']['terms'].' days'))]);return add_document($owner,'invoice',$v);}
 if($action==='pay'){if($d['kind']!=='invoice'||$d['status']==='draft')fail('Nur ausgestellte Rechnungen können bezahlt werden.');$amount=integer($b['amount']??null,1,$d['total']-$d['paid']);$date=day($b['date']??'');if($date>date('Y-m-d'))fail('Zahlungsdatum darf nicht in der Zukunft liegen.');query('UPDATE kr_documents SET paid=paid+?,version=version+1 WHERE owner=? AND id=?',[$amount,$owner,$id]);query('INSERT INTO kr_payments(id,owner,document_id,amount,date) VALUES(?,?,?,?,?)',[id(),$owner,$id,$amount,$date]);return ['ok'=>true];}
 if($action==='reminder'){if($d['kind']!=='invoice'||$d['status']==='draft'||$d['paid']>=$d['total']||$d['data']['due']>=date('Y-m-d'))fail('Erinnerungen sind für überfällige, offene Rechnungen verfügbar.');$kind=$b['kind']??'';if(!in_array($kind,['reminder','dunning'],true))fail('Ungültiger Belegtyp.');$due=day($b['due']??'');if($due<=date('Y-m-d'))fail('Bitte ein zukünftiges Zahlungsziel wählen.');$v=array_merge($d['data'],['date'=>date('Y-m-d'),'due'=>$due,'sourceId'=>$id,'sourceNumber'=>$d['number'],'outstanding'=>$d['total']-$d['paid'],'paidAtCreation'=>$d['paid'],'notes'=>text($b['notes']??'',4000,true)]);return add_document($owner,$kind,$v,'issued');}
 fail('Aktion nicht verfügbar.');
}
