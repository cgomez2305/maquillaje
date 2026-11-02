<?php
declare(strict_types=1);

namespace App\Domain\Booking;

/**
 * Estados de una reserva (§9.1):
 *
 * solicitada ──abono──► confirmada ──fecha cumplida──► realizada
 *     │                     │  ├──► no_asistio (motivo)
 *     │                     │  └──► reprogramada ──► confirmada (nueva fecha, mismo código)
 *     ├──48 h sin abono──► vencida
 *     └──► cancelada (motivo; también desde confirmada)
 */
final class BookingStateMachine
{
    public const LABELS = [
        'solicitada' => 'Solicitada',
        'confirmada' => 'Confirmada',
        'realizada' => 'Realizada',
        'cancelada' => 'Cancelada',
        'no_asistio' => 'No asistió',
        'vencida' => 'Vencida',
        'reprogramada' => 'Reprogramada',
    ];

    /** Clase CSS del chip de estado en el panel. */
    public const CHIP = [
        'solicitada' => 'sol', 'confirmada' => 'con', 'realizada' => 'rea', 'cancelada' => 'can',
        'no_asistio' => 'can', 'vencida' => 'ven', 'reprogramada' => 'sol',
    ];

    private const TRANSITIONS = [
        'solicitada' => ['confirmada', 'cancelada', 'vencida'],
        'confirmada' => ['realizada', 'no_asistio', 'reprogramada', 'cancelada'],
        'reprogramada' => ['confirmada', 'cancelada'],
        'realizada' => [],
        'cancelada' => [],
        'no_asistio' => [],
        'vencida' => [],
    ];

    private const REASON_REQUIRED = ['cancelada', 'no_asistio'];

    /** @return list<string> */
    public static function next(string $from): array
    {
        return self::TRANSITIONS[$from] ?? [];
    }

    public static function requiresReason(string $to): bool
    {
        return in_array($to, self::REASON_REQUIRED, true);
    }

    /**
     * Valida una transición. Devuelve null si es válida o el motivo del rechazo.
     *
     * @param array{status:string,starts_at:string} $booking
     */
    public static function check(array $booking, string $to, ?string $reason, ?\DateTimeImmutable $nowLocal = null): ?string
    {
        $from = $booking['status'];
        if (!in_array($to, self::next($from), true)) {
            return 'No se puede pasar de «' . (self::LABELS[$from] ?? $from) . '» a «' . (self::LABELS[$to] ?? $to) . '».';
        }
        if (self::requiresReason($to) && trim((string) $reason) === '') {
            return 'Escribe el motivo: es obligatorio para «' . self::LABELS[$to] . '».';
        }
        if ($to === 'realizada') {
            $nowLocal ??= new \DateTimeImmutable('now', Availability::tz());
            $startDay = local_time($booking['starts_at'])?->format('Y-m-d');
            if ($startDay !== null && $nowLocal->format('Y-m-d') < $startDay) {
                return 'Solo se puede marcar como realizada el mismo día del servicio o después.';
            }
        }
        if ($to === 'no_asistio') {
            $nowLocal ??= new \DateTimeImmutable('now', Availability::tz());
            $start = local_time($booking['starts_at']);
            if ($start !== null && $nowLocal < $start) {
                return 'Solo se puede marcar «No asistió» después de la hora de la cita.';
            }
        }
        return null;
    }
}
