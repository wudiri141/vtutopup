<?php
include "db.php";
require_once "auth_helper.php";

ensureAuthSchema($conn);

function stopNow($msg){
    header("Location: forgot_password_page.php?error=" . urlencode($msg));
    exit();
}

$token = $_POST['token'] ?? '';
$pass1 = $_POST['password'] ?? '';
$pass2 = $_POST['confirm_password'] ?? '';

if ($token === '' || $pass1 === '' || $pass2 === '') {
    stopNow("All fields are required.");
}

if (strlen($pass1) < 6) {
    stopNow("Password must be at least 6 characters.");
}

if ($pass1 !== $pass2) {
    stopNow("Passwords do not match.");
}

$stmt = $conn->prepare("SELECT id, reset_expires FROM users WHERE reset_token=? LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    stopNow("Invalid reset token.");
}

if (empty($user['reset_expires']) || strtotime($user['reset_expires']) < time()) {
    stopNow("Reset link has expired.");
}

$newHash = password_hash($pass1, PASSWORD_DEFAULT);

$up = $conn->prepare("
    UPDATE users
    SET password=?,
        reset_token=NULL,
        reset_expires=NULL,
        password_changed_at=NOW(),
        last_otp_verified_at=NULL
    WHERE id=?
");
$up->bind_param("si", $newHash, $user['id']);

if ($up->execute()) {
    // clear trusted cookie too
    setcookie('trusted_device_token', '', time() - 3600, '/');
    header("Location: login.php?success=" . urlencode("Password reset successful. Please log in again."));
    exit();
} else {
    stopNow("Could not reset password.");
}
