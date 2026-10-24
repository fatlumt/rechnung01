import {Languages} from 'lucide-react';
import {languages,setLocale,t,useLocale} from './i18n';
import './language-picker.css';
export default function LanguagePicker({compact=false}:{compact?:boolean}){
 const locale=useLocale();
 return <label className={`languagePicker ${compact?'compact':''}`}><Languages size={18} aria-hidden="true"/><span>{t('App-Sprache')}</span><select aria-label={t('App-Sprache')} value={locale} onChange={event=>setLocale(event.target.value)}>{languages.map(language=><option key={language.code} value={language.code} lang={language.code}>{language.name}</option>)}</select></label>;
}
