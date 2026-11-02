<?php
declare(strict_types=1);

use App\Support\Env;

/**
 * Parámetros del negocio y de la aplicación. Sin secretos (esos van en .env).
 * Los valores entre corchetes son datos pendientes de la marca (README §15).
 */
return [
    'name' => 'Make Up by Fletcher',
    'short_name' => 'Fletcher',
    'env' => Env::get('APP_ENV', 'production'),
    'debug' => filter_var(Env::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    'url' => rtrim((string) Env::get('APP_URL', 'https://maquillajebucaramanga.com'), '/'),
    'key' => Env::get('APP_KEY', ''),
    'timezone' => 'America/Bogota',
    'locale' => 'es_CO',

    'business' => [
        'legal_name' => 'Make Up by Fletcher',
        'owner' => 'María Paula Fletcher',
        'tagline' => 'Fletcher · Make up artist',
        'address' => 'Carrera 29 #158-08',
        'city' => 'Floridablanca',
        'region' => 'Santander',
        'country' => 'CO',
        'postal_code' => '681004',
        'geo' => null, // [lat, lng] pendiente (README §15)
        'phone_display' => '+57 316 191 0936',
        'phone_e164' => '+573161910936',
        'whatsapp_number' => '573161910936',
        'whatsapp_link' => 'https://wa.me/message/H3JAJC64G4RQM1',
        'email' => 'makeupbyfletcher@gmail.com',
        'instagram' => 'https://www.instagram.com/makeupbyfletcher/',
        'tiktok' => 'https://www.tiktok.com/@makeupbyfletcher',
        'facebook' => 'https://www.facebook.com/makeupbyfletcher',
        'google_review_url' => '[Enlace de reseña de Google Business Profile]',
        'maps_url' => 'https://www.google.com/maps/search/?api=1&query=Carrera+29+%23158-08+Floridablanca+Santander',
        'hours_text' => '[Horario de atención pendiente]',
        'opening_hours' => [], // pendiente: [['days'=>['Monday',…],'opens'=>'08:00','closes'=>'18:00']]
        'area_served' => ['Bucaramanga', 'Floridablanca', 'Piedecuesta', 'Girón'],
        'price_range' => '$$',
    ],

    'booking' => [
        'min_days_ahead' => 2,          // §1.3
        'max_days_ahead' => 540,
        'hold_hours' => 48,             // retención del cupo sin abono
        'deposit_percent' => 50,
        'change_notice_days' => 1,
        // Horas de inicio ofrecidas (hora de Bogotá). Ajustables desde el panel (tabla settings).
        'slots' => ['05:00', '07:00', '09:00', '11:00', '14:00', '16:00'],
        'slot_minutes' => 120,          // duración mínima de una cita
        'minutes_per_person' => 90,     // duración = max(slot_minutes, personas × minutes_per_person)
        'max_people' => 4,              // «4 o más» se registra como 4 y la nota aclara
        'event_types' => [
            'boda' => 'Boda',
            'quince' => 'Quince años',
            'grado' => 'Grado',
            'social' => 'Evento social',
            'sesion' => 'Sesión de fotos',
            'otro' => 'Otro',
        ],
        'how_found' => [
            'google' => 'Google',
            'instagram' => 'Instagram',
            'tiktok' => 'TikTok',
            'recomendacion' => 'Recomendación',
            'otro' => 'Otro',
        ],
    ],

    // Datos del abono (§1.3): NO se publican en la web; solo van en el correo de confirmación.
    'payments' => [
        'nequi' => (Env::get('PAY_NEQUI') ?: '[Número de Nequi pendiente]'),
        'bancolombia' => (Env::get('PAY_BANCOLOMBIA') ?: '[Cuenta Bancolombia pendiente]'),
        'holder' => (Env::get('PAY_HOLDER') ?: '[Titular de la cuenta pendiente]'),
    ],

    // §9.3 · deben coincidir con el acuerdo escrito entre las partes
    'attribution' => [
        'window_months' => 6,
        'cookie_days' => 30,
        'cookie_name' => 'mubf_src',
        'web_sources' => ['web_form', 'whatsapp_web', 'google_business', 'web_returning'],
    ],

    // §9.4
    'commission' => [
        'no_show_commissions_deposit' => true,
        'alert_unmarked_after_hours' => 72,
    ],

    'security' => [
        'session_idle_minutes' => 30,
        'session_absolute_hours' => 12,
        'password_min_length' => 12,
        'max_login_attempts' => 5,
        'form_min_seconds' => 3,
        'form_rate_limit' => [5, 600], // 5 envíos por IP cada 10 min
        'upload_max_bytes' => 5 * 1024 * 1024,
    ],

    'analytics' => [
        'gtm_id' => Env::get('GTM_ID', ''),
        'ga4_id' => Env::get('GA4_MEASUREMENT_ID', ''),
    ],
];
