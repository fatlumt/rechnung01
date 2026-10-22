import {useEffect,useState} from 'react';
import {api} from './api';
export default function MailNotifications(){
 const [items,setItems]=useState<any[]>([]),[busy,setBusy]=useState('');
 async function load(){const r=await api('notifications');setItems(r.items)}
 useEffect(()=>{const reload=()=>{if(!document.hidden)load().catch(()=>{})};reload();const t=setInterval(reload,60000);window.addEventListener('focus',reload);window.addEventListener('mail-attempt',reload);return()=>{clearInterval(t);window.removeEventListener('focus',reload);window.removeEventListener('mail-attempt',reload)}},[]);
 if(!items.length)return null;
 return <section className="mailNotifications" aria-label="E-Mail-Benachrichtigungen"><h2>E-Mail-Versand prüfen ({items.length})</h2>{items.map(n=><article key={n.id}><strong>{n.message}</strong><p>{n.created}</p><p>{n.email_status==='accepted'?'Eine Benachrichtigung an Ihre Konto-E-Mail wurde vom Mailserver angenommen.':n.email_status==='pending'?'Die E-Mail-Benachrichtigung wird versucht.':'Auch die E-Mail-Benachrichtigung konnte nicht bestätigt werden. Dieser Hinweis bleibt hier gespeichert.'}</p><button type="button" className="secondary" disabled={!!busy} onClick={async()=>{setBusy(n.id);try{await api('notifications',{id:n.id});await load()}catch{}finally{setBusy('')}}}>Als gelesen markieren</button></article>)}</section>;
}
