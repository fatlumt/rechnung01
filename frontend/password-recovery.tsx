import {useState} from 'react';
import {api} from './api';
import {t,getLocale} from './i18n';

export default function PasswordRecovery({token='',initialEmail='',back}:{token?:string,initialEmail?:string,back:()=>void}){
 const [email,setEmail]=useState(initialEmail),[password,setPassword]=useState(''),[confirm,setConfirm]=useState(''),[busy,setBusy]=useState(false),[complete,setComplete]=useState(false),[error,setError]=useState('');
 const reset=!!token;
 async function submit(event:any){
  event.preventDefault();if(busy)return;setError('');
  if(reset&&password!==confirm){setError('Die Passwörter stimmen nicht überein.');return;}
  setBusy(true);
  try{
   await api('auth',reset?{action:'resetPassword',token,password,confirm,language:getLocale()}:{action:'forgotPassword',email,language:getLocale()});
   setPassword('');setConfirm('');setComplete(true);
  }catch(e:any){setError(e.message)}finally{setBusy(false)}
 }
 return <section className="passwordRecovery"><h1>{t(reset?'Neues Passwort festlegen':'Passwort vergessen?')}</h1>
  {complete?<div className="alert success" role="status">{t(reset?'Ihr Passwort wurde geändert. Melden Sie sich mit dem neuen Passwort an.':'Wenn ein aktives Konto mit dieser E-Mail-Adresse existiert, erhalten Sie einen Link zum Zurücksetzen. Prüfen Sie auch Ihren Spam-Ordner.')}</div>:<>
   <p className="muted">{t(reset?'Wählen Sie ein neues Passwort. Nach dem Speichern werden alle bisherigen Sitzungen beendet.':'Geben Sie Ihre Konto-E-Mail ein. Der Link ist 30 Minuten gültig.')}</p>
   {error&&<div className="alert error" role="alert">{t(error)}</div>}
   <form onSubmit={submit}>
    {reset?<><label className="field"><span>{t('Neues Passwort (mindestens 12 Zeichen)')}</span><input type="password" autoComplete="new-password" minLength={12} maxLength={72} required value={password} disabled={busy} onChange={e=>setPassword(e.target.value)}/></label><label className="field"><span>{t('Passwort wiederholen')}</span><input type="password" autoComplete="new-password" minLength={12} maxLength={72} required value={confirm} disabled={busy} onChange={e=>setConfirm(e.target.value)}/></label></>:<label className="field"><span>{t('E-Mail')}</span><input type="email" autoComplete="email" required maxLength={190} value={email} disabled={busy} onChange={e=>setEmail(e.target.value)}/></label>}
    <button className="primary" disabled={busy}>{t(busy?'Bitte warten …':reset?'Neues Passwort speichern':'Link zum Zurücksetzen anfordern')}</button>
   </form>
  </>}
  <button type="button" className="textButton recoveryBack" disabled={busy} onClick={back}>{t('Zurück zur Anmeldung')}</button>
 </section>;
}
