<?php
declare(strict_types=1);

namespace App\Support;

final class Logger
{
    public static function error(string $message): void
    {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $line = sprintf("[%s] %s\n", date('c'), $message);
        file_put_contents($logDir . '/app.log', $line, FILE_APPEND);
    }
}
