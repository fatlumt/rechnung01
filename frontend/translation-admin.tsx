import React,{useEffect,useMemo,useRef,useState} from 'react';
import {Languages,Search,RotateCcw,Save,ChevronLeft,ChevronRight} from 'lucide-react';
import {api} from './api';
import {t} from './i18n';
import english from './locales/en.json';
import albanian from './locales/sq.json';
import './translation-admin.css';

type Locale='de'|'en'|'sq';
type Overrides=Record<string,Record<string,string>>;
type TranslationData={version:number,overrides:Overrides};
const defaults=english as Record<string,string>;
const pageSize=12;
const own=(value:object,key:string)=>Object.prototype.hasOwnProperty.call(value,key);

export default function TranslationAdmin({onSaved}:{onSaved?:(overrides:Overrides)=>void}){
 const [data,setData]=useState<TranslationData|null>(null),[locale,setLocale]=useState<Locale>('en'),[query,setQuery]=useState(''),[customOnly,setCustomOnly]=useState(false),[page,setPage]=useState(0),[source,setSource]=useState(''),[drafts,setDrafts]=useState<Record<string,string>>({}),[busy,setBusy]=useState(false),[error,setError]=useState(''),[notice,setNotice]=useState('');
 const editor=useRef<HTMLDivElement>(null);
 const base=(key:string)=>locale==='de'?key:locale==='sq'?(albanian as Record<string,string>)[key]||key:defaults[key];
 const overrides=data?.overrides[locale]||{};
 const keys=useMemo(()=>Object.keys(defaults).sort((a,b)=>a.localeCompare(b,'de')),[]);
 const filtered=keys.filter(key=>{
  if(customOnly&&!own(overrides,key))return false;
  const needle=query.trim().toLocaleLowerCase();
  return !needle||[key,base(key),overrides[key]||''].some(value=>value.toLocaleLowerCase().includes(needle));
 });
 const pages=Math.max(1,Math.ceil(filtered.length/pageSize)),shownPage=Math.min(page,pages-1);
 const draftKey=`${locale}\u0000${source}`,value=own(drafts,draftKey)?drafts[draftKey]:(overrides[source]??base(source)??''),custom=own(overrides,source);
 const placeholders=Array.from(new Set(source.match(/\{[a-zA-Z_][a-zA-Z0-9_]*\}/g)||[]));
 async function reload(){setError('');setBusy(true);try{const next=await api('translations');setData(next);onSaved?.(next.overrides)}catch(e:any){setError(e.message)}finally{setBusy(false)}}
 useEffect(()=>{reload()},[]);
 useEffect(()=>{if(source&&window.matchMedia('(max-width: 760px)').matches)editor.current?.scrollIntoView({block:'start',behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth'})},[source]);
 function forgetDraft(){setDrafts(old=>{const next={...old};delete next[draftKey];return next})}
 async function change(action:'save'|'reset'){
  if(!data||!source)return;setBusy(true);setError('');setNotice('');
  try{const next=await api('translations',{action,locale,source,text:value,version:data.version});setData(next);forgetDraft();onSaved?.(next.overrides);setNotice(action==='save'?t('Übersetzung gespeichert.'):t('Standardübersetzung wiederhergestellt.'))}
  catch(e:any){setError(e.message)}finally{setBusy(false)}
 }
 return <section className="translationAdmin" aria-label={t('Übersetzungen verwalten')}>
  <div className="translationAdminHeading"><div><Languages size={22}/><h2>{t('Übersetzungen verwalten')}</h2></div><button type="button" className="secondary" disabled={busy} onClick={reload}>{t('Neu laden')}</button></div>
  <p className="muted">{t('Diese Übersetzungen gelten für alle Konten. Firmendaten und eigene Texte bleiben unverändert. Aktualisierte Beschriftungen erscheinen beim nächsten Erstellen eines PDFs.')}</p>
  {error&&<div className="alert error" role="alert">{error}</div>}{notice&&<div className="alert success" role="status">{notice}</div>}
  <div className="translationTools"><label className="field"><span>{t('Sprache bearbeiten')}</span><select value={locale} disabled={busy} onChange={e=>{setLocale(e.target.value as Locale);setPage(0);setNotice('')}}><option value="de">Deutsch</option><option value="en">English</option><option value="sq">Shqip</option></select></label><label className="field"><span>{t('Text suchen')}</span><div className="translationSearch"><Search size={18}/><input type="search" value={query} placeholder={t('Original oder Übersetzung')} onChange={e=>{setQuery(e.target.value);setPage(0)}}/></div></label></div>
  <label className="checkbox"><input type="checkbox" checked={customOnly} onChange={e=>{setCustomOnly(e.target.checked);setPage(0)}}/>{t('Nur eigene Anpassungen anzeigen')}</label>
  {!data?<p role="status">{error?t('Übersetzungen nicht verfügbar.'):t('Übersetzungen werden geladen …')}</p>:<div className="translationWorkspace">
   <div className="translationResults"><p className="hint">{t('{count} Texte gefunden',{count:filtered.length})}</p>
    <div className="translationChoices">{filtered.slice(shownPage*pageSize,(shownPage+1)*pageSize).map(key=><button type="button" key={key} disabled={busy} className={`translationChoice${source===key?' selected':''}`} aria-pressed={source===key} onClick={()=>{setSource(key);setNotice('');setError('')}}><span data-no-translate="true">{key}</span><small>{own(overrides,key)?t('Angepasst'):t('Standard')}</small></button>)}</div>
    {!filtered.length&&<p className="muted">{t('Keine passenden Texte gefunden.')}</p>}
    <nav className="translationPagination" aria-label={t('Übersetzungsseiten')}><button type="button" className="secondary" disabled={busy||shownPage===0} aria-label={t('Vorherige Seite')} onClick={()=>setPage(shownPage-1)}><ChevronLeft size={18}/></button><span>{t('Seite {page} von {pages}',{page:shownPage+1,pages})}</span><button type="button" className="secondary" disabled={busy||shownPage>=pages-1} aria-label={t('Nächste Seite')} onClick={()=>setPage(shownPage+1)}><ChevronRight size={18}/></button></nav>
   </div>
   <div className="translationEditor" ref={editor}>{!source?<div className="translationEmpty"><Languages size={30}/><h3>{t('Text zum Bearbeiten auswählen')}</h3><p>{t('Suchen Sie nach dem sichtbaren App-Text und wählen Sie den passenden Eintrag.')}</p></div>:<form onSubmit={e=>{e.preventDefault();change('save')}}>
    <label className="field"><span>{t('Original (Deutsch)')}</span><div className="translationOriginal" data-no-translate="true">{source}</div></label>
    <label className="field"><span>{t('Mitgelieferte Übersetzung')}</span><div className="translationOriginal" data-no-translate="true">{base(source)}</div></label>
    <label className="field"><span>{t('Ihre Übersetzung')} <small>{custom?t('Angepasst'):t('Standard')}</small></span><textarea data-no-translate="true" required maxLength={8000} rows={6} value={value} disabled={busy} onChange={e=>setDrafts({...drafts,[draftKey]:e.target.value})}/></label>
    {!!placeholders.length&&<p className="hint">{t('Diese Platzhalter unverändert übernehmen:')} <code data-no-translate="true">{placeholders.join(', ')}</code></p>}
    <p className="hint">{t('Nur Text eingeben, kein HTML. Speichern veröffentlicht die Änderung für alle Konten. Bei einem Konflikt neu laden; Ihre Eingabe bleibt erhalten.')}</p>
    <div className="translationActions"><button className="primary" disabled={busy||!value.trim()}><Save size={16}/>{busy?t('Speichert …'):t('Übersetzung speichern')}</button><button type="button" className="secondary" disabled={busy||!custom} onClick={()=>change('reset')}><RotateCcw size={16}/>{t('Auf Standard zurücksetzen')}</button></div>
   </form>}</div>
  </div>}
 </section>;
}
