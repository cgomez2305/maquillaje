<?php
/**
 * Encuesta post-servicio (/encuesta/{token}).
 * @var array $s @var array $errors @var array $old @var bool $answered @var bool $justAnswered
 * @var string $fecha @var ?string $reviewUrl @var string $firstName
 */
$err = static fn(string $f): string => isset($errors[$f]) ? '<p class="err" id="e-' . e($f) . '">' . e($errors[$f]) . '</p>' : '';
$aria = static fn(string $f): string => isset($errors[$f]) ? ' aria-describedby="e-' . e($f) . '"' : '';
$labels = [1 => 'Mal', 2 => 'Regular', 3 => 'Bien', 4 => 'Muy bien', 5 => 'Excelente'];
?>
<section class="wrap rsv enc">
  <h1 class="rsv-head">
    <span class="lbl c-terracota in">Encuesta · <?= e($s['code']) ?></span>
    <span class="d rsv-title in d1"><?= $answered ? 'Gracias, ' . e($firstName) . '.' : '¿Cómo te fue?' ?></span>
  </h1>
  <p class="rsv-intro in d2"><?= $answered
      ? 'Tu respuesta nos ayuda a cuidar cada detalle del servicio.'
      : 'Hola, ' . e($firstName) . '. Cuéntanos cómo te fue con tu ' . e(mb_strtolower((string) $s['service_name'])) . ' del ' . e($fecha) . '. Te toma menos de un minuto.' ?></p>

  <div class="enc-box">
    <?php if ($answered): ?>
    <div class="mesh rsv-done pane">
      <p class="lbl"><?= $justAnswered ? 'Respuesta recibida' : 'Ya respondiste esta encuesta' ?></p>
      <h2 class="d">¿Nos dejas una reseña?</h2>
      <p class="txt">Si te gustó el resultado, una reseña en Google ayuda a que otras novias y quinceañeras de Bucaramanga nos encuentren.</p>
      <div class="wrap-row">
        <?php if ($reviewUrl): ?>
        <a class="btn" href="<?= e($reviewUrl) ?>" rel="noopener" target="_blank">Dejar reseña en Google</a>
        <?php else: ?>
        <span class="btn" aria-disabled="true"><span class="pending">[Enlace de reseña de Google pendiente]</span></span>
        <?php endif; ?>
        <a class="btn ghost" href="/">Volver al inicio</a>
      </div>
    </div>
    <?php else: ?>
    <form class="pane enc-form" method="post" action="/encuesta/<?= e($s['token']) ?>" novalidate>
      <?= csrf_field() ?>
      <?php if (isset($errors['form'])): ?><div class="notice err" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>
      <fieldset class="rsv-group"<?= $aria('rating') ?>>
        <legend class="rsv-q">Califica el servicio</legend>
        <div class="slots enc-rating">
          <?php foreach ($labels as $n => $label): ?>
          <label class="slot"><input class="sr-only" type="radio" name="rating" value="<?= $n ?>" required<?= (int) ($old['rating'] ?? 0) === $n ? ' checked' : '' ?>><span><span aria-hidden="true" class="enc-n"><?= $n ?></span> <?= e($label) ?></span></label>
          <?php endforeach; ?>
        </div>
        <?= $err('rating') ?>
      </fieldset>
      <fieldset class="rsv-group"<?= $aria('attended') ?>>
        <legend class="rsv-q">¿Recibiste el servicio?</legend>
        <div class="slots">
          <label class="slot"><input class="sr-only" type="radio" name="attended" value="si" required<?= ($old['attended'] ?? '') === 'si' ? ' checked' : '' ?>><span>Sí, me maquillaron</span></label>
          <label class="slot"><input class="sr-only" type="radio" name="attended" value="no"<?= ($old['attended'] ?? '') === 'no' ? ' checked' : '' ?>><span>No, no se realizó</span></label>
        </div>
        <?= $err('attended') ?>
      </fieldset>
      <div class="f rsv-group">
        <label for="enc-c">¿Algo que quieras contarnos? (opcional)</label>
        <textarea id="enc-c" name="comment" rows="3" maxlength="1000" placeholder="Lo que más te gustó, lo que podríamos mejorar…"><?= e($old['comment'] ?? '') ?></textarea>
      </div>
      <?= spam_fields() ?>
      <div class="rsv-nav enc-nav">
        <p class="rsv-hint">Tus respuestas son privadas y solo se usan para mejorar el servicio (<a href="/politica-de-privacidad">política de datos</a>).</p>
        <button class="btn" type="submit">Enviar respuesta <?= icon('arrow', 'arr') ?></button>
      </div>
    </form>
    <?php endif; ?>
  </div>
</section>
