<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Domain\Catalog;
use App\Http\Request;
use App\Http\Response;
use App\Support\Schema;

final class CursosController extends Controller
{
    public function index(Request $request): Response
    {
        $courses = Catalog::courses();
        $path = (string) config('seo.cursos.path');

        $cursos = [
            [
                'id' => 'automaquillaje',
                'code' => 'curso-auto',
                'label' => 'Curso 01 · 5 módulos',
                'title' => 'Automaquillaje',
                'text' => 'Para ti, que quieres verte impecable sin depender de nadie: conocer tu piel, elegir tus productos y hacer tu look de día y de noche.',
                'modules_label' => 'Aprenderás · 5 módulos de 1 a 1,5 horas',
                'modules' => ['Teoría básica', 'Maquillaje de piel', 'Maquillaje de ojos para día', 'Maquillaje de ojos para noche', 'Maquillaje de ojos glam', 'Maquillaje de cejas', 'Maquillaje de labios'],
                'note' => null,
                'buy' => '/tienda/curso-automaquillaje',
                'schema_desc' => 'Curso presencial de automaquillaje en Floridablanca: teoría básica, piel, ojos día, noche y glam, cejas y labios. 5 módulos de 1 a 1,5 horas.',
            ],
            [
                'id' => 'profesional',
                'code' => 'curso-pro',
                'label' => 'Curso 02 · 7 módulos',
                'title' => 'Maquillaje profesional',
                'text' => 'Para quien quiere trabajar maquillando: técnica, higiene, piel, novias y cómo construir tu agenda.',
                'modules_label' => 'Aprenderás · 7 módulos de 2 a 3 horas',
                'modules' => ['Teoría básica', 'Maquillaje de piel', 'Maquillaje social para día, noche y glam', 'Maquillaje de novias y quinceañeras', 'Maquillaje editorial y para sesión de fotos', 'Maquillaje de cejas', 'Maquillaje de labios'],
                'note' => 'Debes venir acompañada de una modelo de práctica.',
                'buy' => '/tienda/curso-maquillaje-profesional',
                'schema_desc' => 'Curso presencial de maquillaje profesional en Floridablanca: piel, social día, noche y glam, novias, quinceañeras, editorial, sesión de fotos, cejas y labios. 7 módulos de 2 a 3 horas. Requiere modelo de práctica.',
            ],
        ];
        foreach ($cursos as &$c) {
            $c['price'] = (int) ($courses[$c['code']]['price'] ?? 0);
        }
        unset($c);

        $faq = [
            ['q' => '¿Necesito experiencia previa?', 'a' => 'No. Los dos cursos empiezan por teoría básica. En el curso profesional debes venir con una modelo de práctica.'],
            ['q' => '¿Los productos están incluidos?', 'a' => 'Recibes una lista de materiales con un descuento especial en una tienda aliada. Mientras los consigues, puedes empezar con el kit de maquillaje de María Paula.'],
            ['q' => '¿Cuándo empieza el próximo grupo?', 'a' => '[Fecha de inicio y horario del próximo grupo].', 'pending' => true],
            ['q' => '¿Cómo separo mi cupo?', 'a' => 'Inscríbete en línea con pago seguro (tarjeta, PSE o Nequi) o escríbenos por WhatsApp: el cupo también queda separado con un abono del 50 % por Nequi o Bancolombia.'],
        ];

        $schema = [];
        foreach ($cursos as $c) {
            $schema[] = Schema::course('Curso de ' . mb_strtolower($c['title']), $c['schema_desc'], $c['price'], $path . '#' . $c['id']);
        }
        // FAQPage solo con respuestas reales (no se marca el dato pendiente)
        $schema[] = Schema::faq(array_values(array_filter($faq, static fn(array $i): bool => empty($i['pending']))));

        return $this->page('cursos', 'site/cursos', [
            'cursos' => $cursos,
            'faq' => $faq,
            'css' => ['pages/cursos.css'],
            'preload' => ['key' => 'fotos/catalogo-maria-paula-tablero', 'sizes' => '(max-width: 960px) 100vw, 560px'],
            'schema' => $schema,
            'waOrigin' => 'cursos',
        ]);
    }
}
