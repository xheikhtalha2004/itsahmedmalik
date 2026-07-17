<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/src/bootstrap.php';

$pdo = db();
$databaseName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
$lockName = 'portfolio_migrations_' . substr(hash('sha256', $databaseName), 0, 32);
$lockStatement = $pdo->prepare('SELECT GET_LOCK(:lock_name, 10)');
$lockStatement->execute(['lock_name' => $lockName]);
if ((int) $lockStatement->fetchColumn() !== 1) {
    fwrite(STDERR, "Could not acquire the migration lock.\n");
    exit(1);
}

try {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations (
            version VARCHAR(191) NOT NULL,
            applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (version)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC'
    );

    $applied = array_fill_keys(
        $pdo->query('SELECT version FROM schema_migrations ORDER BY version')->fetchAll(PDO::FETCH_COLUMN),
        true,
    );
    $directory = dirname(__DIR__) . '/migrations';
    $files = glob($directory . '/*.sql') ?: [];
    sort($files, SORT_NATURAL);

    foreach ($files as $file) {
        $version = basename($file);
        if (isset($applied[$version])) {
            fwrite(STDOUT, "skip  {$version}\n");
            continue;
        }

        fwrite(STDOUT, "apply {$version}\n");
        $sql = file_get_contents($file);
        if (!is_string($sql) || trim($sql) === '') {
            throw new RuntimeException("Migration {$version} is empty or unreadable.");
        }
        // Trusted, tracked migrations may contain many statements and HTML semicolons.
        $pdo->exec($sql);

        $record = $pdo->prepare(
            'INSERT INTO schema_migrations (version, applied_at) VALUES (:version, UTC_TIMESTAMP())'
        );
        $record->execute(['version' => $version]);
        fwrite(STDOUT, "done  {$version}\n");
    }

    fwrite(STDOUT, "Database is up to date.\n");
} catch (Throwable $exception) {
    app_log('migration_failed', [
        'migration' => $version ?? 'bootstrap',
        'exception' => $exception::class,
    ]);
    fwrite(STDERR, 'Migration failed: ' . $exception->getMessage() . "\n");
    exit(1);
} finally {
    try {
        $release = $pdo->prepare('SELECT RELEASE_LOCK(:lock_name)');
        $release->execute(['lock_name' => $lockName]);
    } catch (Throwable) {
        // The connection closing also releases the advisory lock.
    }
}
