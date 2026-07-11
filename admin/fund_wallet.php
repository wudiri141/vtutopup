<?php
session_start();
include "../db.php";

/* ===== SECURITY: ADMIN ONLY ===== */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
  die("Access denied");
}

$msg = "";
$err = "";

if (isset($_POST['submit'])) {

  $identity = trim($_POST['identity']);
  $amount   = (float)$_POST['amount'];
  $type     = $_POST['type']; // credit | debit
  $reason   = trim($_POST['reason']);

  if ($identity === "" || $amount <= 0 || !in_array($type, ['credit','debit'])) {
    $err = "All fields are required";
  } else {

    /* ===== FIND USER BY EMAIL OR PHONE ===== */
    $isEmail = filter_var($identity, FILTER_VALIDATE_EMAIL);

    if ($isEmail) {
      $u = $conn->prepare("SELECT id, wallet, fullname FROM users WHERE email=? LIMIT 1");
      $u->bind_param("s", $identity);
    } else {
      $phone = preg_replace('/\s+/', '', $identity);
      $u = $conn->prepare("SELECT id, wallet, fullname FROM users WHERE phone=? LIMIT 1");
      $u->bind_param("s", $phone);
    }

    $u->execute();
    $user = $u->get_result()->fetch_assoc();

    if (!$user) {
      $err = "User not found";
    } else {

      $user_id = (int)$user['id'];
      $wallet  = (float)$user['wallet'];

      if ($type === 'debit' && $wallet < $amount) {
        $err = "Insufficient user balance";
      } else {

        /* ===== TRANSACTION START ===== */
        $conn->begin_transaction();

        try {
          if ($type === 'credit') {
            $q = "UPDATE users SET wallet = wallet + ? WHERE id=?";
          } else {
            $q = "UPDATE users SET wallet = wallet - ? WHERE id=?";
          }

          $up = $conn->prepare($q);
          $up->bind_param("di", $amount, $user_id);
          $up->execute();

          $ref = "ADMIN_" . time();
          $service = "Admin Wallet Adjustment";

          $ins = $conn->prepare("
            INSERT INTO transactions
            (user_id, type, service, amount, status, reference, description)
            VALUES (?, ?, ?, ?, 'completed', ?, ?)
          ");
          $ins->bind_param(
            "issdss",
            $user_id,
            $type,
            $service,
            $amount,
            $ref,
            $reason
          );
          $ins->execute();

          $conn->commit();
          $msg = "Wallet updated for " . htmlspecialchars($user['fullname']);

        } catch (Exception $e) {
          $conn->rollback();
          $err = "Operation failed";
        }
      }
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Wallet Adjustment</title>
<style>
body{font-family:Inter,Arial;background:#f5f7fa;padding:20px}
.card{
  max-width:420px;margin:auto;background:#fff;
  padding:20px;border-radius:16px;
  box-shadow:0 10px 25px rgba(0,0,0,.08)
}
h3{text-align:center;margin-bottom:10px}
input,select{
  width:90%;padding:12px;margin-top:10px;
  border-radius:10px;border:1px solid #ddd
}
button{
  width:100%;margin-top:14px;padding:12px;
  border:none;border-radius:10px;
  background:#1E9BD7;color:#fff;
  font-weight:900;cursor:pointer
}
.ok{background:#dcfce7;color:#166534;padding:10px;border-radius:10px;margin-bottom:10px}
.err{background:#fee2e2;color:#991b1b;padding:10px;border-radius:10px;margin-bottom:10px}
.back{
  display:block;text-align:center;margin-top:12px;
  text-decoration:none;color:#1E9BD7;font-weight:800
}
</style>
</head>
<body>

<div class="card">
  <h3>Admin Wallet Control</h3>

  <?php if($msg): ?><div class="ok"><?= $msg ?></div><?php endif; ?>
  <?php if($err): ?><div class="err"><?= $err ?></div><?php endif; ?>

  <form method="POST">
    <input type="text" name="identity" placeholder="User Email or Phone" required>
    <input type="number" name="amount" placeholder="Amount (₦)" min="1" required>

    <select name="type">
      <option value="credit">Credit Wallet</option>
      <option value="debit">Debit Wallet</option>
    </select>

    <input type="text" name="reason" placeholder="Reason (e.g VTU reversal)" required>

    <button name="submit">Apply</button>
  </form>

  <a class="back" href="dashboard.php">← Back to Admin Dashboard</a>
</div>

</body>
</html>
