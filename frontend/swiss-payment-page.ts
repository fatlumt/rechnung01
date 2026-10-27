import type {jsPDF} from 'jspdf';

// SIX payment-part dimensions: 62 mm receipt + 148 mm payment part, 105 mm high.
// Uploads are normalised to 600 px, with the original code in the central 510 px.
// Keep its payload unchanged: a neutral bank code has no amount or payer.
export function paymentDetails(company:any){
 const p=String(company.qrPayload||'').trim().split(/\r\n|\r|\n/);
 if(p.length<31||p[0]!=='SPC'||p[1]!=='0200'||p[2]!=='1'||p[4]!=='S'||p.slice(11,18).join('')!==''||p[18]!==''||p[19]!=='CHF'||p.slice(20,27).join('')!==''||p[27]!=='NON'||p[28]!==''||p[30]!=='EPD')throw Error('Ungültiger neutraler Bank-QR-Code. Bitte in den Einstellungen erneut hochladen.');
 const iban=p[3].replace(/\s/g,'').toUpperCase();
 if(iban!==String(company.iban||'').replace(/\s/g,'').toUpperCase())throw Error('QR-Code und Firmen-IBAN stimmen nicht überein.');
 return {iban:iban.match(/.{1,4}/g)!.join(' '),address:[p[5],[p[6],p[7]].filter(Boolean).join(' '),[p[8],p[9]].filter(Boolean).join(' '),p[10]==='CH'?'':p[10]].filter(Boolean),information:p[29]||''};
}
export function renderSwissPaymentPage(pdf:jsPDF,company:any,language:string){
 const details=paymentDetails(company);
 // Standard payment-slip headings use German or English. Albanian invoices use German here.
 const en=language==='en';
 const labels=en?{receipt:'Receipt',payment:'Payment part',account:'Account / Payable to',payer:'Payable by (name/address)',currency:'Currency',amount:'Amount',acceptance:'Acceptance point',cut:'Separate before paying in',information:'Additional information'}:{receipt:'Empfangsschein',payment:'Zahlteil',account:'Konto / Zahlbar an',payer:'Zahlbar durch (Name/Adresse)',currency:'Währung',amount:'Betrag',acceptance:'Annahmestelle',cut:'Vor der Einzahlung abzutrennen',information:'Zusätzliche Informationen'};
 pdf.addPage('a4','portrait');pdf.setTextColor(0);pdf.setDrawColor(0);pdf.setLineWidth(.2);
 pdf.line(0,192,210,192);pdf.line(62,192,62,297);
 pdf.setFont('helvetica','normal');pdf.setFontSize(6);pdf.text(labels.cut,105,190.5,{align:'center'});
 function text(value:string,x:number,y:number,size:number,bold=false){pdf.setFont('helvetica',bold?'bold':'normal');pdf.setFontSize(size);pdf.text(value,x,y);}
 function corners(x:number,y:number,w:number,h:number){pdf.setLineWidth(.75*25.4/72);for(const [a,b,dx,dy] of [[x,y,3,3],[x+w,y,-3,3],[x,y+h,3,-3],[x+w,y+h,-3,-3]]){pdf.line(a,b,a+dx,b);pdf.line(a,b,a,b+dy)}}
 function creditor(x:number,y:number,width:number,size:number,lineHeight:number){pdf.setFont('helvetica','normal');pdf.setFontSize(size);const lines=[details.iban,...details.address].flatMap(v=>pdf.splitTextToSize(v,width));pdf.text(lines,x,y,{lineHeightFactor:lineHeight/(size*25.4/72)});return y+(lines.length-1)*lineHeight;}
 text(labels.receipt,5,200,11,true);text(labels.payment,67,200,11,true);
 text(labels.account,5,206,6,true);const receiptEnd=creditor(5,210,52,8,3.1);
 const receiptPayer=Math.max(225,receiptEnd+5);
 if(receiptPayer>240)throw Error('Bankadresse ist für den QR-Zahlteil zu lang. Bitte die Original-Bank-PDF hochladen.');
 text(labels.payer,5,receiptPayer,6,true);corners(5,receiptPayer+3,52,20);
 text(labels.currency,5,264,6,true);text(labels.amount,22,264,6,true);text('CHF',5,268,8);corners(27,267,30,10);
 pdf.setFont('helvetica','bold');pdf.setFontSize(6);pdf.text(labels.acceptance,57,283,{align:'right'});
 const imageSize=46*600/510,inset=(imageSize-46)/2;
 const image=pdf.getImageProperties(company.qrUrl);pdf.addImage(company.qrUrl,image.fileType,67-inset,209-inset,imageSize,imageSize);
 text(labels.currency,67,262,8,true);text(labels.amount,82,262,8,true);text('CHF',67,267,10);corners(82,266,40,15);
 text(labels.account,118,199,8,true);const paymentEnd=creditor(118,203,87,10,3.6);
 const paymentPayer=Math.max(225,paymentEnd+6);
 if(paymentPayer>242)throw Error('Bankadresse ist für den QR-Zahlteil zu lang. Bitte die Original-Bank-PDF hochladen.');
 text(labels.payer,118,paymentPayer,8,true);corners(118,paymentPayer+3,65,25);
 if(details.information){const heading=paymentPayer+34;pdf.setFont('helvetica','normal');pdf.setFontSize(10);const lines=pdf.splitTextToSize(details.information,87);if(heading+4+lines.length*3.6>287)throw Error('Bankangaben sind für den QR-Zahlteil zu lang. Bitte die Original-Bank-PDF hochladen.');text(labels.information,118,heading,8,true);pdf.setFont('helvetica','normal');pdf.setFontSize(10);pdf.text(lines,118,heading+4,{lineHeightFactor:1.02});}
}
