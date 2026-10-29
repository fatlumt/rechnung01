import {t} from './i18n';
import {currencyOf,documentTitle} from './country';
import React from 'react';
import {StatusControl} from './row-actions';
import DocumentMenu from './document-menu';
import {dateLabel,money,labels} from './model';
export default function DocumentCards({docs,allDocs,busy,open,status,action}:any){return <div className="documentCards">{docs.map((d:any)=><article className="documentCard" key={d.id}><div className="documentCardTop"><button className="cardOpen" onClick={()=>open(d)}><span><small>{t(documentTitle(d))} · {d.number}</small><strong>{d.data.customer.name}</strong><span>{d.data.title}</span></span><strong className="cardAmount"><small>{currencyOf(d.data)}</small>{money(d.data.amountDue??d.data.outstanding??d.total,currencyOf(d.data))}</strong></button><DocumentMenu doc={d} busy={busy} choose={action} converted={allDocs.some((x:any)=>x.kind==='invoice'&&x.source_offer===d.id)}/></div><div className="cardDates"><span>{dateLabel(d.data.date)}</span><span>{d.kind==='offer'?t("Gültig"):t("Zahlbar")} {t("bis")} {dateLabel(d.data.due)}</span></div><div className="cardControls"><label><span>{t("Status")}</span><StatusControl doc={d} busy={busy||!!d.trashed} change={status}/></label></div></article>)}</div>}
