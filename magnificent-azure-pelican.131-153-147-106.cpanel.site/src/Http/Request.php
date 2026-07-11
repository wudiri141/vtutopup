<?php
declare(strict_types=1);

namespace App\Http;

final class Request
{
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $body
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $body = [];

        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? ''));

        if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
            if (str_contains($contentType, 'application/json')) {
                $decoded = json_decode((string) file_get_contents('php://input'), true);
                $body = is_array($decoded) ? $decoded : [];
            } else {
                $body = $_POST;
            }
        }

        return new self($method, $path, $body);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function json(): array
    {
        return $this->body;
    }
}
