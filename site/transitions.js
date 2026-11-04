(() => {
 const reduced=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
 if(reduced||!('IntersectionObserver' in window))return;
 const observer=new IntersectionObserver(entries=>{for(const entry of entries){if(entry.isIntersecting){entry.target.classList.remove('pending');observer.unobserve(entry.target);}}},{threshold:.08});
 document.querySelectorAll('.feature-card,.mobile-banner,.benefits article,.steps article,.cta').forEach(el=>{el.classList.add('reveal','pending');observer.observe(el);});
})();
