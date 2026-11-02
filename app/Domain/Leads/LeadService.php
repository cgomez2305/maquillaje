<?php
declare(strict_types=1);

namespace App\Domain\Leads;

use App\Domain\Attribution\AttributionService;
use App\Support\Counter;
use App\Support\Db;
use App\Support\Metrics;

/**
 * Leads con código WEB-AAMM-NNNN (§9.3): clics a WhatsApp, cotizaciones del estudio y guía para novias.
 */
final class LeadService
{
    /** @return array{id:int,code:string} */
    public static function create(string $kind, array $data = []): array
    {
        if (!in_array($kind, ['cotizacion', 'guia_novias', 'whatsapp_click'], true)) {
            throw new \InvalidArgumentException('Tipo de lead no válido');
        }
        return Db::transaction(static function () use ($kind, $data): array {
            $code = Counter::code('WEB');
            $utm = AttributionService::current();
            $id = Db::insert(
                'INSERT INTO leads (code, kind, name, phone_e164, email, payload, utm, origin, confirm_token_hash, consent_at, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $code, $kind,
                    $data['name'] ?? null, $data['phone_e164'] ?? null, $data['email'] ?? null,
                    isset($data['payload']) ? json_encode($data['payload'], JSON_UNESCAPED_UNICODE) : null,
                    $utm ? json_encode($utm, JSON_UNESCAPED_UNICODE) : null,
                    isset($data['origin']) ? mb_substr((string) $data['origin'], 0, 60) : null,
                    $data['confirm_token_hash'] ?? null,
                    $data['consent_at'] ?? null,
                    now_utc(),
                ],
            );
            Metrics::bump($kind === 'whatsapp_click' ? 'whatsapp_clicks' : ($kind === 'cotizacion' ? 'quotes' : 'guide_signups'));
            return ['id' => $id, 'code' => $code];
        });
    }

    /** Mensaje prellenado de WhatsApp con el código del lead. */
    public static function whatsappText(string $code, ?string $topic = null): string
    {
        $topics = [
            'novias' => 'quiero información del maquillaje de novia',
            'quince' => 'quiero información del maquillaje para quince años',
            'grados' => 'quiero información del maquillaje para grados',
            'domicilio' => 'quiero pedir un servicio a domicilio',
            'cursos' => 'quiero información de los cursos',
            'tienda' => 'quiero pedir un producto de la tienda',
            'estudio' => 'quiero agendar en el estudio',
        ];
        $text = 'Hola, vengo de la web · ' . $code;
        if ($topic !== null && isset($topics[$topic])) {
            $text .= '. ' . ucfirst($topics[$topic]) . '.';
        }
        return $text;
    }
}
