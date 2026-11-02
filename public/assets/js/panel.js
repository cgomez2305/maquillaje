/**
 * Panel: confirmaciones de acciones sensibles, envío automático de filtros y
 * copia de enlaces. Todo funciona sin JS; esto solo agrega comodidad.
 */
document.addEventListener('submit', (e) => {
  const form = e.target;
  const msg = form.getAttribute('data-confirm');
  if (msg && !window.confirm(msg)) { e.preventDefault(); return; }
  const btn = e.submitter;
  if (btn) { setTimeout(() => btn.setAttribute('disabled', ''), 0); }
});

document.querySelectorAll('[data-autosubmit]').forEach((el) => {
  el.addEventListener('change', () => el.form?.requestSubmit());
});

let t;
document.querySelectorAll('[data-search]').forEach((input) => {
  input.addEventListener('input', () => {
    clearTimeout(t);
    t = setTimeout(() => input.form?.requestSubmit(), 450);
  });
});

document.querySelectorAll('[data-copy]').forEach((b) => b.addEventListener('click', async () => {
  try { await navigator.clipboard.writeText(b.dataset.copy); b.textContent = 'Copiado'; } catch { /* sin permiso */ }
}));

// Tablas desplazables: enfocables con teclado solo cuando de verdad desbordan (WCAG 2.1.1)
function syncScrollRegions() {
  document.querySelectorAll('.tbl-wrap').forEach((w) => {
    if (w.scrollWidth > w.clientWidth + 1) {
      const head = w.closest('section, .card')?.querySelector('h2, h3, caption')?.textContent.trim();
      w.setAttribute('tabindex', '0');
      w.setAttribute('role', 'region');
      w.setAttribute('aria-label', 'Tabla desplazable' + (head ? ': ' + head : ''));
    } else {
      ['tabindex', 'role', 'aria-label'].forEach((a) => w.removeAttribute(a));
    }
  });
}
syncScrollRegions();
window.addEventListener('resize', () => requestAnimationFrame(syncScrollRegions));
