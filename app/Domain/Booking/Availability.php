<?php
declare(strict_types=1);

namespace App\Domain\Booking;

use App\Support\Db;
use App\Support\Settings;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Disponibilidad de la agenda (§7.1): una sola maquilladora, citas que no se solapan.
 * Ocupan agenda: reservas solicitadas con retención vigente, confirmadas y reprogramadas;
 * bloqueos manuales (blocked_slots); festivos; días de la semana cerrados; mínimo 2 días de anticipación.
 */
final class Availability
{
    public const ACTIVE_SQL = "(status IN ('confirmada','reprogramada') OR (status = 'solicitada' AND (hold_expires_at IS NULL OR hold_expires_at > ?)))";

    public static function tz(): DateTimeZone
    {
        return new DateTimeZone((string) config('app.timezone', 'America/Bogota'));
    }

    public static function durationMinutes(int $people): int
    {
        $people = max(1, min((int) config('app.booking.max_people', 4), $people));
        return max((int) config('app.booking.slot_minutes', 120), $people * (int) config('app.booking.minutes_per_person', 90));
    }

    /** Primer día reservable (hora local). */
    public static function firstDay(?DateTimeImmutable $now = null): DateTimeImmutable
    {
        $now ??= new DateTimeImmutable('now', self::tz());
        return $now->setTimezone(self::tz())->setTime(0, 0)->modify('+' . (int) config('app.booking.min_days_ahead', 2) . ' days');
    }

    public static function lastDay(?DateTimeImmutable $now = null): DateTimeImmutable
    {
        $now ??= new DateTimeImmutable('now', self::tz());
        return $now->setTimezone(self::tz())->setTime(0, 0)->modify('+' . (int) config('app.booking.max_days_ahead', 540) . ' days');
    }

    /**
     * Estado de cada día de un mes: Y-m-d => ['available' => bool, 'reason' => ?string].
     *
     * @return array<string,array{available:bool,reason:?string}>
     */
    public static function month(int $year, int $month, int $people = 1, ?int $ignoreBookingId = null): array
    {
        $tz = self::tz();
        $start = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month), $tz);
        $end = $start->modify('first day of next month');
        $busy = self::busyIntervals($start, $end, $ignoreBookingId);
        $out = [];
        for ($d = $start; $d < $end; $d = $d->modify('+1 day')) {
            $key = $d->format('Y-m-d');
            $reason = self::dayClosedReason($d);
            if ($reason === null) {
                $free = self::freeSlotsFor($d, $people, $busy);
                $reason = $free === [] ? 'ocupado' : null;
            }
            $out[$key] = ['available' => $reason === null, 'reason' => $reason];
        }
        return $out;
    }

    /** @return list<string> horas libres «HH:MM» de un día */
    public static function slotsForDay(string $ymd, int $people = 1, ?int $ignoreBookingId = null): array
    {
        $tz = self::tz();
        $day = DateTimeImmutable::createFromFormat('!Y-m-d', $ymd, $tz);
        if ($day === false || $day->format('Y-m-d') !== $ymd || self::dayClosedReason($day) !== null) {
            return [];
        }
        $busy = self::busyIntervals($day, $day->modify('+1 day'), $ignoreBookingId);
        return self::freeSlotsFor($day, $people, $busy);
    }

    public static function isSlotFree(string $ymd, string $hhmm, int $people, ?int $ignoreBookingId = null): bool
    {
        return in_array($hhmm, self::slotsForDay($ymd, $people, $ignoreBookingId), true);
    }

    /** Motivo por el que el día completo no se puede reservar, o null. */
    public static function dayClosedReason(DateTimeImmutable $day): ?string
    {
        $ymd = $day->format('Y-m-d');
        if ($day < self::firstDay()) {
            return 'anticipacion';
        }
        if ($day > self::lastDay()) {
            return 'fuera_de_rango';
        }
        if (Holidays::name($ymd) !== null && !in_array($ymd, (array) Settings::get('open_holidays', []), true)) {
            return 'festivo';
        }
        if (in_array((int) $day->format('N'), array_map('intval', (array) Settings::get('closed_weekdays', [])), true)) {
            return 'cerrado';
        }
        return null;
    }

    /**
     * @param list<array{0:int,1:int}> $busy intervalos [inicio, fin) en timestamps
     * @return list<string>
     */
    private static function freeSlotsFor(DateTimeImmutable $day, int $people, array $busy): array
    {
        $dur = self::durationMinutes($people) * 60;
        $free = [];
        foreach (Settings::slots() as $slot) {
            [$h, $m] = array_map('intval', explode(':', $slot));
            $s = $day->setTime($h, $m)->getTimestamp();
            $e = $s + $dur;
            $clash = false;
            foreach ($busy as [$bs, $be]) {
                if ($s < $be && $bs < $e) {
                    $clash = true;
                    break;
                }
            }
            if (!$clash) {
                $free[] = $slot;
            }
        }
        return $free;
    }

    /** @return list<array{0:int,1:int}> */
    private static function busyIntervals(DateTimeImmutable $from, DateTimeImmutable $to, ?int $ignoreBookingId): array
    {
        $fromUtc = $from->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $toUtc = $to->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $now = gmdate('Y-m-d H:i:s');
        $out = [];
        try {
            $rows = Db::all(
                'SELECT starts_at, ends_at FROM bookings WHERE starts_at < ? AND ends_at > ? AND id <> ? AND ' . self::ACTIVE_SQL,
                [$toUtc, $fromUtc, $ignoreBookingId ?? 0, $now],
            );
            $rows = array_merge($rows, Db::all('SELECT starts_at, ends_at FROM blocked_slots WHERE starts_at < ? AND ends_at > ?', [$toUtc, $fromUtc]));
        } catch (\Throwable) {
            $rows = [];
        }
        foreach ($rows as $r) {
            $out[] = [strtotime($r['starts_at'] . ' UTC'), strtotime($r['ends_at'] . ' UTC')];
        }
        return $out;
    }
}
