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
        // [foto, categoría, alt, clase de encuadre]. Fotos profesionales de clientas reales (originales en alta, sin EXIF).
        $photos = [
            ['fotos/novia-tiara-retrato', 'novias', 'Novia con tiara y velo, piel luminosa y labios rosados, sonriendo con su ramo', ''],
            ['fotos/social-cumple-pastel', 'social', 'Clienta con maquillaje de ojos dorados y pestañas, sonriendo con su torta de cumpleaños', ''],
            ['fotos/estudio-vestido-azul-sonrisa', 'social', 'Clienta con vestido azul bordado, ojos ahumados en tonos cálidos y pestañas, sonriendo en el estudio', ''],
            ['fotos/proceso-rubor-novia', 'detras', 'Novia con tiara riendo mientras le aplican rubor con brocha', ''],
            ['fotos/novia-calas-editorial', 'editorial', 'Novia con velo y vestido de seda entre calas blancas', ''],
            ['fotos/estudio-ondas-vino', 'social', 'Clienta con ondas suaves, vestido vino y maquillaje de piel satinada con labio coral', ''],
            ['fotos/novia-jardin-cuello', 'novias', 'Novia con tiara y vestido de encaje en un jardín, ojos cerrados y piel luminosa', ''],
            ['fotos/proceso-labios-detalle', 'detras', 'Detalle de labios con brillo y pincel de labial', ''],
            ['fotos/estudio-rizos-sonrisa', 'social', 'Clienta de cabello rizado con maquillaje social de piel luminosa y labio rosado, sonriendo en el estudio', ''],
            ['fotos/novia-bata-ramo', 'novias', 'Novia en bata de seda con ondas suaves y ramo de lirios blancos', ''],
            ['fotos/social-cumple-beso', 'social', 'Clienta con ojos ahumados dorados soplando un beso junto a su torta', ''],
            ['fotos/social-cumple-pampas', 'social', 'Clienta sentada con vestido verde entre globos dorados y pampas, con su torta de cumpleaños', ''],
            ['fotos/novia-velo-ventana', 'editorial', 'Novia con velo detrás de una ventana, luz natural y mirada baja', ''],
            ['fotos/novia-cortina-retrato', 'novias', 'Novia con tiara y vestido de tirantes frente a una cortina violeta', ''],
            ['fotos/estudio-delineado-negro', 'social', 'Clienta con delineado negro alargado, cejas definidas y labio nude frente al espejo del estudio', ''],
            ['fotos/proceso-brocha-bn', 'detras', 'María Paula Fletcher sonriendo mientras maquilla a una novia, en blanco y negro', ''],
            ['fotos/novia-jardin-velo', 'novias', 'Novia con velo largo y trenza mirando su ramo en un jardín', ''],
            ['fotos/estudio-bata-ondas', 'social', 'Clienta en bata de seda negra con ondas largas y maquillaje glam antes de su evento', ''],
            ['fotos/social-cumple-bengala', 'social', 'Clienta sonriendo con una torta de cumpleaños y una bengala encendida', ''],
            ['fotos/proceso-estudio-espejo', 'detras', 'Maquilladora preparando a una novia frente al espejo con luces doradas', ''],
            ['fotos/estudio-sonrisa-dorada', 'social', 'Clienta sonriendo con maquillaje social de ojos dorados y collar fino, en el estudio', ''],
            ['fotos/novia-tiara-ramo', 'novias', 'Novia con tiara y recogido alto sonriendo con su ramo de hortensias', ''],
            ['fotos/novia-balcon', 'editorial', 'Novia con velo y vestido de seda en un balcón con vista a la ciudad', ''],
            ['fotos/estudio-vestido-azul-ojos', 'social', 'Clienta con los ojos cerrados que muestran sombras ahumadas en tonos cobre y delineado', ''],
            ['fotos/proceso-labios-perfil', 'detras', 'Perfil de una novia mientras le delinean los labios', ''],
            ['fotos/novia-madre', 'novias', 'Novia y su mamá abrazadas en batas de seda durante la preparación', ''],
            ['fotos/estudio-natural-sonrisa', 'social', 'Clienta con maquillaje natural de piel luminosa y ojos definidos, sonriendo con suéter verde', ''],
            ['fotos/social-cumple-globos', 'social', 'Clienta sentada con vestido verde, globos dorados y maquillaje social', ''],
            ['fotos/novia-cola-jardin', 'novias', 'Novia con velo catedral en un jardín, de espaldas a la cámara', ''],
            ['fotos/social-cumple-globos-pie', 'social', 'Clienta de pie con vestido verde, globos dorados y torta de cumpleaños, con maquillaje social', ''],
            ['fotos/proceso-velo-bn', 'detras', 'Maquilladora retocando a una novia con velo junto a la ventana, en blanco y negro', ''],
            ['fotos/novia-ramo-ceremonia', 'novias', 'Novia con tiara y vestido de encaje sosteniendo su ramo durante la ceremonia', ''],
            ['fotos/estudio-bata-rosa', 'social', 'Clienta en bata de seda rosa con ondas suaves y maquillaje luminoso antes de su evento', ''],
            ['fotos/proceso-retoque-jardin', 'detras', 'Retoque de maquillaje a una novia en el jardín antes de la ceremonia', ''],
            ['fotos/novia-pareja', 'novias', 'Novia con tiara recibiendo un beso del novio en la frente durante la recepción', ''],
            ['fotos/estudio-chaqueta-negra', 'social', 'Clienta de cabello largo con maquillaje de ojos rosados y labio en tono piel, en el estudio', ''],
            ['fotos/novia-damas', 'novias', 'Novia con sus damas de honor en batas verdes y la maquilladora', ''],
            ['fotos/proceso-domicilio', 'detras', 'Maquilladora aplicando rubor a una novia en bata en su casa, con el vestido al fondo', ''],
            ['fotos/estudio-ondas-vino-ojos', 'social', 'Clienta de perfil con los ojos cerrados, sombras doradas suaves y pestañas, junto al espejo con luces', ''],
            ['fotos/estudio-bata-negra-madura', 'social', 'Clienta madura en bata de seda negra con maquillaje suave de ojos definidos y labio rosado', ''],
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
