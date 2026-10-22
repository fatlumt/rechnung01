import React,{useState} from 'react';

export default function CompanyHeading({company}:any){
 const [failedLogo,setFailedLogo]=useState('');
 const name=company.name?.trim()||'Ihr Unternehmen';
 const initials=name.split(/\s+/).slice(0,2).map((part:string)=>Array.from(part)[0]).join('').toLocaleUpperCase('de').slice(0,4);
 return <section className="companyHeading" aria-label="Ihr Unternehmen">
  <div className="companyHeadingLogo">{company.logoUrl&&failedLogo!==company.logoUrl?<img src={company.logoUrl} alt="Firmenlogo" onError={()=>setFailedLogo(company.logoUrl)}/>:<span aria-hidden="true">{initials}</span>}</div>
  <div className="companyHeadingText"><p>Willkommen zurück</p><h2>{name}</h2><span className="companyWelcomeNote">Ihr Unternehmen. Alles im Blick.</span></div>
 </section>;
}
