<?php
declare(strict_types=1);

/**
 * Diario 3:00 (§14.4): copia de seguridad cifrada de la base de datos.
 *
 *   php cron/respaldo.php                      → storage/backups/AAAA-MM-DD.sql.gz.enc (borra los de más de 30 días)
 *   php cron/respaldo.php --descifrar=ARCHIVO  → escribe ARCHIVO sin «.enc» (un .sql.gz listo para restaurar)
 *
 * Formato: volcado SQL (estructura, datos y triggers) comprimido con gzip y cifrado con
 * sodium_crypto_secretstream_xchacha20poly1305; la clave se deriva de APP_KEY. Sin APP_KEY no se
 * puede descifrar: guarda APP_KEY fuera del servidor junto a las copias.
 *
 * Restaurar:
 *   1. php cron/respaldo.php --descifrar=storage/backups/2026-11-02.sql.gz.enc
 *   2. gunzip -c storage/backups/2026-11-02.sql.gz | mysql -u USUARIO_MIGRACIONES -p NOMBRE_BD
 *      (o importa el .sql en phpMyAdmin; el archivo usa DELIMITER para los triggers).
 * Copia el .enc fuera del servidor (Hostinger guarda copias propias, pero §14.4 pide una externa)
 * y prueba la restauración una vez al mes en una base vacía.
 */

require_once __DIR__ . '/_lock.php';

use App\Support\Db;

const BACKUP_CHUNK = 65536;

function backup_key(): string
{
    return sodium_crypto_generichash('mubf-backup-v1', app_key(), SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES);
}

/** Escribe el volcado SQL comprimido en $gzPath. @return int tablas volcadas */
function backup_dump(string $gzPath): int
{
    $pdo = Db::pdo();
    $gz = gzopen($gzPath, 'wb6');
    if ($gz === false) {
        throw new RuntimeException('No se pudo crear ' . $gzPath);
    }
    gzwrite($gz, "-- Make Up by Fletcher · respaldo " . gmdate('c') . "\nSET NAMES utf8mb4;\nSET time_zone = '+00:00';\nSET FOREIGN_KEY_CHECKS = 0;\n\n");
    $tables = $pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_NUM);
    foreach ($tables as [$table]) {
        $create = $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`')->fetch(PDO::FETCH_NUM)[1];
        gzwrite($gz, "DROP TABLE IF EXISTS `{$table}`;\n{$create};\n");
        $st = $pdo->query('SELECT * FROM `' . str_replace('`', '``', $table) . '`', PDO::FETCH_NUM);
        $batch = [];
        while ($row = $st->fetch(PDO::FETCH_NUM)) {
            $batch[] = '(' . implode(',', array_map(static fn($v) => $v === null ? 'NULL' : (is_int($v) || is_float($v) ? (string) $v : $pdo->quote((string) $v)), $row)) . ')';
            if (count($batch) >= 200) {
                gzwrite($gz, "INSERT INTO `{$table}` VALUES " . implode(",\n", $batch) . ";\n");
                $batch = [];
            }
        }
        if ($batch) {
            gzwrite($gz, "INSERT INTO `{$table}` VALUES " . implode(",\n", $batch) . ";\n");
        }
        gzwrite($gz, "\n");
    }
    // Triggers (tablas de solo inserción y periodos cerrados)
    foreach ($pdo->query('SHOW TRIGGERS')->fetchAll(PDO::FETCH_ASSOC) as $t) {
        $sql = $pdo->query('SHOW CREATE TRIGGER `' . str_replace('`', '``', $t['Trigger']) . '`')->fetch(PDO::FETCH_ASSOC);
        $body = (string) ($sql['SQL Original Statement'] ?? '');
        $body = preg_replace('/^CREATE\s+DEFINER=\S+\s+/i', 'CREATE ', $body) ?? $body;
        gzwrite($gz, "DROP TRIGGER IF EXISTS `{$t['Trigger']}`;\nDELIMITER $$\n{$body}$$\nDELIMITER ;\n");
    }
    gzwrite($gz, "SET FOREIGN_KEY_CHECKS = 1;\n");
    gzclose($gz);
    return count($tables);
}

function backup_encrypt(string $in, string $out): void
{
    $src = fopen($in, 'rb');
    $dst = fopen($out, 'wb');
    [$state, $header] = sodium_crypto_secretstream_xchacha20poly1305_init_push(backup_key());
    fwrite($dst, 'MUBF1' . $header);
    $size = filesize($in);
    $read = 0;
    do {
        $chunk = (string) fread($src, BACKUP_CHUNK);
        $read += strlen($chunk);
        $tag = $read >= $size ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE;
        fwrite($dst, sodium_crypto_secretstream_xchacha20poly1305_push($state, $chunk, '', $tag));
    } while ($tag !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL);
    fclose($src);
    fclose($dst);
}

function backup_decrypt(string $in, string $out): void
{
    $src = fopen($in, 'rb');
    if ($src === false || fread($src, 5) !== 'MUBF1') {
        throw new RuntimeException('El archivo no es un respaldo de MUBF.');
    }
    $header = (string) fread($src, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
    $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, backup_key());
    $dst = fopen($out, 'wb');
    $abytes = SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES;
    do {
        $chunk = (string) fread($src, BACKUP_CHUNK + $abytes);
        $res = sodium_crypto_secretstream_xchacha20poly1305_pull($state, $chunk);
        if ($res === false) {
            throw new RuntimeException('No se pudo descifrar: APP_KEY distinta o archivo alterado.');
        }
        [$plain, $tag] = $res;
        fwrite($dst, $plain);
    } while ($tag !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL && !feof($src));
    fclose($src);
    fclose($dst);
    if ($tag !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL) {
        throw new RuntimeException('Respaldo incompleto (truncado).');
    }
}

/** @return array{file:string,tables:int,deleted:int} */
function backup_run(?string $dir = null): array
{
    $dir ??= BASE_PATH . '/storage/backups';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }
    $day = local_time(now_utc())->format('Y-m-d');
    $tmp = $dir . '/.tmp-' . bin2hex(random_bytes(6)) . '.sql.gz';
    $final = $dir . '/' . $day . '.sql.gz.enc';
    try {
        $tables = backup_dump($tmp);
        backup_encrypt($tmp, $final . '.part');
        rename($final . '.part', $final);
        @chmod($final, 0600);
    } finally {
        @unlink($tmp);
        @unlink($final . '.part');
    }
    $deleted = 0;
    foreach (glob($dir . '/*.sql.gz.enc') ?: [] as $f) {
        if (filemtime($f) < time() - 30 * 86400) {
            unlink($f);
            $deleted++;
        }
    }
    return ['file' => $final, 'tables' => $tables, 'deleted' => $deleted];
}

if (cron_should_run()) {
    $opt = getopt('', ['descifrar:']);
    if (isset($opt['descifrar'])) {
        $in = (string) $opt['descifrar'];
        $out = preg_replace('/\.enc$/', '', $in) ?? ($in . '.dec');
        if ($out === $in) {
            $out = $in . '.sql.gz';
        }
        backup_decrypt($in, $out);
        cron_out('respaldo', "descifrado en {$out}");
        exit(0);
    }
    $lock = cron_lock('respaldo');
    if ($lock !== null) {
        $r = backup_run();
        cron_out('respaldo', basename($r['file']) . ' tablas=' . $r['tables'] . ' borrados=' . $r['deleted'] . ' tamaño=' . filesize($r['file']));
    }
}
