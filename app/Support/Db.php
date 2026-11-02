<?php
declare(strict_types=1);

namespace App\Support;

use PDO;
use PDOStatement;

/**
 * Conexión PDO perezosa (§4): sentencias preparadas reales, excepciones, UTC.
 */
final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = self::connect((string) env('DB_USER', ''), (string) env('DB_PASS', ''));
        }
        return self::$pdo;
    }

    public static function connect(string $user, string $pass): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            env('DB_HOST', '127.0.0.1'),
            (int) env('DB_PORT', '3306'),
            env('DB_NAME', ''),
        );
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
        $pdo->exec("SET time_zone = '+00:00', NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        return $pdo;
    }

    /** Para pruebas. */
    public static function setPdo(?PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function available(): bool
    {
        try {
            self::pdo();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /** @param array<int|string,mixed> $params */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        foreach ($params as $k => $v) {
            $type = match (true) {
                is_int($v) => PDO::PARAM_INT,
                is_bool($v) => PDO::PARAM_BOOL,
                $v === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            $st->bindValue(is_int($k) ? $k + 1 : (str_starts_with($k, ':') ? $k : ':' . $k), $v, $type);
        }
        $st->execute();
        return $st;
    }

    /** @return array<string,mixed>|null */
    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** @return list<array<string,mixed>> */
    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $v = self::run($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public static function insert(string $sql, array $params = []): int
    {
        self::run($sql, $params);
        return (int) self::pdo()->lastInsertId();
    }

    /**
     * @template T
     * @param callable():T $fn
     * @return T
     */
    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        if ($pdo->inTransaction()) {
            return $fn();
        }
        $pdo->beginTransaction();
        try {
            $result = $fn();
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
