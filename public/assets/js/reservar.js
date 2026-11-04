/**
 * Reservar: mejora progresiva del formulario por pasos.
 * - Actualiza el resumen lateral al instante.
 * - Al elegir un día, consulta /reservar/disponibilidad y desactiva las horas ocupadas.
 * - Eventos de analítica booking_start, booking_step y booking_submit (§13).
 * Sin este archivo, cada paso funciona igual con POST normales.
 */
const form = document.querySelector('[data-reservar]');
const track = (event, params = {}) => (window.mubfTrack ? window.mubfTrack(event, params) : null);

if (form) {
  const step = Number(form.dataset.step || '1');
  const money = (n) => `${Math.round(n).toLocaleString('es-CO')} COP`;
  // El resumen aparece dos veces (lateral y barra fija en móvil): se actualizan todas las copias
  const sum = (key) => {
    const els = [...document.querySelectorAll(`[data-sum="${key}"]`)];
    return els.length ? { set textContent(v) { els.forEach((el) => { el.textContent = v; }); } } : null;
  };
  const priceOf = () => Number(form.querySelector('input[name="service"]:checked')?.dataset.price || 0);
  const peopleOf = () => Number(form.querySelector('input[name="people"]:checked')?.value || 0);

  if (step === 1) track('booking_start');
  track('booking_step', { step });

  document.querySelector('[data-focus-on-load]')?.focus();

  form.addEventListener('change', (e) => {
    const input = e.target;
    if (!(input instanceof HTMLInputElement) || input.type !== 'radio') return;
    const label = input.dataset.label || input.dataset.name;
    if (input.name === 'service' && sum('service')) sum('service').textContent = input.dataset.name;
    if (input.name === 'people' && sum('people')) sum('people').textContent = label;
    if (input.name === 'date' && sum('date')) { sum('date').textContent = label; loadSlots(input.value); }
    if (input.name === 'time' && sum('time')) sum('time').textContent = label;
    if (input.name === 'location' && sum('location')) sum('location').textContent = label;
    if ((input.name === 'service' || input.name === 'people') && priceOf() && peopleOf()) {
      const total = priceOf() * peopleOf();
      sum('total').textContent = money(total);
      sum('deposit').textContent = money(Math.floor(total / 2));
    }
  });

  async function loadSlots(date) {
    const box = form.querySelector('[data-slots]');
    if (!box) return;
    box.setAttribute('aria-busy', 'true');
    try {
      const people = new URL(location.href).searchParams.get('people') || form.dataset.people || '1';
      const res = await fetch(`/reservar/disponibilidad?date=${encodeURIComponent(date)}&people=${encodeURIComponent(people)}`, { headers: { Accept: 'application/json' } });
      if (!res.ok) return;
      const { slots } = await res.json();
      box.querySelectorAll('input[name="time"]').forEach((r) => {
        const free = slots.includes(r.value);
        r.disabled = !free;
        r.closest('.slot')?.classList.toggle('off', !free);
        if (!free && r.checked) {
          r.checked = false;
          if (sum('time')) sum('time').textContent = '—';
        }
      });
    } catch { /* sin red: el servidor valida al enviar */ } finally {
      box.removeAttribute('aria-busy');
    }
  }

  form.addEventListener('submit', (e) => {
    const action = e.submitter?.value;
    if (step === 3 && action === 'next') {
      track('booking_submit', { service: form.dataset.service || '', value: priceOf() * peopleOf() });
    }
    if (e.submitter) {
      e.submitter.setAttribute('aria-disabled', 'true');
    }
  });
}
