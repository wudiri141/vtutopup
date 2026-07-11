<?php
session_start();

// Only allow access if coming from registration
if (!isset($_SESSION['verify_email'])) {
    header("Location: register.php");
    exit();
}

$maskedEmail = $_SESSION['verify_email_mask'] ?? 'your email';
$realEmail   = $_SESSION['verify_email'] ?? '';

// Handle resend
$resendMsg   = '';
$resendError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend'])) {
    include "db.php";
    require_once "send_verification_email.php";

    $stmt = $conn->prepare("SELECT id, fullname, email_verified FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $realEmail);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $resendError = "Account not found.";
    } elseif ((int)$user['email_verified'] === 1) {
        // Already verified — redirect to login
        session_destroy();
        header("Location: login.php?success=" . urlencode("Email already verified. You can now sign in."));
        exit();
    } else {
        $newToken = bin2hex(random_bytes(32));
        $up = $conn->prepare("UPDATE users SET verification_token=? WHERE id=?");
        $up->bind_param("si", $newToken, $user['id']);
        $up->execute();
        $up->close();

        $sent = sendVerificationEmail($realEmail, $user['fullname'], $newToken);
        $resendMsg = $sent
            ? "Verification email resent! Check your inbox."
            : "Failed to send. Please try again in a moment.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check Your Email – VTU TOPUP</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:"Poppins",sans-serif; }

    body {
      background: #F5F7FA;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .card {
      background: #ffffff;
      border-radius: 20px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.10);
      padding: 48px 40px;
      max-width: 480px;
      width: 100%;
      text-align: center;
    }

    /* Logo */
    .brand {
      margin-bottom: 32px;
    }
    .brand img {
      width: 72px;
      height: 72px;
      object-fit: contain;
      border-radius: 16px;
    }
    .brand-name {
      font-size: 20px;
      font-weight: 700;
      color: #29ABE2;
      margin-top: 8px;
    }

    /* Email icon circle */
    .icon-circle {
      width: 88px;
      height: 88px;
      background: linear-gradient(135deg, #29ABE2, #1A8FC0);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 28px;
      box-shadow: 0 8px 20px rgba(41,171,226,0.35);
    }
    .icon-circle i {
      font-size: 36px;
      color: #ffffff;
    }

    h1 {
      font-size: 24px;
      font-weight: 700;
      color: #111827;
      margin-bottom: 12px;
    }

    .subtitle {
      font-size: 15px;
      color: #6b7280;
      line-height: 1.6;
      margin-bottom: 8px;
    }

    .email-highlight {
      display: inline-block;
      background: #eff6ff;
      color: #29ABE2;
      font-weight: 600;
      padding: 6px 16px;
      border-radius: 8px;
      font-size: 15px;
      margin: 8px 0 24px;
    }

    /* Steps */
    .steps {
      background: #f8fafc;
      border-radius: 14px;
      padding: 20px;
      margin: 20px 0;
      text-align: left;
    }
    .steps p {
      font-size: 13px;
      font-weight: 600;
      color: #374151;
      margin-bottom: 10px;
    }
    .step {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      margin-bottom: 10px;
    }
    .step-num {
      width: 24px;
      height: 24px;
      background: #29ABE2;
      color: white;
      border-radius: 50%;
      font-size: 12px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .step span {
      font-size: 13px;
      color: #4b5563;
      line-height: 1.5;
      padding-top: 3px;
    }

    /* Messages */
    .msg-success {
      background: #dcfce7;
      color: #166534;
      border-radius: 10px;
      padding: 12px 16px;
      font-size: 14px;
      font-weight: 500;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .msg-error {
      background: #fee2e2;
      color: #991b1b;
      border-radius: 10px;
      padding: 12px 16px;
      font-size: 14px;
      font-weight: 500;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* Buttons */
    .btn-resend {
      width: 100%;
      padding: 14px;
      border-radius: 12px;
      border: 2px solid #29ABE2;
      background: transparent;
      color: #29ABE2;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      margin-bottom: 12px;
    }
    .btn-resend:hover {
      background: #29ABE2;
      color: white;
    }

    .btn-login {
      display: block;
      width: 100%;
      padding: 14px;
      border-radius: 12px;
      border: none;
      background: #29ABE2;
      color: white;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.2s;
      margin-bottom: 16px;
    }
    .btn-login:hover { background: #1A8FC0; }

    .spam-note {
      font-size: 12px;
      color: #9ca3af;
      margin-top: 8px;
    }
    .spam-note a {
      color: #29ABE2;
      text-decoration: none;
    }
  </style>
</head>
<body>

<div class="card">

  <!-- Brand -->
  <div class="brand">
    <img src="assets/logo-transparent.png" alt="VTU TOPUP">
    <div class="brand-name">VTU TOPUP</div>
  </div>

  <!-- Email icon -->
  <div class="icon-circle">
    <i class="fas fa-envelope"></i>
  </div>

  <h1>Check Your Email</h1>
  <p class="subtitle">We sent a verification link to</p>
  <div class="email-highlight"><?= htmlspecialchars($maskedEmail) ?></div>

  <!-- Messages -->
  <?php if ($resendMsg): ?>
    <div class="msg-success">
      <i class="fas fa-circle-check"></i>
      <?= htmlspecialchars($resendMsg) ?>
    </div>
  <?php endif; ?>
  <?php if ($resendError): ?>
    <div class="msg-error">
      <i class="fas fa-circle-exclamation"></i>
      <?= htmlspecialchars($resendError) ?>
    </div>
  <?php endif; ?>

  <!-- Steps -->
  <div class="steps">
    <p>How to verify:</p>
    <div class="step">
      <div class="step-num">1</div>
      <span>Open your email inbox</span>
    </div>
    <div class="step">
      <div class="step-num">2</div>
      <span>Find the email from <strong>VTU TOPUP</strong></span>
    </div>
    <div class="step">
      <div class="step-num">3</div>
      <span>Click the <strong>Verify Email</strong> button</span>
    </div>
    <div class="step">
      <div class="step-num">4</div>
      <span>Come back and sign in</span>
    </div>
  </div>

  <!-- Sign in button -->
  <a href="login.php" class="btn-login">
    <i class="fas fa-sign-in-alt"></i> Go to Sign In
  </a>

  <!-- Resend form -->
  <form method="POST">
    <button type="submit" name="resend" class="btn-resend">
      <i class="fas fa-paper-plane"></i> Resend Verification Email
    </button>
  </form>

  <p class="spam-note">
    Didn't see it? Check your <strong>Spam</strong> or <strong>Junk</strong> folder.<br>
    Wrong email? <a href="register.php">Register again</a>
  </p>

</div>

</body>
</html>
