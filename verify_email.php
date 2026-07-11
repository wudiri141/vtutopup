<?php
include "db.php";
require_once "referral_helper.php";
require_once "send_verification_email.php";

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    $status = 'error';
    $msg    = 'Invalid verification link.';
} else {
    $stmt = $conn->prepare("SELECT id, fullname, email, email_verified FROM users WHERE verification_token = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $status = 'error';
        $msg    = 'This verification link is invalid or has already been used.';
    } elseif ((int)$user['email_verified'] === 1) {
        $status = 'already';
        $msg    = 'Your email is already verified!';
    } else {
        $update = $conn->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?");
        $update->bind_param("i", $user['id']);
        if ($update->execute()) {
            try {
                payPendingReferralReward($conn, (int)$user['id']);
            } catch (Throwable $e) {
                error_log("Referral payout failed during email verification for user {$user['id']}: " . $e->getMessage());
            }

            try {
                sendWelcomeEmail($user['email'], $user['fullname']);
            } catch (Throwable $e) {
                error_log("Welcome email failed for user {$user['id']}: " . $e->getMessage());
            }

            $status = 'success';
            $msg    = 'Email verified successfully!';
            $name   = $user['fullname'];
        } else {
            $status = 'error';
            $msg    = 'Verification failed. Please try again.';
        }
        $update->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Email Verification - VTU TOPUP</title>
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
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.10);
      padding: 48px 40px;
      max-width: 440px;
      width: 100%;
      text-align: center;
    }
    .brand-name {
      font-size: 20px;
      font-weight: 700;
      color: #29ABE2;
      margin-bottom: 32px;
    }
    .icon-circle {
      width: 88px;
      height: 88px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 28px;
      font-size: 38px;
    }
    .icon-success { background: #dcfce7; color: #16a34a; box-shadow: 0 8px 20px rgba(22,163,74,0.2); }
    .icon-error   { background: #fee2e2; color: #dc2626; box-shadow: 0 8px 20px rgba(220,38,38,0.2); }
    .icon-already { background: #eff6ff; color: #2563eb; box-shadow: 0 8px 20px rgba(37,99,235,0.2); }

    h1 { font-size: 24px; font-weight: 700; color: #111827; margin-bottom: 12px; }
    p  { font-size: 15px; color: #6b7280; line-height: 1.6; margin-bottom: 24px; }

    .name-tag {
      display: inline-block;
      background: #eff6ff;
      color: #29ABE2;
      font-weight: 600;
      padding: 6px 16px;
      border-radius: 8px;
      margin-bottom: 24px;
    }

    .btn {
      display: inline-block;
      padding: 14px 32px;
      border-radius: 12px;
      border: none;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s;
    }
    .btn-primary { background: #29ABE2; color: white; }
    .btn-primary:hover { background: #1A8FC0; }
    .btn-outline { background: transparent; border: 2px solid #29ABE2; color: #29ABE2; margin-top: 10px; }
  </style>
</head>
<body>

<div class="card">
  <div class="brand-name">VTU TOPUP</div>

  <?php if ($status === 'success'): ?>
    <div class="icon-circle icon-success">
      <i class="fas fa-circle-check"></i>
    </div>
    <h1>Email Verified! 🎉</h1>
    <?php if (!empty($name)): ?>
      <div class="name-tag">Welcome, <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>!</div>
    <?php endif; ?>
    <p>Your email has been verified successfully. You can now sign in to your VTU TOPUP account.</p>
    <a href="login.php" class="btn btn-primary">Sign In Now</a>

  <?php elseif ($status === 'already'): ?>
    <div class="icon-circle icon-already">
      <i class="fas fa-circle-check"></i>
    </div>
    <h1>Already Verified</h1>
    <p>Your email address has already been verified. Go ahead and sign in!</p>
    <a href="login.php" class="btn btn-primary">Sign In</a>

  <?php else: ?>
    <div class="icon-circle icon-error">
      <i class="fas fa-circle-xmark"></i>
    </div>
    <h1>Verification Failed</h1>
    <p><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
    <a href="login.php" class="btn btn-primary">Back to Sign In</a>
    <br>
    <a href="register.php" class="btn btn-outline">Register Again</a>

  <?php endif; ?>
</div>

</body>
</html>
