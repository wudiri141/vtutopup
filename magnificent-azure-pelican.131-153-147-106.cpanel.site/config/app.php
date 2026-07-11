<?php

use App\Support\Environment;

return [
    'name' => 'Event Reservation System',
    'timezone' => Environment::get('APP_TIMEZONE', 'UTC'),
    'default_total_slots' => 10,
    'debug' => filter_var(Environment::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
];
