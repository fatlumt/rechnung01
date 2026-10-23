(() => {
  'use strict';
  const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (motion.matches || !('IntersectionObserver' in window) || !Element.prototype.animate) return;
  const selector = '.hero-copy, .product-scene, .section-heading, .feature-card, .phone-scene, .mobile-copy, .steps article, .faq-grid > div, .cta';
  const running = new Set();
  const shown = new WeakSet();
  const observer = new IntersectionObserver(entries => {
    for (const entry of entries) {
      if (!entry.isIntersecting) { shown.delete(entry.target); continue; }
      if (entry.intersectionRatio < .12 || shown.has(entry.target)) continue;
      shown.add(entry.target);
      if (motion.matches || entry.target.contains(document.activeElement)) continue;
      const card = entry.target.matches('.feature-card, .steps article');
      const columns = window.innerWidth > 760 ? 3 : window.innerWidth > 420 ? 2 : 1;
      const index = card ? Array.from(entry.target.parentElement.children).indexOf(entry.target) : 0;
      const delay = card ? (index % columns) * 100 : entry.target.matches('.product-scene') ? 120 : 0;
      try {
        const animation = entry.target.animate([
          { opacity: 0, transform: 'translateY(32px)' },
          { opacity: 1, transform: 'translateY(0)' }
        ], { duration: 760, delay, easing: 'cubic-bezier(.16,1,.3,1)', fill: 'backwards' });
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
