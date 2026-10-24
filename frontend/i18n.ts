import {useSyncExternalStore} from 'react';
import english from './locales/en.json';
import albanian from './locales/sq.json';

export const languages=[{code:'de',name:'Deutsch'},{code:'en',name:'English'},{code:'sq',name:'Shqip'}] as const;
export type Locale=typeof languages[number]['code'];
export type TranslationOverrides=Record<string,Record<string,string>>;
const storageKey='digitalrechnung-language';
let locale:Locale='de';
try{const saved=localStorage.getItem(storageKey);if(languages.some(l=>l.code===saved))locale=saved as Locale;}catch{}
let overrides:TranslationOverrides={};
let revision=0;
const listeners=new Set<()=>void>();
const own=(object:object,key:string)=>Object.prototype.hasOwnProperty.call(object,key);
function emit(){revision++;listeners.forEach(fn=>fn());}
function updateDocumentLanguage(){if(typeof document!=='undefined')document.documentElement.lang=locale;}
updateDocumentLanguage();
export function getLocale():Locale{return locale;}
export function setLocale(value:string){if(!languages.some(l=>l.code===value))return;locale=value as Locale;try{localStorage.setItem(storageKey,locale);}catch{}updateDocumentLanguage();emit();}
export function setTranslationOverrides(value:TranslationOverrides){
 const next:TranslationOverrides={};
 for(const language of languages){const entries=value?.[language.code];next[language.code]=Object.create(null);if(entries&&typeof entries==='object')for(const [key,text] of Object.entries(entries))if(typeof text==='string'&&typeof key==='string')next[language.code][key]=text;}
 overrides=next;emit();
}
export function getTranslationState(){return {locale,overrides,revision};}
export function useLocale(){useSyncExternalStore(callback=>{listeners.add(callback);return()=>{listeners.delete(callback)}},()=>revision,()=>0);return locale;}
export function translateFor(language:string,source:string,variables:Record<string,string|number>={}):string{
 if(typeof source!=='string')return source??'';
 const custom=overrides[language];
 const base:Record<string,string>=language==='en'?english:language==='sq'?albanian:{};
 const translated=custom&&own(custom,source)?custom[source]:own(base,source)?base[source]:source;
 return translated.replace(/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/g,(match,key)=>own(variables,key)?String(variables[key]):match);
}
export function t(source:string,variables:Record<string,string|number>={}):string{return translateFor(locale,source,variables);}
// Only recognise known system messages. Customer names and entered text are
// substituted unchanged, never used as translation keys.
export function systemMessage(source:string):string{
 const placeholder=/^Unbekannter E-Mail-Platzhalter: (.+)$/.exec(source);
 if(placeholder)return t('Unbekannter E-Mail-Platzhalter: {TOKEN}',{TOKEN:placeholder[1]});
 const mail=/^Der Versand von (.+) an (.+) konnte nicht bestätigt werden\. Bitte Postfach und Versandverlauf prüfen, bevor Sie erneut senden\.$/.exec(source);
 if(mail)return t('Der Versand von {number} an {email} konnte nicht bestätigt werden. Bitte Postfach und Versandverlauf prüfen, bevor Sie erneut senden.',{number:mail[1],email:mail[2]});
 return t(source);
}
if(typeof window!=='undefined')window.addEventListener('storage',event=>{if(event.key===storageKey&&languages.some(l=>l.code===event.newValue)){locale=event.newValue as Locale;updateDocumentLanguage();emit();}});
