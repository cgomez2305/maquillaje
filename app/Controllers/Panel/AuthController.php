<?php
declare(strict_types=1);

namespace App\Controllers\Panel;

use App\Controllers\Controller;
use App\Domain\Audit\AuditLog;
use App\Domain\Notifications\Mailer;
use App\Http\RateLimiter;
use App\Http\Request;
use App\Http\Response;
use App\Http\Session;
use App\Panel\Auth;
use App\Support\Crypto;
use App\Support\Db;
use App\Support\Totp;

/** Acceso al panel: usuario + contraseña + 2FA, recuperación y seguridad de la cuenta (§14.2). */
final class AuthController extends Controller
{
    public function login(Request $request): Response
    {
        if (Auth::user() !== null && Session::get('setup_only') !== true) {
            return Response::redirect('/panel', 303);
        }
        $error = null;
        $email = '';
        if ($request->isPost()) {
            $email = $request->str('email', 190);
            $error = Auth::attempt($email, (string) ($request->post['password'] ?? ''), $request);
            if ($error === null) {
                if (Session::get('2fa_ok') === true) {
                    return Response::redirect(Session::get('setup_only') ? '/panel/seguridad' : $this->intended(), 303);
                }
                return Response::redirect('/panel/login/2fa', 303);
            }
        }
        return $this->view('panel/login', ['title' => 'Entrar al panel', 'error' => $error, 'email' => $email], 'panel-bare', $error ? 422 : 200);
    }

    public function twoFactor(Request $request): Response
    {
        if (!is_int(Session::get('uid'))) {
            return Response::redirect('/panel/login', 303);
        }
        if (Session::get('2fa_ok') === true) {
            return Response::redirect('/panel', 303);
        }
        $error = null;
        if ($request->isPost()) {
            if (Auth::verifySecondFactor($request->str('code', 20), $request)) {
                return Response::redirect($this->intended(), 303);
            }
            $error = 'Código incorrecto o vencido. Usa el código actual de tu app o un código de respaldo.';
        }
        return $this->view('panel/login-2fa', ['title' => 'Verificación en dos pasos', 'error' => $error], 'panel-bare', $error ? 422 : 200);
    }

    public function logout(Request $request): Response
    {
        Auth::logout($request);
        Session::start();
        Session::flash('notice', 'Cerraste sesión.');
        return Response::redirect('/panel/login', 303);
    }

    public function forgot(Request $request): Response
    {
        $sent = false;
        if ($request->isPost()) {
            $sent = true; // respuesta idéntica exista o no la cuenta
            $email = mb_strtolower($request->str('email', 190));
            if (RateLimiter::hit('reset:' . $request->ip(), 5, 3600) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $u = Db::one('SELECT id, name, email FROM users WHERE email = ? AND active = 1', [$email]);
                if ($u !== null) {
                    $token = random_token(32);
                    Db::run('UPDATE password_resets SET used_at = ? WHERE user_id = ? AND used_at IS NULL', [now_utc(), (int) $u['id']]);
                    Db::run(
                        'INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?)',
                        [(int) $u['id'], hash('sha256', $token), gmdate('Y-m-d H:i:s', time() + 1800), now_utc()],
                    );
                    Mailer::send($u['email'], 'Restablecer tu contraseña del panel', 'panel-reset', ['name' => $u['name'], 'url' => url('/panel/recuperar/' . $token)]);
                    AuditLog::record((int) $u['id'], 'recuperacion_solicitada', null, null, $request->ip());
                }
            }
        }
        return $this->view('panel/forgot', ['title' => 'Recuperar acceso', 'sent' => $sent], 'panel-bare');
    }

    public function reset(Request $request): Response
    {
        $token = $request->param('token');
        $row = Db::one(
            'SELECT r.id, r.user_id FROM password_resets r WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > ?',
            [hash('sha256', $token), now_utc()],
        );
        if ($row === null) {
            return $this->view('panel/reset', ['title' => 'Enlace vencido', 'invalid' => true, 'error' => null], 'panel-bare', 410);
        }
        $error = null;
        if ($request->isPost()) {
            $error = $this->passwordError((string) ($request->post['password'] ?? ''), (string) ($request->post['password_confirm'] ?? ''));
            if ($error === null) {
                Db::transaction(static function () use ($row, $request): void {
                    Db::run('UPDATE users SET password_hash = ?, failed_logins = 0, locked_until = NULL, updated_at = ? WHERE id = ?', [password_hash((string) $request->post['password'], PASSWORD_ARGON2ID), now_utc(), (int) $row['user_id']]);
                    Db::run('UPDATE password_resets SET used_at = ? WHERE id = ?', [now_utc(), (int) $row['id']]);
                });
                AuditLog::record((int) $row['user_id'], 'contrasena_cambiada', 'recuperacion', null, $request->ip());
                Session::flash('notice', 'Contraseña actualizada. Ya puedes entrar.');
                return Response::redirect('/panel/login', 303);
            }
        }
        return $this->view('panel/reset', ['title' => 'Nueva contraseña', 'invalid' => false, 'error' => $error], 'panel-bare', $error ? 422 : 200);
    }

    /** Seguridad de la cuenta: configurar 2FA, códigos de respaldo y cambio de contraseña. */
    public function security(Request $request): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect('/panel/login', 303);
        }
        $id = (int) $user['id'];
        $row = (array) Db::one('SELECT totp_enabled, totp_secret FROM users WHERE id = ?', [$id]);
        $codes = null;
        $error = null;
        $action = $request->str('action', 20);

        if ($request->isPost() && $action === 'totp_enable') {
            $secret = Session::get('totp_pending');
            $step = is_string($secret) ? Totp::verify($secret, $request->str('code', 10)) : null;
            if ($step === null) {
                $error = 'El código no coincide. Revisa la hora del teléfono y escribe el código actual.';
            } else {
                Db::run('UPDATE users SET totp_secret = ?, totp_enabled = 1, totp_last_step = ?, updated_at = ? WHERE id = ?', [Crypto::encrypt($secret), $step, now_utc(), $id]);
                Session::forget('totp_pending');
                Session::put('setup_only', false);
                Session::regenerate();
                $codes = Auth::newBackupCodes($id);
                AuditLog::record($id, '2fa_activado', null, null, $request->ip());
                $row['totp_enabled'] = 1;
            }
        } elseif ($request->isPost() && $action === 'backup_codes' && (int) $row['totp_enabled']) {
            $codes = Auth::newBackupCodes($id);
            AuditLog::record($id, '2fa_codigos_regenerados', null, null, $request->ip());
        } elseif ($request->isPost() && $action === 'password') {
            $current = (string) ($request->post['current'] ?? '');
            $hash = (string) Db::value('SELECT password_hash FROM users WHERE id = ?', [$id]);
            $error = password_verify($current, $hash) ? $this->passwordError((string) ($request->post['password'] ?? ''), (string) ($request->post['password_confirm'] ?? '')) : 'La contraseña actual no es correcta.';
            if ($error === null) {
                Db::run('UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?', [password_hash((string) $request->post['password'], PASSWORD_ARGON2ID), now_utc(), $id]);
                Session::regenerate();
                AuditLog::record($id, 'contrasena_cambiada', 'panel', null, $request->ip());
                Session::flash('notice', 'Contraseña actualizada.');
                return Response::redirect('/panel/seguridad', 303);
            }
        }

        $pending = null;
        if (!(int) $row['totp_enabled']) {
            $pending = Session::get('totp_pending');
            if (!is_string($pending)) {
                $pending = Totp::newSecret();
                Session::put('totp_pending', $pending);
            }
        }
        $remaining = (int) Db::value('SELECT COUNT(*) FROM user_backup_codes WHERE user_id = ? AND used_at IS NULL', [$id]);
        $layout = Session::get('setup_only') === true ? 'panel-bare' : 'panel';
        return $this->view('panel/security', [
            'title' => 'Seguridad de la cuenta', 'active' => '', 'user' => $user, 'enabled' => (bool) $row['totp_enabled'],
            'secret' => $pending, 'otpauth' => $pending ? Totp::uri($pending, (string) $user['email']) : null,
            'codes' => $codes, 'remaining' => $remaining, 'error' => $error, 'setupOnly' => Session::get('setup_only') === true,
            'content_is_form' => true,
        ], $layout, $error ? 422 : 200);
    }

    private function passwordError(string $p, string $confirm): ?string
    {
        $min = (int) config('app.security.password_min_length', 12);
        if (mb_strlen($p) < $min) {
            return "La contraseña debe tener al menos {$min} caracteres.";
        }
        if ($p !== $confirm) {
            return 'Las contraseñas no coinciden.';
        }
        if (mb_strlen($p) > 200) {
            return 'La contraseña es demasiado larga.';
        }
        return null;
    }

    private function intended(): string
    {
        $to = Session::get('intended');
        Session::forget('intended');
        $role = Auth::role();
        $home = $role === 'staff' ? '/panel/reservas' : '/panel';
        return is_string($to) && str_starts_with($to, '/panel') && !str_starts_with($to, '/panel/login') ? $to : $home;
    }
}
