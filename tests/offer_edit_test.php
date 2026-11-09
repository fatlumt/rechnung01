<?php
require __DIR__.'/../backend/data.php';
function text($v,...$rest){return $v;} function integer($v){return $v;}
function fail($s,$code=400){throw new RuntimeException($s,$code);}
function invoice_values($v){return $v;} function encode($v){return json_encode($v);} function decode_row($v){return $v;}
function draft($v){return $v;} function document_language($v){return $v;} function template_id($v){return $v;}
function pricing_lines($v){return [];} function apply_deposits(...$v){} function reserve_deposits(...$v){} function features_for($v){return ['projects'=>false];} function document_total($v){return $v['price'];}
function one($sql,$args){global $doc;if($args[0]!=='owner')return null;if(str_contains($sql,'kr_document_trash'))return null;if(str_contains($sql,'kr_customers'))return ['data'=>['name'=>'Client','country'=>'CH']];return $doc;}
function query($sql,$args=[]){global $writes;$writes[]=[$sql,$args];}
$data=['kind'=>'offer','customerId'=>'customer','price'=>100,'company'=>['country'=>'CH','vat'=>false],'customer'=>['country'=>'CH']];
$doc=['id'=>'offer','kind'=>'offer','status'=>'issued','version'=>4,'data'=>$data,'total'=>100];$writes=[];
data_change('owner',['action'=>'edit','id'=>'offer','version'=>4,'data'=>array_merge($data,['price'=>200])]);
if(count($writes)!==2||json_decode($writes[0][1][3],true)!==$doc||$writes[1][1][1]!==200||array_slice($writes[1][1],2)!==['owner','offer'])throw new Exception('Revision or isolated update failed');
$doc['status']='accepted';$writes=[];data_change('owner',['action'=>'edit','id'=>'offer','version'=>4,'data'=>$data]);if(!str_contains($writes[1][0],"status='issued'"))throw new Exception('Acceptance not reset');
foreach(['invoice','stale','other-owner'] as $case){$doc['kind']=$case==='invoice'?'invoice':'offer';$writes=[];try{data_change($case==='other-owner'?'other':'owner',['action'=>'edit','id'=>'offer','version'=>$case==='stale'?3:4,'data'=>$data]);throw new LogicException('Expected rejection');}catch(RuntimeException $e){}if($writes)throw new Exception('Rejected edit wrote data');}
echo "PASS: issued offer revision, accepted status reset, invoice immutability, stale version and tenant isolation.\n";
