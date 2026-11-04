<?php
declare(strict_types=1);

namespace App;

use App\Http\Csrf;
use App\Http\Request;
use App\Http\Response;
use App\Http\SecurityHeaders;
use App\Http\Session;

/**
 * Ciclo de vida de una petición: normalización de URL → ruta → middleware → controlador
 * → cabeceras de seguridad. Cualquier excepción termina en una página 500 genérica.
 */
final class Kernel
{
    public static function handle(Request $request): Response
    {
        Session::configure($request);
        try {
            $response = self::dispatch($request);
        } catch (Http\HttpException $e) {
            $response = self::errorPage($e->status, $request);
        } catch (\Throwable $e) {
            app_log('error', $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'path' => $request->path]);
            if (config('app.debug')) {
                $response = Response::html('<pre>' . e((string) $e) . '</pre>', 500);
            } else {
                $response = self::errorPage(500, $request);
            }
        }
        return SecurityHeaders::apply($response, $request);
    }

    private static function dispatch(Request $request): Response
    {
        // URLs limpias: sin barra final y en minúscula (§12.4)
        $path = $request->path;
        if (preg_match('/[\x00-\x1F\x7F\\\\]/', $path) || str_starts_with($path, '//')) {
            throw new Http\HttpException(404); // caracteres de control, barras invertidas o «//»: nunca son rutas válidas
        }
        if ($path !== '/' && str_ends_with($path, '/')) {
            return self::canonicalRedirect(rtrim($path, '/') ?: '/', $request);
        }
        $router = new Router();
        (require BASE_PATH . '/config/routes.php')($router);
        $match = $router->match($request);
        if ($match === null && $path !== strtolower($path)) {
            // URLs en minúscula: solo redirige si la versión en minúscula existe (los códigos y tokens conservan mayúsculas)
            $lower = new Http\Request($request->method, strtolower($path), [], [], $request->server, []);
            if ($router->match($lower) !== null) {
                return self::canonicalRedirect(strtolower($path), $request);
            }
        }
        if ($match === null) {
            throw new Http\HttpException(404);
        }
        if ($match === false) {
            throw new Http\HttpException(405);
        }
        $request->params = $match['params'];
        $options = $match['options'];

        if ($request->method === 'POST' && ($options['csrf'] ?? true) && !Csrf::verify($request)) {
            if ($request->wantsJson()) {
                return Response::json(['error' => 'La sesión caducó. Recarga la página e inténtalo de nuevo.'], 419);
            }
            return self::errorPage(419, $request);
        }

        foreach ($options['middleware'] ?? [] as $mw) {
            $result = $mw::handle($request, $options);
            if ($result instanceof Response) {
                return $result;
            }
        }

        [$class, $method] = $match['handler'];
        $controller = new $class();
        $response = $controller->$method($request);
        if (is_string($response)) {
            $response = Response::html($response);
        }
        if ($request->method === 'HEAD') {
            $response = new Response('', $response->status(), $response->headers());
        }
        return $response;
    }

    private static function canonicalRedirect(string $path, Request $request): Response
    {
        $qs = $request->query ? '?' . http_build_query($request->query) : '';
        return Response::redirect($path . $qs, 301);
    }

    public static function errorPage(int $status, Request $request): Response
    {
        $titles = [
            404 => ['Página no encontrada', 'La página que buscas no existe o cambió de dirección.'],
            405 => ['Método no permitido', 'Esta dirección no acepta ese tipo de petición.'],
            419 => ['La sesión caducó', 'Por seguridad, el formulario expiró. Vuelve atrás, recarga la página e inténtalo de nuevo.'],
            429 => ['Demasiados intentos', 'Espera unos minutos antes de volver a intentarlo.'],
            403 => ['Acceso no permitido', 'No tienes permiso para ver esta página.'],
            500 => ['Algo salió mal', 'Tuvimos un problema al cargar la página. Inténtalo de nuevo en unos minutos o escríbenos por WhatsApp.'],
        ];
        [$title, $text] = $titles[$status] ?? $titles[500];
        try {
            $layout = 'site';
            $html = View::render('site/error', [
                'status' => $status,
                'title' => $title,
                'text' => $text,
                'meta' => ['title' => $title . ' · Make Up by Fletcher', 'robots' => 'noindex', 'description' => $text],
                'path' => $request->path,
            ], $layout);
        } catch (\Throwable $e) {
            app_log('error', 'error page: ' . $e->getMessage());
            $html = '<!doctype html><meta charset="utf-8"><title>' . e($title) . '</title><p>' . e($text) . '</p>';
        }
        return Response::html($html, $status);
    }
}
