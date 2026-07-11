<?php

use App\Support\Environment;

$driver = strtolower((string) Environment::get('DB_DRIVER', 'sqlite'));

if ($driver === 'sqlite') {
    $databasePath = Environment::get('DB_DATABASE', dirname(__DIR__) . '/database/database.sqlite');
    $dsn = 'sqlite:' . $databasePath;
} else {
    $dsn = sprintf(
        '%s:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $driver,
        Environment::get('DB_HOST', '127.0.0.1'),
        Environment::get('DB_PORT', '3306'),
        Environment::get('DB_NAME', 'event_reservation_system')
    );
}

return [
    'driver' => $driver,
    'dsn' => $dsn,
    'username' => Environment::get('DB_USERNAME', ''),
    'password' => Environment::get('DB_PASSWORD', ''),
];
