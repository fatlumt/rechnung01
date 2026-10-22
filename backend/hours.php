<?php
declare(strict_types=1);
function hours_schema():void {
 foreach(['kr_employees','kr_hours'] as $table)query("CREATE TABLE IF NOT EXISTS $table (id CHAR(36) PRIMARY KEY,owner CHAR(36) NOT NULL,data MEDIUMTEXT NOT NULL,version INT NOT NULL DEFAULT 1,created DATETIME NOT NULL,INDEX(owner)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function hours_list(string $owner,string $table):array{return array_map('decode_row',query("SELECT * FROM $table WHERE owner=? ORDER BY created DESC,id",[$owner])->fetchAll());}
function hours_record(string $owner,string $table,string $id):array{$r=one("SELECT * FROM $table WHERE owner=? AND id=?",[$owner,$id]);if(!$r)fail('Eintrag nicht gefunden.',404);return decode_row($r);}
function hours_clock(string $s):int {
 $d=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$s,new DateTimeZone('Europe/Zurich'));
 if(!$d||$d->format('Y-m-d\TH:i')!==$s)fail('Datum und Uhrzeit sind ungültig.');return $d->getTimestamp();
}
function hours_no_overlap(string $owner,string $employee,int $start,int $end,string $exclude=''):void {
 foreach(hours_list($owner,'kr_hours') as $r){$v=$r['data'];if($r['id']===$exclude||$v['employeeId']!==$employee)continue;
 if($start<($v['end']??PHP_INT_MAX)&&$end>$v['start'])fail('Diese Zeit überschneidet sich mit einem anderen Eintrag oder Timer.');}
}
function hours_write(string $owner,string $table,array $v,?array $old):array {
 $id=$old['id']??id();if($old)query("UPDATE $table SET data=?,version=version+1 WHERE owner=? AND id=?",[encode($v),$owner,$id]);else query("INSERT INTO $table(id,owner,data,created) VALUES(?,?,?,?)",[$id,$owner,encode($v),date('Y-m-d H:i:s')]);return ['id'=>$id];
}
function hours_change(string $owner,array $b):array {
 $action=$b['action'];$table=$action==='employee'?'kr_employees':'kr_hours';$old=isset($b['id'])&&$b['id']?hours_record($owner,$table,text($b['id'],36,true)):null;
 if($old&&integer($b['version']??null)!==$old['version'])fail('Eintrag wurde geändert. Bitte neu laden.',409);
 if($action==='employee'){$v=$b['data']??[];$v=['name'=>text($v['name']??'',160,true),'role'=>text($v['role']??'',100),'hourlyCost'=>integer($v['hourlyCost']??0,0,10000000),'active'=>($v['active']??true)===true];if(!$v['active']&&$old){foreach(hours_list($owner,'kr_hours') as $r)if($r['data']['employeeId']===$old['id']&&$r['data']['state']!=='done')fail('Bitte zuerst den laufenden Timer stoppen.');}return hours_write($owner,$table,$v,$old);}
 if($action==='hoursDelete'){if(!$old)fail('Eintrag fehlt.');query('DELETE FROM kr_hours WHERE owner=? AND id=?',[$owner,$old['id']]);return ['ok'=>true];}
 if($action==='hoursTimer'&&$old){$v=$old['data'];$op=$b['op']??'';$now=time();
  if($v['state']==='done')fail('Timer wurde bereits gestoppt.',409);
  if($op==='pause'&&$v['state']==='running'){$v['seconds']+=$now-$v['since'];$v['state']='paused';$v['since']=null;}
  elseif($op==='resume'&&$v['state']==='paused'){$v['since']=$now;$v['state']='running';}
  elseif($op==='stop'){if($v['state']==='running')$v['seconds']+=$now-$v['since'];$v['end']=$now;$v['state']='done';$v['since']=null;$v['breakSeconds']=max(0,$now-$v['start']-$v['seconds']);}
  else fail('Timer-Aktion passt nicht zum aktuellen Zustand.',409);
  return hours_write($owner,$table,$v,$old);
 }
 if(!in_array($action,['hoursSave','hoursTimer'],true))fail('Aktion ungültig.');
 $raw=$b['data']??[];$employee=hours_record($owner,'kr_employees',text($raw['employeeId']??'',36,true));if(!$employee['data']['active']&&(!$old||$old['data']['employeeId']!==$employee['id']))fail('Mitarbeiter ist archiviert.');
 $v=['employeeId'=>$employee['id'],'project'=>text($raw['project']??'',200,true),'notes'=>text($raw['notes']??'',2000),'hourlyCost'=>$old&&$old['data']['employeeId']===$employee['id']?$old['data']['hourlyCost']:$employee['data']['hourlyCost']];
 if($action==='hoursTimer'){if(($b['op']??'')!=='start')fail('Timer-Aktion ungültig.');$v+=['start'=>time(),'end'=>null,'seconds'=>0,'breakSeconds'=>0,'since'=>time(),'state'=>'running'];hours_no_overlap($owner,$employee['id'],$v['start'],PHP_INT_MAX);}
 else {if($old&&$old['data']['state']!=='done')fail('Bitte zuerst den Timer stoppen.');$start=hours_clock(text($raw['start']??'',16,true));$end=hours_clock(text($raw['end']??'',16,true));$break=integer($raw['breakMinutes']??0,0,1440)*60;if($end<=$start||$end-$start>86400||$break>=$end-$start||$end>time())fail('Zeitraum prüfen: maximal 24 Stunden, keine Zukunft und Pause kürzer als Arbeitszeit.');hours_no_overlap($owner,$employee['id'],$start,$end,$old['id']??'');$v+=['start'=>$start,'end'=>$end,'seconds'=>$end-$start-$break,'breakSeconds'=>$break,'since'=>null,'state'=>'done'];}
 return hours_write($owner,$table,$v,$old);
}
