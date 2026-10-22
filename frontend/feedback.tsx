import {useEffect,useState} from 'react';
import {createPortal} from 'react-dom';
import './feedback.css';
export function notify(message:string,kind:'success'|'error'='success') {window.dispatchEvent(new CustomEvent('app-feedback',{detail:{message,kind,id:Date.now()}}));}
export default function Feedback(){
 const [item,setItem]=useState<any>(null);
 useEffect(()=>{const listener=(e:Event)=>setItem((e as CustomEvent).detail);let last=0;const invalid=()=>{if(Date.now()-last>1000){last=Date.now();notify('Bitte die markierten Pflichtfelder prüfen.','error')}};window.addEventListener('app-feedback',listener);document.addEventListener('invalid',invalid,true);return()=>{window.removeEventListener('app-feedback',listener);document.removeEventListener('invalid',invalid,true)}},[]);
 useEffect(()=>{if(!item||item.kind==='error')return;const t=setTimeout(()=>setItem(null),6000);return()=>clearTimeout(t)},[item]);
 return createPortal(item?<div className={'feedbackToast '+item.kind} role={item.kind==='error'?'alert':'status'}><span aria-hidden="true">{item.kind==='error'?'!':'✓'}</span><span>{item.message}</span><button type="button" aria-label="Meldung schliessen" onClick={()=>setItem(null)}>×</button></div>:null,document.body);
}
