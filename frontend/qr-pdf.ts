import jsQR from 'jsqr';
// Loaded only when a PDF is selected. All assets are served by this installation.
export async function readPdfQr(file:File,validate:(raw:string)=>unknown,progress:(message:string)=>void){
 const moduleUrl=new URL('assets/pdf-reader/pdf.min.mjs',document.baseURI).href;
 const pdfjs=await import(/* webpackIgnore: true */ moduleUrl);
 pdfjs.GlobalWorkerOptions.workerSrc=new URL('assets/pdf-reader/pdf.worker.min.mjs',document.baseURI).href;
 const task=pdfjs.getDocument({data:new Uint8Array(await file.arrayBuffer()),isEvalSupported:false,useWasm:false,disableFontFace:true,useSystemFonts:true});
 try{
  const pdf=await task.promise;
  if(pdf.numPages>10)throw Error('Bitte eine PDF mit höchstens 10 Seiten auswählen.');
  let invalid='';
  for(let n=1;n<=pdf.numPages;n++){
   progress(`PDF wird geprüft: Seite ${n} von ${pdf.numPages} …`);
   const page=await pdf.getPage(n),base=page.getViewport({scale:1}),viewport=page.getViewport({scale:Math.min(3,2200/Math.max(base.width,base.height))});
   const canvas=document.createElement('canvas');canvas.width=Math.ceil(viewport.width);canvas.height=Math.ceil(viewport.height);
   try{const ctx=canvas.getContext('2d',{willReadFrequently:true})!;await page.render({canvas,canvasContext:ctx,viewport}).promise;
    const pixels=ctx.getImageData(0,0,canvas.width,canvas.height),code=jsQR(pixels.data,pixels.width,pixels.height);
    if(code){try{validate(code.data);return {canvas,code}}catch(e:any){invalid=e.message}}
   }finally{page.cleanup()}
  }
  throw Error(invalid||'Kein lesbarer Swiss QR-Code in dieser PDF gefunden. Bitte die Bank-PDF mit dem QR-Code auswählen.');
 }catch(e:any){if(e.name==='PasswordException')throw Error('Diese PDF ist passwortgeschützt. Bitte eine ungeschützte Kopie verwenden.');if(e.name==='InvalidPDFException')throw Error('Die PDF konnte nicht gelesen werden. Bitte eine gültige Bank-PDF auswählen.');throw e}
 finally{await task.destroy()}
}
