<?php
/**
 * Router para el servidor integrado de PHP en desarrollo (imita el .htaccess):
 *   php -S localhost:8000 -t public tools/dev-router.php
 */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . '/../public' . $path;
if ((str_starts_with($path, '/assets/') || $path === '/favicon.ico' || $path === '/.well-known/security.txt') && is_file($file)) {
    return false; // archivo estático
}
require __DIR__ . '/../public/index.php';
