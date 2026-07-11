<?php
declare(strict_types=1);

namespace App\Http;

final class JsonResponse
{
    public static function send(int $statusCode, array $payload): void
    {
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
