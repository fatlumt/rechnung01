import React,{useState} from 'react';
import jsQR from 'jsqr';
export function parsePaymentQr(raw:string){
 const p=raw.trim().split(/\r\n|\r|\n/);
 if(p.length<31||p[0]!=='SPC'||p[1]!=='0200'||p[2]!=='1'||p[19]!=='CHF'||p[27]!=='NON'||p[28]!==''||p[30]!=='EPD')throw Error('Bitte einen Swiss QR-Code in CHF ohne feste Referenz hochladen.');
 if(p[4]!=='S'||p.slice(11,18).join('')!=='')throw Error('Bitte einen aktuellen Bank-QR-Code mit strukturierter Adresse verwenden.');
 if(p[18]!==''||p.slice(20,27).join('')!=='')throw Error('Dieser QR-Code enthält einen festen Betrag oder Kunden. Bitte einen neutralen Code Ihrer Bank verwenden.');
 return {iban:p[3].replace(/\s/g,'').toUpperCase(),name:p[5],raw:raw.trim()};
}
export default function PaymentQr({value,set}:any){
 const [error,setError]=useState(''),[busy,setBusy]=useState(false);
 async function upload(file?:File){if(!file)return;setBusy(true);setError('');try{
  if(!['image/png','image/jpeg','image/webp'].includes(file.type)||file.size>12000000)throw Error('Bitte PNG, JPG oder WebP bis 12 MB verwenden.');
  const src=await new Promise<string>((resolve,reject)=>{const r=new FileReader();r.onload=()=>resolve(String(r.result));r.onerror=reject;r.readAsDataURL(file)});
  const img=await new Promise<HTMLImageElement>((resolve,reject)=>{const i=new Image();i.onload=()=>resolve(i);i.onerror=reject;i.src=src});
  const scale=Math.min(1,2000/Math.max(img.width,img.height));const canvas=document.createElement('canvas');canvas.width=Math.round(img.width*scale);canvas.height=Math.round(img.height*scale);const ctx=canvas.getContext('2d')!;ctx.fillStyle='white';ctx.fillRect(0,0,canvas.width,canvas.height);ctx.drawImage(img,0,0,canvas.width,canvas.height);
  const pixels=ctx.getImageData(0,0,canvas.width,canvas.height);const code=jsQR(pixels.data,pixels.width,pixels.height);if(!code)throw Error('Kein lesbarer QR-Code erkannt. Bitte ein scharfes Bild des Bank-QR-Codes wählen.');
  const parsed=parsePaymentQr(code.data);if(value.iban&&value.iban.replace(/\s/g,'').toUpperCase()!==parsed.iban)throw Error('Der QR-Code gehört zu einer anderen IBAN als Ihrer Firmen-IBAN.');
  const corners=[code.location.topLeftCorner,code.location.topRightCorner,code.location.bottomLeftCorner,code.location.bottomRightCorner];const x=Math.min(...corners.map(p=>p.x)),y=Math.min(...corners.map(p=>p.y)),side=Math.max(...corners.map(p=>p.x))-x,height=Math.max(...corners.map(p=>p.y))-y;
  const cropped=document.createElement('canvas');cropped.width=600;cropped.height=600;const out=cropped.getContext('2d')!;out.fillStyle='white';out.fillRect(0,0,600,600);out.drawImage(canvas,x,y,side,height,45,45,510,510);const url=cropped.toDataURL('image/png');if(url.length>266690)throw Error('QR-Bild zu gross. Bitte einen klaren digitalen QR-Code verwenden.');
  const verify=out.getImageData(0,0,600,600);if(jsQR(verify.data,600,600)?.data!==code.data)throw Error('Das zugeschnittene Bild ist nicht lesbar. Bitte einen gerade ausgerichteten QR-Code wählen.');
  set((old:any)=>({...old,iban:old.iban||parsed.iban,qrUrl:url,qrPayload:parsed.raw}));
 }catch(e:any){setError(e.message||'QR-Code konnte nicht gelesen werden.')}finally{setBusy(false)}}
 return <section className="qrSettings"><hr/><h2>QR-Zahlungsseite</h2><p className="muted">Bank-QR-Code ohne festen Betrag, Kunden oder Referenz. Er wird auf einer eigenen Zahlungsseite für neue Rechnungen angezeigt. Die Bankverbindung bleibt im Beleg gespeichert.</p><label className="field"><span>Swiss QR-Code hochladen</span><input type="file" accept="image/png,image/jpeg,image/webp" disabled={busy} onChange={e=>upload(e.target.files?.[0])}/></label>{busy&&<p role="status">QR-Code wird lokal geprüft …</p>}{error&&<p className="alert error" role="alert">{error}</p>}{value.qrUrl&&<div className="qrUploadPreview"><img src={value.qrUrl} alt="Hochgeladener Bank-QR-Code"/><p>CHF · {value.iban}</p><button type="button" className="secondary" onClick={()=>set({...value,qrUrl:'',qrPayload:''})}>QR-Code entfernen</button></div>}<p className="hint">Bitte Firmendaten speichern. Dies ist eine Zahlungsinformation mit Ihrem Bank-Code, kein automatisch generierter standardisierter QR-Zahlteil.</p></section>;
}
