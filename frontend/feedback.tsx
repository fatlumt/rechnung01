import {t} from './i18n';
import {useEffect,useState} from 'react';
import {createPortal} from 'react-dom';
import './feedback.css';
export function notify(message:string,kind:'success'|'error'='success') {window.dispatchEvent(new CustomEvent('app-feedback',{detail:{message,kind,id:Date.now()}}));}
export default function Feedback(){
 const [item,setItem]=useState<any>(null),[pending,setPending]=useState<number[]>([]),[slow,setSlow]=useState(false);
 useEffect(()=>{const activity=(e:Event)=>{const {id,active}=(e as CustomEvent).detail;setPending(ids=>active?[...ids,id]:ids.filter(v=>v!==id));};window.addEventListener('app-activity',activity);return()=>window.removeEventListener('app-activity',activity)},[]);
 useEffect(()=>{if(!pending.length){setSlow(false);return;}const timer=setTimeout(()=>setSlow(true),8000);return()=>clearTimeout(timer)},[!!pending.length]);
 useEffect(()=>{const listener=(e:Event)=>setItem((e as CustomEvent).detail);let last=0;const invalid=()=>{if(Date.now()-last>1000){last=Date.now();notify('Bitte die markierten Pflichtfelder prüfen.','error')}};window.addEventListener('app-feedback',listener);document.addEventListener('invalid',invalid,true);return()=>{window.removeEventListener('app-feedback',listener);document.removeEventListener('invalid',invalid,true)}},[]);
 useEffect(()=>{if(!item||item.kind==='error')return;const t=setTimeout(()=>setItem(null),6000);return()=>clearTimeout(t)},[item]);
 return createPortal(<>{pending.length>0&&<div className="savingIndicator" role="status" aria-live="polite"><span className="savingSpinner" aria-hidden="true"/><span><strong>{t("Wird verarbeitet …")}</strong><small>{t(slow?"Es dauert etwas länger. Bitte nicht erneut speichern.":"Bitte kurz warten.")}</small></span></div>}{item?<div className={'feedbackToast '+item.kind} role={item.kind==='error'?'alert':'status'}><span aria-hidden="true">{item.kind==='error'?'!':'✓'}</span><span>{t(item.message)}</span><button type="button" aria-label={t("Meldung schliessen")} onClick={()=>setItem(null)}>×</button></div>:null}</>,document.body);
}
