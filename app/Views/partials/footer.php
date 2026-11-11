<?php
/** C18 · pie con enlazado interno por servicio y por zona. */
$b = config('app.business');
?>
<footer class="foot">
  <div class="wrap foot-in">
    <div class="foot-cols">
      <div class="foot-col foot-brand">
        <img src="<?= e(img_src('marca/logo-nude', 336)) ?>" alt="Fletcher, make up artist" width="168" height="130" loading="lazy">
        <p>Maquillaje profesional con acabado natural y duradero en Bucaramanga y Floridablanca.</p>
      </div>
      <nav class="foot-col" aria-label="Servicios">
        <p class="lbl">Servicios</p>
        <a href="/maquillaje-de-novia-bucaramanga">Maquillaje de novia</a>
        <a href="/maquillaje-de-novia-bucaramanga#quinceaneras">Maquillaje para quinceañeras</a>
        <a href="/precios-maquillaje-bucaramanga#grados">Maquillaje para grados</a>
        <a href="/precios-maquillaje-bucaramanga#packs">Maquillaje social</a>
        <a href="/precios-maquillaje-bucaramanga#domicilio">Maquillaje a domicilio</a>
        <a href="/cursos-de-maquillaje-bucaramanga">Cursos de maquillaje</a>
        <a href="/tienda">Tienda</a>
        <a href="/diario">Diario de belleza</a>
      </nav>
      <div class="foot-col">
        <p class="lbl">Estudio</p>
        <p><?= e($b['address']) ?><br><?= e($b['city']) ?>, <?= e($b['region']) ?></p>
        <a class="foot-ic" href="<?= e(wa_href('footer')) ?>" rel="nofollow"><?= icon('whatsapp') ?><?= e($b['phone_display']) ?></a>
        <a class="foot-ic" href="mailto:<?= e($b['email']) ?>"><?= icon('mail') ?><?= e($b['email']) ?></a>
        <a class="foot-ic" href="<?= e($b['instagram']) ?>" rel="noopener" target="_blank"><?= icon('instagram') ?>Instagram · @makeupbyfletcher</a>
        <a class="foot-ic" href="<?= e($b['tiktok']) ?>" rel="noopener" target="_blank"><?= icon('tiktok') ?>TikTok · @makeupbyfletcher</a>
      </div>
      <nav class="foot-col" aria-label="Zonas">
        <p class="lbl">Zonas</p>
        <a href="/">Maquillaje en Bucaramanga</a>
        <a href="/estudio-maquillaje-floridablanca">Maquillaje en Floridablanca</a>
        <a href="/precios-maquillaje-bucaramanga#domicilio">Piedecuesta y Girón a domicilio</a>
      </nav>
    </div>
    <div class="d foot-word" aria-hidden="true" data-word="Fletcher"></div>
    <div class="foot-legal">
      <p>© <?= e(gmdate('Y')) ?> Make Up by Fletcher · maquillajebucaramanga.com</p>
      <nav aria-label="Legal">
        <a href="/politica-de-privacidad">Política de datos</a>
        <a href="/terminos">Términos y reservas</a>
        <a href="#" data-consent-open>Cookies</a>
        <a class="foot-credit" href="https://clikrank.com" target="_blank" rel="nofollow noopener">Desarrollado con <?= icon('heart', 'ic foot-heart') ?><span class="sr-only">amor</span> por ClikRank</a>
      </nav>
    </div>
  </div>
</footer>
