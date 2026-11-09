import {useEffect,useRef,useState,ReactNode} from 'react';
import {FileDown,Send,MoreHorizontal,X} from 'lucide-react';
import {t} from './i18n';
import './save-options.css';
import './document-actions.css';
function ActionDialog({title,close,children}: {title:string;close:()=>void;children:ReactNode}){
 const ref=useRef<HTMLDialogElement>(null);
 useEffect(()=>{ref.current?.showModal();return()=>ref.current?.close();},[]);
 return <dialog ref={ref} className="documentSaveDialog documentActionsDialog" aria-label={t(title)} onCancel={e=>{e.preventDefault();close();}} onKeyDown={e=>{if(e.key==='Escape')e.stopPropagation();}}><header className="documentSaveHeading"><h2>{t(title)}</h2><button className="iconButton" aria-label={t('Schliessen')} onClick={close}><X/></button></header>{children}</dialog>;
}
export default function DocumentActions({status,files,manage,sending,history}:any){
 const [open,setOpen]=useState('');
 const close=()=>setOpen('');
 // Close after the selected button runs. Closing React state during capture
 // unmounts the button before React can deliver its click handler.
 function action(e:any){if(e.target.closest('button:not(:disabled)'))close();}
 function beforeFileAction(e:any){if(e.target.closest('button:not(:disabled)'))e.currentTarget.closest('dialog')?.close();}
 return <><div className="documentCompactActions noPrint">{status}<div className="documentActionRow"><button className="secondary" onClick={()=>setOpen('files')}><FileDown size={20}/><span>{t('PDF & Drucken')}</span></button><button className="secondary" disabled={!sending} onClick={()=>setOpen('send')}><Send size={20}/><span>{t('Versenden')}</span></button><button className="secondary" onClick={()=>setOpen('manage')}><MoreHorizontal size={20}/><span>{t('Weitere Aktionen')}</span></button></div></div>
 {open&&<ActionDialog title={open==='files'?'PDF & Drucken':open==='send'?'Versenden':'Weitere Aktionen'} close={close}>{open==='files'?<div onClickCapture={beforeFileAction} onClick={action}>{files}</div>:open==='send'?sending:<><div onClick={action}>{manage}</div>{history}</>}</ActionDialog>}</>;
}
