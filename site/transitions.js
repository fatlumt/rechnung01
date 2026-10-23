(() => {
  'use strict';
  const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (motion.matches || !('IntersectionObserver' in window) || !Element.prototype.animate) return;

  // Content stays visible without JavaScript, or if an animation cannot run.
  const selector = '.section-heading, .feature-card, .phone-scene, .mobile-copy, .steps article, .faq-grid > div, .cta';
  const running = new Set();
  const observer = new IntersectionObserver(entries => {
    for (const entry of entries) {
      if (!entry.isIntersecting) continue;
      observer.unobserve(entry.target);
      if (motion.matches || entry.boundingClientRect.top < 80 || entry.target.contains(document.activeElement)) continue;
      const card = entry.target.matches('.feature-card, .steps article');
      const index = card ? Array.from(entry.target.parentElement.children).indexOf(entry.target) : 0;
      const delay = window.innerWidth > 760 ? (index % 3) * 65 : 0;
      try {
        const animation = entry.target.animate([
          { opacity: 0, transform: 'translateY(18px)' },
          { opacity: 1, transform: 'translateY(0)' }
        ], { duration: 480, delay, easing: 'cubic-bezier(.2,.7,.2,1)', fill: 'backwards' });
        running.add(animation);
        animation.finished.then(() => running.delete(animation), () => running.delete(animation));
      } catch { /* The static page is the fallback. */ }
    }
  }, { threshold: 0, rootMargin: '0px 0px -32px 0px' });

  document.querySelectorAll(selector).forEach(element => {
    if (element.getBoundingClientRect().top >= window.innerHeight - 32) observer.observe(element);
  });
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
