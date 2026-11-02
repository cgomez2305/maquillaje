/**
 * Diario: filtro por tema (C10) y compartir/copiar enlace del artículo.
 * Sin JavaScript todos los artículos están visibles y los enlaces de compartir funcionan.
 */
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ---------- Filtro por tema ---------- */
const chips = document.querySelector('[data-filter-chips]');
const list = document.querySelector('[data-filter-list]');
if (chips && list) {
  chips.hidden = false;
  const items = [...list.querySelectorAll('[data-cat]')];
  chips.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-filter]');
    if (!btn) return;
    const cat = btn.dataset.filter;
    chips.querySelectorAll('[data-filter]').forEach((b) => {
      const on = b === btn;
      b.classList.toggle('on', on);
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
    items.forEach((it) => {
      const show = cat === 'Todo' || it.dataset.cat === cat;
      it.hidden = !show;
      it.classList.remove('pop');
      if (show && !reduceMotion) { void it.offsetWidth; it.classList.add('pop'); }
    });
    window.ScrollTrigger?.refresh();
    window.mubfTrack?.('blog_filter', { topic: cat });
  });
}

/* ---------- Copiar enlace ---------- */
document.querySelectorAll('[data-copy]').forEach((a) => {
  a.addEventListener('click', async (e) => {
    e.preventDefault();
    const status = a.parentElement.querySelector('[data-copied]');
    const url = a.dataset.copy;
    try {
      if (navigator.share && window.matchMedia('(pointer: coarse)').matches) {
        await navigator.share({ title: document.title, url });
        return;
      }
      await navigator.clipboard.writeText(url);
      if (status) status.textContent = 'Enlace copiado';
    } catch {
      if (status) status.textContent = 'Copia el enlace de la barra de direcciones';
    }
    if (status) setTimeout(() => { status.textContent = ''; }, 2600);
  });
});

/* ---------- Tabla de contenido: sección activa ---------- */
const toc = document.querySelector('.toc');
if (toc && 'IntersectionObserver' in window) {
  const links = new Map([...toc.querySelectorAll('a[href^="#"]')].map((a) => [decodeURIComponent(a.hash.slice(1)), a]));
  const io = new IntersectionObserver((entries) => {
    entries.forEach((en) => {
      if (!en.isIntersecting) return;
      links.forEach((a) => a.removeAttribute('aria-current'));
      links.get(en.target.id)?.setAttribute('aria-current', 'true');
    });
  }, { rootMargin: '-110px 0px -70% 0px' });
  links.forEach((_, id) => { const el = document.getElementById(id); if (el) io.observe(el); });
}
