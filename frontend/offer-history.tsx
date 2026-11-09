import {useState} from 'react';
import {api} from './api';
import {downloadInvoicePdf} from './pdf';
export default function OfferHistory({doc}:any){
 const [rows,setRows]=useState<any[]|null>(null),[error,setError]=useState(''),[busy,setBusy]=useState(false);
 async function load(){setBusy(true);setError('');try{const r=await api('data',{action:'offerHistory',id:doc.id,version:doc.version});setRows(r.revisions);}catch(e:any){setError(e.message);}finally{setBusy(false);}}
 return <section className="documentSend noPrint"><button className="secondary" disabled={busy} onClick={load}>Versionsverlauf</button>{error&&<p role="alert">{error}</p>}{rows&&<div>{rows.length===0?<p>Noch keine früheren Versionen gespeichert.</p>:rows.map(r=><p key={r.document.version}>Version {r.document.version} · Gesichert am {r.created} <button className="secondary" onClick={async()=>{try{await downloadInvoicePdf(r.document);}catch{setError('PDF konnte nicht erstellt werden.');}}}>Frühere Offerte als PDF</button></p>)}</div>}</section>;
}
