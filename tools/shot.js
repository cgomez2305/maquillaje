// Capturas de verificación: node tools/shot.js <ruta> <salida.png> [ancho] [alto|full]
const { chromium } = require('playwright');
(async () => {
  const [path = '/', out = 'shot.png', w = '1440', h = 'full'] = process.argv.slice(2);
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: +w, height: h === 'full' ? 900 : +h }, reducedMotion: 'reduce' });
  const errors = [];
  page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });
  page.on('pageerror', (e) => errors.push(e.message));
  await page.goto('http://127.0.0.1:8000' + path, { waitUntil: 'networkidle' });
  await page.evaluate(() => { const c = document.getElementById('consent'); if (c) c.hidden = true; });
  await page.evaluate(async () => {
    document.querySelectorAll('img[loading="lazy"]').forEach((i) => { i.loading = 'eager'; });
    for (let y = 0; y < document.body.scrollHeight; y += 600) { window.scrollTo(0, y); await new Promise((r) => setTimeout(r, 60)); }
    window.scrollTo(0, 0);
  });
  await page.waitForLoadState('networkidle');
  const sw = await page.evaluate(() => document.documentElement.scrollWidth);
  await page.screenshot({ path: out, fullPage: h === 'full' });
  console.log(JSON.stringify({ scrollWidth: sw, viewport: +w, errors }));
  await browser.close();
})();
