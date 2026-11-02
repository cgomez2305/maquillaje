<?php
declare(strict_types=1);

namespace App\Domain\Surveys;

use App\Domain\Alerts\AlertService;
use App\Domain\Audit\EventLog;
use App\Support\Db;

/**
 * Encuesta post-servicio (§9.6): se envía 24 h después de la cita. Si la clienta dice que sí
 * recibió el servicio y la reserva figura como cancelada, no asistió o vencida, alerta al socio.
 */
final class SurveyService
{
    public const CONTRADICTORY = ['cancelada', 'no_asistio', 'vencida'];

    /** Crea la encuesta de una reserva. Devuelve el token, o null si ya existía. */
    public static function create(int $bookingId): ?string
    {
        $token = random_token(32);
        try {
            Db::run('INSERT INTO surveys (booking_id, token) VALUES (?, ?)', [$bookingId, $token]);
        } catch (\PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                return null;
            }
            throw $e;
        }
        return $token;
    }

    /** @return array<string,mixed>|null encuesta + datos de la reserva */
    public static function findByToken(string $token): ?array
    {
        if (!preg_match('/^[A-Za-z0-9_-]{43}$/', $token)) {
            return null;
        }
        $row = Db::one(
            'SELECT s.*, b.code, b.status AS booking_status, b.starts_at, b.client_id, s2.code AS service_code, s2.name AS service_name, c.name AS client_name
             FROM surveys s JOIN bookings b ON b.id = s.booking_id JOIN services s2 ON s2.id = b.service_id JOIN clients c ON c.id = b.client_id
             WHERE s.token = ?',
            [$token],
        );
        return ($row !== null && hash_equals((string) $row['token'], $token)) ? $row : null;
    }

    /**
     * Guarda la respuesta (una sola vez). Devuelve false si ya estaba respondida.
     */
    public static function answer(array $survey, int $rating, bool $attended, ?string $comment, ?string $ip = null, ?string $ua = null): bool
    {
        $rating = max(1, min(5, $rating));
        $comment = $comment !== null && trim($comment) !== '' ? mb_substr(trim($comment), 0, 1000) : null;
        $saved = Db::transaction(static function () use ($survey, $rating, $attended, $comment, $ip, $ua): bool {
            $n = Db::run(
                'UPDATE surveys SET answered_at = ?, rating = ?, attended = ?, comment = ? WHERE id = ? AND answered_at IS NULL',
                [now_utc(), $rating, $attended ? 1 : 0, $comment, (int) $survey['id']],
            )->rowCount();
            if ($n !== 1) {
                return false;
            }
            EventLog::record((int) $survey['booking_id'], 'encuesta_respondida', null, null, null, [
                'calificacion' => $rating, 'asistio' => $attended ? 1 : 0, 'comentario' => $comment !== null ? 1 : 0,
            ], null, $ip, $ua);
            return true;
        });
        if ($saved && $attended) {
            $status = (string) Db::value('SELECT status FROM bookings WHERE id = ?', [(int) $survey['booking_id']]);
            if (in_array($status, self::CONTRADICTORY, true)) {
                AlertService::raise(
                    'encuesta_contradictoria',
                    'La clienta de ' . $survey['code'] . ' respondió en la encuesta que sí recibió el servicio, pero la reserva figura como «' . str_replace('_', ' ', $status) . '».',
                    'encuesta-' . $survey['code'],
                    (int) $survey['booking_id'],
                    (int) $survey['client_id'],
                    ['estado' => $status, 'calificacion' => $rating],
                );
            }
        }
        return $saved;
    }
}
