let next=0;
export function beginActivity(){const id=++next;window.dispatchEvent(new CustomEvent('app-activity',{detail:{id,active:true}}));let ended=false;return()=>{if(ended)return;ended=true;window.dispatchEvent(new CustomEvent('app-activity',{detail:{id,active:false}}));};}
