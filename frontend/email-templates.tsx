import {t} from './i18n';
import {documentText} from './country';
import {useState} from 'react';
const kinds:Record<string,string>={invoice:'Rechnung',offer:'Offerte',reminder:'Zahlungserinnerung',dunning:'Mahnung'};
export const defaultEmailTemplate={subject:'{belegart} {belegnummer} – {firma}',message:'Guten Tag\n\nIm Anhang erhalten Sie {belegart} {belegnummer}.\n\nFreundliche Grüsse\n{firma}'};
export const defaultEmailTemplateFor=(value:any)=>({subject:documentText(value,defaultEmailTemplate.subject),message:documentText(value,defaultEmailTemplate.message),isDefault:true});
export default function EmailTemplates({value,set}:any){
 const [kind,setKind]=useState('invoice');const stored=value.emailTemplates?.[kind];const isDefault=!stored||stored.isDefault===true||(stored.isDefault===undefined&&stored.subject===defaultEmailTemplate.subject&&stored.message===defaultEmailTemplate.message);const current=isDefault?defaultEmailTemplateFor(value):stored;
 const update=(key:string,text:string)=>set({...value,emailTemplates:{...value.emailTemplates,[kind]:{...current,[key]:text,isDefault:false}}});
 const sample:Record<string,string>={kunde:'Musterkunde',firma:value.name||'Ihre Firma',belegart:documentText(value,kinds[kind]),belegnummer:({invoice:'RE',offer:'OF',reminder:'ZE',dunning:'MA'} as any)[kind]+'-2026-00001',belegdatum:'22.10.2026',faelligkeit:'21.11.2026'};
 const preview=(s:string)=>s.replace(/\{([a-zA-Z_]+)\}/g,(match,key)=>sample[key]??match);
 return <section className="emailTemplates" style={{margin:'24px 0',borderTop:'1px solid #e2e7ef',paddingTop:24}}><h2>{t("E-Mail-Vorlagen")}</h2><p className="muted">{t("Standardtexte für Ihre Belege. Vor jedem Versand können Sie den Text noch anpassen.")}</p>
 <label className="field"><span>{t("Belegart")}</span><select value={kind} onChange={e=>setKind(e.target.value)}>{Object.entries(kinds).map(([key,label])=><option value={key} key={key}>{t(label)}</option>)}</select></label>
 <label className="field"><span>{t("Betreff")}</span><input required maxLength={180} value={current.subject} onChange={e=>update('subject',e.target.value)}/></label>
 <label className="field"><span>{t("Nachricht")}</span><textarea required rows={8} maxLength={6000} value={current.message} onChange={e=>update('message',e.target.value)}/></label>
 <p className="hint">{t("Platzhalter:")} {'{kunde}'} · {'{firma}'} · {'{belegart}'} · {'{belegnummer}'} · {'{belegdatum}'} · {'{faelligkeit}'}{t(". Diese werden beim Öffnen der E-Mail automatisch ersetzt.")}</p>
 <p className="hint">{t("Ihr Firmenfussbereich mit Logo und Kontaktdaten wird zusätzlich angefügt. Änderungen unten mit „Firmendaten speichern“ sichern.")}</p>
 <details><summary style={{cursor:'pointer',padding:'12px 0'}}>{t("Textvorschau mit Beispieldaten")}</summary><div style={{padding:16,background:'#f5f7fa',borderRadius:8,overflowWrap:'anywhere'}}><strong>{preview(current.subject)}</strong><p style={{whiteSpace:'pre-wrap',marginTop:16}}>{preview(current.message)}</p></div></details>
 <button type="button" className="secondary" onClick={()=>{if(window.confirm(t("Den Text dieser Belegart auf den Standard zurücksetzen?")))set({...value,emailTemplates:{...value.emailTemplates,[kind]:{...defaultEmailTemplate,isDefault:true}}})}}>{t("Diese Vorlage zurücksetzen")}</button>
 </section>;
}
