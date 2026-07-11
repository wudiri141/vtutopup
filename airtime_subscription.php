<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT wallet, transaction_pin FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();

$wallet = (float)($u['wallet'] ?? 0);
$pinSet = !empty($u['transaction_pin']);

$msg = "";

if (isset($_GET['success'])) {
  $ref = trim($_GET['ref'] ?? '');
  $refSafe = htmlspecialchars($ref, ENT_QUOTES, 'UTF-8');

  $receiptId = 0;

  if ($ref !== '') {
    $stmt = $conn->prepare("SELECT id FROM vtu_transactions WHERE user_id=? AND ref=? LIMIT 1");
    $stmt->bind_param("is", $user_id, $ref);
    $stmt->execute();
    $tx = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($tx) {
      $receiptId = (int)$tx['id'];
    }
  }

  if ($receiptId > 0) {
    $receiptLink = "receipt.php?src=vtu&id=" . $receiptId;
    $pdfLink = "receipt_pdf.php?src=vtu&id=" . $receiptId;

    $msg = "✅ Airtime request sent! Ref: {$refSafe}<br>
            <a href='" . htmlspecialchars($receiptLink, ENT_QUOTES, 'UTF-8') . "'>View Receipt</a>
            &nbsp;|&nbsp;
            <a href='" . htmlspecialchars($pdfLink, ENT_QUOTES, 'UTF-8') . "'>Download PDF</a>";
  } else {
    $msg = "✅ Airtime request sent! Ref: {$refSafe}";
  }
}

if (isset($_GET['error'])) {
  $msg = "❌ " . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Buy Airtime</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="optionstyles.css">
</head>
<body>

<div class="airtime-card">
  <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

  <div class="page-header">
    <h2>Buy Airtime</h2>
    <p class="wallet-balance">Wallet Balance: ₦<?= number_format($wallet, 2) ?></p>
  </div>

  <?php if (!$pinSet): ?>
    <p style="background:#fff7ed;border:1px solid #fed7aa;padding:10px;border-radius:10px;color:#7c2d12;">
      Set Transaction PIN first: <a href="account.php"><b>Go to Account</b></a>
    </p>
  <?php endif; ?>

  <?php if ($msg): ?>
    <div style="margin:10px 0; padding:10px; border-radius:10px; background:#f1f5f9;">
      <?= $msg ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="process_airtime_subscription.php" id="airtimeForm">
    <label>Select Network</label>
    <select name="network" id="network" required>
      <option value="">-- Select Network --</option>
      <option value="MTN">MTN</option>
      <option value="GLO">GLO</option>
      <option value="AIRTEL">AIRTEL</option>
      <option value="9MOBILE">9MOBILE</option>
    </select>

    <label>Phone Number</label>
    <input type="text" name="phone" id="phone" placeholder="080xxxxxxxx" required>

    <label>Amount</label>
    <input type="number" name="amount" id="amount" min="50" placeholder="e.g. 100" required>

    <button type="submit" <?= !$pinSet ? 'disabled style="opacity:.6;cursor:not-allowed;"' : '' ?>>
      Buy Airtime →
    </button>
  </form>
</div>

<?php include "pin_modal.php"; ?>
<script>
  PinModal.bind('#airtimeForm', {
    title: 'Airtime Purchase',
    sub: 'Enter PIN to buy airtime.'
  });
</script>

</body>
</html>