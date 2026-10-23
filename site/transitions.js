(() => {
  'use strict';
  const progress = document.querySelector('.reading-progress');
  const navigation = Array.from(document.querySelectorAll('.desktop-nav a[href^="#"]'));
  let scheduled = false;
  const updatePosition = () => {
    scheduled = false;
    if (progress) {
      const distance = document.documentElement.scrollHeight - window.innerHeight;
      progress.value = distance > 0 ? Math.min(100, Math.max(0, window.scrollY / distance * 100)) : 0;
    }
    let current = null;
    for (const link of navigation) {
      const section = document.querySelector(link.getAttribute('href'));
      if (section && section.getBoundingClientRect().top < window.innerHeight * .45) current = link;
    }
    navigation.forEach(link => current === link ? link.setAttribute('aria-current', 'location') : link.removeAttribute('aria-current'));
  };
  const schedulePosition = () => { if (!scheduled) { scheduled = true; requestAnimationFrame(updatePosition); } };
  if (progress) progress.hidden = false;
  window.addEventListener('scroll', schedulePosition, { passive: true });
  window.addEventListener('resize', schedulePosition);
  window.addEventListener('load', schedulePosition);
  updatePosition();
  const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (motion.matches || !('IntersectionObserver' in window) || !Element.prototype.animate) return;
  const selector = '.hero-copy > *, .product-scene, .section-heading, .feature-card, .phone-scene, .mobile-copy, .steps article, .faq-grid > div, .cta';
  const running = new Set();
  const shown = new WeakSet();
  const observer = new IntersectionObserver(entries => {
    for (const entry of entries) {
      if (!entry.isIntersecting) { shown.delete(entry.target); continue; }
      if (entry.intersectionRatio < .12 || shown.has(entry.target)) continue;
      shown.add(entry.target);
      if (motion.matches || entry.target.contains(document.activeElement)) continue;
      const card = entry.target.matches('.feature-card, .steps article');
      const hero = entry.target.parentElement?.matches('.hero-copy');
      const columns = window.innerWidth > 760 ? 3 : window.innerWidth > 420 ? 2 : 1;
      const index = card || hero ? Array.from(entry.target.parentElement.children).indexOf(entry.target) : 0;
      const delay = hero ? Math.min(index * 80, 320) : card ? (index % columns) * 100 : entry.target.matches('.product-scene') ? 180 : 0;
      const side = window.innerWidth > 760 ? (entry.target.matches('.phone-scene') ? -28 : entry.target.matches('.product-scene, .mobile-copy') ? 28 : 0) : 0;
      const start = side ? `translate(${side}px, 16px) scale(.985)` : 'translateY(28px)';
      try {
        const animation = entry.target.animate([
          { opacity: 0, transform: start },
          { opacity: 1, transform: 'translateY(0)' }
        ], { duration: 800, delay, easing: 'cubic-bezier(.16,1,.3,1)', fill: 'backwards' });
        running.add(animation);
        animation.finished.then(() => running.delete(animation), () => running.delete(animation));
      } catch { /* All content remains visible if animations are unavailable. */ }
    }
  }, { threshold: [0, .12] });
  document.querySelectorAll(selector).forEach(element => observer.observe(element));
  const stop = () => {
    if (!motion.matches) return;
    observer.disconnect();
    running.forEach(animation => animation.cancel());
    running.clear();
  };
  motion.addEventListener?.('change', stop);
  document.addEventListener('focusin', event => {
    running.forEach(animation => {
      if (animation.effect?.target?.contains(event.target)) animation.cancel();
    });
  });
})();
