<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class LegalController extends Controller
{
    /** Fecha de vigencia de los textos legales (actualizar cuando cambien). */
    private const VIGENCIA = '2026-11-01';

    public function privacidad(Request $request): Response
    {
        return $this->page('privacidad', 'site/legal-privacidad', $this->data('privacidad'));
    }

    public function terminos(Request $request): Response
    {
        return $this->page('terminos', 'site/legal-terminos', $this->data('terminos'));
    }

    private function data(string $key): array
    {
        return [
            'css' => ['pages/legal.css'],
            'vigencia' => fecha_larga(new \DateTimeImmutable(self::VIGENCIA), false),
            'b' => config('app.business'),
            'waOrigin' => $key,
        ];
    }
}
