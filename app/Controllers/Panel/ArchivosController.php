<?php
declare(strict_types=1);

namespace App\Controllers\Panel;

use App\Controllers\Controller;
use App\Domain\Booking\BookingAdminService;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Http\Session;
use App\Panel\Auth;

/** Comprobantes de abono: subida (dueña y asistente) y descarga controlada (§14.3). */
final class ArchivosController extends Controller
{
    public function upload(Request $request): Response
    {
        $code = $request->param('code');
        $file = $request->files['file'] ?? null;
        $error = is_array($file)
            ? BookingAdminService::storeFile($code, $file, ['id' => (int) Auth::id(), 'role' => (string) Auth::role(), 'ip' => $request->ip(), 'ua' => $request->userAgent()])
            : 'Elige un archivo.';
        Session::flash($error === null ? 'notice' : 'error', $error ?? 'Comprobante guardado.');
        return Response::redirect('/panel/reservas/' . $code, 303);
    }

    public function download(Request $request): Response
    {
        $id = (int) $request->param('id');
        $f = BookingAdminService::fileForDownload($id);
        if ($f === null) {
            throw new HttpException(404);
        }
        $row = $f['row'];
        BookingAdminService::logDownload($id, ['id' => (int) Auth::id(), 'ip' => $request->ip()], (string) $row['code']);
        $ext = BookingAdminService::MAX_UPLOAD_MIMES[$row['mime']] ?? 'bin';
        $name = 'comprobante-' . $row['code'] . '-' . $id . '.' . $ext;
        $disposition = $row['mime'] === 'application/pdf' ? 'attachment' : 'inline';
        // header() explícito: el constructor de Response antepone text/html a los encabezados recibidos
        return (new Response((string) file_get_contents($f['path']), 200, [
            'Content-Disposition' => $disposition . '; filename="' . $name . '"',
            'Content-Length' => (string) filesize($f['path']),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, private',
        ]))->header('Content-Type', (string) $row['mime']);
    }
}
