<?php
// ============================================================
// FILE: public_html/api/auth.php
// URL:  https://vtutopup.com.ng/api/auth.php
// ============================================================
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once "../db.php";
require_once "../auth_helper.php";
require_once "../referral_helper.php";
require_once "../send_verification_email.php";

ensureAuthSchema($conn);
ensureReferralSchema($conn);

function out($ok, $msg, $extra = []) {
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
    exit();
}

function authUser($conn) {
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(\S+)$/i', $h, $m)) return null;
    $stmt = $conn->prepare("SELECT id,fullname,email,phone,wallet,role,password,transaction_pin,referral_code,referral_count,referral_earnings FROM users WHERE api_token=? LIMIT 1");
    $stmt->bind_param("s", $m[1]);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $u;
}

$body   = json_decode(file_get_contents("php://input"), true) ?? [];
$action = trim($body['action'] ?? $_GET['action'] ?? '');

// ══════════════════════════════════════════
// REGISTER
// ══════════════════════════════════════════
if ($action === 'register') {
    $first = trim($body['first_name'] ?? '');
    $last  = trim($body['last_name']  ?? '');
    $phone = trim($body['phone']      ?? '');
    $email = trim($body['email']      ?? '');
    $pass1 = (string)($body['password']         ?? '');
    $pass2 = (string)($body['confirm_password'] ?? '');
    $pin   = trim($body['pin'] ?? '');
    $referralCode = strtoupper(trim($body['referral_code'] ?? ''));

    if (!$first||!$last||!$phone||!$email||!$pass1||!$pin) out(false,"All fields are required.");
    if (!filter_var($email,FILTER_VALIDATE_EMAIL))         out(false,"Invalid email address.");
    if (strlen($pass1)<6)                                  out(false,"Password must be at least 6 characters.");
    if ($pass1!==$pass2)                                   out(false,"Passwords do not match.");
    if (!preg_match('/^\d{4}$/',$pin))                     out(false,"Transaction PIN must be exactly 4 digits.");

    $chk=$conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1"); $chk->bind_param("s",$email); $chk->execute();
    if ($chk->get_result()->fetch_assoc()) out(false,"Email already registered.");
    $chk->close();

    $chk2=$conn->prepare("SELECT id FROM users WHERE phone=? LIMIT 1"); $chk2->bind_param("s",$phone); $chk2->execute();
    if ($chk2->get_result()->fetch_assoc()) out(false,"Phone number already registered.");
    $chk2->close();

    $fullname=$first.' '.$last; $pass_hash=password_hash($pass1,PASSWORD_DEFAULT);
    $pin_hash=password_hash($pin,PASSWORD_DEFAULT); $wallet=0.00; $role='user';
    $email_verified=0; $verification_token=bin2hex(random_bytes(32));
    $referrerId = getReferrerIdByCode($conn, $referralCode);
    if ($referralCode !== '' && !$referrerId) out(false,"Invalid referral code.");

    $stmt=$conn->prepare("INSERT INTO users (fullname,email,phone,password,transaction_pin,wallet,role,email_verified,verification_token) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("sssssdsis",$fullname,$email,$phone,$pass_hash,$pin_hash,$wallet,$role,$email_verified,$verification_token);
    if ($stmt->execute()) {
        $newUserId = (int)$conn->insert_id;
        ensureUserReferralCode($conn, $newUserId, $fullname);
        attachReferral($conn, $newUserId, $referrerId);
        sendVerificationEmail($email,$fullname,$verification_token);
        out(true,"Registration successful! Check your email to verify your account.");
    } else { out(false,"Registration failed. Please try again."); }
}

// ══════════════════════════════════════════
// LOGIN
// ══════════════════════════════════════════
if ($action === 'login') {
    $identity=trim($body['identity']??''); $password=(string)($body['password']??'');
    if (!$identity||!$password) out(false,"Email/phone and password are required.");

    $isEmail=filter_var($identity,FILTER_VALIDATE_EMAIL);
    $phone=preg_replace('/\D+/','',$identity);

    if ($isEmail) {
        $stmt=$conn->prepare("SELECT id,fullname,email,phone,password,wallet,role,email_verified,referral_code,referral_count,referral_earnings FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s",$identity);
    } else {
        $stmt=$conn->prepare("SELECT id,fullname,email,phone,password,wallet,role,email_verified,referral_code,referral_count,referral_earnings FROM users WHERE phone=? LIMIT 1");
        $stmt->bind_param("s",$phone);
    }
    $stmt->execute(); $user=$stmt->get_result()->fetch_assoc(); $stmt->close();

    if (!$user||!password_verify($password,$user['password'])) out(false,"Incorrect email/phone or password.");
    if ((int)$user['email_verified']!==1) out(false,"Please verify your email first. Check your inbox.");

    $token=bin2hex(random_bytes(32));
    $up=$conn->prepare("UPDATE users SET api_token=? WHERE id=?");
    if ($up) { $up->bind_param("si",$token,$user['id']); $up->execute(); $up->close(); }

    out(true,"Login successful.",[
        'token'=>$token,
        'user'=>['id'=>(int)$user['id'],'fullname'=>$user['fullname'],'email'=>$user['email'],
                 'phone'=>$user['phone'],'wallet'=>(float)$user['wallet'],'role'=>$user['role'],
                 'referral_code'=>$user['referral_code'] ?? '',
                 'referral_count'=>(int)($user['referral_count'] ?? 0),
                 'referral_earnings'=>(float)($user['referral_earnings'] ?? 0)]
    ]);
}

// ══════════════════════════════════════════
// FORGOT PASSWORD
// ══════════════════════════════════════════
if ($action === 'forgot_password') {
    $email = trim($body['email'] ?? '');

    if (!$email) out(false, "Enter your email address.");
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) out(false, "Invalid email address.");

    $stmt = $conn->prepare("SELECT id, fullname, email FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) out(false, "No account found with that email.");

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $up = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id=?");
    $up->bind_param("ssi", $token, $expires, $user['id']);

    if (!$up->execute()) out(false, "Something went wrong.");
    $up->close();

    $sent = sendPasswordResetEmail($user['email'], $user['fullname'], $token);
    if (!$sent) out(false, "Could not send password reset email.");

    out(true, "Password reset email sent successfully.");
}

// ══════════════════════════════════════════
// CHANGE PASSWORD
// ══════════════════════════════════════════
if ($action === 'change_password') {
    $user=authUser($conn);
    if (!$user) out(false,"Unauthorized.");

    $current = (string)($body['current_password'] ?? '');
    $new1    = (string)($body['new_password']     ?? '');
    $new2    = (string)($body['confirm_password'] ?? '');

    if (!$current||!$new1||!$new2) out(false,"All fields are required.");
    if (strlen($new1)<6)           out(false,"New password must be at least 6 characters.");
    if ($new1!==$new2)             out(false,"New passwords do not match.");
    if (!password_verify($current,$user['password'])) out(false,"Current password is incorrect.");

    $hash=$conn->prepare("UPDATE users SET password=?, password_changed_at=NOW(), last_otp_verified_at=NULL WHERE id=?");
    $hashed=password_hash($new1,PASSWORD_DEFAULT);
    $hash->bind_param("si",$hashed,$user['id']); $hash->execute(); $hash->close();

    out(true,"Password changed successfully.");
}

// ══════════════════════════════════════════
// CHANGE PIN
// ══════════════════════════════════════════
if ($action === 'change_pin') {
    $user=authUser($conn);
    if (!$user) out(false,"Unauthorized.");

    $old    = trim($body['old_pin']     ?? '');
    $new1   = trim($body['new_pin']     ?? '');
    $new2   = trim($body['confirm_pin'] ?? '');

    if (!$old||!$new1||!$new2)          out(false,"All fields are required.");
    if (!preg_match('/^\d{4}$/',$new1)) out(false,"New PIN must be exactly 4 digits.");
    if ($new1!==$new2)                  out(false,"New PINs do not match.");
    if (empty($user['transaction_pin'])||!password_verify($old,$user['transaction_pin']))
        out(false,"Current PIN is incorrect.");

    $hashed=password_hash($new1,PASSWORD_DEFAULT);
    $s=$conn->prepare("UPDATE users SET transaction_pin=? WHERE id=?");
    $s->bind_param("si",$hashed,$user['id']); $s->execute(); $s->close();

    out(true,"Transaction PIN changed successfully.");
}

// ══════════════════════════════════════════
// SUPPORT MESSAGE
// ══════════════════════════════════════════
if ($action === 'support') {
    $user=authUser($conn);
    if (!$user) out(false,"Unauthorized.");

    $subject = trim($body['subject'] ?? '');
    $message = trim($body['message'] ?? '');

    if (!$subject||!$message) out(false,"Subject and message are required.");

    // Email support to admin
    $adminEmail = "support@vtutopup.com.ng";
    $from       = $user['email'];
    $name       = $user['fullname'];

    $headers  = "From: noreply@vtutopup.com.ng\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $body_html = "
    <h3>Support Request from {$name}</h3>
    <p><strong>Email:</strong> {$from}</p>
    <p><strong>Phone:</strong> {$user['phone']}</p>
    <p><strong>Subject:</strong> {$subject}</p>
    <p><strong>Message:</strong></p>
    <p>{$message}</p>
    ";

    $sent = mail($adminEmail, "VTU TOPUP Support: $subject", $body_html, $headers);

    // Also send confirmation to user
    $confirm_headers  = "From: noreply@vtutopup.com.ng\r\n";
    $confirm_headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $confirm_body = "<h3>Hi {$name},</h3><p>We received your support request: <b>{$subject}</b>.</p><p>Our team will respond within 24 hours.</p><p>Team VTU TOPUP</p>";
    mail($from, "Support Request Received - VTU TOPUP", $confirm_body, $confirm_headers);

    out(true,"Your message has been sent! We will respond within 24 hours.");
}

out(false,"Unknown action.");
