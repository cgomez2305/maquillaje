/**
 * Make Up by Fletcher · comportamiento común.
 * - Menú móvil accesible (C03): foco atrapado, Escape, bloqueo de scroll, devolución del foco.
 * - Movimiento (§6.5) con GSAP + ScrollTrigger: entradas .rv, parallax .px, recorte .grow.
 * - Consentimiento de cookies y carga diferida de GTM (§13).
 * Todo el contenido se ve sin JavaScript; con movimiento reducido no se anima nada.
 */
window.__mubf = true;

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const root = document.documentElement;

/* ---------- Menú móvil ---------- */
const sheet = document.getElementById('menu');
const openers = document.querySelectorAll('[data-menu-open]');
let lastFocus = null;

function focusables(el) {
  return [...el.querySelectorAll('a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])')]
    .filter((n) => n.offsetParent !== null);
}

function openMenu(trigger) {
  if (!sheet) return;
  lastFocus = trigger || document.activeElement;
  sheet.classList.add('open');
  document.body.classList.add('menu-open');
  openers.forEach((b) => { b.setAttribute('aria-expanded', 'true'); });
  const f = focusables(sheet);
  (f[1] || f[0])?.focus();
}

function closeMenu() {
  if (!sheet) return;
  sheet.classList.remove('open');
  document.body.classList.remove('menu-open');
  openers.forEach((b) => { b.setAttribute('aria-expanded', 'false'); });
  if (location.hash === '#menu') history.replaceState(null, '', location.pathname + location.search);
  lastFocus?.focus();
}

openers.forEach((btn) => {
  btn.addEventListener('click', (e) => { e.preventDefault(); openMenu(btn); });
  btn.addEventListener('keydown', (e) => { if (e.key === ' ') { e.preventDefault(); openMenu(btn); } });
});
sheet?.querySelectorAll('[data-menu-close]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); closeMenu(); }));
sheet?.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') { e.preventDefault(); closeMenu(); return; }
  if (e.key !== 'Tab') return;
  const f = focusables(sheet);
  if (!f.length) return;
  const first = f[0];
  const last = f[f.length - 1];
  if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
  else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
});
// Si se llegó con #menu (modo sin JS), normalizar
if (location.hash === '#menu') { history.replaceState(null, '', location.pathname + location.search); }
window.matchMedia('(min-width: 1181px)').addEventListener?.('change', (m) => { if (m.matches) closeMenu(); });

/* ---------- Movimiento ---------- */
function initMotion() {
  const { gsap, ScrollTrigger } = window;
  if (!gsap || !ScrollTrigger || reduceMotion) { root.classList.remove('js'); return; }
  gsap.registerPlugin(ScrollTrigger);

  gsap.utils.toArray('.rv').forEach((el) => {
    gsap.fromTo(el, { y: 56, autoAlpha: 0 }, {
      y: 0, autoAlpha: 1, duration: 1.1, ease: 'expo.out',
      delay: parseFloat(el.dataset.delay || '0'),
      scrollTrigger: { trigger: el, start: 'top 85%', once: true },
    });
  });

  gsap.utils.toArray('.px').forEach((wrap) => {
    const img = wrap.querySelector('img');
    if (!img) return;
    gsap.set(img, { scale: 1.14 });
    gsap.fromTo(img, { yPercent: -7 }, {
      yPercent: 7, ease: 'none',
      scrollTrigger: { trigger: wrap, start: 'top bottom', end: 'bottom top', scrub: true },
    });
  });

  gsap.utils.toArray('.grow').forEach((el) => {
    gsap.fromTo(el, { clipPath: 'inset(12% 10% 12% 10% round 28px)' }, {
      clipPath: 'inset(0% 0% 0% 0% round 0px)', ease: 'none',
      scrollTrigger: { trigger: el, start: 'top bottom', end: 'top 40%', scrub: true },
    });
  });

  gsap.utils.toArray('[data-progress]').forEach((bar) => {
    gsap.fromTo(bar, { scaleX: 0 }, { scaleX: 1, ease: 'none', scrollTrigger: { start: 0, end: 'max', scrub: true } });
  });

  root.classList.remove('js');
}

if (document.readyState === 'complete') initMotion();
else window.addEventListener('load', initMotion, { once: true });

/* ---------- Consentimiento y analítica ---------- */
const CONSENT_KEY = 'mubf_consent';
const banner = document.getElementById('consent');

function readConsent() {
  try { return JSON.parse(localStorage.getItem(CONSENT_KEY) || 'null'); } catch { return null; }
}
function saveConsent(analytics) {
  const value = { analytics: !!analytics, at: new Date().toISOString(), v: 1 };
  try { localStorage.setItem(CONSENT_KEY, JSON.stringify(value)); } catch { /* sin almacenamiento */ }
  document.cookie = `mubf_consent=${analytics ? 'a' : 'n'}; Max-Age=${60 * 60 * 24 * 180}; Path=/; SameSite=Lax; Secure`;
  return value;
}
function loadGtm() {
  const id = banner?.dataset.gtm;
  if (!id || window.__gtmLoaded || !/^GTM-[A-Z0-9]+$/.test(id)) return;
  window.__gtmLoaded = true;
  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
  const s = document.createElement('script');
  s.async = true;
  s.src = `https://www.googletagmanager.com/gtm.js?id=${id}`;
  const nonce = document.querySelector('script[nonce]')?.nonce;
  if (nonce) s.nonce = nonce;
  document.head.appendChild(s);
}
function hideBanner() { if (banner) banner.hidden = true; }

const consent = readConsent();
if (consent?.analytics) loadGtm();
if (!consent && banner) banner.hidden = false;

banner?.addEventListener('click', (e) => {
  const btn = e.target.closest('[data-consent]');
  if (!btn) return;
  const action = btn.dataset.consent;
  if (action === 'accept') { saveConsent(true); loadGtm(); hideBanner(); }
  if (action === 'reject') { saveConsent(false); hideBanner(); }
  if (action === 'config') {
    banner.querySelector('[data-consent-form]').hidden = false;
    banner.querySelector('[data-consent-actions]').hidden = true;
    banner.querySelector('input[name="analytics"]')?.focus();
  }
});
banner?.querySelector('[data-consent-form]')?.addEventListener('submit', (e) => {
  e.preventDefault();
  const yes = e.target.querySelector('input[name="analytics"]').checked;
  saveConsent(yes);
  if (yes) loadGtm();
  hideBanner();
});
document.querySelectorAll('[data-consent-open]').forEach((a) => a.addEventListener('click', (e) => {
  e.preventDefault();
  if (!banner) return;
  banner.hidden = false;
  banner.querySelector('[data-consent-form]').hidden = false;
  banner.querySelector('[data-consent-actions]').hidden = true;
  const c = readConsent();
  const box = banner.querySelector('input[name="analytics"]');
  if (box) box.checked = !!c?.analytics;
  box?.focus();
}));

/* Eventos de analítica (§13): solo se envían si GTM está cargado */
export function track(event, params = {}) {
  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push({ event, ...params });
}
window.mubfTrack = track;

document.addEventListener('click', (e) => {
  const a = e.target.closest('a[href^="/wa"]');
  if (a) {
    const origin = new URL(a.href, location.href).searchParams.get('o') || 'general';
    track('click_whatsapp', { origin });
  }
});
