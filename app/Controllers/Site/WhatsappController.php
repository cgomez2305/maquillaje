<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Domain\Leads\LeadService;
use App\Http\RateLimiter;
use App\Http\Request;
use App\Http\Response;

/**
 * /wa?o=origen&t=tema → crea un lead whatsapp_click con código WEB-… y redirige a wa.me
 * con el mensaje prellenado que incluye el código (§9.3). Si algo falla, redirige igual sin código.
 */
final class WhatsappController extends Controller
{
    private const BOT_UA = '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|curl|wget|python|headless|lighthouse/i';

    public function go(Request $request): Response
    {
        $origin = preg_replace('/[^a-z0-9-]/', '', strtolower($request->str('o', 60))) ?: 'general';
        $topic = preg_replace('/[^a-z]/', '', strtolower($request->str('t', 20))) ?: null;
        $text = 'Hola, vengo de la web';

        $isBot = preg_match(self::BOT_UA, $request->userAgent()) === 1 || $request->userAgent() === '';
        if (!$isBot && RateLimiter::hit('wa:' . $request->ip(), 20, 600)) {
            try {
                $lead = LeadService::create('whatsapp_click', ['origin' => $origin, 'payload' => ['topic' => $topic]]);
                $text = LeadService::whatsappText($lead['code'], $topic);
            } catch (\Throwable $e) {
                app_log('warning', 'No se pudo crear el lead de WhatsApp: ' . $e->getMessage());
            }
        }

        return Response::redirect(wa_direct($text), 302)
            ->header('Cache-Control', 'no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
