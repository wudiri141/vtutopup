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
  header("Location: electricity_subscription.php?error=invalid_request");
  exit();
}

$user_id = (int)$_SESSION['user_id'];

$disco     = trim($_POST['disco_name'] ?? '');
$meter     = preg_replace('/\D+/', '', trim($_POST['meter_number'] ?? ''));
$meterType = strtolower(trim($_POST['MeterType'] ?? 'prepaid'));
$amount    = (float)($_POST['amount'] ?? 0);
$pin       = trim($_POST['pin'] ?? '');

if (!preg_match('/^\d+$/', $disco)) {
  header("Location: electricity_subscription.php?error=invalid_disco");
  exit();
}
if ($meter === '' || strlen($meter) < 6) {
  header("Location: electricity_subscription.php?error=invalid_meter");
  exit();
}
if (!in_array($meterType, ["prepaid","postpaid"], true)) {
  $meterType = "prepaid";
}
if ($amount < 100) {
  header("Location: electricity_subscription.php?error=min_100");
  exit();
}
if (!preg_match('/^\d{4}$/', $pin)) {
  header("Location: electricity_subscription.php?error=invalid_pin");
  exit();
}

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

  // lock user + verify pin + wallet + email
  $stmt = $conn->prepare("SELECT wallet, transaction_pin, fullname, email FROM users WHERE id=? FOR UPDATE");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $u = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$u) throw new Exception("User not found");

  if (empty($u['transaction_pin'])) {
    $conn->rollback();
    header("Location: account.php");
    exit();
  }

  if (!password_verify($pin, $u['transaction_pin'])) {
    $conn->rollback();
    header("Location: electricity_subscription.php?error=wrong_pin");
    exit();
  }

  $wallet = (float)$u['wallet'];
  if ($wallet < $amount) {
    $conn->rollback();
    header("Location: electricity_subscription.php?error=insufficient_funds");
    exit();
  }

  $ref = "ELEC_" . date("YmdHis") . "_" . rand(100,999);

  // Call VTU bill payment API
  $buyUrl = VTU_BASE_URL . "/api/billpayment/";
  $payload = [
    "disco_name" => $disco,
    "meter_number" => $meter,
    "MeterType" => $meterType,
    "amount" => (string)$amount
  ];

  $api = vtu_post_json($buyUrl, VTU_API_KEY, $payload, VTU_TIMEOUT);

  $meta = json_encode([
    "service" => "electricity",
    "disco_name" => $disco,
    "meter_number" => $meter,
    "MeterType" => $meterType,
    "amount" => $amount,
    "api_http" => $api["http_code"],
    "api_error" => $api["error"],
    "api_raw" => $api["raw"],
    "api_data" => $api["data"] ?? null
  ]);

  if (!$api["ok"]) {
    $desc = "Electricity payment API connection failed";
    $stmt = $conn->prepare("
      INSERT INTO transactions (user_id, type, service, amount, status, reference, description, meta, created_at)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $type = "debit";
    $service = "electricity";
    $status = "failed";
    $stmt->bind_param("issdssss", $user_id, $type, $service, $amount, $status, $ref, $desc, $meta);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    header("Location: electricity_subscription.php?error=api_connection");
    exit();
  }

  $d = $api["data"] ?? [];
  $apiStatus = strtolower((string)($d["status"] ?? $d["Status"] ?? ""));
  $apiMsg = (string)($d["api_response"] ?? $d["message"] ?? "No response");
  $token = (string)($d["token"] ?? $d["electricitytoken"] ?? "");

  if ($apiStatus !== "success" && $apiStatus !== "successful") {
    $desc = $apiMsg ?: "Electricity payment failed";

    $stmt = $conn->prepare("
      INSERT INTO transactions (user_id, type, service, amount, status, reference, description, meta, created_at)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $type = "debit";
    $service = "electricity";
    $status = "failed";
    $stmt->bind_param("issdssss", $user_id, $type, $service, $amount, $status, $ref, $desc, $meta);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    header("Location: electricity_subscription.php?error=api_failed");
    exit();
  }

  // Deduct wallet
  $new_wallet = $wallet - $amount;
  $stmt = $conn->prepare("UPDATE users SET wallet=? WHERE id=?");
  $stmt->bind_param("di", $new_wallet, $user_id);
  $stmt->execute();
  $stmt->close();

  $_SESSION['wallet'] = $new_wallet;

  // Log success
  $desc = $apiMsg . ($token ? " | Token: {$token}" : "");
  $stmt = $conn->prepare("
    INSERT INTO transactions (user_id, type, service, amount, status, reference, description, meta, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
  ");
  $type = "debit";
  $service = "electricity";
  $status = "completed";
  $stmt->bind_param("issdssss", $user_id, $type, $service, $amount, $status, $ref, $desc, $meta);
  $stmt->execute();
  $stmt->close();

  // Send email receipt
  if (!empty($u['email'])) {
    sendTransactionEmail(
      $u['email'],
      $u['fullname'] ?? 'User',
      "Electricity Payment",
      $amount,
      $ref
    );
  }

  $conn->commit();
  header("Location: electricity_subscription.php?success=1&ref=" . urlencode($ref));
  exit();

} catch (Throwable $e) {
  if ($conn) {
    $conn->rollback();
  }
  header("Location: electricity_subscription.php?error=server_error");
  exit();
}