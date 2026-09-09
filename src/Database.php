<?php
declare(strict_types=1);

/** Thin PDO wrapper. Prepared statements only. */
final class Database
{
    private static ?PDO $pdo = null;
    private static array $config = [];

    public static function configure(array $config): void
    {
        self::$config = $config;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $c = self::$config;
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $c['db_host'] ?? 'localhost',
            $c['db_name'] ?? '',
            $c['db_charset'] ?? 'utf8mb4'
        );

        self::$pdo = new PDO($dsn, (string) ($c['db_user'] ?? ''), (string) ($c['db_pass'] ?? ''), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        return self::$pdo;
    }

    /** Run a statement and return the PDOStatement. */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    /** All rows. */
    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** First row, or null. */
    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** First column of the first row. */
    public static function scalar(string $sql, array $params = []): mixed
    {
        $v = self::run($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public static function lastId(): int
    {
        return (int) self::pdo()->lastInsertId();
    }

    /** True if the schema has been installed. */
    public static function isInstalled(): bool
    {
        try {
            self::scalar('SELECT 1 FROM practices LIMIT 1');
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
