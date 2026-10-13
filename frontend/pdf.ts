import {jsPDF} from 'jspdf';
import {autoTable} from 'jspdf-autotable';
import {money,dateLabel,labels,totals} from './model';
import {inkColor} from './theme';

// Generate text and tables directly, with page breaks rather than a screenshot.
export function invoicePdf(doc:any,payments:any[]=[]){
 const pdf=new jsPDF({unit:'mm',format:'a4',compress:true});
 const d=doc.data,c=d.company,customer=d.customer;
 const accent=c.brandColor?inkColor(c.brandColor):'#363636';
 const reminder=['reminder','dunning'].includes(doc.kind);
 const title=doc.kind==='invoice'?(d.documentTitle||'Rechnung'):labels[doc.kind];
 pdf.setProperties({title:`${title} ${doc.number}`,author:c.name,creator:'Klarrechnung'});
 const clean=(s:any)=>String(s??'').replace(/\u202f|\u00a0/g,' ');
 function block(text:string,x:number,y:number,width:number,size=11,bold=false){pdf.setFont('helvetica',bold?'bold':'normal');pdf.setFontSize(size);pdf.setTextColor('#080808');const lines=pdf.splitTextToSize(clean(text),width);pdf.text(lines,x,y);return y+lines.length*size*.42;}
 function image(src:string|undefined,x:number,y:number,w:number,h:number){if(!src)return;const props=pdf.getImageProperties(src);const scale=Math.min(w/props.width,h/props.height);pdf.addImage(src,props.fileType,x,y,props.width*scale,props.height*scale);}
 image(c.logoUrl,10,17,47,22);
 let left=47;
 left=block(c.name,12,left,112,12,true);
 left=block(`${c.street} ${c.house}\n${c.zip} ${c.city}${c.phone?'\nTel: '+c.phone:''}${c.email?'\n'+c.email:''}`,12,left,112);
 pdf.setFont('helvetica','bolditalic');pdf.setTextColor(accent);pdf.setFontSize(19);
 const titleLines=pdf.splitTextToSize(clean(title),115);pdf.text(titleLines,10,left+6);left+=6+titleLines.length*8;
 if(doc.status==='draft')left=block('ENTWURF',10,left,115,9,true);
 left=block(`Zahlungsart: Rechnung\nUnsere Referenz: ${d.title}${c.uid?'\nUnsere ID-Nr.: '+c.uid:''}\n${doc.kind==='offer'?'Gültig bis':'Zahlbar bis'}: ${dateLabel(d.due)}\nBeleg-Nr.: ${doc.number}\nLeistungsdatum: ${dateLabel(d.serviceDate)}`,10,left+2,115,10);
 let right=block(customer.name,134,53,66,12,true);
 right=block(`${customer.street} ${customer.house}\n${customer.zip} ${customer.city}${customer.email?'\n'+customer.email:''}`,134,right,66);
 right=block(`${c.city}, ${dateLabel(d.date)}`,134,right+4,66,10);
 let y=Math.max(left,right)+5;
 const table=(options:any)=>{autoTable(pdf,{margin:{left:10,right:10,top:18,bottom:18},styles:{font:'helvetica',fontSize:11,textColor:'#080808',cellPadding:1.5,overflow:'linebreak'},theme:'plain',...options});y=(pdf as any).lastAutoTable.finalY+4};
 if(reminder){table({startY:y,body:[[clean(d.notes)],[`Rechnung: ${d.sourceNumber}`]]});}
 else {table({startY:y,head:[['Pos.','Beschreibung','Menge','Preis','Total']],body:d.lines.map((l:any,i:number)=>[i+1,clean(l.description),`${l.quantity} ${l.unit||''}`,`${money(l.price)} CHF`,`${money(Math.round(l.price*l.quantity))} CHF`]),headStyles:{textColor:accent,fontStyle:'bold',lineColor:accent,lineWidth:{bottom:.5}},columnStyles:{0:{cellWidth:17},1:{cellWidth:54},2:{cellWidth:36},3:{cellWidth:37},4:{cellWidth:46,halign:'right'}},rowPageBreak:'avoid'});}
 const entries=payments.filter(p=>p.document_id===doc.id),sum=totals(d.lines);
 const paid=entries.length?entries.reduce((n,p)=>n+p.amount,0):doc.paid||0;
 const rows:any[]=[];
 if(reminder)rows.push(['Offener Betrag',`${money(d.outstanding)} CHF`]);
 else {rows.push(['Total',`${money(sum.net)} CHF`]);if(c.vat)for(const [rate,value] of Object.entries(sum.groups))rows.push([`MWST ${rate}%`,`${money(value)} CHF`]);rows.push([c.vat?'Total inkl. MWST':'Total',`${money(sum.total)} CHF`]);for(const p of entries)rows.push([`Akontozahlung (${dateLabel(p.date)})`,`- ${money(p.amount)} CHF`]);if(!entries.length&&paid)rows.push(['Bereits bezahlt',`- ${money(paid)} CHF`]);if(paid)rows.push([c.vat?'Restbetrag inkl. MWST':'Restbetrag',`${money(Math.max(0,sum.total-paid))} CHF`]);}
 if(y+rows.length*8+80>282){pdf.addPage();y=18}
 table({startY:y,pageBreak:'avoid',margin:{left:55,right:10,top:18,bottom:18},body:rows,styles:{font:'helvetica',fontSize:12,cellPadding:1.2,halign:'right',overflow:'linebreak'},columnStyles:{0:{cellWidth:99},1:{cellWidth:46}},didParseCell:(hook:any)=>{if(hook.row.index===rows.length-1){hook.cell.styles.fontStyle='bold';hook.cell.styles.textColor=accent;hook.cell.styles.lineWidth={top:.6};hook.cell.styles.lineColor=accent}}});
 if(!reminder&&d.notes)table({startY:y,body:[[clean(d.notes)]],styles:{font:'helvetica',fontSize:10,cellPadding:0}});
 if(!reminder&&!c.vat)table({startY:y,body:[['Nicht mehrwertsteuerpflichtig. Es wird keine MWST erhoben.']],styles:{font:'helvetica',fontSize:10,cellPadding:0}});
 // Keep the footer together. Reserve enough room for long bank/signatory fields.
 pdf.setFontSize(9);const bank=c.iban?`Den Rechnungsbetrag überweisen Sie bitte auf folgendes Konto:\n${c.bank?'Bank: '+c.bank+'\n':''}IBAN: ${c.iban}`:'';
 const footerHeight=Math.max(35,pdf.splitTextToSize(clean(bank),110).length*4+4,(c.stampUrl?23:0)+pdf.splitTextToSize(clean((c.signatory||'')+'\n'+(c.signatoryRole||'')),70).length*4+4);
 if(y+15+footerHeight>282){pdf.addPage();y=18}
 const fy=Math.max(y+15,282-footerHeight);
 image(c.stampUrl,10,fy,54,21);let sy=fy+(c.stampUrl?24:3);
 if(c.signatory){pdf.setDrawColor(accent);pdf.line(10,sy,80,sy);sy=block(c.signatory,10,sy+5,70,10,true);if(c.signatoryRole)block(c.signatoryRole,10,sy,70,8,true)}
 if(bank)block(bank,90,fy+4,110,9);
 const pages=pdf.getNumberOfPages();for(let i=1;i<=pages;i++){pdf.setPage(i);pdf.setFont('helvetica','normal');pdf.setFontSize(8);pdf.setTextColor('#777777');pdf.text(`${doc.number} · ${i} / ${pages}`,200,291,{align:'right'});}
 return pdf;
}
export function downloadInvoicePdf(doc:any,payments:any[]=[]){const filename=`${doc.number}${doc.status==='draft'?'-Entwurf':''}.pdf`.replace(/[^a-zA-Z0-9._-]/g,'_');invoicePdf(doc,payments).save(filename);}
