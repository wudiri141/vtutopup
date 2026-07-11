<?php
session_start();
include "db.php";
require_once "auth_helper.php";
require_once "referral_helper.php";

ensureAuthSchema($conn);

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$user_id = (int)$_SESSION['user_id'];
$message = "";

function getUser($conn, $user_id){
  $stmt = $conn->prepare("SELECT fullname, email, phone, wallet, password, transaction_pin, role FROM users WHERE id=? LIMIT 1");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  return $stmt->get_result()->fetch_assoc();
}

$user = getUser($conn, $user_id);
$referral = getReferralStats($conn, $user_id);
$referralLink = "https://vtutopup.com.ng/register.php?ref=" . urlencode($referral['code']);

// ====== CHANGE PIN ======
if(isset($_POST['change_pin'])){
  $old = trim($_POST['old_pin'] ?? '');
  $pin  = trim($_POST['pin'] ?? '');
  $pin2 = trim($_POST['pin2'] ?? '');

  $hasPin = !empty($user['transaction_pin']);

  if($hasPin && !preg_match('/^\d{4}$/', $old)){
    $message = "❌ Old PIN is required (4 digits)";
  } elseif(!preg_match('/^\d{4}$/', $pin)){
    $message = "❌ New PIN must be exactly 4 digits";
  } elseif($pin !== $pin2){
    $message = "❌ New PIN does not match";
  } else {
    if($hasPin && !password_verify($old, $user['transaction_pin'])){
      $message = "❌ Old PIN is wrong";
    } else {
      $hash = password_hash($pin, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("UPDATE users SET transaction_pin=? WHERE id=?");
      $stmt->bind_param("si", $hash, $user_id);
      $stmt->execute();
      $user = getUser($conn, $user_id);
      $message = "✅ PIN updated successfully";
    }
  }
}

// ====== CHANGE PASSWORD ======
if(isset($_POST['change_password'])){
  $current = (string)($_POST['current_password'] ?? '');
  $new1    = (string)($_POST['new_password'] ?? '');
  $new2    = (string)($_POST['confirm_new_password'] ?? '');

  if(strlen($new1) < 6){
    $message = "❌ New password must be at least 6 characters";
  } elseif($new1 !== $new2){
    $message = "❌ New password does not match";
  } elseif(empty($user['password']) || !password_verify($current, $user['password'])){
    $message = "❌ Current password is wrong";
  } else {
    $hash = password_hash($new1, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password=?, password_changed_at=NOW(), last_otp_verified_at=NULL WHERE id=?");
    $stmt->bind_param("si", $hash, $user_id);
    $stmt->execute();
    $user = getUser($conn, $user_id);
    $message = "✅ Password updated successfully";
  }
}

$pinSet = !empty($user['transaction_pin']);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Account</title>
<style>
body{margin:0;font-family:Segoe UI,sans-serif;background:#f6f7fb}
.container{max-width:420px;margin:auto;padding:16px}
.card{background:#fff;border-radius:16px;padding:16px;margin-bottom:14px;box-shadow:0 10px 22px rgba(0,0,0,.06)}
h3{margin:0 0 10px;color:#1E9BD7}
input{width:100%;padding:12px;border-radius:12px;border:1px solid #e5e7eb;margin-top:10px}
button{width:100%;padding:12px;border-radius:12px;border:none;background:#1E9BD7;color:#fff;font-weight:900;margin-top:12px;cursor:pointer}
.notice{background:#fff7ed;border:1px solid #fed7aa;color:#7c2d12;padding:10px;border-radius:12px;font-weight:800}
.ok{background:#ecfeff;border:1px solid #a5f3fc;color:#155e75}
.refgrid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:12px}
.refitem{background:#f1f5f9;border-radius:12px;padding:10px}
.refitem span{display:block;color:#64748b;font-size:12px;margin-bottom:4px}
.refitem b{font-size:15px}
.copyrow{display:flex;gap:8px;margin-top:12px}
.copyrow input{margin-top:0;flex:1}
.copyrow button{width:auto;margin-top:0;white-space:nowrap}
</style>
</head>
<body>
<div class="container">

<div class="card">
  <h3>My Account</h3>
  <div><b>Name:</b> <?= htmlspecialchars($user['fullname'] ?? 'User') ?></div>
  <div><b>Email:</b> <?= htmlspecialchars($user['email'] ?? '-') ?></div>
  <div><b>Phone:</b> <?= htmlspecialchars($user['phone'] ?? '-') ?></div>
  <div style="margin-top:8px;"><b>Wallet:</b> ₦<?= number_format((float)($user['wallet'] ?? 0),2) ?></div>
</div>

<div class="card">
  <h3>Referral</h3>
  <div>Invite users and earn ₦<?= number_format((float)$referral['bonus'], 2) ?> after email verification.</div>
  <div class="refgrid">
    <div class="refitem"><span>Code</span><b><?= htmlspecialchars($referral['code'], ENT_QUOTES, 'UTF-8') ?></b></div>
    <div class="refitem"><span>Referrals</span><b><?= (int)$referral['count'] ?></b></div>
    <div class="refitem"><span>Earned</span><b>₦<?= number_format((float)$referral['earnings'], 2) ?></b></div>
  </div>
  <div class="copyrow">
    <input id="referralLink" value="<?= htmlspecialchars($referralLink, ENT_QUOTES, 'UTF-8') ?>" readonly>
    <button type="button" onclick="copyReferralLink()">Copy</button>
  </div>
</div>

<?php if($message): ?>
  <div class="card notice <?= (strpos($message, '✅') !== false ? 'ok' : '') ?>"><?= $message ?></div>
<?php endif; ?>

<div class="card">
  <h3>Transaction PIN</h3>
  <div class="notice" style="margin-bottom:10px;">
    <?= $pinSet ? "PIN is set ✅" : "PIN not set ❌ (Required before purchase)" ?>
  </div>

  <form method="POST">
    <?php if($pinSet): ?>
      <input type="password" name="old_pin" placeholder="Old PIN (4 digits)" maxlength="4" inputmode="numeric" required>
    <?php endif; ?>
    <input type="password" name="pin" placeholder="New PIN (4 digits)" maxlength="4" inputmode="numeric" required>
    <input type="password" name="pin2" placeholder="Confirm New PIN" maxlength="4" inputmode="numeric" required>
    <button type="submit" name="change_pin"><?= $pinSet ? "Change PIN" : "Set PIN" ?></button>
  </form>
</div>

<div class="card">
  <h3>Change Password</h3>
  <form method="POST">
    <input type="password" name="current_password" placeholder="Current Password" required>
    <input type="password" name="new_password" placeholder="New Password (min 6 chars)" required>
    <input type="password" name="confirm_new_password" placeholder="Confirm New Password" required>
    <button type="submit" name="change_password">Update Password</button>
  </form>
</div>

<div class="card">
  <button onclick="location.href='dashboard.php'">Back to Dashboard</button>
</div>

</div>
<script>
function copyReferralLink(){
  const input = document.getElementById('referralLink');
  input.select();
  input.setSelectionRange(0, 99999);
  navigator.clipboard.writeText(input.value).catch(() => document.execCommand('copy'));
}
</script>
</body>
</html>
