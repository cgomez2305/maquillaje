<?php
declare(strict_types=1);

namespace App\Http;

/** Token sincronizado por sesión (§14.3). */
final class Csrf
{
    public static function token(): string
    {
        $t = Session::get('_csrf');
        if (!is_string($t) || strlen($t) < 40) {
            $t = random_token(32);
            Session::put('_csrf', $t);
        }
        return $t;
    }

    public static function verify(Request $request): bool
    {
        $sent = $request->post['_token'] ?? $request->header('X-CSRF-Token');
        $known = Session::get('_csrf');
        return is_string($sent) && is_string($known) && $known !== '' && hash_equals($known, $sent);
    }
}
