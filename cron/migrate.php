<?php
declare(strict_types=1);

/**
 * Ejecuta database/migrations/*.sql en orden con el usuario de migraciones.
 * Uso: php cron/migrate.php [--status]
 * Entiende DELIMITER como el cliente mysql (para los triggers).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Support\Db;

$user = env('DB_MIGRATE_USER') ?: env('DB_USER', '');
$pass = env('DB_MIGRATE_USER') ? (string) env('DB_MIGRATE_PASS', '') : (string) env('DB_PASS', '');
$pdo = Db::connect((string) $user, $pass);

$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (name VARCHAR(190) PRIMARY KEY, applied_at DATETIME NOT NULL) ENGINE=InnoDB');
$done = $pdo->query('SELECT name FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);

$files = glob(BASE_PATH . '/database/migrations/*.sql') ?: [];
sort($files, SORT_STRING);

if (in_array('--status', $argv, true)) {
    foreach ($files as $f) {
        printf("%s %s\n", in_array(basename($f), $done, true) ? '[x]' : '[ ]', basename($f));
    }
    exit(0);
}

/** @return list<string> */
function split_sql(string $sql): array
{
    $statements = [];
    $delimiter = ';';
    $buffer = '';
    foreach (preg_split('/\R/', $sql) ?: [] as $line) {
        if (preg_match('/^\s*DELIMITER\s+(\S+)\s*$/i', $line, $m)) {
            $delimiter = $m[1];
            continue;
        }
        if ($buffer === '' && preg_match('/^\s*(--|#)/', $line)) {
            continue;
        }
        $buffer .= $line . "\n";
        if (str_ends_with(rtrim($line), $delimiter)) {
            $stmt = trim(substr(rtrim($buffer), 0, -strlen($delimiter)));
            if ($stmt !== '') {
                $statements[] = $stmt;
            }
            $buffer = '';
        }
    }
    if (trim($buffer) !== '') {
        $statements[] = trim($buffer);
    }
    return $statements;
}

$applied = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $done, true)) {
        continue;
    }
    echo "→ {$name}\n";
    foreach (split_sql((string) file_get_contents($file)) as $stmt) {
        try {
            $pdo->exec($stmt);
        } catch (PDOException $e) {
            fwrite(STDERR, "Error en {$name}: {$e->getMessage()}\n" . substr($stmt, 0, 300) . "\n");
            exit(1);
        }
    }
    $pdo->prepare('INSERT INTO schema_migrations (name, applied_at) VALUES (?, UTC_TIMESTAMP())')->execute([$name]);
    $applied++;
}
echo $applied === 0 ? "Sin migraciones pendientes.\n" : "Aplicadas: {$applied}\n";
