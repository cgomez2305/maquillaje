<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class PortafolioController extends Controller
{
    /** Categorías del filtro (C10), en orden. */
    private const CATS = ['novias' => 'Novias', 'social' => 'Social', 'editorial' => 'Editorial', 'detras' => 'Detrás de cámaras'];

    public function index(Request $request): Response
    {
        // [foto, categoría, alt, clase de encuadre]. La novia con tiara va al final (§6.6: nunca en lugar destacado).
        // «Retrato editorial»: falta el original en alta; se usa un encuadre cerrado de la foto del catálogo.
        $photos = [
            ['fotos/ig-maquillaje-social', 'social', 'Maquillaje social con piel luminosa y ojo ahumado suave', ''],
            ['fotos/ig-novia-reel', 'detras', 'Novia en bata de seda frente a la cámara durante su preparación', ''],
            ['fotos/catalogo-modelo-hero', 'editorial', 'Retrato editorial con piel luminosa y ojos en tonos cálidos', 'crop-face'],
            ['fotos/ig-clienta-bata-seda', 'novias', 'Clienta en bata de seda con maquillaje natural y peinado recogido', ''],
            ['fotos/catalogo-ojo-detalle', 'editorial', 'Detalle de ojo con sombras cálidas, delineado y pestañas', ''],
            ['fotos/catalogo-paleta', 'detras', 'Aplicación de corrector con pincel desde la paleta', ''],
            ['fotos/catalogo-modelo-hero', 'editorial', 'Modelo con piel luminosa y bata negra de Make Up by Fletcher', ''],
            ['fotos/catalogo-asesoria-estudio', 'detras', 'María Paula Fletcher asesorando a una clienta en el estudio', ''],
            ['fotos/catalogo-brindis-estudio', 'detras', 'Brindis en el estudio después del maquillaje', ''],
            ['fotos/ig-estudio-bn', 'detras', 'Estudio de maquillaje en Floridablanca', ''],
            ['fotos/ig-novia-tiara', 'novias', 'Novia con tiara, recogido alto y ramo de flores blancas', ''],
        ];

        $manifest = json_decode((string) @file_get_contents(BASE_PATH . '/public/assets/img/manifest.json'), true) ?: [];
        $items = [];
        foreach ($photos as [$key, $cat, $alt, $crop]) {
            $m = $manifest[$key] ?? null;
            $items[] = [
                'key' => $key,
                'cat' => $cat,
                'cat_label' => self::CATS[$cat],
                'alt' => $alt,
                'crop' => $crop,
                'full' => $m ? img_src($key, (int) end($m['widths']), 'jpg') : '#',
            ];
        }

        return $this->page('portafolio', 'site/portafolio', [
            'items' => $items,
            'cats' => self::CATS,
            'css' => ['pages/portafolio.css'],
            'js' => ['portafolio.js'],
            'waOrigin' => 'portafolio',
        ]);
    }
}
