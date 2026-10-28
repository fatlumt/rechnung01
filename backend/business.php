<?php
declare(strict_types=1);
function business_schema():void {
 query("CREATE TABLE IF NOT EXISTS kr_deposit_links (deposit_id CHAR(36) PRIMARY KEY,owner CHAR(36) NOT NULL,final_id CHAR(36) NOT NULL,INDEX(owner,final_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 query("CREATE TABLE IF NOT EXISTS kr_expenses (id CHAR(36) PRIMARY KEY,owner CHAR(36) NOT NULL,data TEXT NOT NULL,receipt MEDIUMTEXT NULL,version INT NOT NULL DEFAULT 1,created DATETIME NOT NULL,INDEX(owner,created)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function deposit_breakdown(array $d):array {
 $lines=array_values(array_filter(pricing_lines($d),fn($l)=>($l['type']??'item')!=='section'));$subtotal=array_sum(array_map(fn($l)=>(int)round($l['price']*$l['quantity']),$lines));
 $discount=match($d['discountType']??'none'){'percent'=>(int)round($subtotal*($d['discountValue']??0)/10000),'fixed'=>$d['discountValue']??0,default=>0};
 $cumulative=0;$allocated=0;$net=0;$tax=0;$groups=[];
 foreach($lines as $l){$n=(int)round($l['price']*$l['quantity']);$cumulative+=$n;$next=$subtotal?(int)round($cumulative/$subtotal*$discount):0;$n-=$next-$allocated;$allocated=$next;$t=(int)round($n*$l['rate']/100);$net+=$n;$tax+=$t;$rate=(string)$l['rate'];$groups[$rate]=($groups[$rate]??0)+$t;}
 return ['net'=>$net,'tax'=>$tax,'total'=>$net+$tax,'groups'=>$groups];
}
function apply_deposits(string $owner,array &$v,?string $finalId=null):void {
 $v['depositCredits']=[];$ids=$v['depositIds']??[];
 if(($v['documentTitle']??'')!=='Schlussrechnung'&&count($ids))fail('Akonto-Abzug nur auf einer Schlussrechnung.');
 if(in_array($v['documentTitle']??'',['Akontorechnung','Schlussrechnung'],true)&&($v['project']??'')==='')fail('Bitte eine Projekt-/Baustellenreferenz eingeben.');
 foreach($ids as $id){
  $r=one('SELECT * FROM kr_documents WHERE owner=? AND id=?',[$owner,$id]);if(!$r)fail('Akontorechnung nicht gefunden.');$d=decode_row($r);
  if($d['kind']!=='invoice'||$d['status']==='draft'||($d['data']['documentTitle']??'')!=='Akontorechnung'||$d['paid']<$d['total'])fail('Nur vollständig bezahlte, ausgestellte Akontorechnungen können abgezogen werden.');
  if($d['data']['customerId']!==$v['customerId']||($d['data']['project']??'')!==($v['project']??'')||($d['data']['currency']??'CHF')!==($v['currency']??'CHF')||$d['data']['company']['vat']!==$v['company']['vat'])fail('Akonto muss zu Kunde, Projekt, Währung und Steuerprofil passen.');
  $link=one('SELECT final_id FROM kr_deposit_links WHERE owner=? AND deposit_id=?',[$owner,$id]);if($link&&$link['final_id']!==$finalId)fail('Diese Akontorechnung ist bereits einer Schlussrechnung zugeordnet.',409);
  $sum=deposit_breakdown($d['data']);$v['depositCredits'][]=array_merge($sum,['id'=>$id,'number'=>$d['number'],'date'=>$d['data']['date']]);
 }
 document_total($v);
}
function reserve_deposits(string $owner,string $finalId,array $v):void {
 query('DELETE FROM kr_deposit_links WHERE owner=? AND final_id=?',[$owner,$finalId]);
 foreach($v['depositIds']??[] as $id)query('INSERT INTO kr_deposit_links(deposit_id,owner,final_id) VALUES(?,?,?)',[$id,$owner,$finalId]);
}
function expense_save(string $owner,array $b):array {
 $v=$b['data']??[];if(!is_array($v))fail('Ausgabe fehlt.');$profile=one('SELECT data FROM kr_businesses WHERE owner=?',[$owner]);if(!$profile)fail('Bitte Firma einrichten.');$company=decode_row($profile)['data'];
 $id=isset($b['expenseId'])?text($b['expenseId'],36,true):id();$old=one('SELECT * FROM kr_expenses WHERE owner=? AND id=?',[$owner,$id]);if(isset($b['expenseId'])&&!$old)fail('Ausgabe nicht gefunden.',404);if($old&&integer($b['version']??null)!==(int)$old['version'])fail('Ausgabe wurde geändert. Bitte neu laden.',409);
 $d=['supplier'=>text($v['supplier']??'',180,true),'date'=>day($v['date']??''),'amount'=>integer($v['amount']??null,1,100000000000),'category'=>text($v['category']??'',80,true),'project'=>text($v['project']??'',160),'notes'=>text($v['notes']??'',1200),'currency'=>$company['currency']??'CHF'];
 $receipt=$old['receipt']??'';$name=$old?decode_row($old)['data']['receiptName']??'':'';
 if(array_key_exists('receipt',$b)){
  $receipt=$b['receipt'];if(!is_string($receipt)||strlen($receipt)>6666700)fail('Beleg zu gross (max. 5 MB).');$name=text($b['receiptName']??'',180);
  if($receipt!==''){
   if(str_starts_with($receipt,'data:application/pdf;base64,'))pdf_value($receipt);
   else {if(!preg_match('~^data:image/(jpeg|png|webp);base64,([A-Za-z0-9+/=]+)$~D',$receipt,$m))fail('Beleg muss PDF, JPG, PNG oder WebP sein.');$bytes=base64_decode($m[2],true);$info=$bytes===false?false:@getimagesizefromstring($bytes);if(!$info||strlen($bytes)>5000000||$info['mime']!=='image/'.$m[1]||$info[0]*$info[1]>40000000)fail('Bild ungültig oder zu gross.');}
  }else $name='';
 }
 $d['receiptName']=$name;$d['hasReceipt']=$receipt!=='';
 $d=array_replace($d,project_work_fields($owner,$v,$old?json_decode($old['data'],true):null));
 if($old)query('UPDATE kr_expenses SET data=?,receipt=?,version=version+1 WHERE owner=? AND id=?',[encode($d),$receipt,$owner,$id]);else query('INSERT INTO kr_expenses(id,owner,data,receipt,created) VALUES(?,?,?,?,?)',[$id,$owner,encode($d),$receipt,date('Y-m-d H:i:s')]);return ['expenseId'=>$id];
}
