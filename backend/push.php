<?php
declare(strict_types=1);
require_once __DIR__.'/document-trash.php';
function push_schema():void {
 document_trash_schema();
 static $done=false;if($done)return;
 query('CREATE TABLE IF NOT EXISTS kr_push_config (id TINYINT PRIMARY KEY,data TEXT NOT NULL,last_worker BIGINT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
 query('CREATE TABLE IF NOT EXISTS kr_push_devices (id CHAR(36) PRIMARY KEY,owner CHAR(36) NOT NULL,device CHAR(64) NOT NULL,endpoint_hash CHAR(64) NOT NULL UNIQUE,subscription TEXT NOT NULL,language VARCHAR(2) NOT NULL,auth_version INT NOT NULL,created BIGINT NOT NULL,INDEX(owner)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
 query('CREATE TABLE IF NOT EXISTS kr_push_events (event_key CHAR(64) PRIMARY KEY,owner CHAR(36) NOT NULL,kind VARCHAR(16) NOT NULL,target CHAR(36) NOT NULL,created BIGINT NOT NULL,INDEX(owner,created)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
 query('CREATE TABLE IF NOT EXISTS kr_push_deliveries (event_key CHAR(64) NOT NULL,device_id CHAR(36) NOT NULL,status VARCHAR(16) NOT NULL,attempts INT NOT NULL DEFAULT 0,next_try BIGINT NOT NULL,PRIMARY KEY(event_key,device_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');$done=true;
}
function push_library():void {require_once 'phar://'.__DIR__.'/vendor/webpush.phar/autoload.php';}
function push_keys():array {
 push_schema();$r=one('SELECT data FROM kr_push_config WHERE id=1');if($r)return json_decode($r['data'],true,16,JSON_THROW_ON_ERROR);
 push_library();$keys=\Minishlink\WebPush\VAPID::createVapidKeys();query('INSERT IGNORE INTO kr_push_config(id,data) VALUES(1,?)',[encode($keys)]);return json_decode(one('SELECT data FROM kr_push_config WHERE id=1')['data'],true,16,JSON_THROW_ON_ERROR);
}
function push_device(bool $create=false):string {
 $v=$_COOKIE[device_cookie_name('push_device')]??'';if(is_string($v)&&preg_match('/^[a-f0-9]{64}$/D',$v))return $v;
 if(!$create)return ''; $v=bin2hex(random_bytes(32));device_cookie('push_device',$v,time()+365*86400);$_COOKIE[device_cookie_name('push_device')]=$v;return $v;
}
function push_subscription(mixed $v):array {
 if(!is_array($v))fail('Ungültige Push-Anmeldung.');$endpoint=text($v['endpoint']??'',2048,true);$url=parse_url($endpoint);$host=strtolower($url['host']??'');
 // Only browser-vendor push services. Never let a supplied endpoint address our server/network.
 $allowed=$host==='fcm.googleapis.com'||$host==='updates.push.services.mozilla.com'||$host==='web.push.apple.com'||str_ends_with($host,'.push.apple.com')||$host==='wns.windows.com'||str_ends_with($host,'.wns.windows.com');
 if(!$allowed||($url['scheme']??'')!=='https'||isset($url['user'])||isset($url['pass'])||isset($url['port'])||isset($url['fragment']))fail('Ungültiger Push-Dienst.');
 $keys=$v['keys']??[];$out=[];foreach(['p256dh'=>65,'auth'=>16] as $k=>$length){$s=text($keys[$k]??'',120,true);if(!preg_match('/^[A-Za-z0-9_-]+={0,2}$/D',$s))fail('Ungültiger Push-Schlüssel.');$b=base64_decode(strtr($s,'-_','+/'),true);if($b===false||strlen($b)!==$length||($k==='p256dh'&&ord($b[0])!==4))fail('Ungültiger Push-Schlüssel.');$out[$k]=$s;}
 return ['endpoint'=>$endpoint,'keys'=>$out,'contentEncoding'=>'aes128gcm'];
}
function push_get(array $u):array {push_schema();$device=push_device();$r=$device?one('SELECT id FROM kr_push_devices WHERE owner=? AND device=? AND auth_version=?',[$u['id'],$device,(int)one('SELECT auth_version FROM kr_users WHERE id=?',[$u['id']])['auth_version']]):null;$config=one('SELECT last_worker FROM kr_push_config WHERE id=1');return ['enabled'=>(bool)$r,'publicKey'=>push_keys()['publicKey'],'workerRecent'=>!empty($config['last_worker'])&&(int)$config['last_worker']>time()-1800,'trusted'=>trusted_status($u)];}
function push_post(array $u,array $b):array {
 push_schema();$action=$b['action']??'';
 if($action==='trust'){if(($b['enabled']??false)===true)trusted_enable($u);else trusted_forget();return ['trusted'=>trusted_status($u)];}
 if($action==='unsubscribe'){query('DELETE FROM kr_push_devices WHERE owner=? AND device=?',[$u['id'],push_device()]);return ['ok'=>true];}
 if($action==='subscribe'){
  limit_attempts('push-subscribe',$u['id'],20);$s=push_subscription($b['subscription']??null);$hash=hash('sha256',$s['endpoint']);$device=push_device(true);$old=one('SELECT owner,device FROM kr_push_devices WHERE endpoint_hash=?',[$hash]);if($old&&($old['owner']!==$u['id']||$old['device']!==$device))fail('Dieses Gerät ist bereits mit einem anderen Konto verbunden. Bitte dort zuerst Benachrichtigungen deaktivieren.',409);
  if(!$old&&(int)one('SELECT COUNT(*) AS n FROM kr_push_devices WHERE owner=?',[$u['id']])['n']>=10)fail('Maximal zehn Geräte. Bitte alte Geräte abmelden.');
  $language=document_language($b['language']??'de');$version=(int)one('SELECT auth_version FROM kr_users WHERE id=?',[$u['id']])['auth_version'];
  query('DELETE FROM kr_push_devices WHERE owner=? AND device=? AND endpoint_hash<>?',[$u['id'],$device,$hash]);
  query('INSERT INTO kr_push_devices(id,owner,device,endpoint_hash,subscription,language,auth_version,created) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE subscription=VALUES(subscription),language=VALUES(language),auth_version=VALUES(auth_version)',[id(),$u['id'],$device,$hash,encode($s),$language,$version,time()]);return ['ok'=>true];
 }
 if($action==='test'){
  limit_attempts('push-test',$u['id'],5);$d=one('SELECT id FROM kr_push_devices WHERE owner=? AND device=?',[$u['id'],push_device()]);if(!$d)fail('Bitte zuerst Benachrichtigungen auf diesem Gerät aktivieren.');
  $key=push_enqueue($u['id'],'test','',id(),$d['id']);push_dispatch($key);$r=one('SELECT status FROM kr_push_deliveries WHERE event_key=? AND device_id=?',[$key,$d['id']]);if(($r['status']??'')!=='accepted')fail('Test konnte nicht bestätigt werden. Bitte erneut versuchen.',503);return ['ok'=>true,'status'=>'accepted'];
 }
 fail('Aktion nicht verfügbar.');
}
function push_enqueue(string $owner,string $kind,string $target,string $unique,?string $deviceId=null):string {
 push_schema();$key=hash('sha256',$owner.'|'.$kind.'|'.$unique);$insert=query('INSERT IGNORE INTO kr_push_events(event_key,owner,kind,target,created) VALUES(?,?,?,?,?)',[$key,$owner,$kind,$target,time()]);if(!$insert->rowCount())return $key;
 query("INSERT IGNORE INTO kr_push_deliveries(event_key,device_id,status,next_try) SELECT ?,d.id,'pending',? FROM kr_push_devices d JOIN kr_users u ON u.id=d.owner LEFT JOIN kr_user_access a ON a.user_id=u.id WHERE d.owner=? AND d.auth_version=u.auth_version AND COALESCE(a.state,'active')='active'".($deviceId?' AND d.id=?':''),$deviceId?[$key,time(),$owner,$deviceId]:[$key,time(),$owner]);return $key;
}
function push_event_active(array $r):bool {
 $f=features_for($r['owner']);if($r['kind']==='mail')return $f['email']&&(bool)one('SELECT id FROM kr_mail_notifications WHERE owner=? AND id=? AND read_at IS NULL',[$r['owner'],$r['target']]);
 if($r['kind']==='overdue'){if(!$f['invoices']||document_is_trashed($r['owner'],$r['target']))return false;$d=one("SELECT d.*,COALESCE(f.amount,0) AS fee FROM kr_documents d LEFT JOIN kr_dunning_fees f ON f.document_id=d.id AND f.owner=d.owner WHERE d.owner=? AND d.id=?",[$r['owner'],$r['target']]);$v=$d?json_decode($d['data'],true):[];return $d&&$d['kind']==='invoice'&&$d['status']!=='draft'&&(int)$d['total']+(int)$d['fee']>(int)$d['paid']&&($v['due']??'9999')<date('Y-m-d');}return $r['kind']==='test';
}
function push_payload(array $r):string {
 $text=match($r['kind']){'overdue'=>'Eine Rechnung ist überfällig. Tippen zum Öffnen.','mail'=>'Ein E-Mail-Versand konnte nicht bestätigt werden. Tippen zum Prüfen.',default=>'Test erfolgreich. Tippen, um DigitalRechnung zu öffnen.'};
 $target=$r['kind']==='overdue'?'document='.$r['target']:($r['kind']==='mail'?'notifications=1':'push-settings=1');
 return encode(['title'=>'DigitalRechnung','body'=>translate_message($r['language'],$text),'url'=>'./?'.$target,'tag'=>$r['event_key']]);
}
function push_dispatch(?string $only=null):void {
 push_schema();push_library();$keys=push_keys();$web=new \Minishlink\WebPush\WebPush(['VAPID'=>['subject'=>'mailto:'.(config()['mail_from']??'info@krasniqi-plattenleger.ch'),'publicKey'=>$keys['publicKey'],'privateKey'=>$keys['privateKey']]],['TTL'=>3600],10,['allow_redirects'=>false]);
 $rows=query("SELECT e.*,s.id AS device_id,s.subscription,s.language,d.attempts FROM kr_push_deliveries d JOIN kr_push_events e ON e.event_key=d.event_key JOIN kr_push_devices s ON s.id=d.device_id AND s.owner=e.owner JOIN kr_users u ON u.id=s.owner LEFT JOIN kr_user_access a ON a.user_id=u.id WHERE d.status='pending' AND d.next_try<=? AND e.created>? AND d.attempts<3 AND s.auth_version=u.auth_version AND COALESCE(a.state,'active')='active'".($only?' AND e.event_key=?':'').' ORDER BY e.created LIMIT 50',$only?[time(),time()-86400,$only]:[time(),time()-86400])->fetchAll();
 foreach($rows as $r){if(!push_event_active($r)){query("UPDATE kr_push_deliveries SET status='cancelled' WHERE event_key=? AND device_id=?",[$r['event_key'],$r['device_id']]);continue;}
  $claim=query("UPDATE kr_push_deliveries SET attempts=attempts+1,next_try=? WHERE event_key=? AND device_id=? AND status='pending' AND attempts=? AND next_try<=?",[time()+300,$r['event_key'],$r['device_id'],$r['attempts'],time()]);if(!$claim->rowCount())continue;
  try{$s=push_subscription(json_decode($r['subscription'],true));$report=$web->sendOneNotification(\Minishlink\WebPush\Subscription::create($s),push_payload($r));if($report->isSubscriptionExpired())query('DELETE FROM kr_push_devices WHERE id=?',[$r['device_id']]);$status=$report->isSuccess()?'accepted':($report->isSubscriptionExpired()||$r['attempts']>=2?'failed':'pending');}catch(Throwable $e){$status=$r['attempts']>=2?'failed':'pending';error_log('Push send failed: '.get_class($e));}
  query('UPDATE kr_push_deliveries SET status=? WHERE event_key=? AND device_id=?',[$status,$r['event_key'],$r['device_id']]);
 }
}
function push_scan():void {
 push_schema();mail_notifications_schema();features_schema();
 foreach(query('SELECT DISTINCT owner FROM kr_push_devices')->fetchAll() as $r){$owner=$r['owner'];$f=features_for($owner);
  if($f['email'])foreach(query('SELECT id FROM kr_mail_notifications WHERE owner=? AND read_at IS NULL AND created>=? ORDER BY created DESC LIMIT 100',[$owner,date('Y-m-d H:i:s',time()-86400)])->fetchAll() as $n)push_enqueue($owner,'mail',$n['id'],$n['id']);
  // One notification per overdue invoice per day, during daytime in Switzerland.
  if($f['invoices']&&(int)date('G')>=9&&(int)date('G')<18)foreach(query("SELECT d.id,d.data FROM kr_documents d LEFT JOIN kr_dunning_fees f ON f.document_id=d.id AND f.owner=d.owner WHERE d.owner=? AND NOT EXISTS (SELECT 1 FROM kr_document_trash t WHERE t.owner=d.owner AND t.document_id=d.id) AND d.kind='invoice' AND d.status<>'draft' AND d.total+COALESCE(f.amount,0)>d.paid",[$owner])->fetchAll() as $d){$v=json_decode($d['data'],true);if(($v['due']??'9999')<date('Y-m-d'))push_enqueue($owner,'overdue',$d['id'],$d['id'].'|'.date('Y-m-d'));}
 }
 push_dispatch();query('UPDATE kr_push_config SET last_worker=? WHERE id=1',[time()]);query('DELETE FROM kr_push_deliveries WHERE event_key IN (SELECT event_key FROM kr_push_events WHERE created<?)',[time()-30*86400]);query('DELETE FROM kr_push_events WHERE created<?',[time()-30*86400]);
}
