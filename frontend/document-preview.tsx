import React,{useEffect,useRef,useState} from 'react';
import {invoicePdf} from './pdf';
// Preview the same generated PDF used by download, including attachments.
export default function DocumentPreview({doc,payments,documents,onReady}:any){
 const host=useRef<HTMLDivElement>(null);
 const [state,setState]=useState('loading'),[retry,setRetry]=useState(0);
 useEffect(()=>{
  let cancelled=false,task:any;
  const container=host.current!;
  container.replaceChildren();setState('loading');onReady?.(false);
  async function render(){
   try{
    const moduleUrl=new URL('assets/pdf-reader/pdf.min.mjs',document.baseURI).href;
    const pdfjs=await import(/* webpackIgnore: true */ moduleUrl);
    if(cancelled)return;
    pdfjs.GlobalWorkerOptions.workerSrc=new URL('assets/pdf-reader/pdf.worker.min.mjs',document.baseURI).href;
    const bytes=new Uint8Array(invoicePdf(doc,payments,documents).output('arraybuffer'));
    task=pdfjs.getDocument({data:bytes,isEvalSupported:false,useWasm:false,useSystemFonts:true});
    const pdf=await task.promise;
    for(let n=1;n<=pdf.numPages;n++){
     if(cancelled)return;
     const page=await pdf.getPage(n),viewport=page.getViewport({scale:1.5});
     if(cancelled)return;
     const canvas=document.createElement('canvas');
     canvas.width=Math.ceil(viewport.width);canvas.height=Math.ceil(viewport.height);
     canvas.setAttribute('role','img');canvas.setAttribute('aria-label',`Belegvorschau – Seite ${n} von ${pdf.numPages}`);
     await page.render({canvas,canvasContext:canvas.getContext('2d')!,viewport}).promise;
     page.cleanup();
     if(cancelled)return;
     container.append(canvas);
    }
    setState('ready');onReady?.(true);
   }catch{if(!cancelled){container.replaceChildren();setState('error')}}
  }
  render();
  return()=>{cancelled=true;if(task)void task.destroy().catch(()=>{});container.replaceChildren()};
 },[doc,payments,documents,retry,onReady]);
 return <div className="pdfPreview">
  {state==='loading'&&<p className="hint noPrint" role="status">PDF-Vorschau wird erstellt …</p>}
  {state==='error'&&<div className="alert error noPrint" role="alert">Die PDF-Vorschau konnte nicht geladen werden. Sie können den Beleg weiterhin als PDF herunterladen.<button type="button" onClick={()=>setRetry(n=>n+1)}>Erneut versuchen</button></div>}
  <div className="pdfPreviewPages" ref={host} aria-busy={state==='loading'}/>
 </div>;
}
