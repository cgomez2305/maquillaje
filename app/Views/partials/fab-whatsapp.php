<?php /** C14 · botón flotante de WhatsApp. @var string $origin */ ?>
<a class="fab" href="<?= e(wa_href('fab-' . ($origin !== '' ? $origin : 'inicio'))) ?>" rel="nofollow" aria-label="Escribir por WhatsApp para reservar" data-wa>
  <?= icon('whatsapp') ?>
  <span>Reservar</span>
</a>
