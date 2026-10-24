<?php
declare(strict_types=1);

function password_reset_schema():void {
 static $ready=false;if($ready)return;
 query('CREATE TABLE IF NOT EXISTS kr_password_resets (user_id CHAR(36) PRIMARY KEY,token_hash CHAR(64) NOT NULL UNIQUE,auth_version INT NOT NULL,expires BIGINT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
 $ready=true;
}
function password_reset_url(string $token):string {
 $base=rtrim(config()['app_url']??'','/');$url=parse_url($base);
 if(!$url||empty($url['host'])||isset($url['user'])||isset($url['pass'])||isset($url['query'])||isset($url['fragment'])||(($url['scheme']??'')!=='https'&&!(($url['scheme']??'')==='http'&&in_array($url['host'],['localhost','127.0.0.1'],true))))fail('Passwort-Wiederherstellung ist derzeit nicht verfügbar. Bitte später erneut versuchen.',503);
 return $base.'/#reset='.$token;
}
function password_reset_mail(string $email,string $language,string $subject,string $message):void {
 foreach(['Exception','SMTP','PHPMailer'] as $name)require_once __DIR__.'/vendor/phpmailer/'.$name.'.php';
 $c=config();$mail=new \PHPMailer\PHPMailer\PHPMailer(true);$mail->isSMTP();$mail->Host=$c['smtp_host'];$mail->Port=(int)($c['smtp_port']??465);$mail->SMTPSecure=($c['smtp_security']??'ssl')==='tls'?'tls':'ssl';$mail->SMTPAuth=true;$mail->Username=$c['smtp_user'];$mail->Password=$c['smtp_password'];$mail->Timeout=5;$mail->Timelimit=5;$mail->CharSet='UTF-8';
 $mail->setFrom($c['mail_from'],'DigitalRechnung');$mail->addAddress($email);$mail->Subject=preg_replace('/[\r\n]+/',' ',translate_message($language,$subject));$mail->Body=$message;$mail->send();
}
function password_reset_request(array $body):array {
 limit_attempts('reset-request-ip',$_SERVER['REMOTE_ADDR']??'unknown',12);
 $email=invitation_email($body['email']??'');limit_attempts('reset-request-email',$email,3);
 $language=document_language($body['language']??'de');password_reset_url(str_repeat('0',64));
 // Availability is global, not dependent on whether an account exists.
 if(!mail_ready())fail('Passwort-Wiederherstellung ist derzeit nicht verfügbar. Bitte später erneut versuchen.',503);
 password_reset_schema();
 // Lookup and mail delivery happen after the identical public response has
 // finished. No token or account existence is exposed in the response/logs.
 $GLOBALS['after_auth_response']=function()use($email,$language){
  db()->beginTransaction();
  one('SELECT complete FROM kr_bootstrap WHERE id=1 FOR UPDATE');
  $user=one("SELECT u.id,u.email,u.auth_version,COALESCE(a.state,'active') AS state FROM kr_users u LEFT JOIN kr_user_access a ON a.user_id=u.id WHERE u.email=? FOR UPDATE",[$email]);
  if(!$user||$user['state']!=='active'){db()->commit();return;}
  $token=bin2hex(random_bytes(32));$hash=hash('sha256',$token);
  query('DELETE FROM kr_password_resets WHERE user_id=?',[$user['id']]);
  query('INSERT INTO kr_password_resets(user_id,token_hash,auth_version,expires) VALUES(?,?,?,?)',[$user['id'],$hash,(int)$user['auth_version'],time()+1800]);
  db()->commit();
  $message=translate_message($language,'Öffnen Sie diesen Link, um ein neues Passwort für DigitalRechnung festzulegen. Der Link ist 30 Minuten gültig und kann nur einmal verwendet werden.')."\n\n".password_reset_url($token)."\n\n".translate_message($language,'Wenn Sie diese Änderung nicht angefordert haben, ignorieren Sie diese E-Mail. Ihr Passwort bleibt unverändert.');
  try{password_reset_mail($user['email'],$language,'DigitalRechnung – Passwort zurücksetzen',$message);security_event('password_reset_link_sent',$user['id']);}
  catch(Throwable $e){security_event('password_reset_delivery_unconfirmed',$user['id']);}
 };
 return ['ok'=>true];
}
function password_reset_complete(array $body):array {
 limit_attempts('reset-complete-ip',$_SERVER['REMOTE_ADDR']??'unknown',15);password_reset_schema();
 $token=$body['token']??'';
 if(!is_string($token)||!preg_match('/^[a-f0-9]{64}$/D',$token))fail('Dieser Link ist ungültig oder abgelaufen. Bitte einen neuen Link anfordern.',400);
 $password=password_value($body['password']??'');
 if(!is_string($body['confirm']??null)||!hash_equals($password,$body['confirm']))fail('Die Passwörter stimmen nicht überein.');
 $language=document_language($body['language']??'de');$hash=hash('sha256',$token);$passwordHash=password_hash($password,PASSWORD_DEFAULT);
 db()->beginTransaction();
 try{
  // Match account-management lock order; recheck state and credential version
  // under lock so disabled users and superseded links cannot regain access.
  one('SELECT complete FROM kr_bootstrap WHERE id=1 FOR UPDATE');
  $reset=one('SELECT * FROM kr_password_resets WHERE token_hash=?',[$hash]);
  $user=$reset?one("SELECT u.id,u.email,u.auth_version,COALESCE(a.state,'active') AS state FROM kr_users u LEFT JOIN kr_user_access a ON a.user_id=u.id WHERE u.id=? FOR UPDATE",[$reset['user_id']]):null;
  if(!$reset||!$user||$user['state']!=='active'||(int)$reset['expires']<=time()||(int)$reset['auth_version']!==(int)$user['auth_version'])fail('Dieser Link ist ungültig oder abgelaufen. Bitte einen neuen Link anfordern.',400);
  query('UPDATE kr_users SET password_hash=?,auth_version=auth_version+1 WHERE id=?',[$passwordHash,$user['id']]);
  query('DELETE FROM kr_password_resets WHERE user_id=?',[$user['id']]);
  db()->commit();
 }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
 reset_session();security_event('password_reset_completed',$user['id']);
 $GLOBALS['after_auth_response']=function()use($user,$language){
  try{password_reset_mail($user['email'],$language,'DigitalRechnung – Passwort geändert',translate_message($language,'Ihr Passwort wurde geändert. Alle bisherigen Sitzungen wurden beendet. Falls Sie diese Änderung nicht vorgenommen haben, setzen Sie Ihr Passwort erneut zurück und kontaktieren Sie den Administrator.'));}
  catch(Throwable $e){security_event('password_reset_notice_unconfirmed',$user['id']);}
 };
 return ['ok'=>true,'csrf'=>$_SESSION['csrf']];
}
function password_reset_dispatch():void {
 $job=$GLOBALS['after_auth_response']??null;unset($GLOBALS['after_auth_response']);if(!$job)return;
 session_write_close();ignore_user_abort(true);
 if(function_exists('fastcgi_finish_request'))fastcgi_finish_request();
 elseif(function_exists('litespeed_finish_request'))litespeed_finish_request();
 else{while(ob_get_level()>0)ob_end_flush();flush();}
 try{$job();}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();security_event('password_reset_dispatch_failed');}
}
