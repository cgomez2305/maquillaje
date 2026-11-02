<?php
declare(strict_types=1);

/**
 * Front controller: toda petición que no sea un archivo de /assets pasa por aquí.
 */
// El proyecto vive un nivel por encima de public/. En Hostinger, si public/ se sube a public_html,
// el resto puede ir en ~/maquillaje (un nivel arriba) y se detecta solo.
$base = dirname(__DIR__);
foreach ([$base, $base . '/maquillaje', $base . '/mubf'] as $candidate) {
    if (is_file($candidate . '/app/bootstrap.php')) {
        $base = $candidate;
        break;
    }
}
define('BASE_PATH', $base);

require BASE_PATH . '/app/bootstrap.php';

App\Kernel::handle(App\Http\Request::capture())->send();
