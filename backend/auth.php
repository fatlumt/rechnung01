<?php
declare(strict_types=1);
function auth_get():array{return ['user'=>current_user(),'csrf'=>$_SESSION['csrf'],'setupRequired'=>(int)(one('SELECT complete FROM kr_bootstrap WHERE id=1')['complete']??0)===0];}
function auth_post(array $b):array{
 $action=$b['action']??'';$u=current_user();
 if($action==='logout'){$_SESSION=[];session_regenerate_id(true);return ['ok'=>true];}
 if(in_array($action,['login','register'],true)){
  $email=strtolower(text($b['email']??'',190,true));if(!filter_var($email,FILTER_VALIDATE_EMAIL))fail('E-Mail-Adresse prüfen.');
  limit_attempts('auth-ip',$_SERVER['REMOTE_ADDR']??'unknown',40);limit_attempts('auth-email',$email,12);
  $password=password_value($b['password']??'');
  if($action==='login'){$r=one('SELECT * FROM kr_users WHERE email=?',[$email]);$hash=$r['password_hash']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';if(!password_verify($password,$hash)||!$r)fail('E-Mail oder Passwort stimmt nicht.',401);signed_in($r);return auth_get();}
  $token=text($b['token']??'',128,true);db()->beginTransaction();$guard=one('SELECT complete FROM kr_bootstrap WHERE id=1 FOR UPDATE');$admin=(int)$guard['complete']===0;
  if($admin){$key=config()['setup_key']??'';if(strlen($key)<32||str_starts_with($key,'REPLACE_')||!hash_equals($key,$token))fail('Einrichtungscode ungültig.',403);}else{$invite=one('SELECT * FROM kr_invites WHERE token_hash=? FOR UPDATE',[hash('sha256',$token)]);if(!$invite||$invite['used']||$invite['email']!==$email||$invite['expires']<date('Y-m-d H:i:s'))fail('Einladung ungültig oder abgelaufen.',403);query('UPDATE kr_invites SET used=1 WHERE token_hash=?',[hash('sha256',$token)]);}
  if(one('SELECT id FROM kr_users WHERE email=?',[$email]))fail('Konto konnte nicht angelegt werden.',409);
  $id=id();query('INSERT INTO kr_users(id,email,password_hash,is_admin,created) VALUES(?,?,?,?,?)',[$id,$email,password_hash($password,PASSWORD_DEFAULT),(int)$admin,date('Y-m-d H:i:s')]);if($admin)query('UPDATE kr_bootstrap SET complete=1 WHERE id=1');db()->commit();signed_in(['id'=>$id]);return auth_get();
 }
 if(!$u)fail('Bitte anmelden.',401);
 if($action==='password'){limit_attempts('password',$u['id'],10);$r=one('SELECT password_hash FROM kr_users WHERE id=?',[$u['id']]);if(!password_verify(text($b['current']??'',72,true),$r['password_hash']))fail('Aktuelles Passwort stimmt nicht.',403);$password=password_value($b['password']??'');query('UPDATE kr_users SET password_hash=?,auth_version=auth_version+1 WHERE id=?',[password_hash($password,PASSWORD_DEFAULT),$u['id']]);$_SESSION['auth_version']++;return ['ok'=>true];}
 if($action==='invite'){if(!$u['isAdmin'])fail('Nicht erlaubt.',403);$email=strtolower(text($b['email']??'',190,true));if(!filter_var($email,FILTER_VALIDATE_EMAIL))fail('E-Mail-Adresse prüfen.');if(one('SELECT id FROM kr_users WHERE email=?',[$email]))fail('Dieses Konto existiert bereits.',409);$token=bin2hex(random_bytes(32));query('INSERT INTO kr_invites(token_hash,email,expires) VALUES(?,?,?)',[hash('sha256',$token),$email,date('Y-m-d H:i:s',time()+3*86400)]);return ['url'=>rtrim(config()['app_url'],'/').'/#invite='.$token,'email'=>$email];}
 fail('Aktion nicht verfügbar.');
}
