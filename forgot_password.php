<?php
include "db.php";
require_once "auth_helper.php";
require_once "send_verification_email.php";

ensureAuthSchema($conn);

function back($msg){
    header("Location: forgot_password_page.php?error=" . urlencode($msg));
    exit();
}

$email = trim($_POST['email'] ?? '');

if ($email === '') {
    back("Enter your email address.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    back("Invalid email address.");
}

$stmt = $conn->prepare("SELECT id, fullname, email FROM users WHERE email=? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    back("No account found with that email.");
}

$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

$up = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id=?");
$up->bind_param("ssi", $token, $expires, $user['id']);

if ($up->execute()) {
    $sent = sendPasswordResetEmail($user['email'], $user['fullname'], $token);

    if ($sent) {
        header("Location: forgot_password_page.php?success=" . urlencode("Password reset email sent successfully."));
        exit();
    } else {
        back("Could not send password reset email.");
    }
} else {
    back("Something went wrong.");
}
