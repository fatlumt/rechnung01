<?php
declare(strict_types=1);
date_default_timezone_set('Europe/Zurich');
final class AppError extends RuntimeException {public function __construct(string $message,public int $status=400){parent::__construct($message);}}
function fail(string $message,int $status=400):never{throw new AppError($message,$status);}
function config():array {static $c;if($c!==null)return $c;$file=dirname(__DIR__,3).'/klarrechnung-config.php';if(!is_file($file))$file=__DIR__.'/config.php';if(!is_file($file))fail('Die Datenbank ist noch nicht eingerichtet. Bitte die Hostinger-Anleitung befolgen.',503);$c=require $file;if(!is_array($c))fail('Konfiguration nicht verfügbar.',503);return $c;}
function db():PDO {static $db;if($db)return $db;$c=config();$db=new PDO('mysql:host='.$c['db_host'].';port='.($c['db_port']??3306).';dbname='.$c['db_name'].';charset=utf8mb4',$c['db_user'],$c['db_password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);// Additive upgrade: old accounts are active by default; existing roles are retained.
try{$db->query('SELECT user_id FROM kr_user_access LIMIT 0');}catch(PDOException $e){if(($e->errorInfo[1]??0)!==1146)throw $e;$db->exec("CREATE TABLE IF NOT EXISTS kr_user_access (user_id CHAR(36) PRIMARY KEY,state VARCHAR(16) NOT NULL DEFAULT 'active',activated TINYINT NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");}
try{$db->query('SELECT document_id FROM kr_dunning_fees LIMIT 0');}catch(PDOException $e){if(($e->errorInfo[1]??0)!==1146)throw $e;$db->exec("CREATE TABLE IF NOT EXISTS kr_dunning_fees (document_id CHAR(36) PRIMARY KEY,owner CHAR(36) NOT NULL,amount BIGINT NOT NULL,created DATETIME NOT NULL,INDEX(owner)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");}
return $db;}
function query(string $sql,array $args=[]):PDOStatement{$s=db()->prepare($sql);$s->execute($args);return $s;}
function one(string $sql,array $args=[]):?array{return query($sql,$args)->fetch()?:null;}
function id():string{$x=bin2hex(random_bytes(16));return substr($x,0,8).'-'.substr($x,8,4).'-4'.substr($x,13,3).'-a'.substr($x,17,3).'-'.substr($x,20);}
function text(mixed $v,int $max=300,bool $required=false):string {if(!is_string($v))fail('Ungültiger Text.');$v=trim($v);if(mb_strlen($v)>$max||($required&&$v===''))fail('Bitte alle Pflichtfelder korrekt ausfüllen.');return $v;}
function integer(mixed $v,int $min=0,int $max=100000000000):int {if(!is_int($v)||$v<$min||$v>$max)fail('Ungültiger Betrag oder Zahlenwert.');return $v;}
function day(mixed $v):string{$s=text($v,10,true);$d=DateTimeImmutable::createFromFormat('!Y-m-d',$s);if(!$d||$d->format('Y-m-d')!==$s)fail('Ungültiges Datum.');return $s;}
function address(mixed $v):array{if(!is_array($v))fail('Adresse fehlt.');$a=[];foreach(['name','street','house','zip','city','country','email'] as $k)$a[$k]=text($v[$k]??'',300,in_array($k,['name','street','zip','city','country'],true));if(!in_array($a['country'],['CH','DE'],true))fail('Ungültiges Land.');if($a['email']!==''&&!filter_var($a['email'],FILTER_VALIDATE_EMAIL))fail('E-Mail-Adresse prüfen.');return $a;}
function image_value(mixed $v):string{$v=text($v??'',280000);if($v==='')return '';if(!preg_match('~^data:image/(png|jpeg|webp);base64,([A-Za-z0-9+/=]+)$~D',$v,$m))fail('Logo/Stempel muss PNG, JPG oder WebP sein.');$bytes=base64_decode($m[2],true);$info=$bytes===false?false:@getimagesizefromstring($bytes);if(!$info||strlen($bytes)>200000||$info[0]>2500||$info[1]>2500||$info['mime']!=='image/'.$m[1])fail('Bild zu gross oder ungültig (max. 200 KB / 2500 Pixel).');return $v;}
function company(mixed $v):array{$a=address($v);if(!isset($v['vat'])||!is_bool($v['vat']))fail('MWST-Einstellung fehlt.');$a['vat']=$v['vat'];$a['brandColor']=text($v['brandColor']??'#2558d7',7);if(!preg_match('/^#[0-9a-fA-F]{6}$/D',$a['brandColor']))fail('Bitte eine gültige Firmenfarbe wählen.');$a['terms']=integer($v['terms']??30,0,365);foreach(['uid','iban','phone','bank','signatory','signatoryRole'] as $k)$a[$k]=text($v[$k]??'');foreach(['logoUrl','stampUrl','qrUrl'] as $k)$a[$k]=image_value($v[$k]??'');if($a['country']==='CH'&&$a['vat']&&!preg_match('/^CHE-\d{3}\.\d{3}\.\d{3}( MWST)?$/D',$a['uid']))fail('UID bitte als CHE-123.456.789 eintragen.');if($a['iban']!==''){ $iban=strtoupper(preg_replace('/\s+/','',$a['iban']));if(!preg_match($a['country']==='DE'?'/^DE[0-9]{20}$/D':'/^(CH|LI)[A-Z0-9]{19}$/D',$iban))fail('Bitte eine gültige IBAN für das gewählte Land eingeben.');$check=substr($iban,4).substr($iban,0,4);$digits='';foreach(str_split($check) as $char)$digits.=ctype_alpha($char)?(string)(ord($char)-55):$char;$mod=0;foreach(str_split($digits) as $n)$mod=($mod*10+(int)$n)%97;if($mod!==1)fail('IBAN-Prüfsumme ungültig.');$a['iban']=$iban;}if($a['country']==='DE'){$a['qrUrl']='';$a['qrPayload']='';}if($a['qrUrl']!==''){$a['qrPayload']=qr_payload($v['qrPayload']??'',$a['iban']);}else $a['qrPayload']='';$a['qrPdf']=$a['qrUrl']!==''?pdf_value($v['qrPdf']??''):'';$a['qrPdfName']=$a['qrPdf']!==''?text($v['qrPdfName']??'QR-Zahlungsseite.pdf',180):'';$a['taxNumber']=text($v['taxNumber']??'',40);$a['legalDetails']=text($v['legalDetails']??'',1200);$a['templateId']=template_id($v['templateId']??'classic');$a['visibility']=field_visibility($v['visibility']??[]);if($a['country']==='DE'){foreach(['uid','number','serviceDate'] as $key)$a['visibility'][$key]=true;}$a['language']='de';$a['currency']=$a['country']==='DE'?'EUR':'CHF';if($a['country']==='DE'){if($a['uid']!==''&&!preg_match('/^DE[0-9]{9}$/D',$a['uid']))fail('USt-IdNr. bitte als DE123456789 eintragen.');if($a['uid']===''&&!preg_match('/^[0-9][0-9 \/-]{7,19}$/D',$a['taxNumber']))fail('Bitte Steuernummer oder USt-IdNr. eintragen.');}return $a;}
function template_id(mixed $v):string{if(!in_array($v,['classic','modern','compact'],true))fail('Ungültige Vorlage.');return $v;}
function qr_payload(mixed $value,string $iban):string {
 $raw=text($value,3000,true);$parts=preg_split('/\r\n|\r|\n/',$raw);
 if(count($parts)<31||$parts[0]!=='SPC'||$parts[1]!=='0200'||$parts[2]!=='1'||$parts[19]!=='CHF'||$parts[27]!=='NON'||$parts[28]!==''||$parts[30]!=='EPD')fail('Bitte einen wiederverwendbaren Swiss QR-Code in CHF ohne Referenz hochladen.');
 if($parts[4]!=='S'||implode('',array_slice($parts,11,7))!=='')fail('Bitte einen aktuellen Bank-QR-Code mit strukturierter Adresse verwenden.');
 if($parts[18]!==''||implode('',array_slice($parts,20,7))!=='')fail('Der QR-Code darf keinen festen Betrag oder Kunden enthalten.');
 if($iban===''||strtoupper(preg_replace('/\s+/','',$parts[3]))!==$iban)fail('QR-IBAN und Firmen-IBAN stimmen nicht überein.');
 return $raw;
}
function invoice_values(array $row):array {$r=decode_row($row);$r['fee']=(int)($r['fee']??0);if($r['kind']==='invoice'){$r['invoiceTotal']=$r['total'];$r['total']+=$r['fee'];}return $r;}
function draft(mixed $v):array {
 if(!is_array($v)||!in_array($v['kind']??'',['invoice','offer'],true))fail('Ungültiger Belegtyp.');
 $a=['kind'=>$v['kind'],'customerId'=>text($v['customerId']??'',36,true),'title'=>text($v['title']??'',300,true),'notes'=>text($v['notes']??'',4000),'documentTitle'=>text($v['documentTitle']??($v['kind']==='invoice'?'Rechnung':'Offerte'),80,true)];
 foreach(['date','due','serviceDate'] as $k)$a[$k]=day($v[$k]??'');
 if($a['due']<$a['date'])fail('Zahlungsziel liegt vor dem Belegdatum.');
 $a['templateId']=template_id($v['templateId']??'classic');$a['language']='de';
 $a['pricingMode']=$v['pricingMode']??'items';if(!in_array($a['pricingMode'],['items','total'],true))fail('Ungültige Preisart.');
 $a['visibility']=[];$visibility=$v['visibility']??[];if(!is_array($visibility))fail('Ungültige Anzeigeoptionen.');
 foreach(['payment','reference','uid','due','number','serviceDate'] as $key){$value=$visibility[$key]??true;if(!is_bool($value))fail('Ungültige Anzeigeoption.');$a['visibility'][$key]=$value;}
 $a['totalPrice']=integer($v['totalPrice']??0,0,100000000000);$a['totalRate']=$v['totalRate']??0;
 if(!is_numeric($a['totalRate'])||!in_array((float)$a['totalRate'],[0.0,2.6,3.8,8.1,7.0,19.0],true))fail('MWST ungültig.');$a['totalRate']=(float)$a['totalRate'];
 $lines=$v['lines']??[];if(!is_array($lines)||count($lines)<1||count($lines)>100)fail('Bitte 1 bis 100 Zeilen eingeben.');$a['lines']=[];$items=0;
 foreach($lines as $l){
  if(!is_array($l))fail('Ungültige Position.');
  if(($l['type']??'item')==='section'){$a['lines'][]=['type'=>'section','description'=>text($l['description']??'',160,true)];continue;}
  if(($l['type']??'item')!=='item')fail('Ungültige Position.');
  $quantity=$l['quantity']??1;$rate=$l['rate']??0;
  if(!is_numeric($quantity)||$quantity<=0||$quantity>100000||!is_numeric($rate)||!in_array((float)$rate,[0.0,2.6,3.8,8.1,7.0,19.0],true))fail('Menge oder MWST ungültig.');
  $a['lines'][]=['description'=>text($l['description']??'',300,true),'quantity'=>(float)$quantity,'unit'=>text($l['unit']??'',25),'price'=>integer($l['price']??0,0,100000000),'rate'=>(float)$rate];$items++;
 }
 $a['discountType']=$v['discountType']??'none';if(!in_array($a['discountType'],['none','percent','fixed'],true))fail('Ungültige Rabattart.');
 $a['discountValue']=integer($v['discountValue']??0,0,$a['discountType']==='percent'?10000:100000000000);
 if($a['discountType']==='none')$a['discountValue']=0;
 if(!$items)fail('Bitte mindestens eine Leistungsbeschreibung eingeben.');document_total($a);return $a;
}
function pricing_lines(array $d):array{return ($d['pricingMode']??'items')==='total'?[['quantity'=>1,'price'=>$d['totalPrice'],'rate'=>$d['totalRate']]]:$d['lines'];}
function document_total(array $d):int{
 $lines=array_values(array_filter(pricing_lines($d),fn($l)=>($l['type']??'item')!=='section'));
 $subtotal=array_sum(array_map(fn($l)=>(int)round($l['price']*$l['quantity']),$lines));
 if($subtotal<=0||$subtotal>100000000000)fail('Gesamtbetrag muss positiv und kleiner als CHF 1 Milliarde sein.');
 $discount=match($d['discountType']??'none'){'percent'=>(int)round($subtotal*($d['discountValue']??0)/10000),'fixed'=>$d['discountValue']??0,default=>0};
 if($discount<0||$discount>=$subtotal)fail('Der Rabatt muss kleiner als die Zwischensumme sein.');
 $cumulative=0;$allocated=0;$adjusted=[];foreach($lines as $l){$n=(int)round($l['price']*$l['quantity']);$cumulative+=$n;$next=(int)round($cumulative/$subtotal*$discount);$adjusted[]=['quantity'=>1,'price'=>$n-($next-$allocated),'rate'=>$l['rate']];$allocated=$next;}
 return total($adjusted);
}
function total(array $lines):int{$sum=0;foreach($lines as $l){if(($l['type']??'item')==='section')continue;$n=(int)round($l['price']*$l['quantity']);$sum+=$n+(int)round($n*$l['rate']/100);}if($sum<=0||$sum>100000000000)fail('Gesamtbetrag muss positiv und kleiner als CHF 1 Milliarde sein.');return $sum;}
function decode_row(array $r):array{if(isset($r['data']))$r['data']=json_decode($r['data'],true,512,JSON_THROW_ON_ERROR);foreach(['total','paid','version'] as $k)if(isset($r[$k]))$r[$k]=(int)$r[$k];return $r;}
function encode(array $v):string{return json_encode($v,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);}
function begin_session():void{$c=config();$url=parse_url($c['app_url']);$local=in_array($url['host']??'',['127.0.0.1','localhost'],true);if(($url['scheme']??'')!=='https'&&!$local)fail('HTTPS muss konfiguriert sein.',503);session_name('klarrechnung_session');session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!$local,'httponly'=>true,'samesite'=>'Lax']);ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');if(!session_start())fail('Sitzungsspeicher nicht verfügbar.',503);if(isset($_SESSION['last'])&&time()-$_SESSION['last']>28800){$_SESSION=[];session_regenerate_id(true);}$_SESSION['last']=time();$_SESSION['csrf']??=bin2hex(random_bytes(32));}
function current_user():?array {if(empty($_SESSION['uid']))return null;$u=one("SELECT u.id,u.email,u.is_admin,u.auth_version,COALESCE(a.state,'active') AS state FROM kr_users u LEFT JOIN kr_user_access a ON a.user_id=u.id WHERE u.id=?",[$_SESSION['uid']]);if(!$u||$u['state']!=='active'||(int)$u['auth_version']!==($_SESSION['auth_version']??0))return null;return ['id'=>$u['id'],'email'=>$u['email'],'isAdmin'=>(bool)$u['is_admin'],'isSuperadmin'=>(bool)$u['is_admin']];}
function csrf():void {if(!hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??''))fail('Sitzung abgelaufen. Bitte neu laden.',403);if(isset($_SERVER['HTTP_ORIGIN'])){$u=parse_url(config()['app_url']);$origin=$u['scheme'].'://'.$u['host'].(isset($u['port'])?':'.$u['port']:'');if($_SERVER['HTTP_ORIGIN']!==$origin)fail('Anfrage nicht erlaubt.',403);}}
function limit_attempts(string $scope,string $key,int $max):void{$hash=hash('sha256',$scope.'|'.$key);$now=time();query('INSERT INTO kr_rate_limits(bucket,attempts,expires) VALUES(?,1,?) ON DUPLICATE KEY UPDATE attempts=IF(expires<?,1,attempts+1),expires=IF(expires<?,VALUES(expires),expires)',[$hash,$now+900,$now,$now]);$r=one('SELECT attempts FROM kr_rate_limits WHERE bucket=?',[$hash]);if((int)$r['attempts']>$max)fail('Zu viele Versuche. Bitte in 15 Minuten erneut versuchen.',429);query('DELETE FROM kr_rate_limits WHERE expires<? LIMIT 100',[$now]);}
function password_value(mixed $v):string{if(!is_string($v)||strlen($v)<12||strlen($v)>72)fail('Passwort: mindestens 12, höchstens 72 Bytes.');return $v;}
function signed_in(array $u):void{session_regenerate_id(true);$_SESSION['uid']=$u['id'];$_SESSION['auth_version']=(int)($u['auth_version']??1);$_SESSION['csrf']=bin2hex(random_bytes(32));}

function field_visibility(mixed $visibility):array {
 if(!is_array($visibility))fail('Ungültige Anzeigeoptionen.');
 $result=[];foreach(['payment','reference','uid','due','number','serviceDate'] as $key){$value=$visibility[$key]??true;if(!is_bool($value))fail('Ungültige Anzeigeoption.');$result[$key]=$value;}return $result;
}

function pdf_value(mixed $v):string {
 if($v==='')return '';
 if(!is_string($v)||strlen($v)>6666700||!str_starts_with($v,'data:application/pdf;base64,'))fail('Bitte eine PDF bis 5 MB verwenden.');
 $bytes=base64_decode(substr($v,28),true);
 if($bytes===false||strlen($bytes)>5000000||!str_starts_with($bytes,'%PDF-')||!str_contains(substr($bytes,-2048),'%%EOF'))fail('Ungültige PDF-Datei.');
 return $v;
}
