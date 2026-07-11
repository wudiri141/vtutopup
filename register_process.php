<?php
session_start();
include "db.php";
require_once "send_verification_email.php";
require_once "referral_helper.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php?error=invalid_request");
    exit();
}

/* ================= INPUT ================= */
$first = trim($_POST['first_name'] ?? '');
$last  = trim($_POST['last_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$pass1 = $_POST['password'] ?? '';
$pass2 = $_POST['confirm_password'] ?? '';
$pin   = trim($_POST['pin'] ?? '');
$referralCode = strtoupper(trim($_POST['referral_code'] ?? $_GET['ref'] ?? ''));

/* ================= VALIDATION ================= */
if (!$first || !$last || !$phone || !$email || !$pin) {
    header("Location: register.php?error=fill_all_fields");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: register.php?error=invalid_email");
    exit();
}

if (strlen($pass1) < 6) {
    header("Location: register.php?error=password_too_short");
    exit();
}

if ($pass1 !== $pass2) {
    header("Location: register.php?error=password_not_match");
    exit();
}

if (!preg_match('/^\d{4}$/', $pin)) {
    header("Location: register.php?error=pin_must_be_4_digits");
    exit();
}

/* ================= CHECK USER ================= */
$chk = $conn->prepare("SELECT id FROM users WHERE email=? OR phone=? LIMIT 1");
$chk->bind_param("ss", $email, $phone);
$chk->execute();
$res = $chk->get_result();

if ($res->num_rows > 0) {
    header("Location: register.php?error=user_exists");
    exit();
}
$chk->close();

/* ================= PROCESS ================= */
$fullname = $first . " " . $last;
$pass_hash = password_hash($pass1, PASSWORD_DEFAULT);
$pin_hash  = password_hash($pin, PASSWORD_DEFAULT);

$wallet = "0.00"; // safer as string
$role   = "user";
$email_verified = 0;
$verification_token = bin2hex(random_bytes(32));
$referrerId = getReferrerIdByCode($conn, $referralCode);
if ($referralCode !== '' && !$referrerId) {
    header("Location: register.php?error=invalid_referral_code");
    exit();
}

/* ================= INSERT ================= */
$stmt = $conn->prepare("
    INSERT INTO users (
        fullname,
        email,
        phone,
        password,
        transaction_pin,
        wallet,
        role,
        email_verified,
        verification_token
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "sssssssis", // ALL STRINGS except email_verified
    $fullname,
    $email,
    $phone,
    $pass_hash,
    $pin_hash,
    $wallet,
    $role,
    $email_verified,
    $verification_token
);

/* ================= EXECUTE ================= */
if ($stmt->execute()) {
    $newUserId = (int)$conn->insert_id;
    ensureUserReferralCode($conn, $newUserId, $fullname);
    attachReferral($conn, $newUserId, $referrerId);

    $mailSent = sendVerificationEmail($email, $fullname, $verification_token);

    if ($mailSent) {
        header("Location: register.php?success=verify_email_sent");
    } else {
        header("Location: register.php?success=account_created_email_failed");
    }

} else {
    // DEBUG (important)
    error_log("Register Error: " . $stmt->error);
    header("Location: register.php?error=server_error");
}

$stmt->close();
$conn->close();
