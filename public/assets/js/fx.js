/**
 * Componentes de autor (Fase 1). Todo es mejora progresiva: sin este archivo el contenido se ve completo.
 * - Botones con el texto que rueda letra por letra (solo con cursor fino y movimiento permitido).
 * - Mapa del rostro: líneas y etiquetas que se dibujan al entrar en pantalla.
 * - Lista con foto que sigue el cursor (servicios de Inicio).
 * - Frase que se «entinta» palabra por palabra con el scroll (GSAP ScrollTrigger si está cargado).
 * - Índice de precios con la sección activa resaltada (scroll-spy).
 * - Órbita de fotos del cierre: solo gira mientras está en pantalla.
 * Con prefers-reduced-motion todo queda en su estado final, sin animación.
 */
const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

/* ---------- Botones: el texto rueda ---------- */
function rollButtons() {
  if (reduce || !finePointer) return;
  document.querySelectorAll('.btn, .link-line, .rail-more-cta').forEach((btn) => {
    if (btn.dataset.roll) return;
    const node = [...btn.childNodes].find((n) => n.nodeType === 3 && n.textContent.trim() !== '');
    if (!node) return;
    const text = node.textContent.trim();
    const make = (cls) => {
      const s = document.createElement('span');
      s.className = cls;
      [...text].forEach((ch, i) => {
        const c = document.createElement('span');
        c.className = 'ch';
        c.textContent = ch === ' ' ? ' ' : ch;
        c.style.transitionDelay = `${i * 14}ms`;
        s.append(c);
      });
      return s;
    };
    const wrap = document.createElement('span');
    wrap.className = 'roll';
    wrap.setAttribute('aria-hidden', 'true');
    wrap.append(make('roll-a'), make('roll-b'));
    const sr = document.createElement('span');
    sr.className = 'sr-only';
    sr.textContent = text;
    const lead = /^\s/.test(node.textContent) ? ' ' : '';
    const trail = /\s$/.test(node.textContent) ? ' ' : '';
    node.replaceWith(document.createTextNode(lead), wrap, sr, document.createTextNode(trail));
    btn.dataset.roll = '1';
  });
}

/* ---------- Activar una vez al entrar en pantalla ---------- */
function onceVisible(selector, cls, margin = '0px 0px -20% 0px') {
  const els = document.querySelectorAll(selector);
  if (!els.length) return;
  if (reduce || !('IntersectionObserver' in window)) { els.forEach((el) => el.classList.add(cls)); return; }
  const io = new IntersectionObserver((entries) => {
    entries.forEach((en) => { if (en.isIntersecting) { en.target.classList.add(cls); io.unobserve(en.target); } });
  }, { rootMargin: margin });
  els.forEach((el) => io.observe(el));
}

/* ---------- Lista con foto que sigue el cursor ---------- */
function followList() {
  document.querySelectorAll('[data-follow]').forEach((list) => {
    if (!finePointer) return;
    list.querySelectorAll('[data-follow-row]').forEach((row) => {
      const fig = row.querySelector('[data-follow-ph]');
      if (!fig) return;
      let x = 0; let tx = 0; let y = 0; let ty = 0; let raf = 0;
      const tick = () => {
        x += (tx - x) * 0.16;
        y += (ty - y) * 0.16;
        const w = row.clientWidth || 1;
        const rot = ((x / w) - 0.5) * 14;
        fig.style.transform = `translate(${x}px, ${y}px) translate(-50%, -50%) rotate(${rot.toFixed(2)}deg)`;
        if (Math.abs(tx - x) > 0.3 || Math.abs(ty - y) > 0.3) raf = requestAnimationFrame(tick);
        else raf = 0;
      };
      row.addEventListener('pointerenter', (e) => {
        const r = row.getBoundingClientRect();
        x = tx = e.clientX - r.left;
        y = ty = e.clientY - r.top;
        if (!raf) raf = requestAnimationFrame(tick);
      });
      row.addEventListener('pointermove', (e) => {
        const r = row.getBoundingClientRect();
        tx = e.clientX - r.left;
        ty = e.clientY - r.top;
        if (!raf && !reduce) raf = requestAnimationFrame(tick);
        if (reduce) { x = tx; y = ty; tick(); }
      });
    });
  });
}

/* ---------- Frase entintada ---------- */
function inkText() {
  const els = document.querySelectorAll('[data-ink]');
  if (!els.length) return;
  els.forEach((el) => {
    // Divide los textos en palabras conservando los elementos internos (resaltados, miniaturas)
    const split = (node) => {
      [...node.childNodes].forEach((n) => {
        if (n.nodeType === 3) {
          const frag = document.createDocumentFragment();
          n.textContent.split(/(\s+)/).forEach((part) => {
            if (part === '') return;
            if (/^\s+$/.test(part)) { frag.append(document.createTextNode(part)); return; }
            const w = document.createElement('span');
            w.className = 'ink-w';
            w.textContent = part;
            frag.append(w);
          });
          n.replaceWith(frag);
        } else if (n.nodeType === 1 && !n.matches('.qface')) {
          split(n);
        }
      });
    };
    split(el);
  });
  const { gsap, ScrollTrigger } = window;
  if (reduce || !gsap || !ScrollTrigger) return;
  gsap.registerPlugin(ScrollTrigger);
  els.forEach((el) => {
    el.classList.add('ink-on');
    // Las palabras de acento (terracota) ya aparecen entintadas: son el énfasis de la frase
    gsap.fromTo([...el.querySelectorAll('.ink-w, .qface')].filter((w) => !w.closest('.c-terracota')), { opacity: 0.48 }, {
      opacity: 1, ease: 'none', stagger: 0.05,
      scrollTrigger: { trigger: el, start: 'top 82%', end: 'bottom 52%', scrub: 0.6 },
    });
  });
}

/* ---------- Índice de precios (scroll-spy) ---------- */
function scrollSpy() {
  document.querySelectorAll('[data-spy]').forEach((nav) => {
    const links = [...nav.querySelectorAll('a[href^="#"]')];
    const targets = links.map((a) => document.querySelector(a.getAttribute('href'))).filter(Boolean);
    if (!targets.length || !('IntersectionObserver' in window)) return;
    const set = (id) => links.forEach((a) => {
      if (a.getAttribute('href') === `#${id}`) a.setAttribute('aria-current', 'true');
      else a.removeAttribute('aria-current');
    });
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => { if (en.isIntersecting) set(en.target.id); });
    }, { rootMargin: '-45% 0px -50% 0px' });
    targets.forEach((t) => io.observe(t));
  });
}

/* ---------- Órbita: gira solo en pantalla ---------- */
function orbit() {
  const els = document.querySelectorAll('[data-orbit]');
  if (!els.length || reduce || !('IntersectionObserver' in window)) return;
  const io = new IntersectionObserver((entries) => {
    entries.forEach((en) => en.target.classList.toggle('is-spinning', en.isIntersecting));
  });
  els.forEach((el) => io.observe(el));
  const { gsap, ScrollTrigger } = window;
  if (!gsap || !ScrollTrigger) return;
  gsap.registerPlugin(ScrollTrigger);
  els.forEach((el) => {
    const ring = el.querySelector('.orbit-ring');
    if (!ring) return;
    gsap.fromTo(ring, { scale: 0.72, rotate: -40 }, {
      scale: 1, rotate: 0, ease: 'none',
      scrollTrigger: { trigger: el, start: 'top bottom', end: 'center center', scrub: 0.8 },
    });
  });
}

/* ---------- Pestañas (looks de Novias) ---------- */
function tabs() {
  document.querySelectorAll('[data-tabs]').forEach((box) => {
    const list = box.querySelector('[role="tablist"]');
    const tabsEls = [...box.querySelectorAll('[role="tab"]')];
    const panels = tabsEls.map((t) => document.getElementById(t.getAttribute('aria-controls')));
    if (!list || !tabsEls.length) return;
    list.hidden = false;
    box.classList.add('is-tabs');
    const select = (i, focus) => {
      tabsEls.forEach((t, k) => {
        const on = k === i;
        t.setAttribute('aria-selected', on ? 'true' : 'false');
        t.tabIndex = on ? 0 : -1;
        if (panels[k]) panels[k].hidden = !on;
      });
      if (focus) tabsEls[i].focus();
    };
    tabsEls.forEach((t, i) => {
      t.addEventListener('click', () => select(i, false));
      t.addEventListener('keydown', (e) => {
        const map = { ArrowRight: i + 1, ArrowLeft: i - 1, Home: 0, End: tabsEls.length - 1 };
        if (!(e.key in map)) return;
        e.preventDefault();
        select((map[e.key] + tabsEls.length) % tabsEls.length, true);
      });
    });
    select(Math.max(0, tabsEls.findIndex((t) => t.getAttribute('aria-selected') === 'true')), false);
  });
}

/* ---------- Escenas con scroll (solo escritorio y con GSAP) ---------- */
function scenes() {
  const { gsap, ScrollTrigger } = window;
  if (reduce || !gsap || !ScrollTrigger) return;
  gsap.registerPlugin(ScrollTrigger);
  const mm = gsap.matchMedia();
  mm.add('(min-width: 961px) and (min-height: 700px)', () => {
    // Baraja: las cartas salen apiladas al centro y se abren en abanico
    document.querySelectorAll('[data-deck]').forEach((sec) => {
      const deck = sec.querySelector('.deck');
      const cards = [...sec.querySelectorAll('.deck-card')];
      if (!deck || !cards.length) return;
      const box = deck.getBoundingClientRect();
      const cx = box.left + box.width / 2;
      const tl = gsap.timeline({ scrollTrigger: { trigger: sec.querySelector('.deck-pin'), start: 'top top', end: '+=900', scrub: 0.8, pin: true, anticipatePin: 1 } });
      cards.forEach((c, i) => {
        const r = c.getBoundingClientRect();
        tl.from(c, { x: cx - (r.left + r.width / 2), y: 40, rotation: (i - 1.5) * -3, ease: 'power2.out' }, 0);
      });
    });
    // Collage del Estudio: cada foto flota a su propia velocidad
    document.querySelectorAll('[data-collage] [data-speed]').forEach((el) => {
      gsap.fromTo(el, { yPercent: 0 }, {
        yPercent: -Number(el.dataset.speed), ease: 'none',
        scrollTrigger: { trigger: el.closest('[data-collage]'), start: 'top bottom', end: 'bottom top', scrub: true },
      });
    });
  });
}

function init() {
  rollButtons();
  onceVisible('[data-facemap]', 'is-on', '0px 0px -25% 0px');
  followList();
  scrollSpy();
  orbit();
  inkText();
  tabs();
  scenes();
}

// GSAP llega con defer después de este módulo: se espera al evento load para las piezas con scroll
if (document.readyState === 'complete') init();
else window.addEventListener('load', init, { once: true });
