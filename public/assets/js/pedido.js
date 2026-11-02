/**
 * Pedido: lleva a Wompi automáticamente tras crear el pedido (con botón visible si no hay JS)
 * y, al volver de Wompi, consulta el estado guardado cada pocos segundos. Nunca confirma desde el cliente.
 */
const form = document.querySelector('form[data-autopay]');
if (form) {
  window.mubfTrack?.('begin_checkout', {});
  setTimeout(() => form.requestSubmit(), 900);
}
const box = document.querySelector('[data-order-status="pendiente"]');
if (box && !document.querySelector('form[action^="https://checkout.wompi.co"]')) {
  let tries = 0;
  const tick = async () => {
    tries += 1;
    try {
      const res = await fetch(location.pathname + location.search.replace(/([?&])(id|env)=[^&]*/g, '$1'), { headers: { Accept: 'text/html' } });
      const html = await res.text();
      if (!html.includes('data-order-status="pendiente"')) { location.reload(); return; }
    } catch { /* reintenta */ }
    if (tries < 20) setTimeout(tick, 4000);
  };
  setTimeout(tick, 4000);
}
