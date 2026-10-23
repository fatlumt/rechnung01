<?php

declare(strict_types=1);



function require_superadmin(array $user):void {

 if(empty($user['isSuperadmin']))fail('Nur fÃ¼r Superadmin verfÃ¼gbar.',403);

}

function admin_get(array $user):array {

 require_superadmin($user);features_schema();

 // Deliberately expose account metadata only, never another business's records.

 $users=query("SELECT u.id,u.email,u.is_admin,u.created,COALESCE(a.state,'active') AS state FROM kr_users u LEFT JOIN kr_user_access a ON a.user_id=u.id ORDER BY u.created DESC,u.id")->fetchAll();

 return ['featureCatalog'=>feature_catalog(),'users'=>array_map(fn($u)=>['id'=>$u['id'],'email'=>$u['email'],'role'=>$u['is_admin']?'Superadmin':'Benutzer','created'=>$u['created'],'state'=>$u['state'],'isSelf'=>$u['id']===$user['id'],'features'=>features_for($u['id']),'featureVersion'=>(int)(one('SELECT version FROM kr_features WHERE owner=?',[$u['id']])['version']??0)],$users),

 'invitations'=>query("SELECT i.email,MAX(i.expires) AS expires FROM kr_invites i LEFT JOIN kr_users u ON u.email=i.email WHERE i.used=0 AND i.expires>? AND u.id IS NULL GROUP BY i.email ORDER BY expires DESC",[date('Y-m-d H:i:s')])->fetchAll()];

}

function invitation_email(mixed $value):string {

 $email=strtolower(text($value??'',190,true));if(!filter_var($email,FILTER_VALIDATE_EMAIL))fail('E-Mail-Adresse prÃ¼fen.');return $email;

}

function new_invitation(string $email):array {

 query('UPDATE kr_invites SET used=1 WHERE email=? AND used=0',[$email]);

 $token=bin2hex(random_bytes(32));$expires=date('Y-m-d H:i:s',time()+3*86400);

 query('INSERT INTO kr_invites(token_hash,email,expires) VALUES(?,?,?)',[hash('sha256',$token),$email,$expires]);

 return ['url'=>rtrim(config()['app_url'],'/').'/#invite='.$token,'email'=>$email,'expires'=>$expires];

}

function admin_post(array $user,array $body):array {

 require_superadmin($user);features_schema();hours_schema();limit_attempts('user-management',$user['id'],100);

 db()->beginTransaction();

 // Same lock as registration prevents activation/creation/deactivation races.

 one('SELECT complete FROM kr_bootstrap WHERE id=1 FOR UPDATE');

 one('SELECT id FROM kr_users WHERE id=? FOR UPDATE',[$user['id']]);
 $fresh=current_user();if(!$fresh||$fresh['id']!==$user['id'])fail('Bitte erneut anmelden.',401);require_superadmin($fresh);

 $result=admin_change($fresh,$body);db()->commit();security_event('admin_account_changed',$fresh['id']);return $result;

}

function admin_change(array $user,array $body):array {

 $action=$body['action']??'';

 if(in_array($action,['create','invite'],true)){

  $email=invitation_email($body['email']??'');

  if(one('SELECT id FROM kr_users WHERE email=?',[$email]))fail('Dieses Konto existiert bereits. Nutzen Sie die Benutzerliste.',409);

  if($action==='create'){

   $id=id();

   // No shared initial password: only the recipient can set their own password.

   query('INSERT INTO kr_users(id,email,password_hash,is_admin,created) VALUES(?,?,?,0,?)',[$id,$email,password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT),date('Y-m-d H:i:s')]);

   query("INSERT INTO kr_user_access(user_id,state,activated) VALUES(?,'pending',0)",[$id]);

  }

  return new_invitation($email);

 }

 if($action==='revoke'){

  $email=invitation_email($body['email']??'');

  if(one('SELECT id FROM kr_users WHERE email=?',[$email]))fail('Bitte dieses Konto Ã¼ber die Benutzerliste verwalten.',409);

  query('UPDATE kr_invites SET used=1 WHERE email=? AND used=0',[$email]);return ['ok'=>true];

 }

 $id=text($body['id']??'',36,true);

 $target=one('SELECT id,email,is_admin FROM kr_users WHERE id=? FOR UPDATE',[$id]);

 if(!$target)fail('Benutzer nicht gefunden.',404);

 if($target['is_admin']||$id===$user['id'])fail('Superadmin-Konten kÃ¶nnen hier nicht geÃ¤ndert werden.',403);



 if($action==='features'){

  $previous=one('SELECT version FROM kr_features WHERE owner=?',[$id]);if(integer($body['version']??null)!==(int)($previous['version']??0))fail('Freischaltungen wurden inzwischen geändert. Bitte neu laden.',409);

  $incoming=$body['features']??null;if(!is_array($incoming)||array_diff(array_keys($incoming),array_keys(feature_catalog())))fail('Ungültige Funktionen.');$v=[];

  foreach(feature_catalog() as $key=>$label){if(!isset($incoming[$key])||!is_bool($incoming[$key]))fail('Bitte alle Funktionen auswählen.');$v[$key]=$incoming[$key];}

  if(!$v['invoices']){$v['reminders']=false;$v['deposits']=false;}

  if(!$v['hours'])foreach(hours_list($id,'kr_hours') as $r)if($r['data']['state']!=='done')fail('Das Konto hat laufende Timer. Bitte zuerst stoppen lassen.');

  $before=features_for($id);query('INSERT INTO kr_features(owner,data,version) VALUES(?,?,1) ON DUPLICATE KEY UPDATE data=VALUES(data),version=version+1',[$id,encode($v)]);

  query('INSERT INTO kr_feature_audit(id,owner,actor,data,created) VALUES(?,?,?,?,?)',[id(),$id,$user['id'],encode(['before'=>$before,'after'=>$v]),date('Y-m-d H:i:s')]);return ['ok'=>true];

 }

 $access=one('SELECT state,activated FROM kr_user_access WHERE user_id=?',[$id]);

 $state=$access['state']??'active';$activated=(int)($access['activated']??1);

 if($action==='resend'){

  if($state!=='pending')fail('Ein Einrichtungslink ist nur fÃ¼r noch nicht eingerichtete Konten verfÃ¼gbar.',409);

  return new_invitation($target['email']);

 }

 if(!in_array($action,['deactivate','reactivate'],true))fail('Aktion nicht verfÃ¼gbar.');

 if($action==='deactivate'&&$state==='disabled')fail('Konto ist bereits deaktiviert.',409);

 if($action==='reactivate'&&$state!=='disabled')fail('Konto ist nicht deaktiviert.',409);

 $next=$action==='deactivate'?'disabled':($activated?'active':'pending');

 query('INSERT INTO kr_user_access(user_id,state,activated) VALUES(?,?,?) ON DUPLICATE KEY UPDATE state=VALUES(state)',[$id,$next,$activated]);

 query('UPDATE kr_users SET auth_version=auth_version+1 WHERE id=?',[$id]);

 query('UPDATE kr_invites SET used=1 WHERE email=? AND used=0',[$target['email']]);

 return ['ok'=>true,'state'=>$next];

}

