<?php
require_once "db.php";

function normalizePhone($phone) {
    $phone = preg_replace('/\D+/', '', $phone);

    if (strpos($phone, '234') === 0) {
        return '0' . substr($phone, 3);
    }

    return $phone;
}

function getUserByPhone($phone) {
    global $conn;

    $stmt = $conn->prepare("SELECT id, fullname, email, phone, wallet, transaction_pin FROM users WHERE phone=? LIMIT 1");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $user ?: null;
}

function getWalletBalance($userId) {
    global $conn;

    $stmt = $conn->prepare("SELECT wallet FROM users WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (float)($row['wallet'] ?? 0);
}

function getWaSession($phone) {
    global $conn;

    $stmt = $conn->prepare("SELECT * FROM whatsapp_sessions WHERE phone=? LIMIT 1");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function saveWaSession($phone, $action, $step, $dataArr = []) {
    global $conn;

    $json = json_encode($dataArr);

    try {
        $stmt = $conn->prepare("
            INSERT INTO whatsapp_sessions (phone, action, step, data)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                action=VALUES(action),
                step=VALUES(step),
                data=VALUES(data)
        ");

        if (!$stmt) {
            file_put_contents(
                __DIR__ . "/wa_debug.txt",
                date('Y-m-d H:i:s') . " | saveWaSession prepare failed | err=" . $conn->error . PHP_EOL,
                FILE_APPEND
            );
            return false;
        }

        $stmt->bind_param("ssss", $phone, $action, $step, $json);
        $ok = $stmt->execute();
        $err = $stmt->error;
        $stmt->close();

        file_put_contents(
            __DIR__ . "/wa_debug.txt",
            date('Y-m-d H:i:s') . " | saveWaSession | phone={$phone} | action={$action} | step={$step} | ok=" . ($ok ? '1' : '0') . " | err={$err}" . PHP_EOL,
            FILE_APPEND
        );

        return $ok;
    } catch (Throwable $e) {
        file_put_contents(
            __DIR__ . "/wa_debug.txt",
            date('Y-m-d H:i:s') . " | saveWaSession exception | " . $e->getMessage() . PHP_EOL,
            FILE_APPEND
        );
        return false;
    }
}

function clearWaSession($phone) {
    global $conn;

    $stmt = $conn->prepare("DELETE FROM whatsapp_sessions WHERE phone=?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $stmt->close();

    file_put_contents(
        __DIR__ . "/wa_debug.txt",
        date('Y-m-d H:i:s') . " | clearWaSession | phone={$phone}" . PHP_EOL,
        FILE_APPEND
    );
}

function getDataPlansByNetwork($network) {
    global $conn;

    $plans = [];

    $stmt = $conn->prepare("
        SELECT data_plan_id, network, size, datatype, validity_days, sell_price
        FROM vtu_dataplans
        WHERE network=? AND status='On'
        ORDER BY sell_price ASC
        LIMIT 20
    ");
    $stmt->bind_param("s", $network);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $plans[] = $row;
    }

    $stmt->close();
    return $plans;
}

function formatDataPlansForWhatsApp($plans) {
    if (empty($plans)) {
        return "No plans available for this network right now.";
    }

    $text = "Select Data Plan\n";
    $i = 1;

    foreach ($plans as $plan) {
        $size = $plan['size'] ?? '';
        $type = $plan['datatype'] ?? '';
        $days = $plan['validity_days'] ?? '';
        $price = number_format((float)($plan['sell_price'] ?? 0), 2);

        $text .= "{$i}. {$size} {$type} - ₦{$price}";
        if ($days !== '') {
            $text .= " ({$days} days)";
        }
        $text .= "\n";
        $i++;
    }

    return trim($text);
}