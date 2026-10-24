import {t} from './i18n';
import {documentText,documentTitle} from './country';
import {defaultEmailTemplateFor} from './email-templates';
import {useEffect,useState,useRef} from 'react';
import EmailFooter from './email-footer';
import {api} from './api';
import {invoicePdfBytes,downloadInvoicePdf} from './pdf';
import {pdfFilename,labels} from './model';

export default function DocumentSend({doc,payments,documents,user,emailEnabled=true}:any){
 const [file,setFile]=useState<File|null>(null),[error,setError]=useState(''),[notice,setNotice]=useState(''),[target,setTarget]=useState(''),[busy,setBusy]=useState(false),[sent,setSent]=useState(false);
 const [status,setStatus]=useState<any>(null),[to,setTo]=useState(doc.data.customer.email||''),[subject,setSubject]=useState(`${documentTitle(doc)} ${doc.number} – ${doc.data.company.name}`),[message,setMessage]=useState(defaultEmailTemplateFor(doc.data).message.replace(/\{(belegart|belegnummer|firma)\}/g,(_:string,key:string)=>({belegart:documentTitle(doc),belegnummer:doc.number,firma:doc.data.company.name} as any)[key]));
 const applied=useRef(false),editedSubject=useRef(false),editedMessage=useRef(false);
 const [requestId]=useState(()=>crypto.randomUUID());
 async function load(){if(!emailEnabled)return;const next=await api('mail&documentId='+encodeURIComponent(doc.id));setStatus(next);if(!applied.current&&next.defaults){if(!editedSubject.current)setSubject(next.defaults.subject);if(!editedMessage.current)setMessage(next.defaults.message);applied.current=true;}}
 useEffect(()=>{let cancelled=false;invoicePdfBytes(doc,payments,documents).then(bytes=>{if(!cancelled)setFile(new File([bytes as BlobPart],pdfFilename(doc),{type:'application/pdf'}));}).catch(()=>{if(!cancelled)setError('PDF konnte nicht vorbereitet werden. Bitte Beleg erneut öffnen.');});load().catch(()=>{if(!cancelled)setStatus({ready:false,history:[],unavailable:true});});return()=>{cancelled=true};},[doc,payments,documents,emailEnabled]);
 function choose(next:string){setTarget(next);setError('');setNotice('');}
 async function share(){if(!file)return;setError('');try{if(navigator.canShare?.({files:[file]})){await navigator.share({files:[file],title:pdfFilename(doc)});setNotice('Teilen-Menü geschlossen. Den Versand bestätigen Sie in der gewählten App.');}else{await downloadInvoicePdf(doc,payments,documents);setNotice('PDF heruntergeladen. Hängen Sie die Datei in WhatsApp oder Ihrer E-Mail-App an.');}}catch(e:any){if(e.name!=='AbortError')setError('Teilen nicht möglich. Bitte PDF herunterladen und in WhatsApp als Dokument anhängen.');}}
 async function send(e:any){e.preventDefault();if(!emailEnabled||!file||busy||sent||!status?.ready||subject.length>180||message.length>6000)return;setBusy(true);setError('');try{
  const pdf=await new Promise<string>((resolve,reject)=>{const r=new FileReader();r.onload=()=>resolve(String(r.result));r.onerror=()=>reject(Error('PDF konnte nicht gelesen werden.'));r.readAsDataURL(file);});
  const r=await api('mail',{documentId:doc.id,version:doc.version,companyVersion:status.companyVersion,target,to,subject,message,pdf,filename:file.name,requestId});setSent(true);
  if(r.status==='accepted')setNotice('E-Mail gesendet.');else setError('Ein Versandversuch ist bereits erfasst, aber nicht bestätigt. Bitte Verlauf und Postfach prüfen.');await load();
 }catch(e:any){setError(e.message);load().catch(()=>{});}finally{setBusy(false);}}
 return <section className="documentSend noPrint" aria-label={t("Beleg versenden")}>
  <div className="sendButtons">{emailEnabled&&<><button className="secondary" disabled={busy} onClick={()=>choose('self')}>{t("An mich senden")}</button><button className="secondary" disabled={busy} onClick={()=>choose('customer')}>{t("An Kunden senden")}</button></>}<button className="secondary" disabled={!file||busy} onClick={share}>{file?t("Teilen / WhatsApp"):t("PDF wird vorbereitet …")}</button></div>
  {error&&<p className="alert error" role="alert">{t(error)}</p>}{notice&&<p className="alert success" role="status">{t(notice)}</p>}
  {emailEnabled&&target&&<form className="sendReview" onSubmit={send}><h3>{t("E-Mail prüfen")}</h3>
   <p className="hint">{t("Absender:")} {status?.company?.name||doc.data.company.name} &lt;{status?.from||t("Noch nicht eingerichtet")}&gt; · {t('Antworten an:')} {user.email}</p>
   {!status?.ready&&<p role="status" className="hint">{status?.unavailable?t("Versandstatus konnte nicht geladen werden. Beleg bitte erneut öffnen."):t("E-Mail-Versand muss zuerst mit dem Hostinger-Postfach verbunden werden. PDF teilen und herunterladen ist bereits möglich.")}</p>}
   {doc.status==='draft'&&<p className="hint">{t("Dieser Beleg ist ein Entwurf. Er wird als Entwurf versendet und nicht automatisch ausgestellt.")}</p>}
   <label className="field"><span>{t("Empfänger")}</span><input type="email" required maxLength={190} disabled={busy||sent||target==='self'} value={target==='self'?user.email:to} onChange={e=>setTo(e.target.value)}/></label>
   <label className="field"><span>{t("Betreff")}</span><input required maxLength={180} disabled={busy||sent} value={subject} onChange={e=>{editedSubject.current=true;setSubject(e.target.value)}}/></label>
   <label className="field"><span>{t("Nachricht")}</span><textarea required rows={6} maxLength={6000} disabled={busy||sent} value={message} onChange={e=>{editedMessage.current=true;setMessage(e.target.value)}}/></label>
   <p className="hint">{t("Dieser Firmenfussbereich wird automatisch angefügt. Logo und Kontaktdaten ändern Sie in den Einstellungen.")}</p>
   <EmailFooter company={status?.company}/>
   {(subject.length>180||message.length>6000)&&<p role="alert" className="alert error">{t("Nach dem Ersetzen der Platzhalter ist der Text zu lang. Bitte auf höchstens 180 Zeichen im Betreff und 6000 Zeichen in der Nachricht kürzen.")}</p>}
   <p className="sendAttachment">{t("Anhang:")} {file?.name||t("PDF wird vorbereitet …")}{file&&file.size>5000000?" "+t("· Zu gross für E-Mail (max. 5 MB). Bitte herunterladen und separat versenden."):''}</p>
   <div className="sendButtons"><button className="primary" type="submit" disabled={busy||sent||!file||file.size>5000000||!status?.ready||subject.length>180||message.length>6000}>{busy?t("Wird gesendet …"):sent?t("Versand abgeschlossen"):t('Senden')}</button><button className="secondary" type="button" disabled={busy} onClick={()=>setTarget('')}>{t("Schliessen")}</button></div>
  </form>}
  {emailEnabled&&!!status?.history?.length&&<details className="sendHistory"><summary>{t("E-Mail-Versandverlauf")}</summary>{status.history.map((h:any,i:number)=><p key={i}>{h.created} · {h.recipient}<br/>{h.status==='accepted'?t("Vom Mailserver angenommen"):h.status==='unknown'?t("Versand unbestätigt – vor erneutem Senden Postfach prüfen"):t("Versand begonnen – Ergebnis noch nicht bestätigt")}</p>)}</details>}
 </section>;
}
