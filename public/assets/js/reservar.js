/**
 * Reservar (solicitud por WhatsApp): total estimado y vista previa del mensaje al instante.
 * Sin este archivo el formulario funciona igual: el servidor arma el mensaje y abre WhatsApp.
 */
const form = document.querySelector('[data-reservar]');
const track = (event, params = {}) => (window.mubfTrack ? window.mubfTrack(event, params) : null);

if (form) {
  const money = (n) => `${Math.round(n).toLocaleString('es-CO')} COP`;
  const set = (key, text) => document.querySelectorAll(`[data-sum="${key}"]`).forEach((el) => { el.textContent = text; });
  const val = (name) => form.querySelector(`[name="${name}"]:checked`) || form.querySelector(`[name="${name}"]`);
  const deposit = 0.5;
  const maxPeople = form.querySelectorAll('input[name="people"]').length;

  track('booking_start');
  document.querySelector('[data-focus-on-load]')?.focus();

  function longDate(ymd) {
    if (!ymd) return '';
    const [y, m, d] = ymd.split('-').map(Number);
    const s = new Date(y, m - 1, d).toLocaleDateString('es-CO', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    return s.charAt(0).toUpperCase() + s.slice(1);
  }

  function update() {
    const service = form.querySelector('input[name="service"]:checked');
    const peopleEl = form.querySelector('input[name="people"]:checked');
    const people = Number(peopleEl?.value || 1);
    const time = form.querySelector('input[name="time"]:checked');
    const loc = form.querySelector('input[name="location"]:checked');
    const zone = val('zone')?.value.trim();
    const eventSel = form.querySelector('[name="event"]');
    const name = val('name')?.value.trim();
    const notes = val('notes')?.value.trim();
    const date = val('date')?.value;

    const lines = [];
    if (service) lines.push(`• Servicio: ${service.dataset.name} (${money(Number(service.dataset.price))} por persona)`);
    lines.push(`• Personas: ${peopleEl?.dataset.label || 1}`);
    if (eventSel?.value) lines.push(`• Evento: ${eventSel.options[eventSel.selectedIndex].text}`);
    if (date) lines.push(`• Fecha: ${longDate(date)}`);
    if (time?.value) lines.push(`• Hora de inicio: ${time.dataset.label}`);
    lines.push(`• Lugar: ${loc?.value === 'home' ? `A domicilio${zone ? ` · ${zone}` : ''}` : 'En el estudio de Floridablanca'}`);
    if (service) {
      const total = Number(service.dataset.price) * Math.min(people, maxPeople);
      lines.push(`• Total estimado: ${money(total)}${people >= maxPeople ? ' o más' : ''}`);
      set('total', money(total));
      set('deposit', money(Math.floor(total * deposit)));
    }
    if (name) lines.push(`• Mi nombre: ${name}`);
    if (notes) lines.push(`• Nota: ${notes}`);
    const box = document.querySelector('[data-sum="lines"]');
    if (box) box.replaceChildren(...lines.flatMap((l, i) => (i ? [document.createElement('br'), l] : [l])));
  }

  form.addEventListener('change', update);
  form.addEventListener('input', update);
  form.addEventListener('submit', () => track('booking_submit', { service: form.querySelector('input[name="service"]:checked')?.value || '' }));
  update();
}
