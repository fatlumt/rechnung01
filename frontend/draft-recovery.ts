import {useEffect,useState} from 'react';
import {t} from './i18n';
const prefix='klarrechnung-draft:';
export function clearRecoveredDrafts(owner:string){try{for(const key of Object.keys(localStorage))if(key.startsWith(prefix+owner+':'))localStorage.removeItem(key)}catch{}}
export function useRecoveredDraft(owner:string,identity:string,initial:any){
 const key=prefix+owner+':'+identity;
 const [recovered]=useState(()=>{try{const item=JSON.parse(localStorage.getItem(key)||'null');return item&&Date.now()-item.time<7*86400000?item.value:null}catch{return null}});
 const [value,setValue]=useState(()=>({...initial,...recovered}));const [saved,setSaved]=useState(false);const [message,setMessage]=useState(recovered?'Lokaler Entwurf wiederhergestellt.':'');
 useEffect(()=>{if(saved)return;try{const {company,customer,sourceInvoice,...fields}=value;localStorage.setItem(key,JSON.stringify({time:Date.now(),value:fields}));setMessage('Auf diesem Gerät automatisch gesichert.')}catch{setMessage('Lokale Sicherung nicht verfügbar. Bitte Entwurf speichern.')}},[key,value,saved]);
 function clear(){setSaved(true);try{localStorage.removeItem(key)}catch{}}
 return {value,setValue,message:recovered?t('Entwurf wiederhergestellt')+' · '+t(message):t(message),clear};
}
