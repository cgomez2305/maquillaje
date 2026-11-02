<?php
declare(strict_types=1);

namespace App\Domain\Notifications;

use App\Domain\Audit\EventLog;

/**
 * Correos de reserva (§10). Cada envío queda como evento «correo_enviado» en el historial,
 * que es parte de la verificación independiente (§9.6). La interfaz Channel permite sumar
 * WhatsApp Cloud API en la v2 sin tocar a quienes llaman.
 */
final class BookingNotifier
{
    /** @param array<string,mixed> $b reserva (BookingService::SELECT) */
    public static function created(array $b): void
    {
        $data = self::data($b);
        self::toClient($b, 'reserva-clienta', 'Tu reserva ' . $b['code'] . ' · Make Up by Fletcher', $data, 'creada');
        $owner = Mailer::owner();
        if ($owner !== '') {
            Mailer::send($owner, 'Nueva reserva ' . $b['code'] . ' · ' . $b['client_name'], 'reserva-duena', $data, 'reserva-duena-' . $b['code']);
        }
    }

    public static function depositConfirmed(array $b): void
    {
        self::toClient($b, 'reserva-confirmada', 'Tu fecha está confirmada · ' . $b['code'], self::data($b), 'confirmada');
    }

    public static function reminder(array $b): bool
    {
        return self::toClient($b, 'reserva-recordatorio', 'Mañana es tu cita · ' . $b['code'], self::data($b), 'recordatorio');
    }

    public static function holdExpired(array $b): bool
    {
        return self::toClient($b, 'reserva-vencida', 'Liberamos el cupo de tu reserva ' . $b['code'], self::data($b), 'vencida');
    }

    public static function rescheduled(array $b): void
    {
        self::toClient($b, 'reserva-reprogramada', 'Nueva fecha para tu reserva ' . $b['code'], self::data($b), 'reprogramada-' . substr(sha1((string) $b['starts_at']), 0, 8));
    }

    public static function survey(array $b, string $token): bool
    {
        return self::toClient($b, 'encuesta', '¿Cómo te fue con tu maquillaje?', self::data($b) + ['surveyUrl' => url('/encuesta/' . $token)], 'encuesta');
    }

    /** @return array<string,mixed> */
    public static function data(array $b): array
    {
        $start = local_time($b['starts_at']);
        return [
            'b' => $b,
            'fecha' => $start ? fecha_larga($start) : '',
            'hora' => $start ? hora($start) : '',
            'lugar' => $b['location'] === 'home' ? 'A domicilio · ' . ($b['address'] ?? '') : 'Estudio · ' . config('app.business.address') . ', ' . config('app.business.city'),
            'statusUrl' => url('/reserva/' . $b['code'] . '?t=' . $b['public_token']),
            'waUrl' => wa_direct('Hola, envío el comprobante del abono de mi reserva ' . $b['code']),
            'pay' => config('app.payments'),
            'panelUrl' => url('/panel/reservas/' . $b['code']),
        ];
    }

    private static function toClient(array $b, string $template, string $subject, array $data, string $kind): bool
    {
        $to = (string) ($b['client_email'] ?? '');
        if ($to === '') {
            return false;
        }
        $key = 'b' . $b['id'] . '-' . $kind;
        $ok = Mailer::send($to, $subject, $template, $data, $key);
        if ($ok) {
            try {
                EventLog::record((int) $b['id'], 'correo_enviado', null, null, null, ['plantilla' => $template, 'para' => mask_email($to)]);
            } catch (\Throwable $e) {
                app_log('error', 'evento correo: ' . $e->getMessage());
            }
        }
        return $ok;
    }
}
