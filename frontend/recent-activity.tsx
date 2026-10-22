import {FileText,Mail,Wallet,ChevronRight} from 'lucide-react';
import {dateLabel,money,labels} from './model';
import {currencyOf} from './country';
export function recentActivity(data:any){
 const docs=data.documents||[];const byId=new Map(docs.map((d:any)=>[d.id,d]));
 const entries:any[]=docs.map((d:any)=>({id:'doc-'+d.id,doc:d,at:d.created,title:`${labels[d.kind]} erstellt`,detail:d.data.customer.name,kind:'document'}));
 for(const p of data.payments||[]){const d:any=byId.get(p.document_id);if(d)entries.push({id:'pay-'+p.id,doc:d,at:p.date+' 00:00:00',title:p.amount<0?'Zahlung korrigiert':'Zahlung erfasst',detail:`${currencyOf(d.data)} ${money(p.amount,currencyOf(d.data))} · Zahlungsdatum ${dateLabel(p.date)}`,kind:'payment'})}
 if(data.features?.email!==false)for(const [i,m] of (data.mailHistory||[]).entries()){const d:any=byId.get(m.document_id);if(d)entries.push({id:'mail-'+i,doc:d,at:m.created,title:m.status==='accepted'?'E-Mail gesendet':m.status==='pending'?'E-Mail-Versand begonnen':'E-Mail-Versand unbestätigt',detail:d.data.customer.name,kind:m.status==='accepted'?'mail':'warning'})}
 return entries.filter(e=>e.at).sort((a,b)=>b.at.localeCompare(a.at)||a.id.localeCompare(b.id)).slice(0,8);
}
export default function RecentActivity({data,open}:any){
 const entries=recentActivity(data);
 return <details className="recentActivity"><summary className="activityToggle"><span>Letzte Aktivitäten</span><ChevronRight size={20}/></summary>{!entries.length?<p className="muted">Sobald Sie Belege erstellen, E-Mails senden oder Zahlungen erfassen, sehen Sie diese hier.</p>:entries.map(e=>{const Icon=e.kind==='document'?FileText:e.kind==='payment'?Wallet:Mail;return <button className={'activityRow '+e.kind} key={e.id} onClick={()=>open(e.doc)}><span className="activityIcon"><Icon size={18}/></span><span className="activityText"><strong>{e.title}</strong><small>{e.doc.number} · {e.detail}</small></span><span className="activityDate">{dateLabel(e.at.slice(0,10))}</span><ChevronRight size={16}/></button>})}</details>;
}
