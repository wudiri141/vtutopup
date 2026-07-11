<?php
session_start();
include "db.php";
require_once "auth_helper.php";

ensureAuthSchema($conn);

function fail($msg){
    header("Location: verify_login_otp.php?error=" . urlencode($msg));
    exit();
}

$userId = $_SESSION['otp_user_id'] ?? 0;
$otp = trim($_POST['otp'] ?? '');

if (!$userId || $otp === '') {
    fail("Invalid OTP request.");
}

if (!preg_match('/^\d{4}$/', $otp)) {
    fail("OTP must be 4 digits.");
}

$stmt = $conn->prepare("
    SELECT id, fullname, email, wallet, profile, role, login_otp, login_otp_expires
    FROM users
    WHERE id=? LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    fail("User not found.");
}

if (($user['login_otp'] ?? '') !== $otp) {
    fail("Invalid OTP.");
}

if (empty($user['login_otp_expires']) || strtotime($user['login_otp_expires']) < time()) {
    fail("OTP has expired.");
}

// clear otp + save otp verified time
$clear = $conn->prepare("
    UPDATE users
    SET login_otp=NULL,
        login_otp_expires=NULL,
        last_otp_verified_at=NOW()
    WHERE id=?
");
$clear->bind_param("i", $user['id']);
$clear->execute();
$clear->close();

// create trusted device cookie for 30 days
$trustedToken = bin2hex(random_bytes(32));
setcookie(
    'trusted_device_token',
    $trustedToken,
    time() + (30 * 24 * 60 * 60),
    '/',
    '',
    isset($_SERVER['HTTPS']),
    true
);

unset($_SESSION['otp_user_id']);

$_SESSION['user_id']  = (int)$user['id'];
$_SESSION['fullname'] = $user['fullname'] ?? "User";
$_SESSION['wallet']   = (float)($user['wallet'] ?? 0);
$_SESSION['profile']  = $user['profile'] ?? "profile.png";
$_SESSION['role']     = $user['role'] ?? "user";

header("Location: dashboard.php");
exit();
