import {t} from './i18n';
import {useFeatures} from './features';
import {currencyOf} from './country';
import React from 'react';
import {statusLabel} from './model';
export function StatusControl({doc,busy,change}:any){
 const label=statusLabel(doc);
 if(doc.kind!=='invoice')return <span className={`badge ${doc.status==='draft'?'draft':doc.status==='accepted'?'paid':'open'}`}>{t(label)}</span>;
 const value=doc.status==='draft'?'draft':doc.paid>=doc.total?'paid':label==='Überfällig'?'overdue':doc.paid>0?'partial':'open';
 return <select className={`rowStatus ${value}`} aria-label={t('Status {number}',{number:doc.number})} value={value} disabled={busy} onChange={e=>change(doc,e.target.value)}>
  {doc.status==='draft'&&<option value="draft" disabled>{t("Entwurf")}</option>}
  <option value="open">{doc.status==='draft'?t("Offen – Rechnung ausstellen"):t("Offen")}</option>
  {value==='partial'&&<option value="partial" disabled>{t("Teilbezahlt")}</option>}
  {value==='overdue'&&<option value="overdue" disabled>{t("Überfällig")}</option>}
  {doc.status!=='draft'&&<option value="paid">{t("Bezahlt")}</option>}
 </select>;
}
export function ActionControl({doc,busy,choose,converted}:any){
 const features=useFeatures();if((doc.kind==='offer'&&features.invoices===false)||(doc.kind==='invoice'&&features.reminders===false))return <span className="muted">—</span>;
 const invoice=doc.kind==='invoice'&&doc.status!=='draft'&&doc.paid<doc.total;
 if(!invoice&&doc.kind!=='offer')return <span className="muted">—</span>;
 return <select className="rowAction" value="" disabled={busy} aria-label={t('Aktionen {number}',{number:doc.number})} onChange={e=>choose(doc,e.target.value)}><option value="" disabled>{t("Aktion wählen …")}</option>{doc.kind==='offer'?<option value="convert">{converted?t("Rechnung öffnen"):t("In Rechnung umwandeln")}</option>:<><option value="reminder">{t("Zahlungserinnerung erstellen")}</option><option value="dunning">{doc.fee?t("Mahnung erneut erstellen"):currencyOf(doc.data)==='CHF'?t("Mahnung (+ CHF 50) erstellen"):t("Mahnung erstellen")}</option></>}</select>;
}
