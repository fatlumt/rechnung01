<?php
declare(strict_types=1);
function auth_get():array{return ['user'=>current_user(),'csrf'=>$_SESSION['csrf'],'setupRequired'=>(int)(one('SELECT complete FROM kr_bootstrap WHERE id=1')['complete']??0)===0];}
function auth_post(array $b):array {
 $action=$b['action']??'';$u=current_user();
 if($action==='logout'){$_SESSION=[];session_regenerate_id(true);return ['ok'=>true];}
 if(in_array($action,['login','register'],true)){
  $email=invitation_email($b['email']??'');
  limit_attempts('auth-ip',$_SERVER['REMOTE_ADDR']??'unknown',40);limit_attempts('auth-email',$email,12);
  $password=password_value($b['password']??'');
  if($action==='login'){
   $r=one('SELECT * FROM kr_users WHERE email=?',[$email]);$hash=$r['password_hash']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';
   if(!password_verify($password,$hash)||!$r)fail('E-Mail oder Passwort stimmt nicht.',401);
   $access=one('SELECT state FROM kr_user_access WHERE user_id=?',[$r['id']]);
   if($access&&$access['state']!=='active')fail('Dieses Konto ist nicht aktiv. Bitte den Superadmin kontaktieren.',403);
   signed_in($r);return auth_get();
  }
  $token=text($b['token']??'',128,true);db()->beginTransaction();$guard=one('SELECT complete FROM kr_bootstrap WHERE id=1 FOR UPDATE');$admin=(int)$guard['complete']===0;
  if($admin){
   $key=config()['setup_key']??'';if(strlen($key)<32||str_starts_with($key,'REPLACE_')||!hash_equals($key,$token))fail('Einrichtungscode ungültig.',403);
  }else{
   $invite=one('SELECT * FROM kr_invites WHERE token_hash=? FOR UPDATE',[hash('sha256',$token)]);
   if(!$invite||$invite['used']||$invite['email']!==$email||$invite['expires']<date('Y-m-d H:i:s'))fail('Einladung ungültig oder abgelaufen.',403);
  }
  $existing=one('SELECT id,is_admin,auth_version FROM kr_users WHERE email=? FOR UPDATE',[$email]);
  if($existing){
   $access=one('SELECT state,activated FROM kr_user_access WHERE user_id=?',[$existing['id']]);
   if($admin||$existing['is_admin']||!$access||$access['state']!=='pending'||$access['activated'])fail('Konto konnte nicht angelegt werden.',409);
   $id=$existing['id'];$version=(int)$existing['auth_version']+1;
   query('UPDATE kr_users SET password_hash=?,auth_version=? WHERE id=?',[password_hash($password,PASSWORD_DEFAULT),$version,$id]);
   query("UPDATE kr_user_access SET state='active',activated=1 WHERE user_id=?",[$id]);
  }else{
   $id=id();$version=1;
   query('INSERT INTO kr_users(id,email,password_hash,is_admin,created) VALUES(?,?,?,?,?)',[$id,$email,password_hash($password,PASSWORD_DEFAULT),(int)$admin,date('Y-m-d H:i:s')]);
  }
  query('UPDATE kr_invites SET used=1 WHERE email=? AND used=0',[$email]);
  if($admin)query('UPDATE kr_bootstrap SET complete=1 WHERE id=1');
  db()->commit();signed_in(['id'=>$id,'auth_version'=>$version]);return auth_get();
 }
 if(!$u)fail('Bitte anmelden.',401);
 if($action==='password'){
  limit_attempts('password',$u['id'],10);db()->beginTransaction();
  $r=one('SELECT password_hash FROM kr_users WHERE id=? FOR UPDATE',[$u['id']]);
  if(!current_user())fail('Bitte erneut anmelden.',401);
  if(!password_verify(text($b['current']??'',72,true),$r['password_hash']))fail('Aktuelles Passwort stimmt nicht.',403);
  $password=password_value($b['password']??'');
  query('UPDATE kr_users SET password_hash=?,auth_version=auth_version+1 WHERE id=?',[password_hash($password,PASSWORD_DEFAULT),$u['id']]);
  db()->commit();$_SESSION['auth_version']++;return ['ok'=>true];
 }
 // Keep the previous invitation endpoint working for already-open app tabs.
 if($action==='invite')return admin_post($u,$b);
 fail('Aktion nicht verfügbar.');
}
