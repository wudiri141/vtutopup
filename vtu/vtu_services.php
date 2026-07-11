<?php
require_once __DIR__ . "/vtu_client.php";

function vtu_list_data_plans(): array {
    return vtu_post("/api/listdataplans/", []); // sends {}
}

function vtu_buy_data(string $network, string $mobile_number, string $plan, ?string $request_id = null, string $ported_number = "true"): array {
    $request_id = $request_id ?: vtu_request_id();
    $payload = [
        "network" => $network,                 // 1..4
        "mobile_number" => $mobile_number,
        "Ported_number" => $ported_number,
        "request-id" => $request_id,
        "plan" => $plan                        // plan id
    ];
    return vtu_post("/api/data/", $payload);
}
