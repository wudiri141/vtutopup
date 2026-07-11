<?php
declare(strict_types=1);

use PDO;
use RuntimeException;

$root = dirname(__DIR__);

require $root . '/src/bootstrap.php';

$databaseConfig = require $root . '/config/database.php';

if (($databaseConfig['driver'] ?? '') !== 'sqlite') {
    fwrite(STDERR, "This setup helper currently targets SQLite only.\n");
    exit(1);
}

$databasePath = substr((string) $databaseConfig['dsn'], strlen('sqlite:'));
$databaseDir = dirname($databasePath);

if (!is_dir($databaseDir)) {
    mkdir($databaseDir, 0777, true);
}

$pdo = new PDO(
    (string) $databaseConfig['dsn'],
    null,
    null,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

$pdo->exec('PRAGMA foreign_keys = ON');

$schemaSql = file_get_contents($root . '/database/schema.sql');
$seedSql = file_get_contents($root . '/database/seed.sql');

if ($schemaSql === false || $seedSql === false) {
    throw new RuntimeException('Unable to read database SQL files.');
}

$pdo->exec('DROP TABLE IF EXISTS reservations');
$pdo->exec('DROP TABLE IF EXISTS reservation_dates');

foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n)?/', $schemaSql) ?: [])) as $statement) {
    $pdo->exec($statement);
}

foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n)?/', $seedSql) ?: [])) as $statement) {
    $pdo->exec($statement);
}

fwrite(STDOUT, "Database schema created and seed data inserted.\n");
