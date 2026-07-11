<?php
session_start();
require_once "db.php";
require_once "auth_helper.php";
require_once "send_verification_email.php";

ensureAuthSchema($conn);

if (!isset($_SESSION['otp_user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['otp_user_id'];

$stmt = $conn->prepare("SELECT email, fullname FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: login.php");
    exit();
}

$email = $user['email'];
$name  = $user['fullname'];

$otp = (string)random_int(1000, 9999);
$expires = date("Y-m-d H:i:s", strtotime("+10 minutes"));

$stmt = $conn->prepare("UPDATE users SET login_otp=?, login_otp_expires=? WHERE id=?");
$stmt->bind_param("ssi", $otp, $expires, $user_id);
$stmt->execute();

sendLoginOtpEmail($email, $name, $otp);

header("Location: verify_login_otp.php?success=OTP sent again");
exit();
