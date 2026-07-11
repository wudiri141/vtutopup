<?php
session_start();
include "db.php";
require_once __DIR__ . "/vtu_services.php";
require_once "send_verification_email.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: data_subscription.php?error=" . urlencode("Invalid request"));
  exit();
}

$user_id  = (int)$_SESSION['user_id'];
$network  = strtoupper(trim($_POST['network'] ?? ''));
$phone    = trim($_POST['phone'] ?? '');
$plan_id  = trim($_POST['plan_id'] ?? '');

$allowed = ['MTN','AIRTEL','GLO','9MOBILE'];
if (!in_array($network, $allowed, true)) {
  header("Location: data_subscription.php?error=" . urlencode("Invalid network selected"));
  exit();
}

if ($plan_id === '' || !ctype_digit($plan_id)) {
  header("Location: data_subscription.php?error=" . urlencode("Invalid plan selected"));
  exit();
}

if (!preg_match('/^\d{11}$/', preg_replace('/\D+/', '', $phone))) {
  header("Location: data_subscription.php?error=" . urlencode("Enter a valid 11-digit phone number"));
  exit();
}

$phone_clean = preg_replace('/\D+/', '', $phone);

// 1) Fetch user wallet + email info
$stmt = $conn->prepare("SELECT wallet, fullname, email FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$wallet = (float)($user['wallet'] ?? 0);

// 2) Fetch plan from DB
$stmt = $conn->prepare("
  SELECT data_plan_id, network, sell_price, cost_price
  FROM vtu_dataplans
  WHERE data_plan_id=? AND network=? AND status='On'
  LIMIT 1
");
$stmt->bind_param("is", $plan_id, $network);
$stmt->execute();
$plan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$plan) {
  header("Location: data_subscription.php?error=" . urlencode("Plan not found. Please sync plans again."));
  exit();
}

$sell_price = (float)$plan['sell_price'];
$cost_price = (float)$plan['cost_price'];

if ($wallet < $sell_price) {
  header("Location: data_subscription.php?error=" . urlencode("Insufficient wallet balance"));
  exit();
}

// 3) Map network name -> VTU network id
$network_id_map = [
  'MTN' => '1',
  'GLO' => '2',
  '9MOBILE' => '3',
  'AIRTEL' => '4'
];
$network_id = $network_id_map[$network];

// 4) Create reference
$ref = vtu_request_id();

// 5) Insert transaction as pending
$profit = $sell_price - $cost_price;
$network_name = $network;

$stmtT = $conn->prepare("
  INSERT INTO vtu_transactions
  (user_id, service, provider, ref, network, phone, plan_id, amount_sell, amount_cost, profit, status, provider_message, provider_raw, created_at)
  VALUES (?, 'data', 'vtunaija', ?, ?, ?, ?, ?, ?, ?, 'pending', NULL, NULL, NOW())
");

$stmtT->bind_param(
  "isssdddd",
  $user_id,
  $ref,
  $network_name,
  $phone_clean,
  $plan_id,
  $sell_price,
  $cost_price,
  $profit
);

$stmtT->execute();
$stmtT->close();

// 6) Deduct wallet
$stmt = $conn->prepare("UPDATE users SET wallet = wallet - ? WHERE id=? AND wallet >= ?");
$stmt->bind_param("did", $sell_price, $user_id, $sell_price);
$stmt->execute();

if ($stmt->affected_rows < 1) {
  $stmt->close();

  $failMsg = "Wallet deduction failed";
  $stmtFail = $conn->prepare("UPDATE vtu_transactions SET status='failed', provider_message=? WHERE ref=? LIMIT 1");
  $stmtFail->bind_param("ss", $failMsg, $ref);
  $stmtFail->execute();
  $stmtFail->close();

  header("Location: data_subscription.php?error=" . urlencode("Wallet deduction failed. Try again."));
  exit();
}
$stmt->close();

// update session wallet
$_SESSION['wallet'] = $wallet - $sell_price;

// 7) Call VTU API
$api = vtu_buy_data($network_id, $phone_clean, $plan_id, $ref);

// 8) Handle result
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

  // Send email receipt
  if (!empty($user['email'])) {
    sendTransactionEmail(
      $user['email'],
      $user['fullname'] ?? 'User',
      "Data Subscription",
      $sell_price,
      $ref
    );
  }

  header("Location: data_subscription.php?success=1&ref=" . urlencode($ref));
  exit();
}

// FAIL -> refund wallet
$err = $api['error'] ?? 'VTU request failed';
$raw = $api['raw'] ?? '';

// refund user
$stmt = $conn->prepare("UPDATE users SET wallet = wallet + ? WHERE id=?");
$stmt->bind_param("di", $sell_price, $user_id);
$stmt->execute();
$stmt->close();

$_SESSION['wallet'] = $wallet;

// mark transaction failed
$stmt = $conn->prepare("
  UPDATE vtu_transactions
  SET status='failed', provider_message=?, provider_raw=?
  WHERE ref=?
  LIMIT 1
");
$stmt->bind_param("sss", $err, $raw, $ref);
$stmt->execute();
$stmt->close();

header("Location: data_subscription.php?error=" . urlencode($err));
exit();
