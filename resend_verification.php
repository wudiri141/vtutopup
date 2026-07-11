<?php
include "db.php";
require_once "send_verification_email.php";

function back($msg){
    header("Location: resend_verification_page.php?error=" . urlencode($msg));
    exit();
}

$email = trim($_POST['email'] ?? '');

if ($email === '') {
    back("Enter your email address.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    back("Invalid email address.");
}

$stmt = $conn->prepare("SELECT id, fullname, email, email_verified FROM users WHERE email=? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    back("No account found with that email.");
}

if ((int)$user['email_verified'] === 1) {
    back("This email is already verified. You can login.");
}

$newToken = bin2hex(random_bytes(32));

$up = $conn->prepare("UPDATE users SET verification_token=? WHERE id=?");
$up->bind_param("si", $newToken, $user['id']);

if ($up->execute()) {
    $sent = sendVerificationEmail($user['email'], $user['fullname'], $newToken);

    if ($sent) {
        header("Location: resend_verification_page.php?success=" . urlencode("Verification email sent successfully."));
        exit();
    } else {
        back("Could not send verification email.");
    }
} else {
    back("Something went wrong.");
}