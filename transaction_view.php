<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php"); exit();
}

$user_id = (int)$_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM transactions WHERE id=? AND user_id=? LIMIT 1");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$tx = $stmt->get_result()->fetch_assoc();

if (!$tx) {
  die("Transaction not found");
}

$status = strtolower(trim($tx['status'] ?? 'pending'));
if(!in_array($status, ['completed','pending','failed'])) $status = 'pending';

$badgeClass = $status;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Transaction Receipt</title>
<style>
  body{font-family:Inter,system-ui,Arial,sans-serif;background:#f5f7fa;padding:24px;margin:0}
  .card{max-width:560px;margin:auto;background:#fff;border:1px solid #e5e7eb;padding:22px;border-radius:18px;box-shadow:0 10px 24px rgba(0,0,0,.06)}
  h2{margin:0 0 12px 0}
  .row{display:flex;justify-content:space-between;gap:16px;margin:10px 0;align-items:flex-start}
  .muted{color:#6b7280;font-weight:800}
  .val{font-weight:950;text-align:right}
  .badge{padding:6px 12px;border-radius:999px;font-weight:950;font-size:12px;display:inline-block}
  .completed{background:#dcfce7;color:#166534}
  .pending{background:#fef9c3;color:#854d0e}
  .failed{background:#fee2e2;color:#991b1b}
  .desc{margin-top:14px;padding:12px;border-radius:14px;background:#f1f5f9;border:1px solid #e5e7eb}
  .btns{margin-top:16px;display:flex;gap:10px;flex-wrap:wrap}
  .btn{display:inline-block;text-decoration:none;font-weight:950;background:#1E9BD7;color:#fff;padding:10px 14px;border-radius:12px}
  .btn2{display:inline-block;text-decoration:none;font-weight:950;background:#111827;color:#fff;padding:10px 14px;border-radius:12px}
</style>
</head>
<body>

<div class="card">
  <h2>Transaction Receipt</h2>

  <div class="row"><span class="muted">Service</span><span class="val"><?= htmlspecialchars($tx['service'] ?? '') ?></span></div>
  <div class="row"><span class="muted">Amount</span><span class="val">₦<?= number_format((float)$tx['amount'],2) ?></span></div>
  <div class="row"><span class="muted">Type</span><span class="val"><?= htmlspecialchars(ucfirst($tx['type'] ?? '')) ?></span></div>
  <div class="row">
    <span class="muted">Status</span>
    <span class="badge <?= $badgeClass ?>"><?= ucfirst($status) ?></span>
  </div>
  <div class="row"><span class="muted">Reference</span><span class="val"><?= htmlspecialchars($tx['reference'] ?? '') ?></span></div>
  <?php if(!empty($tx['order_id'])): ?>
    <div class="row"><span class="muted">Order ID</span><span class="val"><?= htmlspecialchars($tx['order_id']) ?></span></div>
  <?php endif; ?>
  <div class="row"><span class="muted">Date</span><span class="val"><?= htmlspecialchars($tx['created_at'] ?? '') ?></span></div>

  <?php if(!empty($tx['description'])): ?>
    <div class="desc">
      <div class="muted" style="margin-bottom:6px;">Description</div>
      <div style="font-weight:850;"><?= htmlspecialchars($tx['description']) ?></div>
    </div>
  <?php endif; ?>

  <div class="btns">
    <a class="btn" href="transactions.php">← Back to transactions</a>
    <a class="btn2" href="dashboard.php">Dashboard</a>
  </div>
</div>

</body>
</html>
