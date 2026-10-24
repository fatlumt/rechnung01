import {t} from './i18n';
import React from 'react';
import {displayFields,visible} from './model';
import {countryOf} from './country';

export default function CompanyFields({value,set}:any){
 return <section className="companyFields"><h3>{t("Felder auf Belegen anzeigen")}</h3>
  <p className="hint">{t("Standard für neue Belege, Vorschau und PDF. Bereits gespeicherte Belege behalten ihre Anzeigeoptionen.")}</p>
  <div className="visibilityFields">{displayFields.map(([key,label])=>{
   const required=countryOf(value)==='DE'&&['uid','number','serviceDate'].includes(key);
   return <label className="checkbox" key={key}><input type="checkbox" disabled={required} checked={required||visible(value,key)} onChange={e=>set({...value,visibility:{...value.visibility,[key]:e.target.checked}})}/>{t(label)}{required?" "+t("(erforderlich)"):''}</label>;
  })}</div>
 </section>;
}
