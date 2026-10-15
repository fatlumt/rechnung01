import {documentTotals,visible} from './model';
import React from 'react';
import {themeStyle} from './theme';

// Template 1: the customer's supplied Krasniqi invoice. No company is hardcoded.
const amount=(n:number)=>new Intl.NumberFormat('de-CH',{minimumFractionDigits:2,maximumFractionDigits:2}).format(n/100);
const date=(s:string)=>s?new Date(s+'T12:00:00').toLocaleDateString('de-CH'):'';
const titles:Record<string,string>={invoice:'Rechnung',offer:'Offerte',reminder:'Zahlungserinnerung',dunning:'Mahnung'};
export default function InvoiceTemplate({doc,payments=[]}:{doc:any,payments?:any[]}){
  const d=doc.data,c=d.company,customer=d.customer;
  const reminder=['reminder','dunning'].includes(doc.kind);
  const {groups,net,total:gross}=documentTotals(d);let position=0;const totalOnly=d.pricingMode==='total';
  const entries=payments.filter(p=>p.document_id===doc.id);
  const paid=entries.reduce((a,p)=>a+p.amount,0);
  const deducted=reminder?d.paidAtCreation:(entries.length?paid:doc.paid||0);
  const balance=reminder?d.outstanding:Math.max(0,gross-deducted);
  const caption=doc.kind==='invoice'&&d.documentTitle?d.documentTitle:titles[doc.kind];
  return <article className="reference-invoice" data-template="krasniqi-classic" style={c.brandColor?themeStyle(c.brandColor):{'--brand':'#363636'} as React.CSSProperties}>
    <div className="reference-logo">{c.logoUrl&&<img src={c.logoUrl} alt={`${c.name} Logo`}/>}</div>
    <div className="reference-heading">
      <section className="reference-sender"><strong>{c.name}</strong><div>{c.street} {c.house}</div><div>{c.zip} {c.city}</div>{c.phone&&<div>Tel: {c.phone}</div>}{c.email&&<div>{c.email}</div>}
        <h1>{caption}</h1>
        {doc.status==='draft'&&<div className="reference-draft">ENTWURF</div>}
        <div className="reference-meta">{visible(d,'payment')&&<div>Zahlungsart: Rechnung</div>}{visible(d,'reference')&&<div>Unsere Referenz: {d.title}</div>}{visible(d,'uid')&&c.uid&&<div>Unsere ID-Nr.: {c.uid}</div>}{visible(d,'due')&&<div>{doc.kind==='offer'?'Gültig bis':'Zahlbar bis'}: {date(d.due)}</div>}{visible(d,'number')&&doc.number&&<div className="reference-number">Beleg-Nr.: {doc.number}</div>}{visible(d,'serviceDate')&&<div className="reference-number">Leistungsdatum: {date(d.serviceDate)}</div>}</div>
      </section>
      <section className="reference-recipient"><strong>{customer.name}</strong><div>{customer.street} {customer.house}</div><div>{customer.zip} {customer.city}</div>{customer.email&&<div>{customer.email}</div>}<p>{c.city}, {date(d.date)}</p></section>
    </div>
    {reminder?<section className="reference-reminder"><p>{d.notes}</p><p>Rechnung: {d.sourceNumber}</p><div className="reference-total-row final"><strong>Offener Betrag</strong><strong>{amount(balance)} CHF</strong></div></section>:<>
    <table className={`reference-items ${totalOnly?'total-only':''}`}><colgroup>{totalOnly?<><col style={{width:'9%'}}/><col style={{width:'91%'}}/></>:<><col style={{width:'9%'}}/><col style={{width:'28%'}}/><col style={{width:'19%'}}/><col style={{width:'20%'}}/><col style={{width:'24%'}}/></>}</colgroup><thead><tr><th>Pos.</th><th>Beschreibung</th>{!totalOnly&&<><th>Menge</th><th>Preis</th><th>Total</th></>}</tr></thead><tbody>{d.lines.map((l:any,i:number)=>l.type==='section'?<tr key={i} className="reference-section"><td colSpan={totalOnly?2:5}>{l.description}</td></tr>:<tr key={i}><td>{++position}</td><td>{l.description}</td>{!totalOnly&&<><td>{l.quantity} {l.unit||''}</td><td>{amount(l.price)} CHF</td><td>{amount(Math.round(l.price*l.quantity))} CHF</td></>}</tr>)}</tbody></table>
    <section className="reference-totals">{c.vat&&<div className="reference-total-row"><strong>Total exkl. MWST</strong><strong>{amount(net)} CHF</strong></div>}{c.vat&&Object.entries(groups).map(([rate,value])=><div className="reference-total-row" key={rate}><span>MWST {rate}%</span><span>{amount(value)} CHF</span></div>)}<div className="reference-total-row gross"><strong>{c.vat?'Total inkl. MWST':'Total'}</strong><strong>{amount(gross)} CHF</strong></div>{entries.map((p:any)=><div className="reference-total-row advance" key={p.id}><span>Akontozahlung ({date(p.date)})</span><span>− {amount(p.amount)} CHF</span></div>)}{!entries.length&&deducted>0&&<div className="reference-total-row advance"><span>Bereits bezahlt</span><span>− {amount(deducted)} CHF</span></div>}{deducted>0&&<div className="reference-total-row final"><strong>{c.vat?'Restbetrag inkl. MWST':'Restbetrag'}</strong><strong>{amount(balance)} CHF</strong></div>}</section>
    {d.notes&&<p className="reference-notes">{d.notes}</p>}
    {!c.vat&&<p className="reference-notes">Nicht mehrwertsteuerpflichtig. Es wird keine MWST erhoben.</p>}
    </>}
    <footer className="reference-footer"><div className="reference-signature">{c.stampUrl&&<img src={c.stampUrl} alt="Firmenstempel"/>}{c.signatory&&<><div className="reference-signature-line"/><strong>{c.signatory}</strong>{c.signatoryRole&&<small>{c.signatoryRole}</small>}</>}</div><div className="reference-bank">{c.iban&&<><div>Den Rechnungsbetrag überweisen Sie bitte auf folgendes Konto:</div>{c.bank&&<div><strong>Bank:</strong> {c.bank}</div>}<div><strong>IBAN:</strong> {c.iban}</div></>}</div></footer>
  </article>;
}
