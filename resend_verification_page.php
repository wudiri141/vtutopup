<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resend Verification</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="styless.css">
</head>
<body>

<div class="container" id="resendVerification">
  <h1 class="form-title">Resend Verification Email</h1>

  <?php if (isset($_GET['error'])): ?>
    <div class="messageDiv" style="color:red;text-align:center;margin-bottom:10px;">
      <?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['success'])): ?>
    <div class="messageDiv" style="color:green;text-align:center;margin-bottom:10px;">
      <?= htmlspecialchars($_GET['success'], ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <form action="resend_verification.php" method="POST">

    <div class="input-group">
      <i class="fas fa-envelope"></i>
      <input type="email" name="email" placeholder=" " required>
      <label>Email Address</label>
    </div>

    <button type="submit" class="btn">Resend Verification</button>
  </form>

  <div class="links">
    <p>Want to return?</p>
    <a href="login.php">Back to Login</a>
  </div>
</div>

</body>
</html>