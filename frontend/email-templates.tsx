import {useState} from 'react';
const kinds:Record<string,string>={invoice:'Rechnung',offer:'Offerte',reminder:'Zahlungserinnerung',dunning:'Mahnung'};
export const defaultEmailTemplate={subject:'{belegart} {belegnummer} – {firma}',message:'Guten Tag\n\nIm Anhang erhalten Sie {belegart} {belegnummer}.\n\nFreundliche Grüsse\n{firma}'};
export default function EmailTemplates({value,set}:any){
 const [kind,setKind]=useState('invoice');const current=value.emailTemplates?.[kind]||defaultEmailTemplate;
 const update=(key:string,text:string)=>set({...value,emailTemplates:{...value.emailTemplates,[kind]:{...current,[key]:text}}});
 const sample:Record<string,string>={kunde:'Musterkunde',firma:value.name||'Ihre Firma',belegart:kinds[kind],belegnummer:({invoice:'RE',offer:'OF',reminder:'ZE',dunning:'MA'} as any)[kind]+'-2026-00001',belegdatum:'22.10.2026',faelligkeit:'21.11.2026'};
 const preview=(s:string)=>s.replace(/\{([a-zA-Z_]+)\}/g,(match,key)=>sample[key]??match);
 return <section className="emailTemplates" style={{margin:'24px 0',borderTop:'1px solid #e2e7ef',paddingTop:24}}><h2>E-Mail-Vorlagen</h2><p className="muted">Standardtexte für Ihre Belege. Vor jedem Versand können Sie den Text noch anpassen.</p>
 <label className="field"><span>Belegart</span><select value={kind} onChange={e=>setKind(e.target.value)}>{Object.entries(kinds).map(([key,label])=><option value={key} key={key}>{label}</option>)}</select></label>
 <label className="field"><span>Betreff</span><input required maxLength={180} value={current.subject} onChange={e=>update('subject',e.target.value)}/></label>
 <label className="field"><span>Nachricht</span><textarea required rows={8} maxLength={6000} value={current.message} onChange={e=>update('message',e.target.value)}/></label>
 <p className="hint">Platzhalter: {'{kunde}'} · {'{firma}'} · {'{belegart}'} · {'{belegnummer}'} · {'{belegdatum}'} · {'{faelligkeit}'}. Diese werden beim Öffnen der E-Mail automatisch ersetzt.</p>
 <p className="hint">Ihr Firmenfussbereich mit Logo und Kontaktdaten wird zusätzlich angefügt. Änderungen unten mit „Firmendaten speichern“ sichern.</p>
 <details><summary style={{cursor:'pointer',padding:'12px 0'}}>Textvorschau mit Beispieldaten</summary><div style={{padding:16,background:'#f5f7fa',borderRadius:8,overflowWrap:'anywhere'}}><strong>{preview(current.subject)}</strong><p style={{whiteSpace:'pre-wrap',marginTop:16}}>{preview(current.message)}</p></div></details>
 <button type="button" className="secondary" onClick={()=>{if(window.confirm('Den Text dieser Belegart auf den Standard zurücksetzen?'))set({...value,emailTemplates:{...value.emailTemplates,[kind]:{...defaultEmailTemplate}}})}}>Diese Vorlage zurücksetzen</button>
 </section>;
}
