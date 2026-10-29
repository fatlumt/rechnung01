<?php
declare(strict_types=1);
function projects_schema():void {
 query('CREATE TABLE IF NOT EXISTS kr_projects (id CHAR(36) PRIMARY KEY,owner CHAR(36) NOT NULL,data TEXT NOT NULL,version INT NOT NULL DEFAULT 1,created DATETIME NOT NULL,INDEX(owner)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
 query('CREATE TABLE IF NOT EXISTS kr_project_documents (document_id CHAR(36) PRIMARY KEY,owner CHAR(36) NOT NULL,project_id CHAR(36) NOT NULL,INDEX(owner,project_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
 query('CREATE TABLE IF NOT EXISTS kr_project_items (id CHAR(36) PRIMARY KEY,owner CHAR(36) NOT NULL,project_id CHAR(36) NOT NULL,data TEXT NOT NULL,payload MEDIUMTEXT NULL,bytes INT NOT NULL DEFAULT 0,version INT NOT NULL DEFAULT 1,created DATETIME NOT NULL,INDEX(owner,project_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}
function project_record(string $owner,string $id):array {
 $r=one('SELECT * FROM kr_projects WHERE owner=? AND id=?',[$owner,$id]);if(!$r)fail('Projekt nicht gefunden.',404);return decode_row($r);
}
function projects_list(string $owner):array {return array_map('decode_row',query('SELECT * FROM kr_projects WHERE owner=? ORDER BY created DESC,id',[$owner])->fetchAll());}
function project_customer(string $owner,string $customerId):void {if(!one('SELECT id FROM kr_customers WHERE owner=? AND id=?',[$owner,$customerId]))fail('Kunde nicht gefunden.',404);}
// Project membership is separate from the immutable issued document snapshot.
function project_document_link(string $owner,string $documentId,string $projectId,string $customerId):void {
 if($projectId!==''){$p=project_record($owner,$projectId);if($p['data']['customerId']!==$customerId)fail('Projekt und Beleg müssen zum selben Kunden gehören.');}
 query('DELETE FROM kr_project_documents WHERE owner=? AND document_id=?',[$owner,$documentId]);
 if($projectId!=='')query('INSERT INTO kr_project_documents(document_id,owner,project_id) VALUES(?,?,?)',[$documentId,$owner,$projectId]);
}
function project_attach_new(string $owner,string $documentId,array $v):void {
 // Follow-ups inherit the current folder of their source, including later reassignments.
 $projectId=$v['projectId']??'';
 if(!empty($v['sourceInvoice'])&&!empty($v['sourceId'])){$link=one('SELECT project_id FROM kr_project_documents WHERE owner=? AND document_id=?',[$owner,$v['sourceId']]);$projectId=$link['project_id']??'';}
 if($projectId!=='')project_document_link($owner,$documentId,$projectId,$v['customerId']);
}
function project_file(mixed $value,string $name):array {
 if(!is_string($value)||strlen($value)>6666800||!preg_match('~^data:([a-zA-Z0-9.+/-]+);base64,([A-Za-z0-9+/=]+)$~D',$value,$m))fail('Datei ungültig oder zu gross (max. 5 MB).');
 $bytes=base64_decode($m[2],true);if($bytes===false||strlen($bytes)===0||strlen($bytes)>5000000)fail('Datei ungültig oder zu gross (max. 5 MB).');
 $extension=strtolower(pathinfo($name,PATHINFO_EXTENSION));$mime='';
 if(in_array($extension,['jpg','jpeg','png','webp'],true)){$info=@getimagesizefromstring($bytes);$expected=['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'][$extension];if(!$info||$info['mime']!==$expected||$info[0]*$info[1]>40000000)fail('Bild ungültig oder zu gross.');$mime=$expected;}
 elseif($extension==='pdf'){if(!str_starts_with($bytes,'%PDF-'))fail('PDF ungültig.');$mime='application/pdf';}
 elseif(in_array($extension,['txt','csv'],true)){if(!mb_check_encoding($bytes,'UTF-8')||str_contains($bytes,"\0"))fail('Textdatei muss UTF-8 verwenden.');$mime=$extension==='csv'?'text/csv':'text/plain';}
 elseif(in_array($extension,['docx','xlsx'],true)){if(!str_starts_with($bytes,"PK\x03\x04"))fail('Office-Datei ungültig.');$mime='application/octet-stream';}
 else fail('Erlaubt: PDF, JPG, PNG, WebP, TXT, CSV, DOCX und XLSX.');
 return ['payload'=>'data:'.$mime.';base64,'.base64_encode($bytes),'bytes'=>strlen($bytes),'mime'=>$mime];
}
function projects_change(string $owner,array $b):array {
 require_feature($owner,'projects');$action=$b['action'];
 if($action==='projectSave'){
  $v=$b['data']??[];if(!is_array($v))fail('Ungültige Anfrage.');$old=!empty($b['id'])?project_record($owner,text($b['id'],36,true)):null;
  if($old&&integer($b['version']??null)!==$old['version'])fail('Projekt wurde geändert. Bitte neu laden.',409);
  $v=['name'=>text($v['name']??'',160,true),'customerId'=>text($v['customerId']??'',36,true),'address'=>text($v['address']??'',500),'description'=>text($v['description']??'',4000),'status'=>text($v['status']??'active',20,true)];
  if(!in_array($v['status'],['planned','active','completed','archived'],true))fail('Ungültiger Status.');if(!$old)project_customer($owner,$v['customerId']);if($old&&isset($old['data']['customerSnapshot']))$v['customerSnapshot']=$old['data']['customerSnapshot'];
  // Never move an existing client's files and notes to another client by accident.
  if($old&&$old['data']['customerId']!==$v['customerId'])fail('Der Kunde eines bestehenden Projekts kann nicht geändert werden.');
  return hours_write($owner,'kr_projects',$v,$old);
 }
 $projectId=text($b['projectId']??'',36,true);$project=project_record($owner,$projectId);
 if($action==='projectGet')return ['items'=>array_map('decode_row',query('SELECT id,data,version,created FROM kr_project_items WHERE owner=? AND project_id=? ORDER BY created DESC,id',[$owner,$projectId])->fetchAll())];
 if($action==='projectLink'){
  $documentId=text($b['documentId']??'',36,true);$d=one('SELECT * FROM kr_documents WHERE owner=? AND id=?',[$owner,$documentId]);if(!$d)fail('Beleg nicht gefunden.',404);require_feature($owner,feature_kind($d['kind']));
  if(integer($b['version']??null)!==(int)$d['version'])fail('Beleg wurde inzwischen geändert. Bitte neu laden.',409);
  $current=one('SELECT project_id FROM kr_project_documents WHERE owner=? AND document_id=?',[$owner,$documentId]);
  if(($b['unlink']??false)===true){if(($current['project_id']??'')!==$projectId)fail('Zuordnung wurde geändert. Bitte neu laden.',409);project_document_link($owner,$documentId,'',decode_row($d)['data']['customerId']);}
  else {if($current&&$current['project_id']!==$projectId)fail('Beleg ist bereits einem anderen Projekt zugeordnet.');project_document_link($owner,$documentId,$projectId,decode_row($d)['data']['customerId']);}
  query('UPDATE kr_documents SET version=version+1 WHERE owner=? AND id=?',[$owner,$documentId]);return ['ok'=>true];
 }
 if($action==='projectItemSave'){
  $v=$b['data']??[];if(!is_array($v))fail('Ungültige Anfrage.');$kind=$v['kind']??'';if(!in_array($kind,['note','file'],true))fail('Ungültiger Dateityp.');
  $old=!empty($b['id'])?one('SELECT id,data,version FROM kr_project_items WHERE owner=? AND project_id=? AND id=?',[$owner,$projectId,text($b['id'],36,true)]):null;
  if(!empty($b['id'])&&!$old)fail('Eintrag nicht gefunden.',404);if($old&&integer($b['version']??null)!==(int)$old['version'])fail('Eintrag wurde geändert. Bitte neu laden.',409);
  if($old&&($kind!=='note'||decode_row($old)['data']['kind']!=='note'))fail('Dateien können nicht überschrieben werden.');
  $d=['kind'=>$kind,'title'=>text($v['title']??'',180,true),'text'=>text($v['text']??'',8000)];$payload=null;$size=0;
  if($kind==='note'&&$d['text']==='')fail('Bitte eine Notiz eingeben.');
  if($kind==='file'){$name=text($b['name']??'',180,true);$name=preg_replace('~[\\\\/\x00-\x1f\x7f]~u','_',$name);$file=project_file($b['file']??null,$name);$payload=$file['payload'];$size=$file['bytes'];$d+=['name'=>$name,'mime'=>$file['mime'],'bytes'=>$size];
   $used=(int)(one('SELECT COALESCE(SUM(bytes),0) AS used FROM kr_project_items WHERE owner=?',[$owner])['used']??0);if($used+$size>100000000)fail('Projektspeicher voll (100 MB pro Firma).');
  }
  $itemId=$old['id']??id();if($old)query('UPDATE kr_project_items SET data=?,version=version+1 WHERE owner=? AND project_id=? AND id=?',[encode($d),$owner,$projectId,$itemId]);
  else query('INSERT INTO kr_project_items(id,owner,project_id,data,payload,bytes,created) VALUES(?,?,?,?,?,?,?)',[$itemId,$owner,$projectId,encode($d),$payload,$size,date('Y-m-d H:i:s')]);return ['id'=>$itemId];
 }
 if($action==='projectFile'){$r=one('SELECT data,payload FROM kr_project_items WHERE owner=? AND project_id=? AND id=?',[$owner,$projectId,text($b['id']??'',36,true)]);if(!$r||!$r['payload'])fail('Datei nicht gefunden.',404);return ['file'=>$r['payload'],'name'=>decode_row($r)['data']['name']];}
 if($action==='projectItemDelete'){$r=one('SELECT version FROM kr_project_items WHERE owner=? AND project_id=? AND id=?',[$owner,$projectId,text($b['id']??'',36,true)]);if(!$r)fail('Eintrag nicht gefunden.',404);if(integer($b['version']??null)!==(int)$r['version'])fail('Eintrag wurde geändert. Bitte neu laden.',409);query('DELETE FROM kr_project_items WHERE owner=? AND project_id=? AND id=?',[$owner,$projectId,$b['id']]);return ['ok'=>true];}
 fail('Aktion nicht verfügbar.');
}

function project_work_fields(string $owner,array $raw,?array $old=null):array {
 $projectId=text($raw['projectId']??'',36);
 if($projectId==='')return ['projectId'=>''];
 if(!features_for($owner)['projects']){if(($old['projectId']??'')===$projectId)return ['projectId'=>$projectId,'project'=>$old['project']];fail('Diese Funktion ist für Ihr Konto nicht freigeschaltet.',403);}
 $p=project_record($owner,$projectId);
 return ['projectId'=>$projectId,'project'=>$p['data']['name']];
}
