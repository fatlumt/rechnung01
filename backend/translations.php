<?php
declare(strict_types=1);

function translation_catalog():array {
 static $catalog;
 if($catalog!==null)return $catalog;
 $raw=file_get_contents(__DIR__.'/translation-catalog.json');
 $catalog=json_decode($raw,true,512,JSON_THROW_ON_ERROR);
 if(!is_array($catalog)||!isset($catalog['languages'],$catalog['messages'])||!is_array($catalog['languages'])||!is_array($catalog['messages']))fail('Übersetzungskatalog nicht verfügbar.',503);
 return $catalog;
}

function translations_schema():void {
 static $ready=false;if($ready)return;
 query('CREATE TABLE IF NOT EXISTS kr_translations (id TINYINT PRIMARY KEY,version INT UNSIGNED NOT NULL DEFAULT 0,data MEDIUMTEXT NOT NULL,updated DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
 query("INSERT IGNORE INTO kr_translations(id,version,data) VALUES(1,0,'{}')");
 $ready=true;
}

function translation_placeholders(string $value):array {
 preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/u',$value,$matches);
 $names=$matches[1];sort($names,SORT_STRING);return $names;
}

function translation_text(mixed $value,string $source):string {
 $value=text($value,8000,true);
 // All display paths treat these as text. Reject markup as an extra guard,
 // including invisible control characters that could disguise UI messages.
 if(preg_match('/[<>\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',$value))fail('Übersetzungen dürfen nur normalen Text ohne HTML enthalten.');
 if(translation_placeholders($value)!==translation_placeholders($source))fail('Alle Platzhalter aus dem Original müssen unverändert enthalten sein.');
 return $value;
}

function translations_values(array $row):array {
 $catalog=translation_catalog();$saved=json_decode($row['data'],true,512,JSON_THROW_ON_ERROR);$out=[];
 foreach($catalog['languages'] as $locale){
  $out[$locale]=[];
  foreach(($saved[$locale]??[]) as $source=>$value){
   // A later release may remove a key. Never publish unrecognised keys.
   if(isset($catalog['messages'][$source])&&is_string($value))$out[$locale][$source]=$value;
  }
  $out[$locale]=(object)$out[$locale];
 }
 return ['version'=>(int)$row['version'],'overrides'=>(object)$out];
}

function translations_get():array {
 translations_schema();return translations_values(one('SELECT version,data FROM kr_translations WHERE id=1'));
}

function translate_message(string $language,string $source,array $variables=[]):string {
 static $overrides=null;
 $catalog=translation_catalog();
 if(!in_array($language,$catalog['languages'],true))$language='de';
 if($overrides===null)$overrides=(array)translations_get()['overrides'];
 $custom=(array)($overrides[$language]??[]);
 $value=$custom[$source]??($catalog['messages'][$source][$language]??$source);
 $replace=[];
 foreach($variables as $name=>$content){
  if(is_string($name)&&preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D',$name)&&(is_scalar($content)||$content===null))$replace['{'.$name.'}']=(string)$content;
 }
 // This returns text, not trusted HTML. HTML/email rendering must escape it.
 return strtr($value,$replace);
}

function translations_post(array $user,array $body):array {
 require_superadmin($user);translations_schema();limit_attempts('translation-management',$user['id'],180);
 $catalog=translation_catalog();$locale=text($body['locale']??'',12,true);
 if(!in_array($locale,$catalog['languages'],true))fail('Diese Sprache ist nicht verfügbar.');
 $source=$body['source']??'';
 if(!is_string($source)||$source===''||mb_strlen($source)>8000)fail('Dieser Text gehört nicht zum Übersetzungskatalog.');
 if(!array_key_exists($source,$catalog['messages']))fail('Dieser Text gehört nicht zum Übersetzungskatalog.');
 $action=$body['action']??'';if(!in_array($action,['save','reset'],true))fail('Ungültige Übersetzungsaktion.');
 $version=integer($body['version']??null,0,4294967294);
 $value=$action==='save'?translation_text($body['text']??null,$source):null;
 db()->beginTransaction();
 try{
  // Lock in the same order as account management. A demoted or disabled
  // administrator must not be able to complete a translation change.
  one('SELECT complete FROM kr_bootstrap WHERE id=1 FOR UPDATE');
  one('SELECT id FROM kr_users WHERE id=? FOR UPDATE',[$user['id']]);
  $fresh=current_user();if(!$fresh||$fresh['id']!==$user['id'])fail('Bitte erneut anmelden.',401);require_superadmin($fresh);
  $row=one('SELECT version,data FROM kr_translations WHERE id=1 FOR UPDATE');
  if((int)$row['version']!==$version)fail('Übersetzungen wurden inzwischen geändert. Bitte neu laden und Ihre Änderung prüfen.',409);
  $saved=json_decode($row['data'],true,512,JSON_THROW_ON_ERROR);
  if($action==='reset')unset($saved[$locale][$source]);else $saved[$locale][$source]=$value;
  $json=encode($saved);if(strlen($json)>2000000)fail('Der Übersetzungsspeicher ist voll. Bitte nicht benötigte Anpassungen zurücksetzen.');
  query('UPDATE kr_translations SET version=version+1,data=?,updated=? WHERE id=1',[$json,date('Y-m-d H:i:s')]);
  $result=translations_values(['version'=>(int)$row['version']+1,'data'=>$json]);
  db()->commit();security_event('translation_changed',$fresh['id']);return $result;
 }catch(Throwable $error){if(db()->inTransaction())db()->rollBack();throw $error;}
}
