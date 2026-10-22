import {notify} from './feedback';
let token='';
export function setToken(value:string){token=value;}
export async function api(route:string,body?:any){
 try{
  const response=await fetch(`api.php?route=${route}`,{cache:'no-store',credentials:'same-origin',...(body?{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':token},body:JSON.stringify(body)}:{})});
  let data;try{data=await response.json()}catch{throw Error('Serverantwort konnte nicht gelesen werden. Bitte den gespeicherten Stand prüfen, bevor Sie erneut versuchen.')}
  if(!response.ok)throw Error(data.error||'Anfrage fehlgeschlagen.');if(data.csrf)setToken(data.csrf);
  if(body){if(route==='mail')notify(data.status==='accepted'?'E-Mail vom Mailserver angenommen. Die Zustellung ist noch nicht bestätigt.':'Versand nicht bestätigt. Bitte Versandverlauf und Postfach prüfen.',data.status==='accepted'?'success':'error');else if(route==='notifications')notify('Benachrichtigung als gelesen markiert.');else if(route!=='auth'&&body.action!=='receipt')notify(body.action==='delete'?'Erfolgreich gelöscht.':'Änderungen erfolgreich gespeichert.');else if(body.action==='password')notify('Passwort erfolgreich geändert.');}
  return data;
 }catch(e:any){const error=e instanceof TypeError?Error('Verbindung unterbrochen. Bitte den gespeicherten Stand prüfen, bevor Sie erneut versuchen.'):e;if(body)notify(error.message||'Die Aktion konnte nicht bestätigt werden.','error');throw error;}
 finally{if(body&&route==='mail')window.dispatchEvent(new Event('mail-attempt'));}
}
