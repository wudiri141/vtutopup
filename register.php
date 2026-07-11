<?php
session_start();
$referralCode = strtoupper(trim($_GET['ref'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up - VTU TOPUP</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="styless.css">
</head>
<body>

<div class="container">

  <h1 class="form-title">Create Account</h1>
  <p class="form-sub">Join VTU TOPUP and start using the wallet service</p>

  <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
      <i class="fas fa-circle-exclamation"></i>
      <?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
      <i class="fas fa-circle-check"></i>
      Account created. Please check your email for verification.
    </div>
  <?php endif; ?>

  <form action="register_process.php" method="POST" autocomplete="off">

    <div class="field">
      <label for="first_name">First Name</label>
      <div class="field-wrap">
        <i class="fas fa-user field-icon"></i>
        <input type="text" name="first_name" id="first_name"
               placeholder="Enter first name" required
               autocomplete="given-name">
      </div>
    </div>

    <div class="field">
      <label for="last_name">Last Name</label>
      <div class="field-wrap">
        <i class="fas fa-user field-icon"></i>
        <input type="text" name="last_name" id="last_name"
               placeholder="Enter last name" required
               autocomplete="family-name">
      </div>
    </div>

    <div class="field">
      <label for="phone">Phone Number</label>
      <div class="field-wrap">
        <i class="fas fa-phone field-icon"></i>
        <input type="tel" name="phone" id="phone"
               placeholder="Enter phone number" required
               autocomplete="tel">
      </div>
    </div>

    <div class="field">
      <label for="email">Email Address</label>
      <div class="field-wrap">
        <i class="fas fa-envelope field-icon"></i>
        <input type="email" name="email" id="email"
               placeholder="Enter email address" required
               autocomplete="email">
      </div>
    </div>

    <div class="field">
      <label for="password">Password</label>
      <div class="field-wrap">
        <i class="fas fa-lock field-icon"></i>
        <input type="password" name="password" id="password"
               placeholder="Create password" required
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
               placeholder="Confirm password" required
               autocomplete="new-password">
        <span class="eye" onclick="togglePass('confirm_password',this)">
          <i class="fas fa-eye-slash"></i>
        </span>
      </div>
    </div>

    <div class="field">
      <label for="pin">Transaction PIN</label>
      <div class="field-wrap">
        <i class="fas fa-key field-icon"></i>
        <input type="password" name="pin" id="pin"
               placeholder="Create 4-digit PIN" maxlength="4"
               inputmode="numeric" required autocomplete="off">
        <span class="eye" onclick="togglePass('pin',this)">
          <i class="fas fa-eye-slash"></i>
        </span>
      </div>
    </div>

    <div class="field">
      <label for="referral_code">Referral Code</label>
      <div class="field-wrap">
        <i class="fas fa-gift field-icon"></i>
        <input type="text" name="referral_code" id="referral_code"
               placeholder="Optional referral code"
               value="<?= htmlspecialchars($referralCode, ENT_QUOTES, 'UTF-8') ?>">
      </div>
    </div>

    <button type="submit" class="btn-submit">Sign Up</button>

  </form>

  <p class="bottom-link">
    Already have an account? <a href="login.php">Sign In</a>
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
