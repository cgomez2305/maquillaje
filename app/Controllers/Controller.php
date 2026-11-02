<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Http\Response;
use App\View;

abstract class Controller
{
    /**
     * Renderiza una página pública con los metadatos de config/seo.php[$seoKey].
     *
     * @param array<string,mixed> $data  puede sobrescribir meta, crumbs, css, js, schema, preload
     */
    protected function page(string $seoKey, string $view, array $data = [], int $status = 200): Response
    {
        $seo = (array) config('seo.' . $seoKey, []);
        $meta = ($data['meta'] ?? []) + [
            'title' => $seo['title'] ?? config('app.name'),
            'description' => $seo['description'] ?? '',
            'canonical' => $seo['path'] ?? '/',
        ];
        if (!isset($data['crumbs']) && $seoKey !== 'home' && isset($seo['crumb'])) {
            $data['crumbs'] = [['Inicio', '/'], [$seo['crumb'], null]];
        }
        $data['meta'] = $meta;
        $data['path'] = $data['path'] ?? ($seo['path'] ?? '/');
        return Response::html(View::render($view, $data, 'site'), $status);
    }

    protected function view(string $view, array $data = [], string $layout = 'site', int $status = 200): Response
    {
        return Response::html(View::render($view, $data, $layout), $status);
    }
}
