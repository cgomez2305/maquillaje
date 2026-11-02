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
