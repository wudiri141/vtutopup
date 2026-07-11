<?php

define("WHATSAPP_TOKEN", "EAANaVJ9o7r4BRL4daJTkJbNwrl9gxMZBF5o7hKpZBBmxmakBvK34UO6s0ujyZCkK3DcxqgIh68NvWtyZB3iCEGxVCWlbJ9yXW5BZB5VJB2ljw6MHNpWj6ykHH5f3pTTtUuFZApnRInC6qHnEZAFyuUkaLHgCpENkjA5K8sj7Jdlk8bSFtntX2DhWzdVrxmOxkb0TOD77wql4ZCXRuvb4NOl7pjZCnZCZAy9uMmtKflotzCjjVCBenU1akOPRFGw5V0o2Oi2yDzn1XJ4bQxRMz8GImr6eUJLk8jPxjL75ugZD");
define("PHONE_NUMBER_ID", "1044342752089482");

function sendWhatsAppMessage($to, $text) {
    $url = "https://graph.facebook.com/v22.0/" . PHONE_NUMBER_ID . "/messages";

    $payload = [
        "messaging_product" => "whatsapp",
        "to" => $to,
        "type" => "text",
        "text" => [
            "body" => $text
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . WHATSAPP_TOKEN,
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}