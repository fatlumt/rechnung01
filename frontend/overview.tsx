import RecentActivity from './recent-activity';
import {FileText,Files,Wallet,BellRing,Plus,ArrowUpRight,Check,ChevronRight} from 'lucide-react';
import {money,dateLabel,today} from './model';
import {currencyOf} from './country';
import './overview.css';

export default function Overview({data,create,navigate,open}:any){
 const enabled=(key:string)=>data.features?.[key]!==false;
 const docs=data.documents, currency=currencyOf(data.company),invoices=docs.filter((d:any)=>d.kind==='invoice'&&d.status!=='draft');
 const unpaid=invoices.filter((d:any)=>d.total>d.paid),overdue=unpaid.filter((d:any)=>d.data.due<today()).sort((a:any,b:any)=>a.data.due.localeCompare(b.data.due));
 const drafts=docs.filter((d:any)=>d.status==='draft');
 const metrics=[
  {feature:'invoices',label:'Offene Rechnungen',value:unpaid.reduce((n:number,d:any)=>n+d.total-d.paid,0),note:`${unpaid.length} offen · inkl. überfällig`,Icon:FileText,color:'blue',go:()=>navigate('invoice','Offen')},
  {feature:'invoices',label:'Überfällig',value:overdue.reduce((n:number,d:any)=>n+d.total-d.paid,0),note:`${overdue.length} Rechnung${overdue.length===1?'':'en'}`,Icon:BellRing,color:'amber',go:()=>navigate('invoice','Überfällig')},
  {feature:'invoices',label:'Zahlungseingänge',value:data.payments.reduce((n:number,p:any)=>n+p.amount,0),note:'Alle erfassten Zahlungen',Icon:Wallet,color:'green',go:()=>navigate('payments')},
  {feature:'expenses',label:'Erfasste Ausgaben',value:(data.expenses||[]).reduce((n:number,e:any)=>n+e.data.amount,0),note:'Alle erfassten Bruttokosten',Icon:Wallet,color:'purple',go:()=>navigate('expenses')}
 ];
 return <div className="homeDashboard">
  <nav className="homeActions" aria-label="Schnellaktionen">{enabled('invoices')&&<button className="homeAction homeActionPrimary" onClick={()=>create('invoice')}><span><Plus size={22}/></span><strong>Neue Rechnung</strong><ArrowUpRight size={18}/></button>}{enabled('offers')&&<button className="homeAction" onClick={()=>create('offer')}><span><Files size={22}/></span><strong>Neue Offerte</strong><ArrowUpRight size={18}/></button>}{enabled('expenses')&&<button className="homeAction" onClick={()=>navigate('expenses')}><span><Wallet size={22}/></span><strong>Ausgaben & Belege</strong><ArrowUpRight size={18}/></button>}</nav>
  {(enabled('invoices')||enabled('expenses'))&&<div className="homeSectionTitle"><h2>Ihre Finanzen</h2><span>Gesamtübersicht · {currency}</span></div>}
  <div className="homeMetrics">{metrics.filter(m=>enabled(m.feature)).map(({label,value,note,Icon,color,go})=><button className={`homeMetric ${color}`} key={label} onClick={go}><div><span>{label}</span><Icon size={18}/></div><strong>{money(value,currency)}</strong><small>{note}</small></button>)}</div>
  {(enabled('invoices')||enabled('offers'))&&<section className={`homePriority ${overdue.length?'needsAttention':''}`} aria-label="Nächste Schritte"><div className="homePriorityHeading"><span className="homePriorityIcon">{overdue.length?<BellRing size={22}/>:<Check size={22}/>}</span><div><h2>{overdue.length?'Diese Zahlungen brauchen Aufmerksamkeit':'Keine überfälligen Rechnungen'}</h2><p>{overdue.length?'Öffnen Sie die Rechnung, um eine Zahlung zu erfassen oder nachzufassen.':unpaid.length?`${unpaid.length} offene Rechnungen liegen noch innerhalb der Zahlungsfrist.`:invoices.length?'Alle ausgestellten Rechnungen sind ausgeglichen.':'Erstellen Sie Ihre erste Rechnung oder Offerte über die Schnellaktionen.'}</p></div></div>{overdue.slice(0,3).map((d:any)=><button className="homeDueRow" key={d.id} onClick={()=>open(d)}><span><strong>{d.data.customer.name}</strong><small>{d.number} · fällig {dateLabel(d.data.due)}</small></span><span><strong>{currency} {money(d.total-d.paid,currency)}</strong><ChevronRight size={18}/></span></button>)}{enabled('reminders')&&overdue.length>0&&<button className="homePriorityLink" onClick={()=>navigate('reminders')}>Zum Mahnwesen <ArrowUpRight size={16}/></button>}{drafts.length>0&&<button className="homeDrafts" onClick={()=>open(drafts[0])}><FileText size={20}/><span><strong>{drafts.length} Entwurf{drafts.length===1?'':'e'} in Arbeit</strong><small>Zuletzt erstellt: {drafts[0].number}</small></span><ChevronRight size={18}/></button>}</section>}
 <RecentActivity data={data} open={open}/>
 </div>;
}
