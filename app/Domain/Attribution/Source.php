<?php
declare(strict_types=1);

namespace App\Domain\Attribution;

/** Origen de una reserva (§9.3). */
enum Source: string
{
    case WEB_FORM = 'web_form';
    case WHATSAPP_WEB = 'whatsapp_web';
    case GOOGLE_BUSINESS = 'google_business';
    case WEB_RETURNING = 'web_returning';
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::WEB_FORM => 'Formulario web',
            self::WHATSAPP_WEB => 'WhatsApp desde la web',
            self::GOOGLE_BUSINESS => 'Google Business',
            self::WEB_RETURNING => 'Clienta web recurrente',
            self::MANUAL => 'Manual (fuera de la web)',
        };
    }

    public function isWeb(): bool
    {
        return in_array($this->value, (array) config('app.attribution.web_sources', []), true);
    }
}
