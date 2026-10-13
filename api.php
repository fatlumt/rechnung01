<?php
declare(strict_types=1);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
require __DIR__.'/backend/core.php';
require __DIR__.'/backend/auth.php';
require __DIR__.'/backend/data.php';
try {
 begin_session();$method=$_SERVER['REQUEST_METHOD'];
 if(!in_array($method,['GET','POST'],true)){header('Allow: GET, POST');fail('Methode nicht erlaubt.',405);}
 $route=$_GET['route']??'';if(!in_array($route,['auth','data'],true))fail('Nicht gefunden.',404);
 $b=[];if($method==='POST'){csrf();if(!str_starts_with($_SERVER['CONTENT_TYPE']??'','application/json'))fail('JSON erforderlich.',415);$raw=file_get_contents('php://input',false,null,0,900001);if(strlen($raw)>900000)fail('Anfrage zu gross.',413);try{$b=json_decode($raw,true,64,JSON_THROW_ON_ERROR);}catch(JsonException $e){fail('Ungültiges JSON.');}if(!is_array($b)||array_is_list($b))fail('Ungültige Anfrage.');}
 if($route==='auth')$result=$method==='GET'?auth_get():auth_post($b);
 else{$u=current_user();if(!$u)fail('Bitte anmelden.',401);$result=$method==='GET'?data_get($u['id']):data_post($u['id'],$b);}
 echo encode($result);
}catch(Throwable $e){try{if(db()->inTransaction())db()->rollBack();}catch(Throwable $ignored){}$status=$e instanceof AppError?$e->status:500;http_response_code($status);if(!($e instanceof AppError))error_log('Klarrechnung request failed: '.get_class($e).' / '.$e->getCode());echo json_encode(['error'=>$e instanceof AppError?$e->getMessage():'Speichern oder Laden fehlgeschlagen. Bitte Konfiguration prüfen oder erneut versuchen.'],JSON_UNESCAPED_UNICODE);}
