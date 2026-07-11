<?php
declare(strict_types=1);

namespace App\Support;

final class Json
{
    public static function encode(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }
}
