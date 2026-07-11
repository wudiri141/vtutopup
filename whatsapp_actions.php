<?php
require_once "db.php";
require_once "config_vtu.php";

function wa_vtu_post_json($url, $apiKey, $payload, $timeout = 30) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Token {$apiKey}",
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => $timeout,
    ]);

    $raw = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    $data = null;
    if ($raw !== false) {
        $data = json_decode($raw, true);
    }

    return [
        "ok" => ($err === '' && $http >= 200 && $http < 300),
        "http_code" => $http,
        "error" => $err ?: null,
        "raw" => $raw,
        "data" => $data
    ];
}

function processWhatsAppAirtime($userId, $network, $phone, $amount, $pin) {
    global $conn;

    $network = strtoupper(trim($network));
    $phone = preg_replace('/\D+/', '', $phone);
    $amount = (float)$amount;
    $pin = trim($pin);

    $netMap = [
        "MTN" => 1,
        "GLO" => 2,
        "9MOBILE" => 3,
        "AIRTEL" => 4
    ];

    if (!isset($netMap[$network])) {
        return ["success" => false, "message" => "Invalid network selected."];
    }

    if (strlen($phone) !== 11) {
        return ["success" => false, "message" => "Phone number must be 11 digits."];
    }

    if ($amount < 50) {
        return ["success" => false, "message" => "Minimum airtime amount is ₦50."];
    }

    if (!preg_match('/^\d{4}$/', $pin)) {
        return ["success" => false, "message" => "PIN must be 4 digits."];
    }

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare("SELECT wallet, transaction_pin FROM users WHERE id=? FOR UPDATE");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $u = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$u) {
            $conn->rollback();
            return ["success" => false, "message" => "User not found."];
        }

        if (empty($u['transaction_pin']) || !password_verify($pin, $u['transaction_pin'])) {
            $conn->rollback();
            return ["success" => false, "message" => "Wrong transaction PIN."];
        }

        $wallet = (float)$u['wallet'];
        if ($wallet < $amount) {
            $conn->rollback();
            return ["success" => false, "message" => "Insufficient wallet balance."];
        }

        $ref = "WA_AIR_" . date("YmdHis") . "_" . rand(100,999);

        $service = "airtime";
        $provider = "vtunaija";
        $status = "pending";
        $providerMessage = "";
        $cost = $amount;
        $profit = 0;

        $stmt = $conn->prepare("
            INSERT INTO vtu_transactions
            (user_id, service, provider, ref, network, phone, plan_id, amount_sell, amount_cost, profit, status, provider_message, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param(
            "isssssdddss",
            $userId,
            $service,
            $provider,
            $ref,
            $network,
            $phone,
            $amount,
            $cost,
            $profit,
            $status,
            $providerMessage
        );
        $stmt->execute();
        $stmt->close();

        $payload = [
            "network" => (string)$netMap[$network],
            "mobile_number" => $phone,
            "Ported_number" => "true",
            "request-id" => $ref,
            "amount" => (string)$amount,
            "airtime_type" => "VTU"
        ];

        $api = wa_vtu_post_json(VTU_BASE_URL . "/api/topup/", VTU_API_KEY, $payload, VTU_TIMEOUT);

        if (!$api["ok"]) {
            $msg = "Airtime API connection failed";
            $stmt = $conn->prepare("UPDATE vtu_transactions SET status='failed', provider_message=? WHERE user_id=? AND ref=? LIMIT 1");
            $stmt->bind_param("sis", $msg, $userId, $ref);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            return ["success" => false, "message" => $msg];
        }

        $data = $api["data"] ?? [];
        $apiStatus = strtolower((string)($data["status"] ?? $data["Status"] ?? ""));
        $apiMsg = (string)($data["api_response"] ?? $data["message"] ?? "No response");

        if ($apiStatus !== "success" && $apiStatus !== "successful") {
            $stmt = $conn->prepare("UPDATE vtu_transactions SET status='failed', provider_message=? WHERE user_id=? AND ref=? LIMIT 1");
            $stmt->bind_param("sis", $apiMsg, $userId, $ref);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            return ["success" => false, "message" => "Airtime failed: " . $apiMsg];
        }

        $newWallet = $wallet - $amount;
        $stmt = $conn->prepare("UPDATE users SET wallet=? WHERE id=?");
        $stmt->bind_param("di", $newWallet, $userId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE vtu_transactions SET status='success', provider_message=? WHERE user_id=? AND ref=? LIMIT 1");
        $stmt->bind_param("sis", $apiMsg, $userId, $ref);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        return [
            "success" => true,
            "message" => "✅ Airtime successful\nNetwork: {$network}\nPhone: {$phone}\nAmount: ₦" . number_format($amount, 2) . "\nRef: {$ref}\nWallet Balance: ₦" . number_format($newWallet, 2)
        ];

    } catch (Throwable $e) {
        if ($conn) {
            $conn->rollback();
        }
        return ["success" => false, "message" => "Server error processing airtime."];
    }
}

function processWhatsAppData($userId, $network, $phone, $planId, $pin) {
    global $conn;

    $network = strtoupper(trim($network));
    $phone = preg_replace('/\D+/', '', $phone);
    $planId = trim($planId);
    $pin = trim($pin);

    $allowed = ['MTN','AIRTEL','GLO','9MOBILE'];
    if (!in_array($network, $allowed, true)) {
        return ["success" => false, "message" => "Invalid network selected."];
    }

    if (!preg_match('/^\d{11}$/', $phone)) {
        return ["success" => false, "message" => "Phone number must be 11 digits."];
    }

    if ($planId === '' || !ctype_digit($planId)) {
        return ["success" => false, "message" => "Invalid data plan ID."];
    }

    if (!preg_match('/^\d{4}$/', $pin)) {
        return ["success" => false, "message" => "PIN must be 4 digits."];
    }

    $networkIdMap = [
        'MTN' => '1',
        'GLO' => '2',
        '9MOBILE' => '3',
        'AIRTEL' => '4'
    ];

    try {
        $conn->begin_transaction();

        // lock user + verify pin
        $stmt = $conn->prepare("SELECT wallet, transaction_pin FROM users WHERE id=? FOR UPDATE");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $u = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$u) {
            $conn->rollback();
            return ["success" => false, "message" => "User not found."];
        }

        if (empty($u['transaction_pin']) || !password_verify($pin, $u['transaction_pin'])) {
            $conn->rollback();
            return ["success" => false, "message" => "Wrong transaction PIN."];
        }

        $wallet = (float)($u['wallet'] ?? 0);

        // fetch plan
        $stmt = $conn->prepare("
            SELECT data_plan_id, network, sell_price, cost_price
            FROM vtu_dataplans
            WHERE data_plan_id=? AND network=? AND status='On'
            LIMIT 1
        ");
        $stmt->bind_param("is", $planId, $network);
        $stmt->execute();
        $plan = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$plan) {
            $conn->rollback();
            return ["success" => false, "message" => "Plan not found."];
        }

        $sellPrice = (float)$plan['sell_price'];
        $costPrice = (float)$plan['cost_price'];
        $profit = $sellPrice - $costPrice;

        if ($wallet < $sellPrice) {
            $conn->rollback();
            return ["success" => false, "message" => "Insufficient wallet balance."];
        }

        $ref = "WA_DAT_" . date("YmdHis") . "_" . rand(100,999);

        // insert pending
        $stmt = $conn->prepare("
            INSERT INTO vtu_transactions
            (user_id, service, provider, ref, network, phone, plan_id, amount_sell, amount_cost, profit, status, provider_message, provider_raw, created_at)
            VALUES (?, 'data', 'vtunaija', ?, ?, ?, ?, ?, ?, ?, 'pending', NULL, NULL, NOW())
        ");
        $stmt->bind_param(
            "isssdddd",
            $userId,
            $ref,
            $network,
            $phone,
            $planId,
            $sellPrice,
            $costPrice,
            $profit
        );
        $stmt->execute();
        $stmt->close();

        // deduct wallet
        $stmt = $conn->prepare("UPDATE users SET wallet = wallet - ? WHERE id=? AND wallet >= ?");
        $stmt->bind_param("dii", $sellPrice, $userId, $sellPrice);
        $stmt->execute();

        if ($stmt->affected_rows < 1) {
            $stmt->close();

            $failMsg = "Wallet deduction failed";
            $stmtFail = $conn->prepare("UPDATE vtu_transactions SET status='failed', provider_message=? WHERE ref=? LIMIT 1");
            $stmtFail->bind_param("ss", $failMsg, $ref);
            $stmtFail->execute();
            $stmtFail->close();

            $conn->rollback();
            return ["success" => false, "message" => "Wallet deduction failed."];
        }
        $stmt->close();

        // call VTU API
        $payload = [
            "network" => $networkIdMap[$network],
            "mobile_number" => $phone,
            "Ported_number" => "true",
            "request-id" => $ref,
            "plan" => $planId
        ];

        $api = wa_vtu_post_json(VTU_BASE_URL . "/api/data/", VTU_API_KEY, $payload, VTU_TIMEOUT);

        if ($api['ok']) {
            $msg = $api['data']['api_response'] ?? 'SUCCESS';
            $raw = $api['raw'] ?? '';

            $stmt = $conn->prepare("
                UPDATE vtu_transactions
                SET status='success', provider_message=?, provider_raw=?
                WHERE ref=?
                LIMIT 1
            ");
            $stmt->bind_param("sss", $msg, $raw, $ref);
            $stmt->execute();
            $stmt->close();

            $newWallet = $wallet - $sellPrice;
            $conn->commit();

            return [
                "success" => true,
                "message" => "✅ Data successful\nNetwork: {$network}\nPhone: {$phone}\nPlan ID: {$planId}\nAmount: ₦" . number_format($sellPrice, 2) . "\nRef: {$ref}\nWallet Balance: ₦" . number_format($newWallet, 2)
            ];
        }

        // fail and refund
        $err = $api['error'] ?? 'VTU request failed';
        $raw = $api['raw'] ?? '';

        $stmt = $conn->prepare("UPDATE users SET wallet = wallet + ? WHERE id=?");
        $stmt->bind_param("di", $sellPrice, $userId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("
            UPDATE vtu_transactions
            SET status='failed', provider_message=?, provider_raw=?
            WHERE ref=?
            LIMIT 1
        ");
        $stmt->bind_param("sss", $err, $raw, $ref);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        return ["success" => false, "message" => "Data purchase failed: " . $err];

    } catch (Throwable $e) {
        if ($conn) {
            $conn->rollback();
        }
        return ["success" => false, "message" => "Server error processing data."];
    }
}