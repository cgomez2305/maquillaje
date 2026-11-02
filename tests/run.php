<?php
declare(strict_types=1);

/**
 * Ejecuta todas las pruebas del dominio (tests/*Test.php) contra la BD local.
 *   php tests/run.php     (o composer test)
 * Nunca en producción: las pruebas crean datos propios.
 */
$fails = 0;
$files = glob(__DIR__ . '/*Test.php') ?: [];
sort($files);
foreach ($files as $f) {
    echo "\n=== " . basename($f) . " ===\n";
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($f), $code);
    if ($code !== 0) {
        $fails++;
    }
}
echo $fails === 0 ? "\nTodas las suites pasaron (" . count($files) . ").\n" : "\n{$fails} suite(s) con fallos.\n";
exit($fails === 0 ? 0 : 1);
