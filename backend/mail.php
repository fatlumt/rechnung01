<?php
declare(strict_types=1);
function mail_schema():void {
 query("CREATE TABLE IF NOT EXISTS kr_mail_log (id CHAR(36) PRIMARY KEY,owner CHAR(36) NOT NULL,document_id CHAR(36) NOT NULL,recipient VARCHAR(190) NOT NULL,status VARCHAR(16) NOT NULL,created DATETIME NOT NULL,INDEX(owner,document_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function mail_ready():bool {$c=config();return !empty($c['smtp_host'])&&!empty($c['smtp_user'])&&!empty($c['smtp_password'])&&filter_var($c['mail_from']??'',FILTER_VALIDATE_EMAIL);}
function mail_get(array $u,string $id):array {
 if(!one('SELECT id FROM kr_documents WHERE owner=? AND id=?',[$u['id'],$id]))fail('Beleg nicht gefunden.',404);
 mail_schema();return ['ready'=>mail_ready(),'from'=>mail_ready()?config()['mail_from']:'','history'=>query('SELECT recipient,status,created FROM kr_mail_log WHERE owner=? AND document_id=? ORDER BY created DESC LIMIT 20',[$u['id'],$id])->fetchAll()];
}
function mail_post(array $u,array $b):array {
 if(!mail_ready())fail('E-Mail-Versand ist noch nicht eingerichtet. Bitte SMTP-Zugang auf dem Server konfigurieren.',503);
 $owner=$u['id'];$id=text($b['documentId']??'',36,true);
 $doc=one('SELECT * FROM kr_documents WHERE owner=? AND id=?',[$owner,$id]);if(!$doc)fail('Beleg nicht gefunden.',404);
 if(integer($b['version']??null)!==(int)$doc['version'])fail('Beleg wurde geändert. Bitte schliessen und erneut öffnen.',409);
 $to=($b['target']??'')==='self'?$u['email']:text($b['to']??'',190,true);
 if(!filter_var($to,FILTER_VALIDATE_EMAIL)||preg_match('/[\r\n]/',$to))fail('E-Mail-Adresse prüfen.');
 $subject=text($b['subject']??'',180,true);if(preg_match('/[\r\n]/',$subject))fail('Betreff darf keinen Zeilenumbruch enthalten.');
 $message=text($b['message']??'',6000,true);
 $pdf=pdf_value($b['pdf']??'');if($pdf==='')fail('PDF fehlt.');$bytes=base64_decode(substr($pdf,28),true);
 $filename=text($b['filename']??'Beleg.pdf',180,true);$filename=preg_replace('/[^\p{L}\p{N} ._()-]/u','_',$filename);if(!str_ends_with(strtolower($filename),'.pdf'))$filename.='.pdf';
 $request=text($b['requestId']??'',36,true);if(!preg_match('/^[a-f0-9-]{36}$/D',$request))fail('Ungültige Versandkennung.');
 mail_schema();db()->beginTransaction();one('SELECT id FROM kr_users WHERE id=? FOR UPDATE',[$owner]);
 $old=one('SELECT status FROM kr_mail_log WHERE id=? AND owner=?',[$request,$owner]);
 if($old){db()->commit();return ['status'=>$old['status']];}
 limit_attempts('mail-owner',$owner,10);limit_attempts('mail-global','all',50);
 query("INSERT INTO kr_mail_log(id,owner,document_id,recipient,status,created) VALUES(?,?,?,?,'pending',?)",[$request,$owner,$id,$to,date('Y-m-d H:i:s')]);db()->commit();
 $accepted=false;
 try {
  foreach(['Exception','SMTP','PHPMailer'] as $name)require_once __DIR__.'/vendor/phpmailer/'.$name.'.php';
  $c=config();$mail=new \PHPMailer\PHPMailer\PHPMailer(true);$mail->isSMTP();$mail->Host=$c['smtp_host'];$mail->Port=(int)($c['smtp_port']??465);$mail->SMTPSecure=($c['smtp_security']??'ssl')==='tls'?'tls':'ssl';$mail->SMTPAuth=true;$mail->Username=$c['smtp_user'];$mail->Password=$c['smtp_password'];$mail->Timeout=20;$mail->CharSet='UTF-8';
  $mail->setFrom($c['mail_from'],$c['mail_from_name']??'digitalrechnung');$mail->addReplyTo($u['email']);$mail->addAddress($to);$mail->Subject=$subject;$mail->Body=$message;$mail->addStringAttachment($bytes,$filename,'base64','application/pdf');$mail->send();$accepted=true;
  query("UPDATE kr_mail_log SET status='accepted' WHERE id=? AND owner=?",[$request,$owner]);return ['status'=>'accepted'];
 }catch(Throwable $e){
  // A connection failure can occur after SMTP acceptance. Never retry automatically.
  if(!$accepted)query("UPDATE kr_mail_log SET status='unknown' WHERE id=? AND owner=?",[$request,$owner]);
  fail('Versand konnte nicht bestätigt werden. Bitte Postfach und Versandverlauf prüfen, bevor Sie erneut senden.',502);
 }
}
