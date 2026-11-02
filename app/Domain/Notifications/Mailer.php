<?php
declare(strict_types=1);

namespace App\Domain\Notifications;

use App\Support\Db;
use App\View;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Correo transaccional por SMTP (PHPMailer). Plantillas en app/Views/emails/<plantilla>.php
 * dentro de emails/layout. Idempotente con $dedupeKey (tabla mail_log): un correo con la misma
 * clave no se envía dos veces, para que los cron se puedan reintentar sin duplicar.
 *
 * MAIL_DRIVER=log escribe el correo en storage/logs/mail.log (desarrollo y pruebas).
 */
final class Mailer
{
    /** @var list<array{to:string,subject:string,template:string,html:string}> correos enviados en esta petición (pruebas) */
    public static array $sent = [];

    public static function send(string $to, string $subject, string $template, array $data = [], ?string $dedupeKey = null, ?string $replyTo = null): bool
    {
        $to = trim($to);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        if ($dedupeKey !== null && self::alreadySent($dedupeKey)) {
            return true;
        }
        $html = View::render('emails/' . $template, $data + ['subject' => $subject], 'email');
        $text = self::toText($html);
        $ok = true;
        $error = null;

        try {
            if (env('MAIL_DRIVER', 'smtp') === 'log') {
                @file_put_contents(
                    BASE_PATH . '/storage/logs/mail.log',
                    sprintf("=== %s\nPara: %s\nAsunto: %s\n\n%s\n\n", gmdate('c'), $to, $subject, $text),
                    FILE_APPEND | LOCK_EX,
                );
            } else {
                $m = new PHPMailer(true);
                $m->isSMTP();
                $m->Host = (string) env('MAIL_HOST', 'smtp.hostinger.com');
                $m->Port = (int) env('MAIL_PORT', '465');
                $m->SMTPAuth = true;
                $m->Username = (string) env('MAIL_USER', '');
                $m->Password = (string) env('MAIL_PASS', '');
                $m->SMTPSecure = env('MAIL_ENCRYPTION', 'ssl') === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
                $m->CharSet = 'UTF-8';
                $m->setFrom((string) env('MAIL_FROM', 'reservas@maquillajebucaramanga.com'), (string) env('MAIL_FROM_NAME', 'Make Up by Fletcher'));
                $m->addAddress($to);
                $m->addReplyTo($replyTo ?? (string) config('app.business.email'));
                $m->Subject = $subject;
                $m->isHTML(true);
                $m->Body = $html;
                $m->AltBody = $text;
                $m->send();
            }
        } catch (\Throwable $e) {
            $ok = false;
            $error = mb_substr($e->getMessage(), 0, 500);
            app_log('error', 'Correo fallido', ['to' => $to, 'template' => $template, 'error' => $error]);
        }

        self::$sent[] = ['to' => $to, 'subject' => $subject, 'template' => $template, 'html' => $html];
        self::log($dedupeKey, $to, $subject, $template, $ok, $error);
        return $ok;
    }

    public static function owner(): string
    {
        return (string) env('OWNER_EMAIL', (string) config('app.business.email'));
    }

    public static function partner(): string
    {
        return (string) env('PARTNER_EMAIL', '');
    }

    private static function alreadySent(string $key): bool
    {
        try {
            return (bool) Db::value("SELECT 1 FROM mail_log WHERE dedupe_key = ? AND status = 'enviado'", [$key]);
        } catch (\Throwable) {
            return false;
        }
    }

    private static function log(?string $key, string $to, string $subject, string $template, bool $ok, ?string $error): void
    {
        try {
            Db::run(
                'INSERT INTO mail_log (dedupe_key, to_email, subject, template, status, error, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE status = VALUES(status), error = VALUES(error), created_at = VALUES(created_at)',
                [$key ?? ('x-' . random_token(16)), $to, mb_substr($subject, 0, 200), $template, $ok ? 'enviado' : 'fallido', $error, now_utc()],
            );
        } catch (\Throwable) {
        }
    }

    private static function toText(string $html): string
    {
        $html = preg_replace('#<(br|/p|/h[1-6]|/tr|/li)\s*/?>#i', "\n", $html) ?? $html;
        $html = preg_replace('#<a [^>]*href="([^"]+)"[^>]*>(.*?)</a>#is', '$2 ($1)', $html) ?? $html;
        $text = html_entity_decode(strip_tags(preg_replace('#<(style|head)[^>]*>.*?</\1>#is', '', $html) ?? ''), ENT_QUOTES, 'UTF-8');
        return trim(preg_replace("/\n{3,}/", "\n\n", preg_replace('/[ \t]+/', ' ', $text) ?? '') ?? '');
    }
}
