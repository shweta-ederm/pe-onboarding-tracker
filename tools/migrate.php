<?php
/**
 * Applies any .sql file in db/migrations/ that has not run yet, in
 * filename order, and records it in schema_migrations.
 *
 * Run it from cPanel Terminal (or SSH) after a deploy that changes the
 * schema:
 *
 *     php /home/USERNAME/onboarding/tools/migrate.php
 *
 * If you have no shell, copy the migration's SQL into phpMyAdmin
 * instead and then add its filename to schema_migrations by hand.
 *
 * Name migrations so they sort correctly: 001_add_column.sql,
 * 002_add_index.sql, and so on. Each must be safe to run once.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Run this from the command line.\n";
    exit(1);
}

require_once dirname(__DIR__) . '/src/bootstrap.php';

$dir = APP_ROOT . '/db/migrations';

try {
    Database::pdo();
} catch (Throwable $ex) {
    fwrite(STDERR, "Cannot connect to the database: " . $ex->getMessage() . "\n");
    exit(1);
}

Database::run(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        filename   VARCHAR(190) NOT NULL,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (filename)
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$appliedRows = Database::all('SELECT filename FROM schema_migrations');
$applied = array_map(static fn($r) => (string) $r['filename'], $appliedRows);

$files = glob($dir . '/*.sql') ?: [];
sort($files, SORT_STRING);

$ran = 0;

foreach ($files as $path) {
    $name = basename($path);
    if (in_array($name, $applied, true)) {
        continue;
    }

    echo "Applying {$name} ... ";

    $sql = (string) file_get_contents($path);
    $lines = preg_split('/\R/', $sql) ?: [];
    $keep = [];
    foreach ($lines as $line) {
        $t = ltrim($line);
        if ($t === '' || str_starts_with($t, '--')) {
            continue;
        }
        $keep[] = $line;
    }

    $statements = array_filter(array_map('trim', explode(';', implode("\n", $keep))));

    try {
        foreach ($statements as $stmt) {
            Database::pdo()->exec($stmt);
        }
        Database::run('INSERT INTO schema_migrations (filename) VALUES (:f)', ['f' => $name]);
        echo "done\n";
        $ran++;
    } catch (Throwable $ex) {
        echo "FAILED\n";
        fwrite(STDERR, "  " . $ex->getMessage() . "\n");
        fwrite(STDERR, "  Nothing after this migration was applied. Fix it and run again.\n");
        exit(1);
    }
}

echo $ran === 0
    ? "Nothing to do, the database is up to date.\n"
    : "Applied {$ran} migration(s).\n";
