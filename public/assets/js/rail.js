/**
 * Riel del portafolio en Inicio.
 *
 * - Escritorio (≥ 961 px, alto ≥ 640 px y sin movimiento reducido): la sección queda fija y el riel
 *   avanza en horizontal sincronizado con el scroll vertical (GSAP ScrollTrigger, scrub).
 * - Móvil, tabletas y movimiento reducido: riel nativo con imán por tarjeta.
 * - En ambos modos: contador, barra de progreso, botones anterior/siguiente (44 px+), flechas del teclado,
 *   Inicio/Fin, y aviso por lector de pantalla al navegar con botones o teclado.
 * Sin JavaScript el riel se desplaza de forma nativa y los controles quedan ocultos.
 */
const section = document.querySelector('[data-hrail]');

if (section) {
  const track = section.querySelector('[data-rail-track]');
  const items = [...track.querySelectorAll('.rail-item')];
  const ui = section.querySelector('[data-rail-ui]');
  const prev = section.querySelector('[data-rail-prev]');
  const next = section.querySelector('[data-rail-next]');
  const bar = section.querySelector('[data-rail-bar]');
  const current = section.querySelector('[data-rail-current]');
  const live = section.querySelector('[data-rail-live]');
  const total = items.length;
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
  const desktop = window.matchMedia('(min-width: 961px) and (min-height: 640px)');
  const pad = (n) => String(n).padStart(2, '0');

  let index = 0;
  let pinned = null; // ScrollTrigger del modo fijado
  let announceAt = null; // índice destino que se anuncia al llegar

  ui.hidden = false;
  section.dataset.enhanced = '';

  function render(progress, i) {
    index = Math.max(0, Math.min(total - 1, i));
    bar.style.transform = `scaleX(${Math.max(0, Math.min(1, progress))})`;
    current.textContent = pad(index + 1);
    prev.disabled = progress <= 0.001;
    next.disabled = progress >= 0.999;
    items.forEach((it, k) => it.toggleAttribute('data-active', k === index));
    if (announceAt !== null && index === announceAt) {
      const cap = items[index].querySelector('figcaption')?.textContent.replace(/^\d+/, '').trim() || 'Ver todo el portafolio';
      live.textContent = `${index + 1} de ${total}: ${cap}`;
      announceAt = null;
    }
  }

  /* ---------- Modo nativo (móvil / movimiento reducido) ---------- */
  function nativeState() {
    const max = track.scrollWidth - track.clientWidth;
    const progress = max > 0 ? track.scrollLeft / max : 0;
    const start = track.scrollLeft + parseFloat(getComputedStyle(track).scrollPaddingInlineStart || '0');
    let nearest = 0;
    let best = Infinity;
    items.forEach((it, k) => {
      const d = Math.abs(it.offsetLeft - start);
      if (d < best) { best = d; nearest = k; }
    });
    if (progress >= 0.999) nearest = total - 1;
    render(progress, nearest);
  }

  let ticking = false;
  track.addEventListener('scroll', () => {
    if (pinned || ticking) return;
    ticking = true;
    requestAnimationFrame(() => { nativeState(); ticking = false; });
  }, { passive: true });

  function goNative(i) {
    const target = items[Math.max(0, Math.min(total - 1, i))];
    const padStart = parseFloat(getComputedStyle(track).scrollPaddingInlineStart || '0');
    track.scrollTo({ left: target.offsetLeft - padStart, behavior: reduce.matches ? 'auto' : 'smooth' });
  }

  /* ---------- Modo fijado (escritorio) ---------- */
  function distance() {
    const last = items[items.length - 1];
    const gutter = parseFloat(getComputedStyle(track).paddingRight || '40');
    return Math.max(0, last.offsetLeft + last.offsetWidth + gutter - track.clientWidth);
  }

  function enablePin() {
    const { gsap, ScrollTrigger } = window;
    if (!gsap || !ScrollTrigger || pinned) return;
    gsap.registerPlugin(ScrollTrigger);
    section.classList.add('is-pinned');
    track.scrollLeft = 0;
    const navH = document.querySelector('.nav')?.offsetHeight || 78;
    const tween = gsap.to(track, {
      x: () => -distance(),
      ease: 'none',
      scrollTrigger: {
        trigger: section.querySelector('.pf-pin'),
        start: () => `top ${navH + 24}px`,
        end: () => `+=${distance()}`,
        pin: section.querySelector('.pf-pin'),
        pinSpacing: true,
        scrub: 0.6,
        anticipatePin: 1,
        invalidateOnRefresh: true,
        refreshPriority: 1,
        onUpdate: (self) => render(self.progress, Math.round(self.progress * (total - 1))),
        // El botón flotante tapa los controles mientras el riel está fijado: se oculta y vuelve al salir
        onToggle: (self) => document.body.classList.toggle('rail-active', self.isActive),
      },
    });
    pinned = tween.scrollTrigger;
    ScrollTrigger.sort();
    ScrollTrigger.refresh();
    render(pinned.progress, Math.round(pinned.progress * (total - 1)));
  }

  function disablePin() {
    if (!pinned) return;
    const anim = pinned.animation;
    pinned.kill(true);
    anim?.kill();
    window.gsap?.set(track, { clearProps: 'transform' });
    pinned = null;
    document.body.classList.remove('rail-active');
    section.classList.remove('is-pinned');
    window.ScrollTrigger?.refresh();
    nativeState();
  }

  function goPinned(i) {
    const p = Math.max(0, Math.min(total - 1, i)) / (total - 1);
    const y = pinned.start + p * (pinned.end - pinned.start);
    window.scrollTo({ top: Math.ceil(y), behavior: reduce.matches ? 'auto' : 'smooth' });
  }

  const go = (i) => {
    announceAt = Math.max(0, Math.min(total - 1, i));
    return pinned ? goPinned(i) : goNative(i);
  };

  prev.addEventListener('click', () => go(index - 1));
  next.addEventListener('click', () => go(index + 1));
  track.addEventListener('keydown', (e) => {
    const keys = { ArrowRight: index + 1, ArrowLeft: index - 1, Home: 0, End: total - 1 };
    if (!(e.key in keys)) return;
    e.preventDefault();
    go(keys[e.key]);
  });
  // Si el foco entra a un enlace de la tarjeta final en modo fijado, lleva el scroll hasta ella
  track.addEventListener('focusin', (e) => {
    if (!pinned) return;
    const k = items.findIndex((it) => it.contains(e.target));
    if (k > 0) goPinned(k);
  });

  function decide() {
    if (desktop.matches && !reduce.matches && window.gsap && window.ScrollTrigger) enablePin();
    else disablePin();
    if (!pinned) nativeState();
  }

  const start = () => {
    decide();
    desktop.addEventListener?.('change', decide);
    reduce.addEventListener?.('change', decide);
  };
  if (document.readyState === 'complete') start();
  else window.addEventListener('load', start, { once: true });
}
