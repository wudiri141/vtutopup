<?php
declare(strict_types=1);

return [
    'app' => [
        'name' => 'Event Reservation System',
        'timezone' => 'UTC',
        'debug' => true,
        'default_total_slots' => 10,
    ],
    'database' => [
        'driver' => 'sqlite',
        'dsn' => 'sqlite:' . __DIR__ . '/database/database.sqlite',
        'username' => '',
        'password' => '',
    ],
    'mail' => [
        'mailer' => 'smtp',
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => '',
        'password' => '',
        'encryption' => 'tls',
        'from_address' => 'no-reply@example.com',
        'from_name' => 'Event Reservation System',
        'organizer_email' => 'organizer@example.com',
    ],
    'event_dates' => [
        '2026-07-01',
        '2026-07-02',
        '2026-07-03',
        '2026-07-04',
    ],
];
