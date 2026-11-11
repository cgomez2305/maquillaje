<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Domain\Catalog;
use App\Http\Request;
use App\Http\Response;
use App\Support\Schema;

/**
 * Páginas de servicio por intención de búsqueda (docs/SEO-AUDITORIA.md §5.4):
 * quinceañeras, grados y domicilio. Usan los mismos packs y precios del catálogo; solo cambia el enfoque.
 * Una sola vista (site/landing) con los datos de cada página.
 */
final class LandingController extends Controller
{
    public function quinceaneras(Request $request): Response
    {
        $packs = Catalog::packs();
        $p = $packs['pack3'];
        $path = '/maquillaje-para-quinceaneras-bucaramanga';
        $faq = [
            ['q' => '¿Cuánto cuesta el maquillaje para quinceañera?', 'a' => 'El Pack 3 cuesta ' . money($p['price']) . '. Incluye asesoría, prueba previa con dos propuestas, skin care, maquillaje el día de los quince con pestañas, kit de retoque, video reel de la preparación y extra fijación.'],
            ['q' => '¿Cuándo se hace la prueba?', 'a' => 'Unos días antes de la fiesta, en el estudio de Floridablanca. La quinceañera puede venir con su mamá: ahí vemos las dos propuestas con el vestido en mente y elegimos juntas.'],
            ['q' => '¿El maquillaje la hace ver mayor?', 'a' => 'No es la idea. Trabajamos piel fresca, color suave y pestañas que abren la mirada sin endurecerla. Si ella quiere algo más intenso para la noche, lo probamos en una de las dos propuestas.'],
            ['q' => '¿Hacen también el peinado?', 'a' => 'No, solo maquillaje. Si tienes estilista para el peinado, coordinamos con ella el orden y la hora para que todo esté listo a tiempo.'],
            ['q' => '¿Pueden maquillar a la mamá y a las amigas?', 'a' => 'Sí. La mamá, las hermanas y las amigas pueden agendar el Pack 1 (' . money($packs['pack1']['price']) . ') o el Pack 2 a domicilio (' . money($packs['pack2']['price']) . ') el mismo día.'],
            ['q' => '¿Con cuánta anticipación debo reservar?', 'a' => 'El mínimo es de 2 días, pero para unos quince conviene separar la fecha en cuanto la tengas, porque la prueba previa también necesita espacio en la agenda. La fecha se separa con un abono del 50 %.'],
        ];

        return $this->landing('quinceaneras', $path, [
            'origin' => 'quince',
            'topic' => 'quince',
            'pack' => 'pack3',
            'h1' => 'Maquillaje para quinceañeras en Bucaramanga',
            'display' => 'Quince años,<br>su propia luz.',
            'lead' => 'Un maquillaje que la haga sentir especial sin verse mayor de lo que es: piel fresca, color suave y una fijación pensada para el vals, las fotos y la fiesta. Con prueba previa, para que el día de los quince no haya sorpresas.',
            'priceNote' => 'COP · Pack 3 con prueba previa',
            'priceAlt' => ['#familia', 'Mamá, hermanas y amigas desde ' . money($packs['pack1']['price'])],
            'cover' => ['fotos/proceso-rubor-novia', 'Clienta con tiara riendo mientras le aplican rubor con brocha'],
            'incTitle' => 'Lo que incluye<br>el Pack 3.',
            'incText' => 'El mismo pack de nuestras novias, pensado para una quinceañera: el maquillaje ya lo probó, lo aprobó con su mamá y sabemos cómo se comporta con su piel.',
            'inc' => [
                ['01', 'Prueba previa', 'Días antes, en el estudio, con calma y con la luz con la que la vamos a maquillar.'],
                ['02', 'Dos propuestas', 'Una más natural y otra con más color, pensadas para su vestido y la hora de la fiesta.'],
                ['03', 'Video reel', 'Grabamos su preparación para que le quede el recuerdo de ese momento.'],
                ['04', 'Extra fijación', 'Sellado reforzado para el vals, los abrazos, el calor y la pista de baile.'],
            ],
            'reserveHref' => '/reservar?servicio=pack3',
            'guideTitle' => 'De su edad,<br>a su estilo.',
            'guideText' => 'Unos quince no son una boda. Por eso no aplicamos el mismo maquillaje: lo ajustamos a su edad, a su piel y a lo que ella quiere ver en las fotos.',
            'guidePhoto' => ['fotos/catalogo-ojo-detalle', 'Detalle de ojo maquillado con sombras cálidas y delineado suave'],
            'guide' => [
                ['Piel fresca', 'Cobertura ligera que unifica sin tapar. La piel se ve como piel, también de cerca y con flash.'],
                ['Color según el vestido', 'Rosados, dorados o tonos tierra: lo definimos en la prueba según el color del vestido y la decoración.'],
                ['Mirada abierta', 'Pestañas postizas incluidas, elegidas para abrir la mirada sin que se vean pesadas.'],
                ['Dura toda la fiesta', 'Extra fijación y kit de retoque con papel de arroz, brillo labial y espejo de bolsillo para la noche.'],
            ],
            'steps' => [
                ['01', 'Separas la fecha', 'Nos escribes por WhatsApp con la fecha, la hora y el lugar. La fecha queda reservada con un abono del 50 %.'],
                ['02', 'Prueba en el estudio', 'En Floridablanca, días antes de la fiesta. Puede venir con su mamá.'],
                ['03', 'Elige su look', 'Le mostramos dos propuestas y elige con tiempo, sin presión.'],
                ['04', 'El día de los quince', 'Skin care, maquillaje con luz de estudio, extra fijación y el video reel de su preparación.'],
            ],
            'family' => ['id' => 'familia', 'title' => 'Mamá, hermanas<br>y amigas.', 'text' => 'Agenda a quien la acompaña el mismo día y mantén un solo estilo en todas las fotos de la fiesta.', 'photo' => ['fotos/novia-madre', 'Madre e hija abrazadas en batas de seda durante la preparación']],
            'gallery' => [
                ['fotos/catalogo-modelo-hero', 'Modelo con piel luminosa entre flores'],
                ['fotos/ig-maquillaje-social', 'Maquillaje social con piel luminosa y ojo ahumado suave'],
                ['fotos/social-cumple-globos', 'Clienta con vestido verde, globos dorados y maquillaje social'],
                ['fotos/proceso-labios-detalle', 'Detalle de labios con brillo y pincel de labial'],
                ['fotos/social-cumple-bengala', 'Clienta sonriendo con una torta y una bengala encendida'],
                ['fotos/catalogo-ojo-detalle', 'Detalle de ojo maquillado con sombras cálidas y delineado'],
            ],
            'galleryTitle' => 'Detalles que se ven en foto.',
            'band' => ['Agenda de quinces', 'Las fechas con prueba previa se llenan primero. Separa la de los quince en cuanto la tengas.'],
            'faq' => $faq,
            'faqTitle' => 'Antes de<br>los quince.',
            'related' => [
                ['/maquillaje-de-novia-bucaramanga', 'Maquillaje de novia en Bucaramanga'],
                ['/maquillaje-a-domicilio-bucaramanga', 'Maquillaje a domicilio'],
                ['/precios-maquillaje-bucaramanga', 'Precios de maquillaje 2026'],
            ],
            'ctaTitle' => 'Separa la fecha.',
            'schema' => Schema::service($p['name'], 'Maquillaje para quinceañeras en Bucaramanga y Floridablanca: ' . implode(', ', $p['includes']) . '.', (int) $p['price'], $path, 'Maquillaje para quinceañeras'),
        ]);
    }

    public function grados(Request $request): Response
    {
        $packs = Catalog::packs();
        $p = $packs['pack1'];
        $path = '/maquillaje-para-grados-bucaramanga';
        $firstSlot = (string) (config('app.booking.slots')[0] ?? '05:00');
        $faq = [
            ['q' => '¿Cuánto cuesta el maquillaje para grado?', 'a' => 'En el estudio de Floridablanca es el Pack 1: ' . money($p['price']) . ', con asesoría previa, maquillaje social y pestañas postizas. Si prefieres que vayamos a tu casa, el Pack 2 a domicilio cuesta ' . money($packs['pack2']['price']) . ' e incluye skin care, silla y luz de estudio y kit de retoque.'],
            ['q' => '¿A qué hora me pueden maquillar?', 'a' => 'Agendamos desde las ' . $firstSlot . ' para ceremonias de la mañana. Cuéntanos la hora del grado y el lugar, y te proponemos la hora de inicio.'],
            ['q' => '¿Con cuánta anticipación debo reservar?', 'a' => 'El mínimo es de 2 días, pero en noviembre y diciembre la agenda se llena por la temporada de grados. Separa tu fecha apenas sepas la hora de la ceremonia: queda reservada con un abono del 50 %.'],
            ['q' => '¿Sirve para grado de colegio y de universidad?', 'a' => 'Sí. Ajustamos la intensidad al tipo de ceremonia y a tu edad: más natural para grados de colegio y de día, y con más definición para ceremonias de noche o fiestas después del grado.'],
            ['q' => '¿Pueden maquillar a mi mamá o a mis amigas?', 'a' => 'Sí. Cada persona agenda su pack y las atendemos el mismo día, en el estudio o en la misma casa.'],
            ['q' => '¿Hacen peinados?', 'a' => 'No, solo maquillaje. Si te vas a peinar, cuéntanos la hora para coordinar el orden.'],
        ];

        return $this->landing('grados', $path, [
            'origin' => 'grados',
            'topic' => 'grados',
            'pack' => 'pack1',
            'h1' => 'Maquillaje para grados en Bucaramanga',
            'display' => 'Tu foto con toga,<br>impecable.',
            'lead' => 'Maquillaje para grado de colegio o universidad: natural, fotogénico y hecho para aguantar la ceremonia, el flash y el calor de la celebración. En el estudio de Floridablanca o a domicilio.',
            'priceNote' => 'COP · Pack 1 con pestañas',
            'priceAlt' => ['/maquillaje-a-domicilio-bucaramanga', 'A domicilio con skin care: ' . money($packs['pack2']['price'])],
            'cover' => ['fotos/proceso-estudio-espejo', 'Maquilladora preparando a una clienta frente al espejo con luces doradas'],
            'incTitle' => 'Lo que incluye<br>el Pack 1.',
            'incText' => 'Un servicio pensado para el día del grado: entras con la piel limpia y sales lista para la ceremonia, las fotos familiares y la celebración.',
            'inc' => [
                ['01', 'Asesoría previa', 'Analizamos tus rasgos, el color de la toga y la hora de la ceremonia antes de empezar.'],
                ['02', 'Maquillaje social', 'Piel satinada, mirada definida y labio que dura, con tonos que no se lavan con el flash.'],
                ['03', 'Pestañas postizas', 'Incluidas, de un solo uso, elegidas para que se vean naturales en foto.'],
                ['04', 'En estudio o en casa', 'En el estudio de Floridablanca o, con el Pack 2, en tu casa con silla y luz de estudio.'],
            ],
            'reserveHref' => '/reservar?servicio=pack1',
            'guideTitle' => 'Pensado para<br>el día del grado.',
            'guideText' => 'Un grado tiene sus propias condiciones: toga oscura, birrete, fotos con flash en auditorio y, casi siempre, calor. El maquillaje se ajusta a eso.',
            'guidePhoto' => ['fotos/ig-maquillaje-social', 'Clienta sonriendo con maquillaje social de piel luminosa y ojo ahumado suave'],
            'guide' => [
                ['De día o de noche', 'Para la mañana, piel luminosa y tonos cálidos. Para la noche o la fiesta, ojo más intenso y labio más definido.'],
                ['Toga y birrete', 'El birrete enmarca la frente y la toga oscura apaga el rostro: cejas definidas y un toque de color en mejillas y labios.'],
                ['Flash del auditorio', 'Evitamos los acabados que se blanquean con flash y sellamos la piel para que no brille en las fotos.'],
                ['Calor de Bucaramanga', 'Capas finas, preparación de la piel y sellado para que el maquillaje llegue intacto a la celebración.'],
            ],
            'steps' => [
                ['01', 'Separas tu fecha', 'Nos escribes con la fecha, la hora de la ceremonia y si lo quieres en estudio o a domicilio. Se separa con un abono del 50 %.'],
                ['02', 'Asesoría', 'El día del grado empezamos por tus rasgos, la toga y el look que quieres.'],
                ['03', 'Maquillaje', 'Piel, ojos, pestañas y labios con luz de estudio, en silla profesional.'],
                ['04', 'A la ceremonia', 'Sales lista para las fotos con toga, la familia y la celebración.'],
            ],
            'family' => ['id' => 'familia', 'title' => 'Mamá, amigas<br>y compañeras.', 'text' => 'Si se gradúan varias o tu mamá también quiere maquillarse, agéndenlas el mismo día. Cada persona elige su pack.', 'photo' => ['fotos/catalogo-brindis-estudio', 'Clienta y maquilladora brindando en el estudio de Floridablanca']],
            'gallery' => [
                ['fotos/social-cumple-pastel', 'Clienta con maquillaje social de ojos dorados y pestañas'],
                ['fotos/social-cumple-beso', 'Clienta con ojos ahumados dorados soplando un beso'],
                ['fotos/ig-maquillaje-social', 'Maquillaje social con piel luminosa y ojo ahumado suave'],
                ['fotos/social-cumple-globos', 'Clienta con vestido verde, globos dorados y maquillaje social'],
                ['fotos/social-cumple-bengala', 'Clienta sonriendo con una torta y una bengala encendida'],
                ['fotos/proceso-labios-detalle', 'Detalle de labios con brillo y pincel de labial'],
            ],
            'galleryTitle' => 'Maquillaje social, en piel real.',
            'band' => ['Temporada de grados', 'Noviembre y diciembre son los meses más pedidos. Separa tu fecha apenas tengas la hora de la ceremonia.'],
            'faq' => $faq,
            'faqTitle' => 'Antes de<br>tu grado.',
            'related' => [
                ['/precios-maquillaje-bucaramanga', 'Precios de maquillaje social 2026'],
                ['/maquillaje-a-domicilio-bucaramanga', 'Maquillaje a domicilio'],
                ['/estudio-maquillaje-floridablanca', 'Estudio de maquillaje en Floridablanca'],
            ],
            'ctaTitle' => 'Reserva tu grado.',
            'schema' => Schema::service('Maquillaje para grados · ' . $p['name'], 'Maquillaje para grados en Bucaramanga y Floridablanca: ' . implode(', ', $p['includes']) . '.', (int) $p['price'], $path, 'Maquillaje para grados'),
        ]);
    }

    public function domicilio(Request $request): Response
    {
        $packs = Catalog::packs();
        $p = $packs['pack2'];
        $path = '/maquillaje-a-domicilio-bucaramanga';
        $faq = [
            ['q' => '¿Cuánto cobra una maquilladora a domicilio?', 'a' => 'Nuestro servicio a domicilio es el Pack 2: ' . money($p['price']) . ' por persona en Bucaramanga, Floridablanca, Piedecuesta y Girón. Incluye asesoría, skin care con mascarilla y masaje, maquillaje en silla profesional con luz de estudio, pestañas y kit de retoque.'],
            ['q' => '¿Cuánto cuesta un maquillaje sencillo?', 'a' => 'El maquillaje social en el estudio de Floridablanca (Pack 1) cuesta ' . money($packs['pack1']['price']) . ', con asesoría y pestañas incluidas.'],
            ['q' => '¿Van fuera del área metropolitana?', 'a' => 'Sí, con cotización previa. Los servicios fuera de Bucaramanga, Floridablanca, Piedecuesta y Girón, o en horarios especiales, se cotizan por WhatsApp según la distancia y la hora.'],
            ['q' => '¿Qué necesito tener listo en casa?', 'a' => 'Un espacio con buena luz, una mesa y un tomacorriente cerca. Nosotras llevamos la silla, la luz de estudio y todo el kit.'],
            ['q' => '¿Pueden maquillar a varias personas en la misma casa?', 'a' => 'Sí. Cada persona agenda su pack y organizamos los turnos según la hora del evento. Ármalo todo en una sola solicitud.'],
            ['q' => '¿Hacen peinados?', 'a' => 'No, solo maquillaje. Si tu evento lo necesita, coordina con tu estilista el orden y la hora: te ayudamos a cuadrar los tiempos.'],
        ];

        return $this->landing('domicilio', $path, [
            'origin' => 'domicilio',
            'topic' => 'domicilio',
            'pack' => 'pack2',
            'h1' => 'Maquillaje a domicilio en Bucaramanga',
            'display' => 'El estudio<br>va a tu casa.',
            'lead' => 'Silla profesional, luz de estudio y skin care en tu casa, tu hotel o el lugar del evento. Atendemos Bucaramanga, Floridablanca, Piedecuesta y Girón.',
            'priceNote' => 'COP · Pack 2 full experience',
            'priceAlt' => ['/maquillaje-de-novia-bucaramanga', 'Novias con prueba previa: ' . money($packs['pack3']['price'])],
            'cover' => ['fotos/proceso-domicilio-h', 'Maquilladora aplicando rubor a una novia en bata en su casa, junto al vestido colgado'],
            'incTitle' => 'Lo que incluye<br>el Pack 2.',
            'incText' => 'Una sesión completa en la comodidad de tu casa, con música relajante y ventilador de mano para que estés fresca mientras te maquillamos.',
            'inc' => [
                ['01', 'Skin care y masaje', 'Mascarilla húmeda, masaje facial, esencias aromáticas y masaje de manos con cera tibia.'],
                ['02', 'Silla y luz de estudio', 'Llevamos la silla profesional y la luz para que el maquillaje se vea igual en el espejo y en la foto.'],
                ['03', 'Pestañas postizas', 'Incluidas y elegidas según tu look y la hora del evento.'],
                ['04', 'Kit de retoque', 'Papel de arroz, brillo labial y espejo de bolsillo para el resto del día.'],
            ],
            'reserveHref' => '/reservar?servicio=pack2',
            'guideTitle' => 'Qué necesitamos<br>en tu casa.',
            'guideText' => 'Muy poco. Llevamos el equipo completo; solo necesitamos un rincón cómodo y tiempo suficiente antes de que llegue el fotógrafo.',
            'guidePhoto' => ['fotos/proceso-domicilio', 'Maquilladora aplicando rubor a una novia en bata en su casa, con el vestido al fondo'],
            'guide' => [
                ['Buena luz', 'Cerca de una ventana si es de día. La luz de estudio la llevamos nosotras.'],
                ['Una mesa', 'Para organizar el kit con higiene y tener todo a mano.'],
                ['Un tomacorriente', 'Cerca de la silla, para la luz de estudio.'],
                ['Rostro limpio', 'Sin maquillaje previo. El skin care lo hacemos nosotras al empezar.'],
            ],
            'steps' => [
                ['01', 'Separas tu fecha', 'Nos escribes con la fecha, la hora del evento, la dirección y cuántas personas se maquillan. Se separa con un abono del 50 %.'],
                ['02', 'Cuadramos la hora', 'Calculamos la hora de llegada según el evento, el número de personas y si hay peinado o fotógrafo.'],
                ['03', 'Llegamos con todo', 'Silla profesional, luz de estudio y el kit completo. Tú solo pones el espacio.'],
                ['04', 'Lista para salir', 'Skin care, maquillaje y pestañas, y te dejamos el kit de retoque para el resto del día.'],
            ],
            'zones' => [
                ['bucaramanga', 'Bucaramanga', 'En tu casa, apartamento u hotel, en cualquier barrio de la ciudad. Es el servicio más pedido para novias que se arreglan en casa y para grados y eventos de noche.'],
                ['floridablanca', 'Floridablanca', 'Aquí está el estudio (Carrera 29 #158-08). Puedes elegir entre venir al estudio con el Pack 1 o que vayamos a tu casa con el Pack 2.'],
                ['piedecuesta', 'Piedecuesta', 'Incluida en el área de servicio, con el mismo precio cerrado del Pack 2, para casas, conjuntos y salones de eventos. Si la ceremonia es temprano, agenda con tiempo para cuadrar la hora de inicio.'],
                ['giron', 'Girón', 'Incluida en el área de servicio, con el mismo precio cerrado del Pack 2, para casas, hoteles y salones. Las fincas o lugares fuera del área metropolitana se cotizan por WhatsApp según la distancia.'],
            ],
            'family' => ['id' => 'grupo', 'title' => 'Varias personas,<br>una sola casa.', 'text' => 'La novia, la mamá y las damas, o la quinceañera y su familia: organizamos los turnos para que todas estén listas a la hora.', 'photo' => ['fotos/novia-madre', 'Novia y su mamá abrazadas en batas de seda durante la preparación en casa']],
            'gallery' => [
                ['fotos/proceso-domicilio', 'Maquilladora aplicando rubor a una novia en bata en su casa'],
                ['fotos/novia-puerta-vestido', 'Novia en bata con tiara frente a su vestido colgado en la puerta'],
                ['fotos/novia-damas', 'Novia con sus damas de honor en batas verdes y la maquilladora'],
                ['fotos/novia-madre', 'Novia y su mamá abrazadas en batas de seda'],
                ['fotos/proceso-retoque-jardin', 'Retoque de maquillaje a una novia en el jardín antes de la ceremonia'],
                ['fotos/novia-bata-sonrisa', 'Novia en bata de seda sonriendo con su ramo junto al vestido'],
            ],
            'galleryTitle' => 'Así se ve en casa.',
            'band' => ['Solo maquillaje', 'No hacemos peinados. Si tu evento lo necesita, coordina con tu estilista el orden y la hora: te ayudamos a cuadrar los tiempos.'],
            'faq' => $faq,
            'faqTitle' => 'Antes de<br>abrir la puerta.',
            'related' => [
                ['/maquillaje-de-novia-bucaramanga', 'Maquillaje de novia en Bucaramanga'],
                ['/maquillaje-para-quinceaneras-bucaramanga', 'Maquillaje para quinceañeras'],
                ['/maquillaje-para-grados-bucaramanga', 'Maquillaje para grados'],
                ['/estudio-maquillaje-floridablanca', 'Estudio de maquillaje en Floridablanca'],
            ],
            'ctaTitle' => 'Pide tu fecha.',
            'schema' => Schema::service($p['name'], 'Maquillaje a domicilio en Bucaramanga, Floridablanca, Piedecuesta y Girón: ' . implode(', ', $p['includes']) . '.', (int) $p['price'], $path, 'Maquillaje a domicilio'),
        ]);
    }

    /** @param array<string,mixed> $d */
    private function landing(string $seoKey, string $path, array $d): Response
    {
        $service = $d['schema'];
        unset($d['schema']);
        return $this->page($seoKey, 'site/landing', [
            'packs' => Catalog::packs(),
            'path' => $path,
            'css' => ['pages/novias.css', 'pages/landing.css'],
            'preload' => ['key' => $d['cover'][0], 'sizes' => '100vw'],
            'schema' => [$service, Schema::faq($d['faq'])],
            'waOrigin' => $d['origin'],
        ] + $d);
    }
}
