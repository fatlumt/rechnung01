<?php
declare(strict_types=1);
// Execute production validators and project operations against an isolated database.
$pdo=new PDO('sqlite::memory:');$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
function db():PDO{global $pdo;return $pdo;}
function query(string $sql,array $args=[]):PDOStatement{$sql=str_replace([' FOR UPDATE',' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'],'',$sql);$sql=preg_replace('/,INDEX\([^)]+\)/','',$sql);$s=db()->prepare($sql);$s->execute($args);return $s;}
function one(string $sql,array $args=[]):?array{return query($sql,$args)->fetch()?:null;}
$core=file_get_contents(__DIR__.'/../backend/core.php');$a=strpos($core,'function config()');$z=strpos($core,'function id()');$core=substr($core,0,$a).substr($core,$z);eval(substr($core,5));
$enabled=true;function require_feature(string $owner,string $feature):void{global $enabled;if(!$enabled)fail('Feature disabled',403);}
function feature_kind(string $kind):string{return $kind;}
require __DIR__.'/../backend/hours.php';require __DIR__.'/../backend/projects.php';
projects_schema();projects_schema();query('CREATE TABLE kr_customers(id TEXT PRIMARY KEY,owner TEXT,data TEXT)');query('CREATE TABLE kr_documents(id TEXT PRIMARY KEY,owner TEXT,kind TEXT,data TEXT,version INT DEFAULT 1)');
foreach([['c1','a'],['c2','a'],['c3','b']] as [$id,$owner])query('INSERT INTO kr_customers VALUES(?,?,?)',[$id,$owner,encode(['name'=>$id])]);
$checks=0;function check(bool $ok,string $message):void{global $checks;if(!$ok)throw new RuntimeException($message);$checks++;}
function rejects(callable $f,int $status=400):void{try{$f();throw new RuntimeException('Expected rejection');}catch(AppError $e){check($e->status===$status,'Expected status '.$status.' got '.$e->status);}}
function make_project(string $owner,string $customer,string $name='Bathroom'):string{return projects_change($owner,['action'=>'projectSave','data'=>['name'=>$name,'customerId'=>$customer]])['id'];}
$p=make_project('a','c1');$other=make_project('b','c3');$p2=make_project('a','c1','Kitchen');
check(count(projects_list('a'))===2,'Owner-scoped list');rejects(fn()=>project_record('b',$p),404);rejects(fn()=>make_project('a','c3'),404);
$v=project_record('a',$p);check($v['data']['status']==='active','Default status');rejects(fn()=>projects_change('a',['action'=>'projectSave','id'=>$p,'version'=>0,'data'=>$v['data']]),409);
rejects(fn()=>projects_change('a',['action'=>'projectSave','id'=>$p,'version'=>1,'data'=>array_merge($v['data'],['customerId'=>'c2'])]));
query('INSERT INTO kr_documents(id,owner,kind,data) VALUES(?,?,?,?)',['d1','a','invoice',encode(['customerId'=>'c1','project'=>'Old label'])]);query('INSERT INTO kr_documents(id,owner,kind,data) VALUES(?,?,?,?)',['d2','a','offer',encode(['customerId'=>'c2'])]);query('INSERT INTO kr_documents(id,owner,kind,data) VALUES(?,?,?,?)',['d3','b','invoice',encode(['customerId'=>'c3'])]);
$original=one('SELECT data FROM kr_documents WHERE id=?',['d1'])['data'];
projects_change('a',['action'=>'projectLink','projectId'=>$p,'documentId'=>'d1','version'=>1]);check(one('SELECT data FROM kr_documents WHERE id=?',['d1'])['data']===$original,'Issued snapshot untouched');check(one('SELECT project_id FROM kr_project_documents WHERE document_id=?',['d1'])['project_id']===$p,'Assigned');
rejects(fn()=>projects_change('a',['action'=>'projectLink','projectId'=>$p,'documentId'=>'d2','version'=>1]));rejects(fn()=>projects_change('a',['action'=>'projectLink','projectId'=>$p,'documentId'=>'d3','version'=>1]),404);rejects(fn()=>projects_change('a',['action'=>'projectLink','projectId'=>$p2,'documentId'=>'d1','version'=>2]));
rejects(fn()=>projects_change('a',['action'=>'projectLink','projectId'=>$p,'documentId'=>'d1','version'=>1,'unlink'=>true]),409);
projects_change('a',['action'=>'projectSave','id'=>$p,'version'=>1,'data'=>array_merge($v['data'],['name'=>'Renamed'])]);check(one('SELECT project_id FROM kr_project_documents WHERE document_id=?',['d1'])['project_id']===$p,'Rename preserves link');
project_attach_new('a','follow-up',['customerId'=>'c1','sourceId'=>'d1','sourceInvoice'=>['number'=>'RE-1'],'projectId'=>$p2]);check(one('SELECT project_id FROM kr_project_documents WHERE document_id=?',['follow-up'])['project_id']===$p,'Reminder inherits current source folder');
$note=projects_change('a',['action'=>'projectItemSave','projectId'=>$p,'data'=>['kind'=>'note','title'=>'Measurements','text'=>'Wall 2.4 m']])['id'];
$result=projects_change('a',['action'=>'projectGet','projectId'=>$p]);check(count($result['items'])===1&&$result['items'][0]['data']['text']==='Wall 2.4 m','Notes stored');rejects(fn()=>projects_change('b',['action'=>'projectGet','projectId'=>$p]),404);
rejects(fn()=>projects_change('a',['action'=>'projectItemSave','projectId'=>$p,'id'=>$note,'version'=>0,'data'=>['kind'=>'note','title'=>'x','text'=>'y']]),409);
projects_change('a',['action'=>'projectItemSave','projectId'=>$p,'id'=>$note,'version'=>1,'data'=>['kind'=>'note','title'=>'Updated','text'=>'Wall 2.5 m']]);
$bytes="%PDF-1.4\noriginal bytes\n%%EOF";$payload='data:application/pdf;base64,'.base64_encode($bytes);
$file=projects_change('a',['action'=>'projectItemSave','projectId'=>$p,'name'=>'plan.pdf','file'=>$payload,'data'=>['kind'=>'file','title'=>'Plan','text'=>'Ground floor']])['id'];
$download=projects_change('a',['action'=>'projectFile','projectId'=>$p,'id'=>$file]);check($download['file']===$payload,'Exact original PDF bytes retained');check($download['name']==='plan.pdf','Filename retained');
$items=projects_change('a',['action'=>'projectGet','projectId'=>$p])['items'];check(!array_key_exists('payload',$items[0]),'No file bodies in metadata');rejects(fn()=>projects_change('a',['action'=>'projectFile','projectId'=>$p2,'id'=>$file]),404);rejects(fn()=>projects_change('b',['action'=>'projectFile','projectId'=>$other,'id'=>$file]),404);
rejects(fn()=>project_file('data:text/plain;base64,'.base64_encode('x'),'bad.exe'));rejects(fn()=>project_file('data:image/png;base64,'.base64_encode('<script>'),'bad.png'));rejects(fn()=>project_file('data:application/pdf;base64,'.base64_encode('not pdf'),'bad.pdf'));rejects(fn()=>project_file('data:text/plain;base64,'.base64_encode("\0"),'bad.txt'));
check(project_file('data:text/plain;base64,'.base64_encode('hello'),'notes.txt')['mime']==='text/plain','Text download supported');
query('UPDATE kr_project_items SET bytes=100000000 WHERE id=?',[$file]);rejects(fn()=>projects_change('a',['action'=>'projectItemSave','projectId'=>$p,'name'=>'plan.pdf','file'=>$payload,'data'=>['kind'=>'file','title'=>'Too much']]));
rejects(fn()=>projects_change('a',['action'=>'projectItemDelete','projectId'=>$p,'id'=>$note,'version'=>1]),409);projects_change('a',['action'=>'projectItemDelete','projectId'=>$p,'id'=>$note,'version'=>2]);check(!one('SELECT id FROM kr_project_items WHERE id=?',[$note]),'Versioned delete');
$enabled=false;rejects(fn()=>projects_change('a',['action'=>'projectGet','projectId'=>$p]),403);
echo "PASS: $checks project checks: isolation, links, snapshots, conflicts, uploads, quotas, exact downloads and feature gate.\n";
