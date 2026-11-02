<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Domain\Surveys\SurveyService;
use App\Http\FormGuard;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;

/** /encuesta/{token}: encuesta post-servicio (§9.6). Una sola respuesta. */
final class EncuestaController extends Controller
{
    public function index(Request $request): Response
    {
        $survey = SurveyService::findByToken($request->param('token'));
        if ($survey === null) {
            throw new HttpException(404);
        }
        $errors = [];
        $old = [];
        $justAnswered = false;
        if ($request->isPost() && $survey['answered_at'] === null) {
            $old = ['rating' => $request->int('rating'), 'attended' => $request->str('attended', 3), 'comment' => $request->str('comment', 1000)];
            if ($old['rating'] < 1 || $old['rating'] > 5) {
                $errors['rating'] = 'Elige una calificación de 1 a 5.';
            }
            if (!in_array($old['attended'], ['si', 'no'], true)) {
                $errors['attended'] = 'Cuéntanos si recibiste el servicio.';
            }
            if ($errors === []) {
                $spam = FormGuard::check($request, 'encuesta');
                if ($spam !== null) {
                    $errors['form'] = $spam;
                }
            }
            if ($errors === []) {
                SurveyService::answer($survey, $old['rating'], $old['attended'] === 'si', $old['comment'], $request->ip(), $request->userAgent());
                return Response::redirect('/encuesta/' . $survey['token'] . '?gracias=1', 303);
            }
        }
        if ($survey['answered_at'] !== null) {
            $justAnswered = $request->str('gracias', 1) === '1';
        }
        $start = local_time($survey['starts_at']);
        $review = (string) config('app.business.google_review_url', '');
        return $this->page('reservar', 'site/encuesta', [
            's' => $survey,
            'errors' => $errors,
            'old' => $old,
            'answered' => $survey['answered_at'] !== null,
            'justAnswered' => $justAnswered,
            'fecha' => $start ? fecha_larga($start, false) : '',
            'reviewUrl' => str_starts_with($review, 'https://') ? $review : null,
            'firstName' => explode(' ', (string) $survey['client_name'])[0],
            'meta' => ['title' => '¿Cómo te fue? · Make Up by Fletcher', 'description' => 'Encuesta post-servicio de Make Up by Fletcher.', 'robots' => 'noindex, nofollow', 'canonical' => '/'],
            'crumbs' => [],
            'css' => ['pages/reservar.css', 'pages/encuesta.css'],
            'focus' => true,
            'hideFab' => true,
        ], $errors ? 422 : 200);
    }
}
