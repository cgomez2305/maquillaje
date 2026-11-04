<?php
/** C04 · sello giratorio con texto circular. Cada uso lleva un id propio para el trazado del texto. */
$GLOBALS['__seal_n'] = ($GLOBALS['__seal_n'] ?? 0) + 1;
$id = 'sealc' . $GLOBALS['__seal_n'];
?>
<div class="seal" aria-hidden="true">
  <svg viewBox="0 0 150 150"><defs><path id="<?= $id ?>" d="M75,75 m-58,0 a58,58 0 1,1 116,0 a58,58 0 1,1 -116,0"/></defs><text font-family="DM Sans, sans-serif" font-size="9.5" letter-spacing="2" fill="#171312"><textPath href="#<?= $id ?>" textLength="360" lengthAdjust="spacing">MAKE UP ARTIST · BUCARAMANGA · FLORIDABLANCA · </textPath></text></svg>
  <img src="<?= e(img_src('marca/isotipo-salmon', 120)) ?>" alt="" width="58" height="58">
</div>
