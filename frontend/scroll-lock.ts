// iOS can still pan the document when only body overflow is hidden.
// Freeze it at its current position while the dialog scrolls independently.
export function lockPageScroll():()=>void {
 const body=document.body,root=document.documentElement;
 const x=window.scrollX,y=window.scrollY;
 const gap=Math.max(0,window.innerWidth-root.clientWidth);
 const padding=parseFloat(window.getComputedStyle(body).paddingRight)||0;
 const properties=['position','top','left','width','overflow','padding-right'];
 const saved=properties.map(name=>[name,body.style.getPropertyValue(name),body.style.getPropertyPriority(name)]);
 const rootOverflow=root.style.getPropertyValue('overflow'),rootPriority=root.style.getPropertyPriority('overflow');
 body.style.setProperty('position','fixed');
 body.style.setProperty('top',`${-y}px`);
 body.style.setProperty('left',`${-x}px`);
 body.style.setProperty('width','100%');
 body.style.setProperty('overflow','hidden');
 body.style.setProperty('padding-right',`${padding+gap}px`);
 root.style.setProperty('overflow','hidden');
 let restored=false;
 return()=>{
  if(restored)return;restored=true;
  const behavior=root.style.getPropertyValue('scroll-behavior'),priority=root.style.getPropertyPriority('scroll-behavior');
  root.style.setProperty('scroll-behavior','auto','important');
  for(const [name,value,importance] of saved){if(value)body.style.setProperty(name,value,importance);else body.style.removeProperty(name);}
  if(rootOverflow)root.style.setProperty('overflow',rootOverflow,rootPriority);else root.style.removeProperty('overflow');
  window.scrollTo(x,y);
  if(behavior)root.style.setProperty('scroll-behavior',behavior,priority);else root.style.removeProperty('scroll-behavior');
 };
}
