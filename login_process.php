<?php
session_start();
include "db.php";
require_once "auth_helper.php";
require_once "send_verification_email.php";

ensureAuthSchema($conn);

function fail($msg){
  header("Location: login.php?error=" . urlencode($msg));
  exit();
}

/* ================= CSRF ================= */
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
  fail("Invalid request.");
}

/* ================= INPUT ================= */
$identity = trim($_POST['identity'] ?? '');
$password = $_POST['password'] ?? '';

if ($identity === '' || $password === '') {
  fail("All fields are required.");
}

/* ================= CHECK USER ================= */
$isEmail = filter_var($identity, FILTER_VALIDATE_EMAIL);
$phone = preg_replace('/\D+/', '', $identity);

if ($isEmail) {
  $stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
  $stmt->bind_param("s", $identity);
} else {
  $stmt = $conn->prepare("SELECT * FROM users WHERE phone=? LIMIT 1");
  $stmt->bind_param("s", $phone);
}

$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* ================= VERIFY ================= */
if (!$user || !password_verify($password, $user['password'])) {
  fail("Invalid login details.");
}

if ((int)$user['email_verified'] !== 1) {
  fail("Please verify your email first.");
}

/* ================= TRUSTED DEVICE ================= */
$needsOtp = true;

if (!empty($user['last_otp_verified_at'])) {
    $lastOtpTime = strtotime($user['last_otp_verified_at']);
    $thirtyDays = 30 * 24 * 60 * 60;

    if ($lastOtpTime && (time() - $lastOtpTime) < $thirtyDays) {
        $needsOtp = false;
    }
}

/* Force OTP if password changed */
if (!empty($user['password_changed_at']) && !empty($user['last_otp_verified_at'])) {
    if (strtotime($user['password_changed_at']) > strtotime($user['last_otp_verified_at'])) {
        $needsOtp = true;
    }
}

/* ================= LOGIN DIRECT ================= */
if (!$needsOtp) {

  $_SESSION['user_id']  = (int)$user['id'];
  $_SESSION['fullname'] = $user['fullname'];
  $_SESSION['wallet']   = (float)$user['wallet'];
  $_SESSION['profile']  = $user['profile'] ?? "profile.png";
  $_SESSION['role']     = $user['role'];

  header("Location: dashboard.php");
  exit();
}

/* ================= GENERATE OTP ================= */
$otp = (string)random_int(1000, 9999);
$otpExpires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

$up = $conn->prepare("UPDATE users SET login_otp=?, login_otp_expires=? WHERE id=?");
$up->bind_param("ssi", $otp, $otpExpires, $user['id']);

if (!$up->execute()) {
  error_log("OTP Error: " . $up->error);
  fail("Could not generate OTP.");
}
$up->close();

/* ================= SEND OTP ================= */
$sent = sendLoginOtpEmail($user['email'], $user['fullname'], $otp);

if (!$sent) {
  fail("Could not send OTP email.");
}

/* ================= STORE SESSION ================= */
$_SESSION['otp_user_id'] = (int)$user['id'];

header("Location: verify_login_otp.php");
exit();
