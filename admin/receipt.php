<?php
session_start();
include "../db.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}

$admin_id = (int)$_SESSION['user_id'];

// Admin check
$chk = $conn->prepare("SELECT role, fullname FROM users WHERE id=? LIMIT 1");
$chk->bind_param("i", $admin_id);
$chk->execute();
$admin = $chk->get_result()->fetch_assoc();

if (!$admin || ($admin['role'] ?? 'user') !== 'admin') {
  http_response_code(403);
  die("Access denied");
}

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("
  SELECT t.*, u.fullname, u.email
  FROM transactions t
  LEFT JOIN users u ON u.id = t.user_id
  WHERE t.id=? LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$tx = $stmt->get_result()->fetch_assoc();

if (!$tx) die("Transaction not found");

$st = strtolower(trim($tx['status'] ?? 'pending'));
if(!in_array($st,['completed','pending','failed'])) $st='pending';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin Receipt</title>
<style>
body{font-family:Inter,system-ui;background:#f5f7fa;padding:30px}
.card{max-width:560px;margin:auto;background:#fff;padding:24px;border-radius:16px;border:1px solid #e5e7eb}
h2{margin-bottom:10px}
.row{display:flex;justify-content:space-between;margin:10px 0;gap:14px}
.badge{padding:6px 12px;border-radius:20px;font-weight:900}
.completed{background:#dcfce7;color:#166534}
.pending{background:#fef9c3;color:#854d0e}
.failed{background:#fee2e2;color:#991b1b}
.small{color:#6b7280;font-weight:800;font-size:13px}
a{color:#1E9BD7;font-weight:900;text-decoration:none}
</style>
</head>
<body>

<div class="card">
  <h2>Transaction Receipt (Admin)</h2>
  <div class="small">User: <?= htmlspecialchars($tx['fullname'] ?? 'Unknown') ?> (<?= htmlspecialchars($tx['email'] ?? '') ?>)</div>
  <hr style="border:none;border-top:1px solid #e5e7eb;margin:14px 0;">

  <div class="row"><span>Service</span><b><?= htmlspecialchars($tx['service'] ?? '') ?></b></div>
  <div class="row"><span>Amount</span><b>₦<?= number_format((float)$tx['amount'],2) ?></b></div>
  <div class="row"><span>Type</span><b><?= htmlspecialchars(ucfirst($tx['type'] ?? '')) ?></b></div>

  <div class="row"><span>Status</span>
    <span class="badge <?= $st ?>"><?= ucfirst($st) ?></span>
  </div>

  <div class="row"><span>Reference</span><b><?= htmlspecialchars($tx['reference'] ?? '') ?></b></div>
  <div class="row"><span>Date</span><b><?= htmlspecialchars($tx['created_at'] ?? '') ?></b></div>

  <?php if(!empty($tx['description'])): ?>
    <div class="small" style="margin-top:12px;">
      <?= htmlspecialchars($tx['description']) ?>
    </div>
  <?php endif; ?>

  <div style="margin-top:16px;display:flex;gap:14px;flex-wrap:wrap;">
    <a href="transactions.php">← Back to Admin Transactions</a>
    <a href="dashboard.php">Admin Dashboard</a>
  </div>
</div>

</body>
</html>
