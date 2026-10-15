export const blankCompany={name:'',street:'',house:'',zip:'',city:'',country:'CH',email:'',uid:'',iban:'',vat:true,terms:30};
export const blankCustomer={name:'',street:'',house:'',zip:'',city:'',country:'CH',email:''};
export const labels:Record<string,string>={invoice:'Rechnung',offer:'Offerte',reminder:'Zahlungserinnerung',dunning:'Mahnung'};
export const money=(n:number)=>new Intl.NumberFormat('de-CH',{minimumFractionDigits:2,maximumFractionDigits:2}).format(n/100);
export const today=()=>new Intl.DateTimeFormat('en-CA',{timeZone:'Europe/Zurich',year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date());
export const dateLabel=(s:string)=>s?new Date(s+'T12:00:00').toLocaleDateString('de-CH'):'';
export function totals(lines:any[]){let net=0,tax=0;const groups:Record<string,number>={};for(const l of lines){if(l.type==='section')continue;const n=Math.round(l.quantity*l.price);const t=Math.round(n*l.rate/100);net+=n;tax+=t;groups[l.rate]=(groups[l.rate]||0)+t;}return {net,tax,total:net+tax,groups};}
export function statusLabel(d:any){if(d.status==='draft')return 'Entwurf';if(d.kind==='invoice'){if(d.paid>=d.total)return 'Bezahlt';if(d.data.due<today())return 'Überfällig';if(d.paid>0)return 'Teilbezahlt';return 'Offen';}return d.status==='accepted'?'Angenommen':'Ausgestellt';}

export const pricingLines=(d:any)=>d.pricingMode==='total'?[{quantity:1,price:d.totalPrice,rate:d.totalRate}]:d.lines;
export const documentTotals=(d:any)=>totals(pricingLines(d));
export const visible=(d:any,key:string)=>d.visibility?.[key]!==false;
export const displayFields=[['payment','Zahlungsart'],['reference','Unsere Referenz'],['uid','Unsere ID-Nr.'],['due','Zahlbar / gültig bis'],['number','Beleg-Nr.'],['serviceDate','Leistungsdatum']] as const;
export function pdfFilename(doc:any){const d=doc.data;const part=(v:string)=>v.normalize('NFC').replace(/[<>:"/\\|?*\u0000-\u001f]/g,'_').replace(/[. ]+$/g,'').trim().slice(0,100)||'Dokument';const type=doc.kind==='invoice'?(d.documentTitle||labels.invoice):labels[doc.kind];return `${part(d.customer.name)}_${part(type)}_${d.date.split('-').reverse().join('.')}.pdf`;}
