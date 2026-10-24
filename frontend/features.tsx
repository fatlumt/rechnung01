import {t} from './i18n';
import {createContext,useContext,useState} from 'react';
export const FeatureContext=createContext<Record<string,boolean>>({});
export const useFeatures=()=>useContext(FeatureContext);
export const viewFeature:Record<string,string>={invoice:'invoices',offer:'offers',reminders:'reminders',payments:'invoices',expenses:'expenses',hours:'hours'};
export function AccountFeatures({user,catalog,busy,save,close}:any){
 const [value,set]=useState({...user.features});
 return <form className="businessBox" onSubmit={e=>{e.preventDefault();save({action:'features',id:user.id,version:user.featureVersion,features:value})}}><h3>{t("Funktionen für")} {user.email}</h3><p>{t("Freigaben gelten für diesen Firmenbereich. Deaktivieren löscht keine Daten. Superadmin-Konten behalten alle Funktionen.")}</p><div className="grid two">{Object.entries(catalog).map(([key,label])=><label className="checkbox" key={key}><input type="checkbox" disabled={busy||(['reminders','deposits'].includes(key)&&!value.invoices)} checked={value[key]} onChange={e=>set({...value,[key]:e.target.checked,...(key==='invoices'&&!e.target.checked?{reminders:false,deposits:false}:{})})}/>{t(String(label))}</label>)}</div><p className="hint">{t("Mahnwesen und Akontos benötigen Rechnungen. Kunden, Firmendaten und Hilfe bleiben verfügbar. Arbeitszeiten können erst deaktiviert werden, wenn alle Timer gestoppt sind. Freischaltungen werden protokolliert; es werden keine Zahlungen abgebucht.")}</p><div className="saveActions"><button className="primary" disabled={busy}>{t("Freigaben speichern")}</button><button className="secondary" type="button" disabled={busy} onClick={close}>{t("Abbrechen")}</button></div></form>
}
