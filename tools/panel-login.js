// Inicia sesión en el panel (con 2FA) desde Playwright, para pruebas locales.
//   const { login } = require('/home/user/maquillaje/tools/panel-login.js'); await login(page, 'socio-a@example.com', 'clave-socio-segura-1');
// Si la cuenta no tiene 2FA, lo configura leyendo la clave de la pantalla. Si ya lo tiene, calcula el código
// descifrando el secreto con APP_KEY (solo en local).
const { execSync } = require('child_process');
const php = (code) => execSync(`php -r '${code.replace(/'/g, "'\\''")}'`, { cwd: '/home/user/maquillaje' }).toString();
const codeFor = (secret) => php(`require "app/bootstrap.php"; echo App\\Support\\Totp::code("${secret}", intdiv(time(),30));`);
const secretOf = (email) => php(`require "app/bootstrap.php"; $c = App\\Support\\Db::value("SELECT totp_secret FROM users WHERE email = ?", ["${email}"]); echo $c ? App\\Support\\Crypto::decrypt($c) : "";`);
async function login(page, email, password, base = 'http://127.0.0.1:8000') {
  await page.goto(base + '/panel/login');
  await page.fill('#l-email', email); await page.fill('#l-pass', password);
  await page.click('button[type=submit]');
  await page.waitForLoadState('load');
  if (page.url().includes('/panel/seguridad') && await page.$('.mono')) {
    const secret = (await page.textContent('.mono')).replace(/\s/g, '');
    await page.fill('#s-code', codeFor(secret)); await page.click('form [type=submit]');
    await page.waitForLoadState('load');
  } else if (page.url().includes('/panel/login/2fa')) {
    // espera al siguiente paso de 30 s si el actual ya se usó (protección anti-reutilización)
    let s = secretOf(email);
    await page.fill('#l-code', codeFor(s)); await page.click('button[type=submit]');
    await page.waitForLoadState('load');
    if (page.url().includes('/2fa')) {
      await page.waitForTimeout((30 - (Math.floor(Date.now() / 1000) % 30) + 1) * 1000);
      await page.fill('#l-code', codeFor(s)); await page.click('button[type=submit]');
      await page.waitForLoadState('load');
    }
  }
  return page.url();
}
module.exports = { login };
