import {useEffect,useRef,useState} from 'react';
import {createPortal} from 'react-dom';
import {LayoutDashboard,FileText,Files,Users,Settings,Plus,X} from 'lucide-react';
import {t} from './i18n';
import {themeStyle} from './theme';
export default function MobileDock({view,change,create,more,features,color}:any){
 const [open,setOpen]=useState(false),plus=useRef<HTMLButtonElement>(null),panel=useRef<HTMLDivElement>(null);
 const allowed=(key:string)=>features?.[key]!==false;
 useEffect(()=>{if(!open)return;panel.current?.querySelector<HTMLButtonElement>('button')?.focus();const close=(e:KeyboardEvent)=>{if(e.key==='Escape'){setOpen(false);plus.current?.focus()}};window.addEventListener('keydown',close);return()=>window.removeEventListener('keydown',close)},[open]);
 const go=(id:string)=>{setOpen(false);change(id)};
 const item=(id:string,label:string,Icon:any)=><button type="button" className={view===id?'selected':''} aria-current={view===id?'page':undefined} onClick={()=>go(id)}><Icon size={23}/><span>{t(label)}</span></button>;
 return createPortal(<div className="phoneDockRoot" style={themeStyle(color)}>
 {open&&<><button className="phoneDockScrim" aria-label={t('Schliessen')} onClick={()=>{setOpen(false);plus.current?.focus()}}/><div className="phoneCreateChoices" ref={panel} id="phone-create-choices" role="region" aria-label={t('Beleg erstellen')}>{allowed('invoices')&&<button onClick={()=>{setOpen(false);create('invoice')}}><FileText size={22}/>{t('Neue Rechnung')}</button>}{allowed('offers')&&<button onClick={()=>{setOpen(false);create('offer')}}><Files size={22}/>{t('Neue Offerte')}</button>}</div></>}
 <nav className="phoneDock" aria-label={t('Hauptnavigation')}>
 {item('overview','Übersicht',LayoutDashboard)}
 {allowed('invoices')?item('invoice','Rechnungen',FileText):allowed('offers')?item('offer','Offerten',Files):<span/>}
 <button type="button" ref={plus} className="phoneDockAdd" aria-label={t('Beleg erstellen')} aria-expanded={open} aria-controls="phone-create-choices" disabled={!allowed('invoices')&&!allowed('offers')} onClick={()=>setOpen(!open)}>{open?<X size={30}/>:<Plus size={32}/>}</button>
 {allowed('customers')?item('customers','Kunden',Users):<span/>}
 <button type="button" className={!['overview','invoice','customers'].includes(view)?'selected':''} onClick={()=>{setOpen(false);more()}}><Settings size={23}/><span>{t('Mehr')}</span></button>
 </nav></div>,document.body);
}
