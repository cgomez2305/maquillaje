<?php
declare(strict_types=1);

/**
 * Catálogo 2026 (README §1.1 y §1.2). Es la fuente de las semillas de la BD;
 * en producción los precios vigentes se leen de la tabla services (editable en Ajustes).
 */
return [
    'services' => [
        'pack1' => [
            'name' => 'Pack 1 · Servicio base (maquillaje social)',
            'short' => 'Maquillaje social',
            'price' => 200000,
            'category' => 'service',
            'includes' => [
                'Asesoría previa (análisis de rasgos)',
                'Maquillaje social',
                'Pestañas postizas de un solo uso',
            ],
        ],
        'pack2' => [
            'name' => 'Pack 2 · Full experience a domicilio',
            'short' => 'Full experience a domicilio',
            'price' => 250000,
            'category' => 'service',
            'includes' => [
                'Asesoría previa',
                'Sesión de relajación y preparación de piel: mascarilla húmeda, masaje facial, esencias aromáticas y masaje de manos con cera tibia',
                'Música relajante y ventilador de mano',
                'Maquillaje en silla profesional con luz de estudio',
                'Pestañas postizas',
                'Kit de retoque: papel de arroz, brillo labial y espejo de bolsillo',
            ],
        ],
        'pack3' => [
            'name' => 'Pack 3 · Novias y quinceañeras',
            'short' => 'Novias y quinceañeras',
            'price' => 600000,
            'category' => 'service',
            'includes' => [
                'Todo lo del Pack 2',
                'Prueba de maquillaje días antes con 2 propuestas',
                'Maquillaje el día del evento',
                'Video reel de la preparación',
                'Extra fijación',
            ],
        ],
    ],
    'courses' => [
        'curso-auto' => [
            'name' => 'Automaquillaje',
            'price' => 400000,
            'category' => 'course',
            'format' => '5 módulos de 1 a 1,5 h',
            'modules' => ['Teoría básica', 'Piel', 'Ojos día', 'Ojos noche', 'Ojos glam', 'Cejas', 'Labios'],
        ],
        'curso-pro' => [
            'name' => 'Maquillaje profesional',
            'price' => 1500000,
            'category' => 'course',
            'format' => '7 módulos de 2 a 3 h',
            'modules' => ['Teoría básica', 'Piel', 'Social día, noche y glam', 'Novias', 'Quinceañeras', 'Editorial', 'Sesión de fotos', 'Cejas', 'Labios'],
            'requires_model' => true,
        ],
    ],
];
