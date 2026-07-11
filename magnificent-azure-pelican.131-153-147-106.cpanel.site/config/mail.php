<?php

use App\Support\Environment;

return [
    'mailer' => strtolower((string) Environment::get('MAIL_MAILER', 'smtp')),
    'host' => Environment::get('MAIL_HOST', ''),
    'port' => (int) Environment::get('MAIL_PORT', '587'),
    'username' => Environment::get('MAIL_USERNAME', ''),
    'password' => Environment::get('MAIL_PASSWORD', ''),
    'encryption' => Environment::get('MAIL_ENCRYPTION', 'tls'),
    'from_address' => Environment::get('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
    'from_name' => Environment::get('MAIL_FROM_NAME', 'Event Reservation System'),
    'organizer_email' => Environment::get('ORGANIZER_EMAIL', 'organizer@example.com'),
];
