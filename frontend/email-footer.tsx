export default function EmailFooter({company:c}:any){
 if(!c)return null;
 const lines=[`${c.street||''} ${c.house||''}`.trim(),`${c.zip||''} ${c.city||''}`.trim(),c.country==='CH'?'Schweiz':c.country==='DE'?'Deutschland':'',c.phone?`Tel. ${c.phone}`:'',c.email,c.uid?`UID / USt-IdNr.: ${c.uid}`:'',c.legalDetails].filter(Boolean);
 return <section aria-label="Firmenfussbereich der E-Mail" style={{margin:'20px 0',padding:'20px 16px',background:'#fff',color:'#202936',borderTop:`2px solid ${c.brandColor||'#2558d7'}`,overflowWrap:'anywhere'}}>
  {c.logoUrl&&<img src={c.logoUrl} alt={c.name} style={{display:'block',maxWidth:150,maxHeight:75,width:'auto',height:'auto',marginBottom:14}}/>}
  <strong style={{display:'block',fontSize:17,marginBottom:5}}>{c.name}</strong>
  <div style={{fontSize:13,lineHeight:1.6,color:'#4b5563',whiteSpace:'pre-line'}}>{lines.join('\n')}</div>
 </section>;
}
