<?php
declare(strict_types=1);
function feature_catalog():array{return ['projects'=>'Projekte & Dateien','invoices'=>'Rechnungen & Zahlungen','offers'=>'Offerten','reminders'=>'Zahlungserinnerungen & Mahnungen','deposits'=>'Akonto- & Schlussrechnungen','expenses'=>'Ausgaben & Belege','hours'=>'Mitarbeiter & Arbeitszeiten','email'=>'Belege per E-Mail versenden'];}
function features_schema():void {
 query('CREATE TABLE IF NOT EXISTS kr_features (owner CHAR(36) PRIMARY KEY,data TEXT NOT NULL,version INT NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
 query('CREATE TABLE IF NOT EXISTS kr_feature_audit (id CHAR(36) PRIMARY KEY,owner CHAR(36) NOT NULL,actor CHAR(36) NOT NULL,data TEXT NOT NULL,created DATETIME NOT NULL,INDEX(owner)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}
function features_for(string $owner):array {
 $r=one('SELECT data FROM kr_features WHERE owner=?',[$owner]);$saved=$r?json_decode($r['data'],true):[];
 // These initial modules stay enabled for existing accounts. Future paid modules must opt in.
 $legacy=['projects','invoices','offers','reminders','deposits','expenses','hours','email'];$out=[];
 foreach(feature_catalog() as $key=>$label)$out[$key]=array_key_exists($key,$saved)?$saved[$key]===true:in_array($key,$legacy,true);
 if(one('SELECT is_admin FROM kr_users WHERE id=?',[$owner])['is_admin']??false)$out=array_fill_keys(array_keys(feature_catalog()),true);
 if(!$out['invoices']){$out['reminders']=false;$out['deposits']=false;}return $out;
}
function require_feature(string $owner,string $key):void {if(empty(features_for($owner)[$key]))fail('Diese Funktion ist für Ihr Konto nicht freigeschaltet. Bitte den Superadmin kontaktieren.',403);}
function feature_kind(string $kind):string{return ['invoice'=>'invoices','offer'=>'offers','reminder'=>'reminders','dunning'=>'reminders'][$kind]??'invoices';}
function feature_guard(string $owner,array $b):void {
 $action=$b['action']??'';
 if(str_starts_with($action,'project')){require_feature($owner,'projects');return;}
 if(!empty($b['data']['projectId'])&&in_array($action,['create','edit','convert'],true))require_feature($owner,'projects');
 if(in_array($action,['employee','hoursSave','hoursDelete','hoursTimer'],true)){require_feature($owner,'hours');return;}
 if(in_array($action,['expense','receipt'],true)){require_feature($owner,'expenses');return;}
 if(in_array($action,['company','customer','customerDelete','customerRestore'],true))return;
 if($action==='create')require_feature($owner,feature_kind($b['data']['kind']??''));
 else {$r=one('SELECT kind,data FROM kr_documents WHERE owner=? AND id=?',[$owner,text($b['id']??'',36,true)]);if($r){require_feature($owner,feature_kind($r['kind']));$v=json_decode($r['data'],true);if(in_array($v['documentTitle']??'',['Akontorechnung','Schlussrechnung'],true))require_feature($owner,'deposits');}}
 if($action==='convert')require_feature($owner,'invoices');
 if(in_array($action,['reminder','quickReminder'],true)){require_feature($owner,'invoices');require_feature($owner,'reminders');}
 if(in_array($b['data']['documentTitle']??'',['Akontorechnung','Schlussrechnung'],true)||!empty($b['data']['depositIds']))require_feature($owner,'deposits');
}
