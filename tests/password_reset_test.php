<?php
// Offline contract test: execute production reset logic against SQLite.
// Strip MySQL locking/DDL suffixes only; capture mail instead of using SMTP.
declare(strict_types=1);
date_default_timezone_set('Europe/Zurich');
final class AppError extends RuntimeException {public function __construct(string $message,public int $status=400){parent::__construct($message);}}
function fail(string $message,int $status=400):never{throw new AppError($message,$status);}
$pdo=new PDO('sqlite::memory:');$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
function db():PDO{global $pdo;return $pdo;}
function query(string $sql,array $args=[]):PDOStatement{$sql=str_replace([' FOR UPDATE',' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'],'',$sql);$s=db()->prepare($sql);$s->execute($args);return $s;}
function one(string $sql,array $args=[]):?array{return query($sql,$args)->fetch()?:null;}
$settings=['app_url'=>'https://example.test/app'];function config():array{global $settings;return $settings;}
$buckets=[];function limit_attempts(string $scope,string $key,int $max):void{global $buckets;$key=$scope.'|'.$key;$buckets[$key]=($buckets[$key]??0)+1;if($buckets[$key]>$max)fail('Rate limited',429);}
function invitation_email(mixed $email):string{if(!is_string($email)||!filter_var(trim($email),FILTER_VALIDATE_EMAIL))fail('Bad email');return strtolower(trim($email));}
function document_language(mixed $language):string{if(!in_array($language,['de','en','sq'],true))fail('Bad language');return $language;}
function password_value(mixed $password):string{if(!is_string($password)||strlen($password)<12||strlen($password)>72)fail('Bad password');return $password;}
function mail_ready():bool{return true;}
function security_event(string $event,?string $actor=null):void{}
function reset_session():void{$_SESSION=['csrf'=>bin2hex(random_bytes(32))];}
function translate_message(string $language,string $source):string{static $catalog;if(!$catalog)$catalog=json_decode(file_get_contents(__DIR__.'/../backend/translation-catalog.json'),true);return $catalog['messages'][$source][$language]??$source;}
$mail=[];function password_reset_mail(string $email,string $language,string $subject,string $message):void{global $mail;$mail[]=compact('email','language','subject','message');}
$code=file_get_contents(__DIR__.'/../backend/password-reset.php');$start=strpos($code,'function password_reset_mail(');$end=strpos($code,'function password_reset_request(');$code=substr($code,0,$start).substr($code,$end);eval(substr($code,5));
query('CREATE TABLE kr_bootstrap(id INTEGER PRIMARY KEY,complete INTEGER)');query('INSERT INTO kr_bootstrap VALUES(1,1)');
query('CREATE TABLE kr_users(id TEXT PRIMARY KEY,email TEXT,auth_version INTEGER,password_hash TEXT)');query('CREATE TABLE kr_user_access(user_id TEXT PRIMARY KEY,state TEXT)');
$old='OldPasswordForTest!';foreach(['active','disabled','pending','other'] as $id){query('INSERT INTO kr_users VALUES(?,?,1,?)',[$id,$id.'@example.test',password_hash($old,PASSWORD_DEFAULT)]);query('INSERT INTO kr_user_access VALUES(?,?)',[$id,in_array($id,['active','other'])?'active':$id]);}
$checks=0;function check(bool $ok,string $label):void{global $checks;if(!$ok)throw new RuntimeException($label);$checks++;}
function rejects(callable $call,int $status=400):void{try{$call();throw new RuntimeException('Unexpected success');}catch(AppError $e){check($e->status===$status,'Expected rejection');if(db()->inTransaction())db()->rollBack();}}
function request_link(string $id,string $language='en'):string{global $mail,$buckets;$buckets=[];$response=password_reset_request(['email'=>$id.'@example.test','language'=>$language]);check($response===['ok'=>true],'Generic response');$before=count($mail);$job=$GLOBALS['after_auth_response'];unset($GLOBALS['after_auth_response']);$job();if(count($mail)===$before)return '';preg_match('/#reset=([a-f0-9]{64})/',$mail[array_key_last($mail)]['message'],$m);return $m[1]??'';}
$token=request_link('active','sq');check(strlen($token)===64,'256-bit token');$row=one('SELECT * FROM kr_password_resets WHERE user_id=?',['active']);check($row['token_hash']===hash('sha256',$token)&&$row['token_hash']!==$token,'Only digest stored');check(abs((int)$row['expires']-time()-1800)<3,'30 minute expiry');check($mail[0]['language']==='sq'&&str_contains($mail[0]['message'],'fjalëkalim'),'Localized mail');
check(request_link('unknown')==='','Unknown account sends nothing');check(request_link('disabled')==='','Disabled account sends nothing');check(request_link('pending')==='','Pending account sends nothing');
$new='NewPasswordForTest!';$body=['token'=>$token,'password'=>$new,'confirm'=>$new,'language'=>'en'];
rejects(fn()=>password_reset_complete(array_merge($body,['confirm'=>'different'])));rejects(fn()=>password_reset_complete(array_merge($body,['password'=>'short','confirm'=>'short'])));rejects(fn()=>password_reset_complete(array_merge($body,['token'=>'bad'])));
$latest=request_link('active');rejects(fn()=>password_reset_complete($body));$body['token']=$latest;
query('UPDATE kr_password_resets SET expires=? WHERE user_id=?',[time()-1,'active']);rejects(fn()=>password_reset_complete($body));
$body['token']=request_link('active');query("UPDATE kr_user_access SET state='disabled' WHERE user_id='active'");rejects(fn()=>password_reset_complete($body));query("UPDATE kr_user_access SET state='active' WHERE user_id='active'");
query("UPDATE kr_users SET auth_version=auth_version+1 WHERE id='active'");rejects(fn()=>password_reset_complete($body));
$body['token']=request_link('active');$version=(int)one("SELECT auth_version FROM kr_users WHERE id='active'")['auth_version'];$_SESSION=['uid'=>'active','auth_version'=>$version,'csrf'=>'old'];
$result=password_reset_complete($body);$user=one("SELECT * FROM kr_users WHERE id='active'");check(password_verify($new,$user['password_hash'])&&!password_verify($old,$user['password_hash']),'Password replaced');check((int)$user['auth_version']===$version+1,'All previous sessions invalidated');check(!isset($_SESSION['uid'])&&$result['csrf']!=='old','No automatic login; rotated CSRF');check(!one("SELECT * FROM kr_password_resets WHERE user_id='active'"),'Token consumed');rejects(fn()=>password_reset_complete($body));
check(password_verify($old,one("SELECT * FROM kr_users WHERE id='other'")['password_hash']),'Other account untouched');$GLOBALS['after_auth_response']();check(str_contains(end($mail)['message'],'previous sessions'),'Change notification prepared');
$_SERVER['HTTP_HOST']='attacker.test';check(str_starts_with(password_reset_url(str_repeat('a',64)),'https://example.test/app/#reset='),'Configured URL only');$settings['app_url']='http://attacker.test';rejects(fn()=>password_reset_url(str_repeat('a',64)),503);
$settings['app_url']='https://example.test/app';$buckets=[];for($i=0;$i<3;$i++)password_reset_request(['email'=>'unknown@example.test']);rejects(fn()=>password_reset_request(['email'=>'unknown@example.test']),429);unset($GLOBALS['after_auth_response']);
echo "PASS: $checks reset checks (expiry, replay, revocation, disabled/pending/unknown accounts, hashed tokens, email text, isolation, rate limits). SMTP captured; no emails sent.\n";
