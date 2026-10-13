let token='';
export function setToken(value:string){token=value;}
export async function api(route:string,body?:any){const response=await fetch(`api.php?route=${route}`,{cache:'no-store',credentials:'same-origin',...(body?{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':token},body:JSON.stringify(body)}:{})});let data;try{data=await response.json()}catch{throw Error('Server nicht erreichbar. Bitte erneut versuchen.')}if(!response.ok)throw Error(data.error||'Anfrage fehlgeschlagen.');if(data.csrf)setToken(data.csrf);return data;}
