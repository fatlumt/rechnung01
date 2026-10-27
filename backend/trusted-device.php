<?php
declare(strict_types=1);
function trusted_schema():void {
 query('CREATE TABLE IF NOT EXISTS kr_trusted_devices (selector CHAR(32) PRIMARY KEY,owner CHAR(36) NOT NULL,token_hash CHAR(64) NOT NULL,auth_version INT NOT NULL,expires BIGINT NOT NULL,INDEX(owner)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}
function device_cookie_name(string $name):string {return (str_starts_with(config()['app_url'],'https://')?'__Host-':'').'dr_'.$name;}
function device_cookie(string $name,string $value,int $expires):void {setcookie(device_cookie_name($name),$value,['expires'=>$expires,'path'=>'/','secure'=>str_starts_with(config()['app_url'],'https://'),'httponly'=>true,'samesite'=>'Lax']);}
function trusted_parts():array {$raw=$_COOKIE[device_cookie_name('trusted')]??'';return is_string($raw)&&preg_match('/^([a-f0-9]{32}):([a-f0-9]{64})$/D',$raw,$m)?[$m[1],$m[2]]:[];}
function trusted_forget():void {trusted_schema();$p=trusted_parts();if($p)query('DELETE FROM kr_trusted_devices WHERE selector=? AND token_hash=?',[$p[0],hash('sha256',$p[1])]);device_cookie('trusted','',time()-3600);unset($_COOKIE[device_cookie_name('trusted')]);}
function trusted_enable(array $u):void {trusted_forget();$selector=bin2hex(random_bytes(16));$token=bin2hex(random_bytes(32));$expires=time()+30*86400;query('INSERT INTO kr_trusted_devices(selector,owner,token_hash,auth_version,expires) VALUES(?,?,?,?,?)',[$selector,$u['id'],hash('sha256',$token),(int)one('SELECT auth_version FROM kr_users WHERE id=?',[$u['id']])['auth_version'],$expires]);device_cookie('trusted',$selector.':'.$token,$expires);$_COOKIE[device_cookie_name('trusted')]=$selector.':'.$token;}
function trusted_record():?array {
 $p=trusted_parts();if(!$p)return null;trusted_schema();$r=one("SELECT t.*,u.auth_version AS current_version,COALESCE(a.state,'active') AS state FROM kr_trusted_devices t JOIN kr_users u ON u.id=t.owner LEFT JOIN kr_user_access a ON a.user_id=u.id WHERE t.selector=?",[$p[0]]);
 if(!$r||!hash_equals($r['token_hash'],hash('sha256',$p[1]))||(int)$r['expires']<=time()||(int)$r['auth_version']!==(int)$r['current_version']||$r['state']!=='active')return null;return $r;
}
function trusted_restore():void {if(!empty($_SESSION['uid']))return;$r=trusted_record();if($r)signed_in(['id'=>$r['owner'],'auth_version'=>(int)$r['auth_version']]);}
function trusted_status(array $u):array {$r=trusted_record();return ['enabled'=>$r&&$r['owner']===$u['id'],'expires'=>$r&&$r['owner']===$u['id']?(int)$r['expires']:null];}
