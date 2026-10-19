import React,{useEffect,useState} from 'react';
import {createRoot} from 'react-dom/client';
import App from './ui';
import {api} from './api';
import './globals.css';
import './invoice-template.css';
import './auth.css';
const invitation=new URLSearchParams(location.hash.slice(1)).get('invite')||'';
if(invitation)history.replaceState(null,'',location.pathname+location.search);
function Root(){
 const [session,setSession]=useState<any>(null),[error,setError]=useState(''),[busy,setBusy]=useState(false),[email,setEmail]=useState(''),[password,setPassword]=useState(''),[confirm,setConfirm]=useState(''),[token,setToken]=useState(invitation);
 async function load(){try{setSession(await api('auth'));setError('')}catch(e:any){setError(e.message)}}
 useEffect(()=>{load()},[]);
 if(session?.user)return <App email={session.user.email} user={session.user} onLogout={async()=>{await api('auth',{action:'logout'});setSession(null);setPassword('');setConfirm('');await load()}}/>;
 const register=session?.setupRequired||!!invitation;
 return <div className="authPage"><section className="authCard"><a className="brand" href="./">digitalrechnung<span className="brandPeriod">.</span></a><p className="eyebrow">IHR UNTERNEHMEN. IHRE BELEGE.</p><h1>{register?'Konto einrichten':'Willkommen zurück.'}</h1><p className="muted">Offerten, Rechnungen und Zahlungen an einem Ort.</p>{error&&<div className="alert error" role="alert">{error}</div>}{!session?<button className="secondary" onClick={load}>Verbindung erneut prüfen</button>:<form onSubmit={async e=>{e.preventDefault();if(register&&password!==confirm){setError('Die Passwörter stimmen nicht überein.');return}setBusy(true);setError('');try{setSession(await api('auth',{action:register?'register':'login',email,password,token}));setPassword('');setConfirm('');}catch(e:any){setError(e.message)}finally{setBusy(false)}}}><label className="field"><span>E-Mail</span><input autoComplete="username" type="email" required value={email} onChange={e=>setEmail(e.target.value)}/></label><label className="field"><span>Passwort{register?' (mindestens 12 Zeichen)':''}</span><input autoComplete={register?'new-password':'current-password'} type="password" minLength={12} required value={password} onChange={e=>setPassword(e.target.value)}/></label>{register&&<><label className="field"><span>Passwort wiederholen</span><input autoComplete="new-password" type="password" required value={confirm} onChange={e=>setConfirm(e.target.value)}/></label>{session.setupRequired&&<label className="field"><span>Einrichtungscode aus der Serverkonfiguration</span><input type="password" required value={token} onChange={e=>setToken(e.target.value)}/></label>}</>}<button className="primary" disabled={busy}>{busy?'Bitte warten …':register?'Konto erstellen':'Anmelden'}</button></form>}<p className="authFoot">Schweiz & Deutschland · Ein eigener Arbeitsplatz pro Unternehmen</p></section></div>;
}
createRoot(document.getElementById('root')!).render(<Root/>);
