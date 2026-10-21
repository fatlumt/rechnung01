import {PDFDocument} from 'pdf-lib';
export const maxOriginalPdf=5_000_000;
export async function originalPdf(file:File){
 if(file.size>maxOriginalPdf)throw Error('Bitte eine PDF bis 5 MB verwenden.');
 const bytes=new Uint8Array(await file.arrayBuffer());
 let pdf:PDFDocument;
 try{pdf=await PDFDocument.load(bytes)}catch{throw Error('Bitte eine gültige, ungeschützte PDF verwenden.')}
 if(pdf.getPageCount()<1||pdf.getPageCount()>10)throw Error('Bitte eine PDF mit 1 bis 10 Seiten verwenden.');
 if(pdf.getForm().getFields().length)throw Error('Bitte die PDF zuerst als nicht ausfüllbare PDF exportieren.');
 const url=await new Promise<string>((resolve,reject)=>{const reader=new FileReader();reader.onload=()=>resolve(String(reader.result));reader.onerror=()=>reject(Error('PDF konnte nicht gelesen werden.'));reader.readAsDataURL(new Blob([bytes],{type:'application/pdf'}))});
 return {url,pages:pdf.getPageCount()};
}
export async function logoFromPdf(file:File){
 if(file.size>maxOriginalPdf)throw Error('Bitte eine Logo-PDF bis 5 MB verwenden.');
 const pdfjs=await import(/* webpackIgnore: true */ new URL('assets/pdf-reader/pdf.min.mjs',document.baseURI).href);
 pdfjs.GlobalWorkerOptions.workerSrc=new URL('assets/pdf-reader/pdf.worker.min.mjs',document.baseURI).href;
 const task=pdfjs.getDocument({data:new Uint8Array(await file.arrayBuffer()),isEvalSupported:false,useWasm:false,useSystemFonts:true});
 try{
  const pdf=await task.promise,page=await pdf.getPage(1),base=page.getViewport({scale:1});
  const viewport=page.getViewport({scale:Math.min(3,1600/Math.max(base.width,base.height))});
  const canvas=document.createElement('canvas');canvas.width=Math.ceil(viewport.width);canvas.height=Math.ceil(viewport.height);
  const ctx=canvas.getContext('2d')!;
  await page.render({canvas,canvasContext:ctx,viewport,background:'rgba(255,255,255,0)'}).promise;
  // Remove empty page margins so an A4 logo PDF still produces a usable logo.
  const pixels=ctx.getImageData(0,0,canvas.width,canvas.height).data;
  let x0=canvas.width,y0=canvas.height,x1=-1,y1=-1;
  for(let y=0;y<canvas.height;y++)for(let x=0;x<canvas.width;x++){
   const i=(y*canvas.width+x)*4;
   if(pixels[i+3]>20&&Math.min(pixels[i],pixels[i+1],pixels[i+2])<245){x0=Math.min(x0,x);y0=Math.min(y0,y);x1=Math.max(x1,x);y1=Math.max(y1,y)}
  }
  if(x1<0)throw Error('Die erste PDF-Seite enthält kein sichtbares Logo.');
  x0=Math.max(0,x0-4);y0=Math.max(0,y0-4);x1=Math.min(canvas.width-1,x1+4);y1=Math.min(canvas.height-1,y1+4);
  for(const scale of [1,.8,.6,.4]){
   const crop=document.createElement('canvas');crop.width=Math.max(1,Math.round((x1-x0+1)*scale));crop.height=Math.max(1,Math.round((y1-y0+1)*scale));
   crop.getContext('2d')!.drawImage(canvas,x0,y0,x1-x0+1,y1-y0+1,0,0,crop.width,crop.height);
   const url=crop.toDataURL('image/png');
   if(url.length<=266690)return url;
  }
  throw Error('Logo zu detailliert. Bitte eine kleinere Logo-PDF verwenden.');
 }catch(e:any){if(e.name==='PasswordException')throw Error('Bitte eine ungeschützte Logo-PDF verwenden.');throw e}
 finally{await task.destroy()}
}
