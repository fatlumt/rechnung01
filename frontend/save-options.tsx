import {useEffect,useRef,useState} from 'react';
import {Check,Download,FilePenLine,LoaderCircle,X} from 'lucide-react';
import {t} from './i18n';
import './save-options.css';

type SaveMode='save'|'pdf'|'draft';
type Props={published?:boolean;busy:boolean;close:()=>void;save:(mode:SaveMode)=>Promise<void>};

export default function SaveOptions({busy,close,save,published=false}:Props){
 const dialog=useRef<HTMLDialogElement>(null);
 const pending=useRef(false);
 const [selected,setSelected]=useState<SaveMode|null>(null);
 useEffect(()=>{dialog.current?.showModal()},[]);
 const locked=busy||selected!==null;
 const choose=async(mode:SaveMode)=>{
  if(pending.current)return;
  pending.current=true;
  setSelected(mode);
  try{await save(mode)}finally{pending.current=false;setSelected(null)}
 };
 const choices=[
  {mode:'save' as const,label:'Speichern',description:'Beleg ausstellen und Vorschau öffnen.',Icon:Check},
  {mode:'pdf' as const,label:'Speichern & PDF',description:'Beleg ausstellen und PDF herunterladen.',Icon:Download},
  {mode:'draft' as const,label:'Als Entwurf speichern',description:'Später weiterbearbeiten und ausstellen.',Icon:FilePenLine},
 ];
 return <dialog ref={dialog} className="documentSaveDialog" aria-labelledby="document-save-title"
  onKeyDown={e=>{if(e.key==='Escape')e.stopPropagation()}}
  onCancel={e=>{e.preventDefault();if(!locked)close()}}>
  <header className="documentSaveHeading"><h2 id="document-save-title">{t('Beleg speichern')}</h2><button type="button" className="iconButton" disabled={locked} aria-label={t('Schliessen')} onClick={close}><X size={22}/></button></header>
  <div className="documentSaveChoices">{choices.filter(c=>!published||c.mode!=='draft').map(({mode,label,description,Icon})=>
   <button key={mode} type="button" className="documentSaveChoice" disabled={locked} onClick={()=>choose(mode)}>
    <span className="documentSaveIcon">{selected===mode?<LoaderCircle className="saveSpinner" size={23}/>:<Icon size={23}/>}</span>
    <span><strong>{selected===mode?t('Speichert …'):t(label)}</strong><small>{t(published?(mode==='pdf'?'Änderungen speichern und PDF herunterladen.':'Änderungen speichern und Vorschau öffnen.'):description)}</small></span>
   </button>
  )}</div>
  <button className="secondary documentSaveCancel" type="button" disabled={locked} onClick={close}>{t('Abbrechen')}</button>
 </dialog>;
}
