import {translateFor} from './i18n';

// Document language is saved with the document. Changing the interface language
// must never change a previously prepared invoice or its attached source invoice.
export const documentLanguage=(v:any)=>['en','sq'].includes(v?.language)?v.language:'de';
export const documentText=(v:any,source:string,variables:Record<string,string|number>={})=>translateFor(documentLanguage(v),source,variables);
export const documentDate=(v:any,s:string)=>s?new Date(s+'T12:00:00').toLocaleDateString(documentLanguage(v)==='en'?'en-GB':documentLanguage(v)==='sq'?'sq-AL':'de-CH'):'';
export const documentMoney=(v:any,n:number,currency=currencyOf(v))=>new Intl.NumberFormat(documentLanguage(v)==='en'?'en-GB':documentLanguage(v)==='sq'?'sq-AL':currency==='EUR'?'de-DE':'de-CH',{minimumFractionDigits:2,maximumFractionDigits:2}).format(n/100);
export const countryOf=(v:any)=>v?.company?.country||v?.country||'CH';
export const currencyOf=(v:any)=>v?.currency||(countryOf(v)==='DE'?'EUR':'CHF');
export const taxLabel=(v:any)=>countryOf(v)==='DE'?'USt.':'MWST';
export const taxRates=(v:any)=>!v?.vat?[0]:countryOf(v)==='DE'?[7,19]:[0,2.6,3.8,8.1];
export const standardRate=(v:any)=>!v?.vat?0:countryOf(v)==='DE'?19:8.1;
export const countryName=(v:any)=>countryOf(v)==='DE'?'Deutschland':'Schweiz';
export const exemption=(v:any)=>documentText(v,countryOf(v)==='DE'?'Steuerbefreiung für Kleinunternehmer gemäß § 19 UStG.':'Nicht mehrwertsteuerpflichtig. Es wird keine MWST erhoben.');
export function documentTitle(doc:any){
 const source=doc.kind==='invoice'?(doc.data.documentTitle||'Rechnung'):doc.kind==='offer'?(countryOf(doc.data)==='DE'?'Angebot':'Offerte'):doc.kind==='dunning'?'Mahnung':'Zahlungserinnerung';
 // These names are also canonical business values. Only their display changes.
 return ['Rechnung','Akontorechnung','Schlussrechnung','Angebot','Offerte','Mahnung','Zahlungserinnerung'].includes(source)?documentText(doc.data,source):source;
}
export const templateOf=(d:any)=>['modern','compact'].includes(d?.templateId)?d.templateId:'classic';
export const templates=[['classic','Vorlage 1','Ihr bisheriger Briefkopf'],['modern','Modern','Farbiger Kopf und klare Flächen'],['compact','Kompakt','Weniger Abstand, mehr Positionen']] as const;
export const germanPdfNotice='Deutschland: PDF-Beleg, keine strukturierte E-Rechnung (XRechnung/ZUGFeRD). Nur verwenden, wenn PDF für diesen Vorgang zulässig ist und der Empfänger erforderlichenfalls zustimmt. Keine Unterstützung für Reverse Charge, Auslandsgeschäfte oder öffentliche Auftraggeber.';
