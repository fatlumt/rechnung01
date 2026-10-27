/* Push only: deliberately no fetch handler and no offline cache of company data. */
self.addEventListener('push',event=>{
 let data={};try{data=event.data?.json()||{}}catch{}
 const url=new URL(data.url||'./?notifications=1',self.registration.scope);
 const safe=url.origin===self.location.origin&&url.pathname.startsWith(new URL(self.registration.scope).pathname)?url.href:self.registration.scope;
 event.waitUntil(self.registration.showNotification('DigitalRechnung',{body:typeof data.body==='string'?data.body:'Neue Benachrichtigung in DigitalRechnung.',icon:'assets/icons/app-192.png',badge:'assets/icons/app-192.png',tag:typeof data.tag==='string'?data.tag:'digitalrechnung',data:{url:safe}}));
});
self.addEventListener('notificationclick',event=>{
 event.notification.close();const url=new URL(event.notification.data?.url||'./',self.registration.scope);
 if(url.origin!==self.location.origin)return;
 event.waitUntil((async()=>{const windows=await self.clients.matchAll({type:'window',includeUncontrolled:true});for(const client of windows){if(new URL(client.url).origin===url.origin){await client.navigate(url.href);return client.focus();}}return self.clients.openWindow(url.href);})());
});
