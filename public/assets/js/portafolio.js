/**
 * Portafolio: filtro por categoría (C10) y visor ampliado accesible.
 * Sin JS: se ven todas las fotos y cada una abre la imagen grande en su enlace.
 */
const grid = document.querySelector('[data-pf-grid]');
const chipsBox = document.querySelector('[data-pf-chips]');
const status = document.querySelector('[data-pf-status]');
const lb = document.querySelector('[data-pf-lb]');
const media = lb?.querySelector('[data-pf-media]');
const cap = lb?.querySelector('[data-pf-cap]');
const closeBtn = lb?.querySelector('[data-pf-close]');
const items = grid ? [...grid.querySelectorAll('.it')] : [];
const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
let lastTrigger = null;

/* ---------- Filtro ---------- */
if (chipsBox && items.length) {
  chipsBox.hidden = false;
  const chips = [...chipsBox.querySelectorAll('.chip')];
  chipsBox.addEventListener('click', (e) => {
    const chip = e.target.closest('.chip');
    if (!chip) return;
    const cat = chip.dataset.cat;
    chips.forEach((c) => {
      const on = c === chip;
      c.classList.toggle('on', on);
      c.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
    let shown = 0;
    items.forEach((it) => {
      const visible = cat === 'todo' || it.dataset.cat === cat;
      it.hidden = !visible;
      it.classList.remove('pop');
      if (visible) {
        shown += 1;
        if (!reduce) { void it.offsetWidth; it.classList.add('pop'); }
      }
    });
    if (status) status.textContent = `${shown} ${shown === 1 ? 'foto' : 'fotos'} en ${chip.textContent.trim()}`;
  });
}

/* ---------- Visor ---------- */
function focusables() {
  return [...lb.querySelectorAll('button, a[href], [tabindex]:not([tabindex="-1"])')];
}

function openViewer(item) {
  if (!lb || !media) return;
  lastTrigger = item;
  const picture = item.querySelector('picture');
  const clone = picture.cloneNode(true);
  clone.querySelectorAll('source, img').forEach((n) => n.setAttribute('sizes', '90vw'));
  const img = clone.querySelector('img');
  img.loading = 'eager';
  img.removeAttribute('class');
  media.replaceChildren(clone);
  cap.textContent = item.dataset.label || '';
  lb.setAttribute('aria-label', `Imagen ampliada: ${img.alt}`);
  lb.hidden = false;
  document.body.classList.add('lb-open');
  closeBtn.focus();
}

function closeViewer() {
  if (!lb || lb.hidden) return;
  lb.hidden = true;
  media.replaceChildren();
  document.body.classList.remove('lb-open');
  lastTrigger?.focus();
}

grid?.addEventListener('click', (e) => {
  const item = e.target.closest('.it');
  if (!item || e.metaKey || e.ctrlKey || e.shiftKey) return;
  e.preventDefault();
  openViewer(item);
});

closeBtn?.addEventListener('click', closeViewer);
lb?.addEventListener('click', (e) => {
  // Clic fuera de la imagen (fondo o margen de la tarjeta) cierra
  if (!e.target.closest('img') && !e.target.closest('[data-pf-close]')) closeViewer();
});
lb?.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') { e.preventDefault(); closeViewer(); return; }
  if (e.key !== 'Tab') return;
  const f = focusables();
  if (f.length <= 1) { e.preventDefault(); f[0]?.focus(); return; }
  const first = f[0];
  const last = f[f.length - 1];
  if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
  else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
});
