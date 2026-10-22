<?php
declare(strict_types=1);
function mail_schema():void {
 query("CREATE TABLE IF NOT EXISTS kr_mail_log (id CHAR(36) PRIMARY KEY,owner CHAR(36) NOT NULL,document_id CHAR(36) NOT NULL,recipient VARCHAR(190) NOT NULL,status VARCHAR(16) NOT NULL,created DATETIME NOT NULL,INDEX(owner,document_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function mail_ready():bool {$c=config();return !empty($c['smtp_host'])&&!empty($c['smtp_user'])&&!empty($c['smtp_password'])&&filter_var($c['mail_from']??'',FILTER_VALIDATE_EMAIL);}
function mail_notifications_schema():void {
 query("CREATE TABLE IF NOT EXISTS kr_mail_notifications (id CHAR(36) PRIMARY KEY,owner CHAR(36) NOT NULL,message TEXT NOT NULL,email_status VARCHAR(16) NOT NULL DEFAULT 'pending',created DATETIME NOT NULL,read_at DATETIME NULL,INDEX(owner,read_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function mail_notifications_get(array $u):array {
 require_feature($u['id'],'email');mail_notifications_schema();
 return ['items'=>query('SELECT id,message,email_status,created FROM kr_mail_notifications WHERE owner=? AND read_at IS NULL ORDER BY created DESC LIMIT 50',[$u['id']])->fetchAll()];
}
function mail_notifications_read(array $u,array $b):array {
 require_feature($u['id'],'email');mail_notifications_schema();$id=text($b['id']??'',36,true);
 if(!one('SELECT id FROM kr_mail_notifications WHERE id=? AND owner=?',[$id,$u['id']]))fail('Benachrichtigung nicht gefunden.',404);
 query('UPDATE kr_mail_notifications SET read_at=? WHERE id=? AND owner=?',[date('Y-m-d H:i:s'),$id,$u['id']]);return ['ok'=>true];
}
function mail_failure_notice(array $u,array $doc,string $request,string $to):void {
 mail_notifications_schema();
 $message='Der Versand von '.$doc['number'].' an '.$to.' konnte nicht bestätigt werden. Bitte Postfach und Versandverlauf prüfen, bevor Sie erneut senden.';
 $insert=query('INSERT IGNORE INTO kr_mail_notifications(id,owner,message,created) VALUES(?,?,?,?)',[$request,$u['id'],$message,date('Y-m-d H:i:s')]);if($insert->rowCount()===0)return;
 // This warning is best effort and never retries the original invoice.
 try {
  if(!mail_ready())throw new RuntimeException('Mail transport unavailable');
  foreach(['Exception','SMTP','PHPMailer'] as $name)require_once __DIR__.'/vendor/phpmailer/'.$name.'.php';
  $c=config();$warning=new \PHPMailer\PHPMailer\PHPMailer(true);$warning->isSMTP();$warning->Host=$c['smtp_host'];$warning->Port=(int)($c['smtp_port']??465);$warning->SMTPSecure=($c['smtp_security']??'ssl')==='tls'?'tls':'ssl';$warning->SMTPAuth=true;$warning->Username=$c['smtp_user'];$warning->Password=$c['smtp_password'];$warning->Timeout=5;$warning->Timelimit=5;$warning->CharSet='UTF-8';
  $warning->setFrom($c['mail_from'],'digitalrechnung');$warning->addAddress($u['email']);$warning->Subject='E-Mail-Versand bitte prüfen';$warning->Body=$message."\n\nDieser Hinweis ist auch in digitalrechnung gespeichert. Eine erneute Sendung wurde nicht automatisch gestartet.";$warning->send();
  query("UPDATE kr_mail_notifications SET email_status='accepted' WHERE id=? AND owner=?",[$request,$u['id']]);
 }catch(Throwable $ignored){query("UPDATE kr_mail_notifications SET email_status='unknown' WHERE id=? AND owner=?",[$request,$u['id']]);}
}
function mail_branding(string $owner,array $doc):array {
 $profile=one('SELECT data,version FROM kr_businesses WHERE owner=?',[$owner]);
 $company=$profile?json_decode($profile['data'],true):(json_decode($doc['data']??'{}',true)['company']??[]);
 $out=[];foreach(['name','street','house','zip','city','country','email','phone','uid','legalDetails','logoUrl','brandColor'] as $key)$out[$key]=(string)($company[$key]??'');
 $out['name']=trim(preg_replace('/[\x00-\x1F\x7F]+/u',' ',$out['name']))?:'digitalrechnung';
 if(!preg_match('/^#[0-9a-fA-F]{6}$/D',$out['brandColor']))$out['brandColor']='#2558d7';
 $templates=email_templates($company['emailTemplates']??null);$data=json_decode($doc['data']??'{}',true);$kind=$doc['kind']??'invoice';$template=$templates[$kind]??$templates['invoice'];
 $date=fn($v)=>preg_match('/^\d{4}-\d{2}-\d{2}$/D',(string)$v)?substr($v,8,2).'.'.substr($v,5,2).'.'.substr($v,0,4):'';
 $variables=['{kunde}'=>$data['customer']['name']??'','{firma}'=>$out['name'],'{belegart}'=>['invoice'=>'Rechnung','offer'=>'Offerte','reminder'=>'Zahlungserinnerung','dunning'=>'Mahnung'][$kind]??'Beleg','{belegnummer}'=>$doc['number']??'','{belegdatum}'=>$date($data['date']??''),'{faelligkeit}'=>$date($data['due']??'')];
 $defaults=['subject'=>preg_replace('/[\r\n]+/',' ',strtr($template['subject'],$variables)),'message'=>strtr($template['message'],$variables)];
 return ['company'=>$out,'companyVersion'=>(int)($profile['version']??0),'defaults'=>$defaults];
}
function mail_signature_lines(array $c):array {
 return array_values(array_filter([
  trim(($c['street']??'').' '.($c['house']??'')),trim(($c['zip']??'').' '.($c['city']??'')),
  ['CH'=>'Schweiz','DE'=>'Deutschland'][$c['country']??'']??'',
  !empty($c['phone'])?'Tel. '.$c['phone']:'',$c['email']??'',!empty($c['uid'])?'UID / USt-IdNr.: '.$c['uid']:'',$c['legalDetails']??''
 ],fn($line)=>$line!==''));
}
function mail_company_body($mail,array $company,string $message):void {
 $escape=fn(string $s):string=>htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
 $logo='';
 if(preg_match('~^data:image/(png|jpeg|webp);base64,([A-Za-z0-9+/=]+)$~D',$company['logoUrl']??'',$m)){
  $bytes=base64_decode($m[2],true);$info=$bytes===false?false:@getimagesizefromstring($bytes);
  if($info&&strlen($bytes)<=200000&&$info[0]<=2500&&$info[1]<=2500&&$info['mime']==='image/'.$m[1]){
   $type=$m[1];$width=max(1,min(150,$info[0],(int)round(75*$info[0]/$info[1])));$height=max(1,(int)round($width*$info[1]/$info[0]));$imageStyle='background:#ffffff;padding:8px;';
   if(function_exists('imagecreatefromstring')&&function_exists('imagecreatetruecolor')&&function_exists('imagepng')){
    $image=@imagecreatefromstring($bytes);
    if($image){
     // Bake the white background into an email-only PNG: mailbox dark-mode CSS
     // cannot turn the transparent area behind the black logo dark.
     $card=imagecreatetruecolor(($width+16)*2,($height+16)*2);
     imagefill($card,0,0,imagecolorallocate($card,255,255,255));
     imagealphablending($card,true);
     imagecopyresampled($card,$image,16,16,0,0,$width*2,$height*2,$info[0],$info[1]);
     ob_start();imagepng($card);$bytes=ob_get_clean();imagedestroy($card);imagedestroy($image);
     $type='png';$width+=16;$height+=16;$imageStyle='background:#ffffff;';
    }
   }
   $mail->addStringEmbeddedImage($bytes,'company-logo','Firmenlogo.'.$type,'base64','image/'.$type);
   $logo='<tr><td style="padding:0 0 14px"><img src="cid:company-logo" alt="'.$escape($company['name']).'" width="'.$width.'" height="'.$height.'" style="display:block;border:0;max-width:100%;height:auto;'.$imageStyle.'"></td></tr>';
  }
 }
 $lines=mail_signature_lines($company);$details=implode('<br>',array_map(fn($s)=>nl2br($escape($s),false),$lines));
 $color=preg_match('/^#[0-9a-fA-F]{6}$/D',$company['brandColor']??'')?$company['brandColor']:'#2558d7';
 $mail->isHTML(true);
 $mail->Body='<!doctype html><html lang="de"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body style="margin:0;padding:24px 16px;background:#ffffff;color:#202936;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px"><tr><td style="padding-bottom:26px;overflow-wrap:anywhere">'.nl2br($escape($message),false).'</td></tr><tr><td style="border-top:2px solid '.$color.';padding-top:20px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0">'.$logo.'<tr><td style="font-size:17px;font-weight:bold;padding-bottom:5px;overflow-wrap:anywhere">'.$escape($company['name']).'</td></tr><tr><td style="font-size:13px;color:#4b5563;overflow-wrap:anywhere">'.$details.'</td></tr></table></td></tr></table></body></html>';
 $mail->AltBody=$message."\n\n—\n".$company['name']."\n".implode("\n",$lines);
}
function mail_get(array $u,string $id):array {
 require_feature($u['id'],'email');
 $doc=one('SELECT kind,data,number FROM kr_documents WHERE owner=? AND id=?',[$u['id'],$id]);if(!$doc)fail('Beleg nicht gefunden.',404);require_feature($u['id'],feature_kind($doc['kind']));
 mail_schema();return array_merge(mail_branding($u['id'],$doc),['ready'=>mail_ready(),'from'=>mail_ready()?config()['mail_from']:'','history'=>query('SELECT recipient,status,created FROM kr_mail_log WHERE owner=? AND document_id=? ORDER BY created DESC LIMIT 20',[$u['id'],$id])->fetchAll()]);
}
function mail_post(array $u,array $b):array {
 require_feature($u['id'],'email');
 if(!mail_ready())fail('E-Mail-Versand ist noch nicht eingerichtet. Bitte SMTP-Zugang auf dem Server konfigurieren.',503);
 $owner=$u['id'];$id=text($b['documentId']??'',36,true);
 $doc=one('SELECT * FROM kr_documents WHERE owner=? AND id=?',[$owner,$id]);if(!$doc)fail('Beleg nicht gefunden.',404);
 require_feature($owner,feature_kind($doc['kind']));
 if(integer($b['version']??null)!==(int)$doc['version'])fail('Beleg wurde geändert. Bitte schliessen und erneut öffnen.',409);
 $to=($b['target']??'')==='self'?$u['email']:text($b['to']??'',190,true);
 if(!filter_var($to,FILTER_VALIDATE_EMAIL)||preg_match('/[\r\n]/',$to))fail('E-Mail-Adresse prüfen.');
 $subject=text($b['subject']??'',180,true);if(preg_match('/[\r\n]/',$subject))fail('Betreff darf keinen Zeilenumbruch enthalten.');
 $message=text($b['message']??'',6000,true);
 $pdf=pdf_value($b['pdf']??'');if($pdf==='')fail('PDF fehlt.');$bytes=base64_decode(substr($pdf,28),true);
 $filename=text($b['filename']??'Beleg.pdf',180,true);$filename=preg_replace('/[^\p{L}\p{N} ._()-]/u','_',$filename);if(!str_ends_with(strtolower($filename),'.pdf'))$filename.='.pdf';
 $request=text($b['requestId']??'',36,true);if(!preg_match('/^[a-f0-9-]{36}$/D',$request))fail('Ungültige Versandkennung.');
 mail_schema();db()->beginTransaction();one('SELECT id FROM kr_users WHERE id=? FOR UPDATE',[$owner]);
 require_feature($owner,'email');require_feature($owner,feature_kind($doc['kind']));
 $old=one('SELECT status FROM kr_mail_log WHERE id=? AND owner=?',[$request,$owner]);
 if($old){db()->commit();return ['status'=>$old['status']];}
 $branding=mail_branding($owner,$doc);
 if(isset($b['companyVersion'])&&integer($b['companyVersion'])!==$branding['companyVersion'])fail('Firmendaten wurden geändert. Bitte den Beleg erneut öffnen und den E-Mail-Fussbereich prüfen.',409);
 limit_attempts('mail-owner',$owner,10);limit_attempts('mail-global','all',50);
 query("INSERT INTO kr_mail_log(id,owner,document_id,recipient,status,created) VALUES(?,?,?,?,'pending',?)",[$request,$owner,$id,$to,date('Y-m-d H:i:s')]);db()->commit();
 $accepted=false;
 try {
  foreach(['Exception','SMTP','PHPMailer'] as $name)require_once __DIR__.'/vendor/phpmailer/'.$name.'.php';
  $c=config();$mail=new \PHPMailer\PHPMailer\PHPMailer(true);$mail->isSMTP();$mail->Host=$c['smtp_host'];$mail->Port=(int)($c['smtp_port']??465);$mail->SMTPSecure=($c['smtp_security']??'ssl')==='tls'?'tls':'ssl';$mail->SMTPAuth=true;$mail->Username=$c['smtp_user'];$mail->Password=$c['smtp_password'];$mail->Timeout=20;$mail->CharSet='UTF-8';
  $mail->setFrom($c['mail_from'],$branding['company']['name']);$mail->addReplyTo($u['email']);$mail->addAddress($to);$mail->Subject=$subject;mail_company_body($mail,$branding['company'],$message);$mail->addStringAttachment($bytes,$filename,'base64','application/pdf');$mail->send();$accepted=true;
  query("UPDATE kr_mail_log SET status='accepted' WHERE id=? AND owner=?",[$request,$owner]);return ['status'=>'accepted'];
 }catch(Throwable $e){
  // A connection failure can occur after SMTP acceptance. Never retry automatically.
  if(!$accepted){query("UPDATE kr_mail_log SET status='unknown' WHERE id=? AND owner=?",[$request,$owner]);mail_failure_notice($u,$doc,$request,$to);}
  fail('Versand konnte nicht bestätigt werden. Bitte Postfach und Versandverlauf prüfen, bevor Sie erneut senden.',502);
 }
}
