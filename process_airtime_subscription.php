<?php
session_start();
require_once "config_vtu.php";
require_once "db.php";
require_once "send_verification_email.php";

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: airtime_subscription.php?error=invalid_request"); exit(); }

$user_id = (int)$_SESSION['user_id'];
$network = trim($_POST['network'] ?? '');
$phone   = preg_replace('/\D+/', '', trim($_POST['phone'] ?? ''));
$amount  = (float)($_POST['amount'] ?? 0);
$pin     = trim($_POST['pin'] ?? ''); // from pin_modal.php

// VTUnaija network IDs
$netMap = [
  "MTN"     => 1,
  "GLO"     => 2,
  "9MOBILE" => 3,
  "AIRTEL"  => 4
];

if(!isset($netMap[$network])) { header("Location: airtime_subscription.php?error=invalid_network"); exit(); }
if(!preg_match('/^\d{4}$/', $pin)) { header("Location: airtime_subscription.php?error=invalid_pin"); exit(); }
if(strlen($phone) < 10 || strlen($phone) > 15) { header("Location: airtime_subscription.php?error=invalid_phone"); exit(); }
if($amount < 50) { header("Location: airtime_subscription.php?error=min_50"); exit(); }

// ====== CONFIG ======
$VTU_URL = VTU_BASE_URL . "/api/topup/";
$VTU_API_KEY = VTU_API_KEY;
$TIMEOUT = defined('VTU_TIMEOUT') ? (int)VTU_TIMEOUT : 30;

// For airtime, provider says 100% (cost = amount). You can add markup later.
$amount_cost = $amount;
$amount_sell = $amount;
$profit = $amount_sell - $amount_cost;

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

  // lock user + verify pin + wallet
  $stmt = $conn->prepare("SELECT wallet, transaction_pin FROM users WHERE id=? FOR UPDATE");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $u = $stmt->get_result()->fetch_assoc();

  if(!$u) throw new Exception("User not found");
  if(empty($u['transaction_pin'])) { $conn->rollback(); header("Location: account.php"); exit(); }
  if(!password_verify($pin, $u['transaction_pin'])) { $conn->rollback(); header("Location: airtime_subscription.php?error=wrong_pin"); exit(); }

  $wallet = (float)$u['wallet'];
  if($wallet < $amount_sell){ $conn->rollback(); header("Location: airtime_subscription.php?error=insufficient_funds"); exit(); }

  // Create unique ref
  $ref = "AIR_" . date("YmdHis") . "_" . rand(100,999);

  // 1) Insert pending VTU transaction
  $service = "airtime";
  $provider = "vtunaija";
  $status = "pending";
  $provider_message = "";

  $stmt = $conn->prepare("
    INSERT INTO vtu_transactions
      (user_id, service, provider, ref, network, phone, plan_id,
       amount_sell, amount_cost, profit, status, provider_message, created_at)
    VALUES
      (?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, NOW())
  ");
  $netName = $network; // store readable name
  $stmt->bind_param(
    "isssssdddss",
    $user_id, $service, $provider, $ref, $netName, $phone,
    $amount_sell, $amount_cost, $profit, $status, $provider_message
  );
  $stmt->execute();

  // 2) Call VTUnaija API
  $payload = [
    "network" => (string)$netMap[$network],
    "mobile_number" => $phone,
    "Ported_number" => "true",
    "request-id" => $ref,
    "amount" => (string)$amount_cost,
    "airtime_type" => "VTU"
  ];

  $api = vtu_post_json($VTU_URL, $VTU_API_KEY, $payload, $TIMEOUT);

  // If HTTP error or curl error
  if (!$api["ok"]) {
    $msg = "HTTP {$api['http_code']}".($api['error'] ? " - {$api['error']}" : "");
    $stmt = $conn->prepare("UPDATE vtu_transactions SET status='failed', provider_message=? WHERE user_id=? AND ref=? LIMIT 1");
    $stmt->bind_param("sis", $msg, $user_id, $ref);
    $stmt->execute();

    $conn->commit();
    header("Location: airtime_subscription.php?error=api_connection");
    exit();
  }

  $data = $api["data"] ?? [];
  $apiStatus = strtolower((string)($data["status"] ?? $data["Status"] ?? ""));
  $apiMsg = (string)($data["api_response"] ?? $data["message"] ?? "No response");

  if ($apiStatus !== "success" && $apiStatus !== "successful") {
    // fail: do not deduct
    $stmt = $conn->prepare("UPDATE vtu_transactions SET status='failed', provider_message=? WHERE user_id=? AND ref=? LIMIT 1");
    $stmt->bind_param("sis", $apiMsg, $user_id, $ref);
    $stmt->execute();

    $conn->commit();
    header("Location: airtime_subscription.php?error=api_failed");
    exit();
  }

  // 3) Success: deduct wallet
  $new_wallet = $wallet - $amount_sell;
  $stmt = $conn->prepare("UPDATE users SET wallet=? WHERE id=?");
  $stmt->bind_param("di", $new_wallet, $user_id);
  $stmt->execute();
  $_SESSION['wallet'] = $new_wallet;

  // 4) Update transaction to success
  $stmt = $conn->prepare("UPDATE vtu_transactions SET status='success', provider_message=? WHERE user_id=? AND ref=? LIMIT 1");
  $stmt->bind_param("sis", $apiMsg, $user_id, $ref);
  $stmt->execute();
    // ===== Send Email Receipt =====
  $userStmt = $conn->prepare("SELECT fullname, email FROM users WHERE id=? LIMIT 1");
  $userStmt->bind_param("i", $user_id);
  $userStmt->execute();
  $user = $userStmt->get_result()->fetch_assoc();

  if ($user && !empty($user['email'])) {

      sendTransactionEmail(
          $user['email'],
          $user['fullname'],
          "Airtime Purchase",
          $amount_sell,
          $ref
      );

  }

  $conn->commit();
  header("Location: airtime_subscription.php?success=1&ref=" . urlencode($ref));
  exit();

} catch (Throwable $e) {
  if ($conn) $conn->rollback();
  header("Location: airtime_subscription.php?error=server_error");
  exit();
}
