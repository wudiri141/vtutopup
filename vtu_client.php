<?php
// vtu_client.php
require_once __DIR__ . "/config_vtu.php";

function vtu_request_id(): string {
    // Unique request-id (safe)
    return 'REQ-' . date('YmdHis') . '-' . bin2hex(random_bytes(6));
}

function vtu_post(string $endpoint, array $payload): array {
    $url = rtrim(VTU_BASE_URL, '/') . $endpoint;

    $ch = curl_init($url);
    $headers = [
        "Authorization: Token " . VTU_API_KEY,
        "Content-Type: application/json",
        "Accept: application/json",
    ];

    // IMPORTANT: some list endpoints require non-empty JSON body -> send {} at least
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => VTU_TIMEOUT,
        CURLOPT_TIMEOUT => VTU_TIMEOUT,
        CURLOPT_POSTFIELDS => $json,
    ]);

    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) {
        return [
            'ok' => false,
            'http_code' => $code ?: 0,
            'error' => $err ?: 'cURL error',
            'raw' => null,
            'data' => null,
        ];
    }

    $decoded = json_decode($body, true);

    // If JSON decode fails, still return raw body
    if (!is_array($decoded)) {
        return [
            'ok' => false,
            'http_code' => $code,
            'error' => 'Invalid JSON from provider',
            'raw' => $body,
            'data' => null,
        ];
    }

    // Provider sometimes returns Status/status
    $status1 = strtolower((string)($decoded['status'] ?? ''));
    $status2 = strtolower((string)($decoded['Status'] ?? ''));

    $success = in_array($status1, ['success', 'successful'], true) || in_array($status2, ['success', 'successful'], true);

    return [
        'ok' => $success,
        'http_code' => $code,
        'error' => $success ? null : ($decoded['api_response'] ?? $decoded['message'] ?? 'Request failed'),
        'raw' => $body,
        'data' => $decoded,
    ];
}
