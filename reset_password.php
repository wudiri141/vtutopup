<?php
include "db.php";
require_once "auth_helper.php";

ensureAuthSchema($conn);

$token = trim($_GET['token'] ?? '');
$error = '';
$valid = false;

if ($token === '') {
    $error = 'Invalid reset link.';
} else {
    $stmt = $conn->prepare("SELECT id, reset_expires FROM users WHERE reset_token=? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $error = 'Invalid or expired reset token.';
    } elseif (empty($user['reset_expires']) || strtotime($user['reset_expires']) < time()) {
        $error = 'Reset link has expired.';
    } else {
        $valid = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password - VTU TOPUP</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="styless.css">
</head>
<body>

<div class="container">
  <h1 class="form-title">Reset Password</h1>
  <p class="form-sub">Create a new password for your account</p>

  <?php if ($error): ?>
    <div class="alert alert-error">
      <i class="fas fa-circle-exclamation"></i>
      <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
    <p class="bottom-link">
      Request a new link <a href="forgot_password_page.php">here</a>
    </p>
  <?php else: ?>
    <form action="reset_password_process.php" method="POST" autocomplete="off">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

      <div class="field">
        <label for="password">New Password</label>
        <div class="field-wrap">
          <i class="fas fa-lock field-icon"></i>
          <input type="password" name="password" id="password"
                 placeholder="Enter new password" required
                 autocomplete="new-password">
          <span class="eye" onclick="togglePass('password',this)">
            <i class="fas fa-eye-slash"></i>
          </span>
        </div>
      </div>

      <div class="field">
        <label for="confirm_password">Confirm Password</label>
        <div class="field-wrap">
          <i class="fas fa-lock field-icon"></i>
          <input type="password" name="confirm_password" id="confirm_password"
                 placeholder="Confirm new password" required
                 autocomplete="new-password">
          <span class="eye" onclick="togglePass('confirm_password',this)">
            <i class="fas fa-eye-slash"></i>
          </span>
        </div>
      </div>

      <button type="submit" class="btn-submit">Reset Password</button>
    </form>
  <?php endif; ?>

  <p class="bottom-link">
    Back to <a href="login.php">Sign In</a>
  </p>
</div>

<script>
function togglePass(id, el) {
  const inp  = document.getElementById(id);
  const icon = el.querySelector('i');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.classList.replace('fa-eye-slash','fa-eye');
  } else {
    inp.type = 'password';
    icon.classList.replace('fa-eye','fa-eye-slash');
  }
}
</script>
</body>
</html>
