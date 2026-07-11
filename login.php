<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In - VTU TOPUP</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="styless.css">
</head>
<body>

<div class="container">

  <h1 class="form-title">Sign In</h1>
  <p class="form-sub">Welcome back to VTU TOPUP</p>

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

  <form action="login_process.php" method="POST" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); ?>">

    <div class="field">
      <label for="identity">Email or Phone Number</label>
      <div class="field-wrap">
        <i class="fas fa-user field-icon"></i>
        <input type="text" name="identity" id="identity"
               placeholder="Enter email or phone" required
               autocomplete="username">
      </div>
    </div>

    <div class="field">
      <label for="password">Password</label>
      <div class="field-wrap">
        <i class="fas fa-lock field-icon"></i>
        <input type="password" name="password" id="password"
               placeholder="Enter your password" required
               autocomplete="current-password">
        <span class="eye" onclick="togglePass('password',this)">
          <i class="fas fa-eye-slash"></i>
        </span>
      </div>
    </div>

    <div class="forgot-row">
      <a href="forgot_password_page.php">Forgot Password?</a>
    </div>

    <button type="submit" class="btn-submit">Sign In</button>
  </form>

  <p class="bottom-link">
    Don't have an account? <a href="register.php">Sign Up</a>
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
