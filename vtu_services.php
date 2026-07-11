<?php
// vtu_services.php
require_once __DIR__ . "/vtu_client.php";

/**
 * Airtime Purchase
 * network: 1=MTN,2=GLO,3=9MOBILE,4=AIRTEL
 * airtime_type: VTU or SNS
 */
function vtu_buy_airtime(string $network, string $mobile_number, string $amount, string $airtime_type = "VTU", ?string $request_id = null, string $ported_number = "true"): array {
    $request_id = $request_id ?: vtu_request_id();

    $payload = [
        "network" => $network,
        "mobile_number" => $mobile_number,
        "Ported_number" => $ported_number,
        "request-id" => $request_id,
        "amount" => $amount,
        "airtime_type" => $airtime_type
    ];

    return vtu_post("/api/topup/", $payload);
}

/**
 * Data Purchase
 * plan = data_plan_id
 */
function vtu_buy_data(string $network, string $mobile_number, string $plan, ?string $request_id = null, string $ported_number = "true"): array {
    $request_id = $request_id ?: vtu_request_id();

    $payload = [
        "network" => $network,
        "mobile_number" => $mobile_number,
        "Ported_number" => $ported_number,
        "request-id" => $request_id,
        "plan" => $plan
    ];

    return vtu_post("/api/data/", $payload);
}

/**
 * Fetch Data Plans
 */
function vtu_list_data_plans(): array {
    return vtu_post("/api/listdataplans/", []); // sends {}
}

/**
 * Cable Verify
 * cablename: 1=GOTV,2=DSTV,3=STARTIMES,4=SHOWMAX
 */
function vtu_cable_verify(string $cablename, string $smart_card_number): array {
    $payload = [
        "cablename" => $cablename,
        "smart_card_number" => $smart_card_number
    ];
    return vtu_post("/api/cablesub/verify/", $payload);
}

/**
 * Cable Purchase
 * cableplan = cabletv_plan_id
 */
function vtu_cable_subscribe(string $cablename, string $smart_card_number, string $cableplan, ?string $request_id = null): array {
    $request_id = $request_id ?: vtu_request_id();

    $payload = [
        "cablename" => $cablename,
        "smart_card_number" => $smart_card_number,
        "cableplan" => $cableplan,
        // provider responses show id/ident but request-id isn't documented here for cable purchase,
        // we still keep your own reference in DB on your side.
    ];
    return vtu_post("/api/cablesub/", $payload);
}

/**
 * Fetch Cable Plans
 */
function vtu_list_cable_plans(): array {
    return vtu_post("/api/listcabletvplans/", []); // sends {}
}

/**
 * Electricity Disco IDs (Plan IDs)
 */
function vtu_list_electricity_discos(): array {
    return vtu_post("/api/listelectricity/", []); // sends {}
}

/**
 * Electricity Verify
 */
function vtu_electricity_verify(string $disco_name, string $meter_number): array {
    $payload = [
        "disco_name" => $disco_name,
        "meter_number" => $meter_number
    ];
    return vtu_post("/api/billpayment/verify/", $payload);
}

/**
 * Electricity Pay
 * MeterType: prepaid or postpaid (defaults to prepaid)
 */
function vtu_pay_electricity(string $disco_name, string $meter_number, string $amount, string $meter_type = "prepaid", ?string $request_id = null): array {
    $request_id = $request_id ?: vtu_request_id();

    $payload = [
        "disco_name" => $disco_name,
        "meter_number" => $meter_number,
        "MeterType" => $meter_type,
        "amount" => $amount
    ];
    return vtu_post("/api/billpayment/", $payload);
}
