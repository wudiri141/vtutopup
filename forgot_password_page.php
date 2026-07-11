<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password - VTU TOPUP</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="styless.css">
</head>
<body>

<div class="container">
  <h1 class="form-title">Forgot Password</h1>
  <p class="form-sub">Enter your email address to receive a reset link</p>

  <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
      <i class="fas fa-circle-exclamation"></i>
      <?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
      <i class="fas fa-circle-check"></i>
      <?= htmlspecialchars($_GET['success'], ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <form action="forgot_password.php" method="POST" autocomplete="off">
    <div class="field">
      <label for="email">Email Address</label>
      <div class="field-wrap">
        <i class="fas fa-envelope field-icon"></i>
        <input type="email" name="email" id="email"
               placeholder="Enter your email address" required
               autocomplete="email">
      </div>
    </div>

    <button type="submit" class="btn-submit">Send Reset Link</button>
  </form>

  <p class="bottom-link">
    Remembered your password? <a href="login.php">Back to Login</a>
  </p>
</div>

</body>
</html>
