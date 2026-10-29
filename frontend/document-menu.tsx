import {useEffect,useRef,useState} from 'react';
import {createPortal} from 'react-dom';
import {MoreHorizontal,Pencil,Archive,ArchiveRestore,Trash2,FileText,BellRing} from 'lucide-react';
import {t} from './i18n';
import {useFeatures} from './features';
import {currencyOf} from './country';

export default function DocumentMenu({doc,busy,choose,converted}:any){
 const features=useFeatures(),trigger=useRef<HTMLButtonElement>(null),panel=useRef<HTMLDivElement>(null);
 const [position,setPosition]=useState<{top?:number,bottom?:number,left:number}|null>(null);
 const actions:any[]=[];
 if(doc.trashed)actions.push(['restore','Wiederherstellen',ArchiveRestore]);
 else{
  if(doc.status==='draft')actions.push(['edit','Bearbeiten',Pencil]);
  if(doc.kind==='offer'&&features.invoices!==false)actions.push(['convert',converted?'Rechnung öffnen':'In Rechnung umwandeln',FileText]);
  if(doc.kind==='invoice'&&doc.status!=='draft'&&doc.paid<doc.total&&features.reminders!==false){
   actions.push(['reminder','Zahlungserinnerung erstellen',BellRing]);
   actions.push(['dunning',doc.fee?'Mahnung erneut erstellen':currencyOf(doc.data)==='CHF'?'Mahnung (+ CHF 50) erstellen':'Mahnung erstellen',BellRing]);
  }
  if(doc.status!=='draft')actions.push([doc.archived?'unarchive':'archive',doc.archived?'Wiederherstellen':'Archivieren',doc.archived?ArchiveRestore:Archive]);
  actions.push(['delete','Löschen',Trash2]);
 }
 function close(focus=false){setPosition(null);if(focus)trigger.current?.focus();}
 function toggle(){if(position){close();return;}const r=trigger.current!.getBoundingClientRect(),width=Math.min(272,window.innerWidth-24),height=actions.length*48+16;
  setPosition({left:Math.max(12,Math.min(r.right-width,window.innerWidth-width-12)),...(r.bottom+height+8<=window.innerHeight?{top:r.bottom+6}:{bottom:Math.max(12,window.innerHeight-r.top+6)})});
 }
 useEffect(()=>{if(!position)return;panel.current?.querySelector<HTMLButtonElement>('button')?.focus();
  const outside=(e:PointerEvent)=>{if(!panel.current?.contains(e.target as Node)&&!trigger.current?.contains(e.target as Node))close();};
  const key=(e:KeyboardEvent)=>{if(e.key==='Escape'){e.preventDefault();e.stopPropagation();close(true);return;}if(e.key==='Tab'){close();return;}if(!['ArrowDown','ArrowUp','Home','End'].includes(e.key))return;e.preventDefault();const buttons=Array.from(panel.current?.querySelectorAll<HTMLButtonElement>('button')||[]);const index=buttons.indexOf(document.activeElement as HTMLButtonElement);buttons[e.key==='Home'?0:e.key==='End'?buttons.length-1:(index+(e.key==='ArrowDown'?1:-1)+buttons.length)%buttons.length]?.focus();};
  const scroll=()=>close();document.addEventListener('pointerdown',outside);document.addEventListener('keydown',key,true);window.addEventListener('resize',scroll);window.addEventListener('scroll',scroll,true);
  return()=>{document.removeEventListener('pointerdown',outside);document.removeEventListener('keydown',key,true);window.removeEventListener('resize',scroll);window.removeEventListener('scroll',scroll,true);};
 },[!!position]);
 return <><button ref={trigger} className="documentMoreButton" disabled={busy} aria-label={t('Aktionen {number}',{number:doc.number})} aria-haspopup="menu" aria-expanded={!!position} onClick={toggle}><MoreHorizontal size={22}/></button>{position&&createPortal(<div ref={panel} className="documentRowMenu" role="menu" aria-label={t('Aktionen {number}',{number:doc.number})} style={position}>{actions.map(([id,label,Icon])=><button role="menuitem" className={id==='delete'?'danger':''} key={id} onClick={()=>{close(true);choose(doc,id)}}><Icon size={18}/><span>{t(label)}</span></button>)}</div>,document.body)}</>;
}
