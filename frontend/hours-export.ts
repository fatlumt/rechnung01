import {t,getLocale} from './i18n';
import {jsPDF} from 'jspdf';
import autoTable from 'jspdf-autotable';
export const localTime=(seconds:number)=>new Intl.DateTimeFormat('sv-SE',{timeZone:'Europe/Zurich',year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hour12:false}).format(new Date(seconds*1000)).replace(' ','T');
export const duration=(seconds:number)=>`${Math.floor(seconds/3600)}:${String(Math.floor(seconds%3600/60)).padStart(2,'0')}`;
export function timesheetPdf(company:string,rows:any[],employees:any[],period:string){
 const pdf=new jsPDF();pdf.setFontSize(18);pdf.text(t('Arbeitszeitnachweis'),14,20);pdf.setFontSize(10);
 const intro=pdf.splitTextToSize(company+'\n'+period+'\n'+t('Zeiten: Europe/Zurich · Pausen sind abgezogen. Nur abgeschlossene Einträge.'),180);pdf.text(intro,14,28);
 autoTable(pdf,{startY:30+intro.length*5,head:[['Mitarbeiter / Projekt','Datum / Zeit','Pause','Arbeitszeit'].map(label=>t(label))],body:rows.map(r=>{const v=r.data;return [`${employees.find(e=>e.id===v.employeeId)?.data.name||t('Mitarbeiter')}\n${v.project}${v.notes?'\n'+v.notes:''}`,`${localTime(v.start).replace('T',' ')}\n${t('bis')} ${localTime(v.end).replace('T',' ')}`,duration(v.breakSeconds),duration(v.seconds)];}),foot:[[t('Total'),'','',duration(rows.reduce((s,r)=>s+r.data.seconds,0))]],showFoot:'lastPage',styles:{fontSize:9,cellPadding:3,overflow:'linebreak'},headStyles:{fillColor:[45,55,65]},footStyles:{fillColor:[240,242,245],textColor:[20,25,30]},columnStyles:{0:{cellWidth:77},1:{cellWidth:58}},margin:{bottom:18},didDrawPage:()=>{pdf.setFontSize(8);pdf.text(t('digitalrechnung · Seite {page}',{page:pdf.getNumberOfPages()}),14,287);}});return pdf;
}
export function timesheetCsv(rows:any[],employees:any[]){
 const safe=(x:any)=>{let s=String(x??'');if(/^[\s]*[=+@-]/.test(s))s="'"+s;return '"'+s.replace(/"/g,'""')+'"'};
 return '\uFEFF'+[['Mitarbeiter','Projekt','Beginn','Ende','Pause (Minuten)','Arbeitszeit (Stunden)','Bemerkung'].map(label=>t(label)),...rows.map(r=>{const v=r.data;return [employees.find(e=>e.id===v.employeeId)?.data.name||'',v.project,localTime(v.start),localTime(v.end),(v.breakSeconds/60).toFixed(2).replace('.',getLocale()==='en'?'.':','),(v.seconds/3600).toFixed(4).replace('.',getLocale()==='en'?'.':','),v.notes]})].map(row=>row.map(safe).join(';')).join('\r\n');
}
