import {useEffect,useState} from 'react';
import {api} from './api';
import {invoicePdfBytes,downloadInvoicePdf} from './pdf';
import {pdfFilename,labels} from './model';

export default function DocumentSend({doc,payments,documents,user,emailEnabled=true}:any){
 const [file,setFile]=useState<File|null>(null),[error,setError]=useState(''),[notice,setNotice]=useState(''),[target,setTarget]=useState(''),[busy,setBusy]=useState(false),[sent,setSent]=useState(false);
 const [status,setStatus]=useState<any>(null),[to,setTo]=useState(doc.data.customer.email||''),[subject,setSubject]=useState(`${labels[doc.kind]} ${doc.number} – ${doc.data.company.name}`),[message,setMessage]=useState(`Guten Tag\n\nIm Anhang erhalten Sie ${labels[doc.kind]} ${doc.number}.\n\nFreundliche Grüsse\n${doc.data.company.name}`);
 const [requestId]=useState(()=>crypto.randomUUID());
 async function load(){if(!emailEnabled)return;setStatus(await api('mail&documentId='+encodeURIComponent(doc.id)));}
 useEffect(()=>{let cancelled=false;invoicePdfBytes(doc,payments,documents).then(bytes=>{if(!cancelled)setFile(new File([bytes as BlobPart],pdfFilename(doc),{type:'application/pdf'}));}).catch(()=>{if(!cancelled)setError('PDF konnte nicht vorbereitet werden. Bitte Beleg erneut öffnen.');});load().catch(()=>{if(!cancelled)setStatus({ready:false,history:[],unavailable:true});});return()=>{cancelled=true};},[doc,payments,documents,emailEnabled]);
 function choose(next:string){setTarget(next);setError('');setNotice('');}
 async function share(){if(!file)return;setError('');try{if(navigator.canShare?.({files:[file]})){await navigator.share({files:[file],title:pdfFilename(doc)});setNotice('Teilen-Menü geschlossen. Den Versand bestätigen Sie in der gewählten App.');}else{await downloadInvoicePdf(doc,payments,documents);setNotice('PDF heruntergeladen. Hängen Sie die Datei in WhatsApp oder Ihrer E-Mail-App an.');}}catch(e:any){if(e.name!=='AbortError')setError('Teilen nicht möglich. Bitte PDF herunterladen und in WhatsApp als Dokument anhängen.');}}
 async function send(e:any){e.preventDefault();if(!emailEnabled||!file||busy||sent||!status?.ready)return;setBusy(true);setError('');try{
  const pdf=await new Promise<string>((resolve,reject)=>{const r=new FileReader();r.onload=()=>resolve(String(r.result));r.onerror=()=>reject(Error('PDF konnte nicht gelesen werden.'));r.readAsDataURL(file);});
  const r=await api('mail',{documentId:doc.id,version:doc.version,target,to,subject,message,pdf,filename:file.name,requestId});setSent(true);
  setNotice(r.status==='accepted'?'Vom Mailserver angenommen. Dies bestätigt noch keine Zustellung.':'Ein Versandversuch ist bereits erfasst. Bitte Verlauf und Postfach prüfen.');await load();
 }catch(e:any){setError(e.message);load().catch(()=>{});}finally{setBusy(false);}}
 return <section className="documentSend noPrint" aria-label="Beleg versenden">
  <div className="sendButtons">{emailEnabled&&<><button className="secondary" disabled={busy} onClick={()=>choose('self')}>An mich senden</button><button className="secondary" disabled={busy} onClick={()=>choose('customer')}>An Kunden senden</button></>}<button className="secondary" disabled={!file||busy} onClick={share}>{file?'Teilen / WhatsApp':'PDF wird vorbereitet …'}</button></div>
  {error&&<p className="alert error" role="alert">{error}</p>}{notice&&<p className="alert success" role="status">{notice}</p>}
  {emailEnabled&&target&&<form className="sendReview" onSubmit={send}><h3>E-Mail prüfen</h3>
   <p className="hint">Absender: {status?.from||'Noch nicht eingerichtet'} · Antworten an: {user.email}</p>
   {!status?.ready&&<p role="status" className="hint">{status?.unavailable?'Versandstatus konnte nicht geladen werden. Beleg bitte erneut öffnen.':'E-Mail-Versand muss zuerst mit dem Hostinger-Postfach verbunden werden. PDF teilen und herunterladen ist bereits möglich.'}</p>}
   {doc.status==='draft'&&<p className="hint">Dieser Beleg ist ein Entwurf. Er wird als Entwurf versendet und nicht automatisch ausgestellt.</p>}
   <label className="field"><span>Empfänger</span><input type="email" required maxLength={190} disabled={busy||sent||target==='self'} value={target==='self'?user.email:to} onChange={e=>setTo(e.target.value)}/></label>
   <label className="field"><span>Betreff</span><input required maxLength={180} disabled={busy||sent} value={subject} onChange={e=>setSubject(e.target.value)}/></label>
   <label className="field"><span>Nachricht</span><textarea required rows={6} maxLength={6000} disabled={busy||sent} value={message} onChange={e=>setMessage(e.target.value)}/></label>
   <p className="sendAttachment">Anhang: {file?.name||'PDF wird vorbereitet …'}{file&&file.size>5000000?' · Zu gross für E-Mail (max. 5 MB). Bitte herunterladen und separat versenden.':''}</p>
   <div className="sendButtons"><button className="primary" type="submit" disabled={busy||sent||!file||file.size>5000000||!status?.ready}>{busy?'Wird gesendet …':sent?'Versand erfasst':'Senden'}</button><button className="secondary" type="button" disabled={busy} onClick={()=>setTarget('')}>Schliessen</button></div>
  </form>}
  {emailEnabled&&!!status?.history?.length&&<details className="sendHistory"><summary>E-Mail-Versandverlauf</summary>{status.history.map((h:any,i:number)=><p key={i}>{h.created} · {h.recipient}<br/>{h.status==='accepted'?'Vom Mailserver angenommen':h.status==='unknown'?'Versand unbestätigt – vor erneutem Senden Postfach prüfen':'Versand begonnen – Ergebnis noch nicht bestätigt'}</p>)}</details>}
 </section>;
}
