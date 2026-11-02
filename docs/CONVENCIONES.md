# Convenciones de código

- **Sin atributos `style=""`** en las vistas: la CSP (`style-src 'self' 'nonce-…'`) los bloquea. Todo estilo va en
  `public/assets/css/{base,components}.css` o en `public/assets/css/pages/<pagina>.css`.
- Scripts en línea solo con `nonce="<?= e(csp_nonce()) ?>"`; preferir módulos en `public/assets/js/`.
- Toda salida con `e()`. Fotos con `img('fotos/<nombre>', 'alt', ['sizes' => …])` (genera `<picture>` AVIF/WebP/JPG
  desde `public/assets/img/manifest.json`). Logos con `img_src('marca/<nombre>', ancho)`.
- Precios siempre desde `App\Domain\Catalog` (BD con respaldo en `config/catalog.php`), nunca escritos en la vista.
- Enlaces de WhatsApp con `wa_href('origen', 'tema')` + `rel="nofollow"` (genera lead WEB-… y mensaje con el código).
- Movimiento: `.in` + `.d1…d5` (entrada de cabecera, CSS), `.rv` (entrada al hacer scroll, GSAP), `.px` (parallax),
  `.grow` (recorte). Todo se ve sin JS y con `prefers-reduced-motion`.
- Datos pendientes del negocio entre corchetes dentro de `<span class="pending">[…]</span>`.
- Controladores públicos extienden `App\Controllers\Controller` y usan `$this->page('<clave seo>', 'site/<vista>', [...])`.
- Fechas en UTC en la BD; `local_time()` para mostrar en America/Bogota. Dinero en enteros (`money()`).
