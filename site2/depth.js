(() => {
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  const scene = document.querySelector('.scene');
  const stage = document.querySelector('.scene-stage');
  if (!scene || !stage) return;
  let frame = 0;
  let pointerX = 0;
  let pointerY = 0;
  const render = () => {
    frame = 0;
    if (reduced.matches) return;
    const rect = scene.getBoundingClientRect();
    stage.style.setProperty('--scroll-depth', `${Math.max(-18, Math.min(18, (window.innerHeight * .45 - rect.top) * .045))}px`);
    stage.style.setProperty('--tilt-x', `${pointerX}deg`);
    stage.style.setProperty('--tilt-y', `${pointerY}deg`);
  };
  const queue = () => { if (!frame && !reduced.matches) frame = requestAnimationFrame(render); };
  scene.addEventListener('pointermove', event => {
    if (event.pointerType !== 'mouse' || reduced.matches) return;
    const rect = scene.getBoundingClientRect();
    pointerX = ((event.clientX - rect.left) / rect.width - .5) * 10;
    pointerY = -((event.clientY - rect.top) / rect.height - .5) * 8;
    queue();
  });
  scene.addEventListener('pointerleave', () => { pointerX = pointerY = 0; queue(); });
  window.addEventListener('scroll', queue, {passive:true});
  window.addEventListener('resize', queue, {passive:true});
  reduced.addEventListener('change', () => {
    if (reduced.matches) {
      if (frame) cancelAnimationFrame(frame);
      frame = 0;
      ['--scroll-depth','--tilt-x','--tilt-y'].forEach(key => stage.style.removeProperty(key));
      document.querySelectorAll('.depth-enter').forEach(el => el.classList.remove('depth-enter'));
    } else queue();
  });
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          if (!reduced.matches) entry.target.classList.add('depth-enter');
          observer.unobserve(entry.target);
        }
      });
    }, {threshold:.15});
    document.querySelectorAll('.feature, .steps article, .final').forEach(el => observer.observe(el));
  }
  queue();
})();
