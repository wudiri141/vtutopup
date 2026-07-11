<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/src/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

App\Support\Environment::load(dirname(__DIR__) . '/.env');

$timezone = App\Support\Environment::get('APP_TIMEZONE', 'UTC');
date_default_timezone_set((string) $timezone);

if (filter_var(App\Support\Environment::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
}
