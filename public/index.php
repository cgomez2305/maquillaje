<?php
declare(strict_types=1);

/**
 * Front controller: toda petición que no sea un archivo de /assets pasa por aquí.
 */
define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/bootstrap.php';

App\Kernel::handle(App\Http\Request::capture())->send();
