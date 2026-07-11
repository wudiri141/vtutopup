<?php
session_start();
require_once "config_vtu.php";
require_once "db.php";
require_once "send_verification_email.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: cable_subscription.php?error=invalid_request");
  exit();
}

$user_id = (int)$_SESSION['user_id'];

$cablename = trim($_POST['cablename'] ?? '');
$smartcard = preg_replace('/\D+/', '', trim($_POST['smart_card_number'] ?? ''));
$cableplan = trim($_POST['cableplan'] ?? '');
$pin       = trim($_POST['pin'] ?? '');

$allowedCables = ["1","2","3","4"];
if (!in_array($cablename, $allowedCables, true)) {
  header("Location: cable_subscription.php?error=invalid_cable");
  exit();
}
if ($smartcard === '' || strlen($smartcard) < 6) {
  header("Location: cable_subscription.php?error=invalid_smartcard");
  exit();
}
if (!preg_match('/^\d+$/', $cableplan)) {
  header("Location: cable_subscription.php?error=invalid_plan");
  exit();
}
if (!preg_match('/^\d{4}$/', $pin)) {
  header("Location: cable_subscription.php?error=invalid_pin");
  exit();
}

$VTU_VERIFY_URL = VTU_BASE_URL . "/api/cablesub/verify/";
$VTU_BUY_URL    = VTU_BASE_URL . "/api/cablesub/";
$TIMEOUT        = defined('VTU_TIMEOUT') ? (int)VTU_TIMEOUT : 30;

function vtu_post_json($url, $apiKey, $payload, $timeout = 30) {
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
  if ($raw !== false) $data = json_decode($raw, true);

  return [
    "ok" => ($err === '' && $http >= 200 && $http < 300),
    "http_code" => $http,
    "error" => $err ?: null,
    "raw" => $raw,
    "data" => $data
  ];
}

try {
  $conn->begin_transaction();

  // Lock user, verify pin, wallet, email
  $stmt = $conn->prepare("SELECT wallet, transaction_pin, fullname, email FROM users WHERE id=? FOR UPDATE");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $u = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$u) {
    throw new Exception("User not found");
  }
  if (empty($u['transaction_pin'])) {
    $conn->rollback();
    header("Location: account.php");
    exit();
  }
  if (!password_verify($pin, $u['transaction_pin'])) {
    $conn->rollback();
    header("Location: cable_subscription.php?error=wrong_pin");
    exit();
  }

  $wallet = (float)$u['wallet'];

  // Optional verify first
  $verifyPayload = [
    "cablename" => $cablename,
    "smart_card_number" => $smartcard
  ];
  $verify = vtu_post_json($VTU_VERIFY_URL, VTU_API_KEY, $verifyPayload, $TIMEOUT);

  $verifyMsg = "";
  if ($verify["ok"]) {
    $vd = $verify["data"] ?? [];
    $vs = strtolower((string)($vd["status"] ?? $vd["Status"] ?? ""));
    if ($vs === "success" || $vs === "successful") {
      $verifyMsg = (string)($vd["Customer_Name"] ?? $vd["name"] ?? "");
    }
  }

  // Get plan prices
  $stmt = $conn->prepare("
    SELECT price_for_basicuser, price_for_premiumuser
    FROM vtu_cabletvplans
    WHERE cabletv_plan_id=? LIMIT 1
  ");
  $stmt->bind_param("i", $cableplan);
  $stmt->execute();
  $plan = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$plan) {
    $conn->rollback();
    header("Location: cable_subscription.php?error=plan_not_found");
    exit();
  }

  $amount_sell = (float)$plan["price_for_basicuser"];
  $amount_cost = (float)$plan["price_for_premiumuser"];
  $profit = $amount_sell - $amount_cost;

  if ($amount_sell <= 0 || $amount_cost <= 0) {
    $conn->rollback();
    header("Location: cable_subscription.php?error=invalid_plan_price");
    exit();
  }

  if ($wallet < $amount_sell) {
    $conn->rollback();
    header("Location: cable_subscription.php?error=insufficient_funds");
    exit();
  }

  $ref = "CAB_" . date("YmdHis") . "_" . rand(100,999);

  $service = "cable";
  $provider = "vtunaija";
  $status = "pending";
  $provider_message = $verifyMsg ? ("Verified: " . $verifyMsg) : "";

  $cableNameReadable = [
    "1" => "GOTV",
    "2" => "DSTV",
    "3" => "STARTIMES",
    "4" => "SHOWMAX"
  ][$cablename] ?? $cablename;

  // Insert pending transaction
  $stmt = $conn->prepare("
    INSERT INTO vtu_transactions
      (user_id, service, provider, ref, network, phone, plan_id,
       amount_sell, amount_cost, profit, status, provider_message, created_at)
    VALUES
      (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
  ");
  $stmt->bind_param(
    "isssssdddsss",
    $user_id,
    $service,
    $provider,
    $ref,
    $cableNameReadable,
    $smartcard,
    $cableplan,
    $amount_sell,
    $amount_cost,
    $profit,
    $status,
    $provider_message
  );
  $stmt->execute();
  $stmt->close();

  // Call purchase API
  $buyPayload = [
    "cablename" => $cablename,
    "smart_card_number" => $smartcard,
    "cableplan" => $cableplan
  ];
  $api = vtu_post_json($VTU_BUY_URL, VTU_API_KEY, $buyPayload, $TIMEOUT);

  if (!$api["ok"]) {
    $msg = "HTTP {$api['http_code']}" . ($api['error'] ? " - {$api['error']}" : "");

    $stmt = $conn->prepare("
      UPDATE vtu_transactions
      SET status='failed', provider_message=?
      WHERE user_id=? AND ref=?
      LIMIT 1
    ");
    $stmt->bind_param("sis", $msg, $user_id, $ref);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    header("Location: cable_subscription.php?error=api_connection");
    exit();
  }

  $data = $api["data"] ?? [];
  $apiStatus = strtolower((string)($data["status"] ?? $data["Status"] ?? ""));
  $apiMsg = (string)($data["api_response"] ?? $data["message"] ?? "No response");

  if ($apiStatus !== "success" && $apiStatus !== "successful") {
    $stmt = $conn->prepare("
      UPDATE vtu_transactions
      SET status='failed', provider_message=?
      WHERE user_id=? AND ref=?
      LIMIT 1
    ");
    $stmt->bind_param("sis", $apiMsg, $user_id, $ref);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    header("Location: cable_subscription.php?error=api_failed");
    exit();
  }

  // Deduct wallet
  $new_wallet = $wallet - $amount_sell;
  $stmt = $conn->prepare("UPDATE users SET wallet=? WHERE id=?");
  $stmt->bind_param("di", $new_wallet, $user_id);
  $stmt->execute();
  $stmt->close();

  $_SESSION['wallet'] = $new_wallet;

  // Update success
  $stmt = $conn->prepare("
    UPDATE vtu_transactions
    SET status='success', provider_message=?
    WHERE user_id=? AND ref=?
    LIMIT 1
  ");
  $stmt->bind_param("sis", $apiMsg, $user_id, $ref);
  $stmt->execute();
  $stmt->close();

  // Send email receipt
  if (!empty($u['email'])) {
    sendTransactionEmail(
      $u['email'],
      $u['fullname'] ?? 'User',
      "Cable Subscription",
      $amount_sell,
      $ref
    );
  }

  $conn->commit();
  header("Location: cable_subscription.php?success=1&ref=" . urlencode($ref));
  exit();

} catch (Throwable $e) {
  if ($conn) {
    $conn->rollback();
  }
  header("Location: cable_subscription.php?error=server_error");
  exit();
}