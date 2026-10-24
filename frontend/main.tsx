import {t,useLocale,setTranslationOverrides} from './i18n';
import LanguagePicker from './language-picker';
import PasswordRecovery from './password-recovery';
import React,{useEffect,useState} from 'react';
import {createRoot} from 'react-dom/client';
import App from './ui';
import Feedback from './feedback';
import {api} from './api';
import './globals.css';
import './invoice-template.css';
import './auth.css';
const invitation=new URLSearchParams(location.hash.slice(1)).get('invite')||'';
const resetToken=new URLSearchParams(location.hash.slice(1)).get('reset')||'';
if(invitation||resetToken)history.replaceState(null,'',location.pathname+location.search);
function Root(){
 useLocale();
 const [recovery,setRecovery]=useState<string|null>(resetToken||null);
 useEffect(()=>{const refresh=()=>api('translations').then(result=>setTranslationOverrides(result.overrides)).catch(()=>{});refresh();window.addEventListener('focus',refresh);return()=>window.removeEventListener('focus',refresh)},[]);
 const [session,setSession]=useState<any>(null),[error,setError]=useState(''),[busy,setBusy]=useState(false),[email,setEmail]=useState(''),[password,setPassword]=useState(''),[confirm,setConfirm]=useState(''),[token,setToken]=useState(invitation);
 async function load(){try{setSession(await api('auth'));setError('')}catch(e:any){setError(e.message)}}
 useEffect(()=>{load()},[]);
 useEffect(()=>{const expired=()=>{setSession(null);setPassword('');setConfirm('');load()};window.addEventListener('session-expired',expired);return()=>window.removeEventListener('session-expired',expired)},[]);
 if(recovery!==null)return <div className="authPage"><section className="authCard"><LanguagePicker compact/><a className="brand" href="./"><img className="appBrandLogo" src="assets/digitalrechnung-logo.png" alt="DigitalRechnung"/></a><PasswordRecovery token={recovery} initialEmail={email} back={()=>{setRecovery(null);setPassword('');setConfirm('');load()}}/></section></div>;
 if(session?.user)return <App email={session.user.email} user={session.user} onLogout={async()=>{await api('auth',{action:'logout'});setSession(null);setPassword('');setConfirm('');await load()}}/>;
 const register=session?.setupRequired||!!invitation;
 return <div className="authPage"><section className="authCard"><LanguagePicker compact/><a className="brand" href="./"><img className="appBrandLogo" src="assets/digitalrechnung-logo.png" alt="DigitalRechnung"/></a><p className="eyebrow">{t("IHR UNTERNEHMEN. IHRE BELEGE.")}</p><h1>{register?t("Konto einrichten"):t("Willkommen zurück.")}</h1><p className="muted">{t("Offerten, Rechnungen und Zahlungen an einem Ort.")}</p>{error&&<div className="alert error" role="alert">{error}</div>}{!session?<button className="secondary" onClick={load}>{t("Verbindung erneut prüfen")}</button>:<form onSubmit={async e=>{e.preventDefault();if(register&&password!==confirm){setError(t("Die Passwörter stimmen nicht überein."));return}setBusy(true);setError('');try{setSession(await api('auth',{action:register?'register':'login',email,password,token}));setPassword('');setConfirm('');}catch(e:any){setError(e.message)}finally{setBusy(false)}}}><label className="field"><span>{t("E-Mail")}</span><input autoComplete="username" type="email" required value={email} onChange={e=>setEmail(e.target.value)}/></label><label className="field"><span>{t("Passwort")}{register?t(" (mindestens 12 Zeichen)"):''}</span><input autoComplete={register?'new-password':'current-password'} type="password" minLength={12} required value={password} onChange={e=>setPassword(e.target.value)}/></label>{register&&<><label className="field"><span>{t("Passwort wiederholen")}</span><input autoComplete="new-password" type="password" required value={confirm} onChange={e=>setConfirm(e.target.value)}/></label>{session.setupRequired&&<label className="field"><span>{t("Einrichtungscode aus der Serverkonfiguration")}</span><input type="password" required value={token} onChange={e=>setToken(e.target.value)}/></label>}</>}<button className="primary" disabled={busy}>{busy?t("Bitte warten …"):register?t("Konto erstellen"):t("Anmelden")}</button></form>}{session&&!register&&<button type="button" className="textButton recoveryBack" disabled={busy} onClick={()=>{setError('');setRecovery('')}}>{t('Passwort vergessen?')}</button>}<p className="authFoot">{t("Schweiz & Deutschland · Ein eigener Arbeitsplatz pro Unternehmen")}</p></section></div>;
}
createRoot(document.getElementById('root')!).render(<><Feedback/><Root/></>);
