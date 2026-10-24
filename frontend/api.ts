import {notify} from './feedback';
import {t,systemMessage} from './i18n';
let token='';
export function setToken(value:string){token=value;}
export async function api(route:string,body?:any){
 try{
  const response=await fetch(`api.php?route=${route}`,{cache:'no-store',credentials:'same-origin',...(body?{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':token},body:JSON.stringify(body)}:{})});
  let data;try{data=await response.json()}catch{throw Error('Serverantwort konnte nicht gelesen werden. Bitte den gespeicherten Stand prüfen, bevor Sie erneut versuchen.')}
  if(!response.ok){if(response.status===401&&route!=='auth'){token='';window.dispatchEvent(new Event('session-expired'));}throw Error(data.error||'Anfrage fehlgeschlagen.');}if(data.csrf)setToken(data.csrf);
  if(body){if(route==='mail')notify(t(data.status==='accepted'?'E-Mail gesendet.':'Versand nicht bestätigt. Bitte Versandverlauf und Postfach prüfen.'),data.status==='accepted'?'success':'error');else if(route==='notifications')notify(t('Benachrichtigung als gelesen markiert.'));else if(route!=='auth'&&body.action!=='receipt')notify(t(body.action==='delete'?'Erfolgreich gelöscht.':'Änderungen erfolgreich gespeichert.'));else if(body.action==='password')notify(t('Passwort erfolgreich geändert.'));}
  return data;
 }catch(e:any){const message=e instanceof TypeError?'Verbindung unterbrochen. Bitte den gespeicherten Stand prüfen, bevor Sie erneut versuchen.':e.message||'Die Aktion konnte nicht bestätigt werden.';const error=Error(systemMessage(message));if(body)notify(error.message,'error');throw error;}
 finally{if(body&&route==='mail')window.dispatchEvent(new Event('mail-attempt'));}
}
