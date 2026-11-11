/**
 * Mapa de tarifas de domicilio. Mejora progresiva: sin este archivo queda la lista de tarifas.
 * Leaflet (self-hosted) y el GeoJSON se cargan solo cuando el bloque se acerca a la pantalla.
 * Teselas de OpenStreetMap con un filtro cálido en CSS para que el mapa combine con la paleta.
 * Tocar un municipio en la lista o en el mapa lo resalta en los dos.
 */
const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const fine = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
const TIERS = [[25000, '#F98F7D'], [30000, '#C9654E'], [Infinity, '#A5462F']];
const color = (fee) => TIERS.find(([max]) => fee <= max)[1];
const money = (n) => `${Math.round(n).toLocaleString('es-CO')} COP`;

let leaflet;
function loadLeaflet() {
  if (window.L) return Promise.resolve(window.L);
  leaflet ??= new Promise((resolve, reject) => {
    const css = document.createElement('link');
    css.rel = 'stylesheet';
    css.href = '/assets/vendor/leaflet/leaflet.css';
    document.head.append(css);
    const s = document.createElement('script');
    s.src = '/assets/vendor/leaflet/leaflet.js';
    s.onload = () => resolve(window.L);
    s.onerror = reject;
    document.head.append(s);
  });
  return leaflet;
}

async function build(box) {
  const canvas = box.querySelector('[data-dmap-canvas]');
  const [L, data] = await Promise.all([
    loadLeaflet(),
    fetch('/assets/data/zonas-domicilio.json').then((r) => r.json()),
  ]);
  canvas.replaceChildren();
  box.classList.add('is-map');

  const map = L.map(canvas, {
    zoomControl: false,
    scrollWheelZoom: false,
    dragging: fine,
    tap: false,
    attributionControl: true,
    zoomSnap: 0.25,
  });
  L.control.zoom({ position: 'bottomright', zoomInTitle: 'Acercar', zoomOutTitle: 'Alejar' }).addTo(map);
  map.attributionControl.setPrefix(false);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  }).addTo(map);

  const buttons = new Map([...box.querySelectorAll('[data-zona]')].map((b) => [b.dataset.zona, b]));
  const zones = new Map();
  let active = '';

  const style = (name, on) => {
    const { tarifa } = zones.get(name).props;
    return { color: color(tarifa), weight: on ? 3 : 1.5, opacity: on ? 1 : 0.85, fillColor: color(tarifa), fillOpacity: on ? 0.42 : 0.2 };
  };
  const select = (name, fly) => {
    active = active === name && !fly ? '' : name;
    zones.forEach((z, n) => {
      z.layer.setStyle(style(n, n === active));
      z.label.getElement()?.classList.toggle('is-on', n === active);
      buttons.get(n)?.setAttribute('aria-pressed', n === active ? 'true' : 'false');
    });
    if (active) {
      zones.get(active).layer.bringToFront();
      if (fly) map.flyTo(zones.get(active).props.centro, 13, { animate: !reduce, duration: 0.8 });
    }
  };

  data.features.forEach((f) => {
    const props = f.properties;
    const layer = L.geoJSON(f, { interactive: true }).getLayers()[0];
    const label = L.marker(props.centro, {
      interactive: false,
      keyboard: false,
      icon: L.divIcon({ className: 'dmap-lbl', html: `<div><span>${props.nombre}</span><strong>${money(props.tarifa)}</strong></div>`, iconSize: [0, 0] }),
    });
    zones.set(props.nombre, { layer, label, props });
    layer.setStyle(style(props.nombre, false)).addTo(map);
    label.addTo(map);
    layer.on('click', () => select(props.nombre, false));
    if (fine) {
      layer.on('mouseover', () => { if (!active) layer.setStyle(style(props.nombre, true)); });
      layer.on('mouseout', () => { if (active !== props.nombre) layer.setStyle(style(props.nombre, false)); });
    }
  });

  const s = data.estudio;
  L.marker([s.lat, s.lon], {
    keyboard: false,
    title: 'Estudio Make Up by Fletcher',
    icon: L.divIcon({ className: 'dmap-pin', html: '<i></i><span>Estudio · sin costo</span>', iconSize: [0, 0] }),
    zIndexOffset: 1000,
  }).addTo(map);

  // Encuadre del área urbana (los polígonos municipales incluyen zona rural)
  const urban = L.latLngBounds([...zones.values()].map((z) => z.props.centro)).extend([s.lat, s.lon]);
  const fit = () => map.fitBounds(urban, { padding: [48, 48], animate: false });
  fit();
  new ResizeObserver(() => { map.invalidateSize(); if (!active) fit(); }).observe(canvas);

  buttons.forEach((b, name) => b.addEventListener('click', () => {
    if (active === name) { select('', false); fit(); return; }
    select(name, true);
    // En móvil el mapa queda arriba de la lista: se trae a la vista para ver el cambio
    const r = canvas.getBoundingClientRect();
    if (r.bottom < 80 || r.top > window.innerHeight) canvas.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });
  }));
}

document.querySelectorAll('[data-dmap]').forEach((box) => {
  const go = () => build(box).catch(() => box.classList.add('is-off'));
  if (!('IntersectionObserver' in window)) { go(); return; }
  const io = new IntersectionObserver((entries) => {
    if (entries.some((e) => e.isIntersecting)) { io.disconnect(); go(); }
  }, { rootMargin: '400px 0px' });
  io.observe(box);
});
