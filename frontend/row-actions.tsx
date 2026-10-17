import {currencyOf} from './country';
import React from 'react';
import {statusLabel} from './model';
export function StatusControl({doc,busy,change}:any){
 const label=statusLabel(doc);
 if(doc.kind!=='invoice')return <span className={`badge ${doc.status==='draft'?'draft':doc.status==='accepted'?'paid':'open'}`}>{label}</span>;
 const value=doc.status==='draft'?'draft':doc.paid>=doc.total?'paid':label==='Überfällig'?'overdue':doc.paid>0?'partial':'open';
 return <select className={`rowStatus ${value}`} aria-label={`Status ${doc.number}`} value={value} disabled={busy} onChange={e=>change(doc,e.target.value)}>
  {doc.status==='draft'&&<option value="draft" disabled>Entwurf</option>}
  <option value="open">{doc.status==='draft'?'Offen – Rechnung ausstellen':'Offen'}</option>
  {value==='partial'&&<option value="partial" disabled>Teilbezahlt</option>}
  {value==='overdue'&&<option value="overdue" disabled>Überfällig</option>}
  {doc.status!=='draft'&&<option value="paid">Bezahlt</option>}
 </select>;
}
export function ActionControl({doc,busy,choose,converted}:any){
 const invoice=doc.kind==='invoice'&&doc.status!=='draft'&&doc.paid<doc.total;
 if(!invoice&&doc.kind!=='offer')return <span className="muted">—</span>;
 return <select className="rowAction" value="" disabled={busy} aria-label={`Aktionen ${doc.number}`} onChange={e=>choose(doc,e.target.value)}><option value="" disabled>Aktion wählen …</option>{doc.kind==='offer'?<option value="convert">{converted?'Rechnung öffnen':'In Rechnung umwandeln'}</option>:<><option value="reminder">Zahlungserinnerung erstellen</option><option value="dunning">{doc.fee?'Mahnung erneut erstellen':currencyOf(doc.data)==='CHF'?'Mahnung (+ CHF 50) erstellen':'Mahnung erstellen'}</option></>}</select>;
}
