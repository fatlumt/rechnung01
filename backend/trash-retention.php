<?php
declare(strict_types=1);
// All purge operations run under the same per-company lock as ordinary saves.
function trash_retention_schema():void {
 static $done=false;if($done)return;
 query('CREATE TABLE IF NOT EXISTS kr_trash_deadlines (owner CHAR(36) NOT NULL,kind VARCHAR(12) NOT NULL,item_id CHAR(36) NOT NULL,expires DATETIME NOT NULL,PRIMARY KEY(owner,kind,item_id),INDEX(expires)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');$done=true;
}
function trash_schedule(string $owner,string $kind,string $id):void {
 query('INSERT IGNORE INTO kr_trash_deadlines(owner,kind,item_id,expires) VALUES(?,?,?,?)',[$owner,$kind,$id,date('Y-m-d H:i:s',time()+14*86400)]);
}
function trash_forget_deadline(string $owner,string $kind,string $id):void {query('DELETE FROM kr_trash_deadlines WHERE owner=? AND kind=? AND item_id=?',[$owner,$kind,$id]);}
function trash_seed_deadlines(string $owner):void {
 // Existing trash receives a full grace period when this policy is introduced.
 foreach(['document'=>'kr_document_trash','customer'=>'kr_customer_trash'] as $kind=>$table){$column=$kind.'_id';query("INSERT IGNORE INTO kr_trash_deadlines(owner,kind,item_id,expires) SELECT owner,?,$column,? FROM $table WHERE owner=?",[$kind,date('Y-m-d H:i:s',time()+14*86400),$owner]);}
}
function trash_purge_customer(string $owner,string $id):void {
 if(!one('SELECT customer_id FROM kr_customer_trash WHERE owner=? AND customer_id=?',[$owner,$id]))fail('Eintrag ist nicht im Papierkorb.',409);
 $c=one('SELECT data FROM kr_customers WHERE owner=? AND id=?',[$owner,$id]);
 // Projects keep their own historical contact snapshot, like issued documents.
 if($c)foreach(query('SELECT id,data FROM kr_projects WHERE owner=?',[$owner])->fetchAll() as $p){$v=json_decode($p['data'],true);if(($v['customerId']??'')!==$id)continue;$v['customerSnapshot']=json_decode($c['data'],true);query('UPDATE kr_projects SET data=?,version=version+1 WHERE owner=? AND id=?',[encode($v),$owner,$p['id']]);}
 query('DELETE FROM kr_customers WHERE owner=? AND id=?',[$owner,$id]);
 query('DELETE FROM kr_customer_trash WHERE owner=? AND customer_id=?',[$owner,$id]);trash_forget_deadline($owner,'customer',$id);
}
function trash_document_blocked(string $owner,string $id):bool {
 return (bool)one('SELECT l.final_id FROM kr_deposit_links l INNER JOIN kr_documents d ON d.owner=l.owner AND d.id=l.final_id WHERE l.owner=? AND l.deposit_id=?',[$owner,$id]);
}
function trash_purge_document(string $owner,string $id):void {
 if(!document_is_trashed($owner,$id))fail('Eintrag ist nicht im Papierkorb.',409);
 if(trash_document_blocked($owner,$id))fail('Akontobeleg ist mit einer Schlussrechnung verknüpft. Bitte zuerst die Schlussrechnung entfernen oder die Verknüpfung lösen.',409);
 foreach(['kr_payments','kr_dunning_fees','kr_project_documents','kr_mail_log','kr_document_archive','kr_document_trash'] as $table)query("DELETE FROM $table WHERE owner=? AND document_id=?",[$owner,$id]);
 query('DELETE FROM kr_deposit_links WHERE owner=? AND final_id=?',[$owner,$id]);
 query('DELETE FROM kr_documents WHERE owner=? AND id=?',[$owner,$id]);trash_forget_deadline($owner,'document',$id);
 // Other documents retain their immutable source snapshots and source number.
}
function trash_purge_batch(string $owner,string $kind,array $ids):array {
 $deleted=0;$blocked=[];
 // Final invoices first, so deposits can be removed in the same batch.
 if($kind==='document')usort($ids,fn($a,$b)=>(int)trash_document_blocked($owner,$a)<=>(int)trash_document_blocked($owner,$b));
 foreach($ids as $id){if($kind==='document'&&trash_document_blocked($owner,$id)){$blocked[]=$id;continue;}if($kind==='document')trash_purge_document($owner,$id);else trash_purge_customer($owner,$id);$deleted++;}
 return ['ok'=>true,'deleted'=>$deleted,'blocked'=>count($blocked)];
}
function trash_action(string $owner,array $b):array {
 if(($b['confirmPermanent']??false)!==true)fail('Endgültiges Löschen muss bestätigt werden.',409);
 $kind=$b['trashKind']??'';if(!in_array($kind,['document','customer'],true))fail('Ungültiger Papierkorb.');
 $ids=[];
 if(($b['action']??'')==='trashPurge'){
  $id=text($b['id']??'',36,true);$ids=[$id];
  if($kind==='document'){$r=one('SELECT version FROM kr_documents WHERE owner=? AND id=?',[$owner,$id]);if(!$r)fail('Beleg nicht gefunden.',404);if(integer($b['version']??null)!==(int)$r['version'])fail('Beleg wurde inzwischen geändert. Bitte neu laden.',409);}
 }else{
  // Explicit IDs make the confirmed set stable: newly trashed items are never swept in.
  if(!is_array($b['ids']??null)||count($b['ids'])>2000)fail('Ungültige Auswahl.');
  $ids=array_values(array_unique(array_map(fn($id)=>text($id,36,true),$b['ids'])));
 }
 $table=$kind==='document'?'kr_document_trash':'kr_customer_trash';$column=$kind.'_id';
 foreach($ids as $id)if(!one("SELECT $column FROM $table WHERE owner=? AND $column=?",[$owner,$id]))fail('Papierkorb wurde geändert. Bitte neu laden.',409);
 return trash_purge_batch($owner,$kind,$ids);
}
function trash_cleanup_owner(string $owner):void {
 trash_retention_schema();db()->beginTransaction();
 try{one('SELECT id FROM kr_users WHERE id=? FOR UPDATE',[$owner]);trash_seed_deadlines($owner);
 foreach(['document','customer'] as $kind){$rows=query('SELECT item_id FROM kr_trash_deadlines WHERE owner=? AND kind=? AND expires<=? ORDER BY expires LIMIT 200',[$owner,$kind,date('Y-m-d H:i:s')])->fetchAll();$table=$kind==='document'?'kr_document_trash':'kr_customer_trash';$column=$kind.'_id';$ids=[];foreach($rows as $r){if(one("SELECT $column FROM $table WHERE owner=? AND $column=?",[$owner,$r['item_id']]))$ids[]=$r['item_id'];else trash_forget_deadline($owner,$kind,$r['item_id']);}trash_purge_batch($owner,$kind,$ids);}
 db()->commit();}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
}
function trash_cleanup_all():void {
 document_archive_schema();business_schema();projects_schema();mail_schema();trash_retention_schema();
 foreach(query('SELECT owner FROM kr_document_trash UNION SELECT owner FROM kr_customer_trash')->fetchAll() as $r)trash_cleanup_owner($r['owner']);
}
