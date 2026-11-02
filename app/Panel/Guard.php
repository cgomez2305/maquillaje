<?php
declare(strict_types=1);

namespace App\Panel;

use App\Http\Request;
use App\Http\Response;
use App\Http\Session;
use App\Kernel;

/**
 * Middleware del panel: sesión válida, 2FA completado, caducidades y rol permitido
 * verificados en el servidor para cada ruta (§9.7).
 */
final class Guard
{
    public static function handle(Request $request, array $options): ?Response
    {
        Session::start();
        $now = time();
        $idle = 60 * (int) config('app.security.session_idle_minutes', 30);
        $absolute = 3600 * (int) config('app.security.session_absolute_hours', 12);
        $seen = Session::get('seen_at');
        $loginAt = Session::get('login_at');

        if (is_int($seen) && ($now - $seen > $idle || !is_int($loginAt) || $now - $loginAt > $absolute)) {
            Session::destroy();
            Session::start();
            Session::flash('notice', 'Tu sesión caducó por seguridad. Vuelve a entrar.');
            return Response::redirect('/panel/login', 303);
        }

        $user = Auth::user();
        if ($user === null) {
            if ($request->method === 'GET') {
                Session::put('intended', $request->path);
            }
            return Response::redirect(Session::get('uid') ? '/panel/login/2fa' : '/panel/login', 303);
        }
        Session::put('seen_at', $now);

        if (Session::get('setup_only') === true && empty($options['allow_setup'])) {
            return Response::redirect('/panel/seguridad', 303);
        }

        $roles = $options['roles'] ?? [];
        if ($roles !== [] && !in_array($user['role'], $roles, true)) {
            return Kernel::errorPage(403, $request);
        }
        return null;
    }
}
