<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = (int)$_SESSION['user_id'];
$id  = (int)($_GET['id'] ?? 0);
$src = strtolower(trim($_GET['src'] ?? 'core'));

if ($id <= 0) {
  die("Transaction not found");
}

function badgeClass($st){
  $st = strtolower(trim($st));
  if (in_array($st,['completed','success'])) return 'completed';
  if (in_array($st,['failed','fail'])) return 'failed';
  return 'pending';
}

if ($src === "vtu") {

  $stmt = $conn->prepare("
    SELECT service, provider, ref, network, phone,
           amount_sell, status, provider_message, created_at
    FROM vtu_transactions
    WHERE id=? AND user_id=? LIMIT 1
  ");
  $stmt->bind_param("ii",$id,$user_id);
  $stmt->execute();
  $tx = $stmt->get_result()->fetch_assoc();

  if (!$tx) die("Transaction not found");

  $status = badgeClass($tx['status'] ?? 'pending');
  $amount = (float)$tx['amount_sell'];

} else {

  $stmt = $conn->prepare("
    SELECT service, amount, type, status, reference, description, created_at
    FROM transactions
    WHERE id=? AND user_id=? LIMIT 1
  ");
  $stmt->bind_param("ii",$id,$user_id);
  $stmt->execute();
  $tx = $stmt->get_result()->fetch_assoc();

  if (!$tx) die("Transaction not found");

  $status = badgeClass($tx['status'] ?? 'pending');
  $amount = (float)$tx['amount'];
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Transaction Receipt</title>

<style>
body{font-family:Inter;background:#f5f7fa;padding:24px;margin:0}
.card{max-width:560px;margin:auto;background:#fff;padding:22px;border-radius:18px;border:1px solid #e5e7eb}
.row{display:flex;justify-content:space-between;margin:10px 0}
.muted{color:#6b7280;font-weight:800}
.val{font-weight:950}
.badge{padding:6px 12px;border-radius:999px;font-size:12px;font-weight:900}
.completed{background:#dcfce7;color:#166534}
.pending{background:#fef9c3;color:#854d0e}
.failed{background:#fee2e2;color:#991b1b}
.btns{margin-top:16px;display:flex;gap:10px}
.btn{background:#1E9BD7;color:#fff;text-decoration:none;padding:10px 14px;border-radius:12px;font-weight:900}
.btn2{background:#111827;color:#fff;text-decoration:none;padding:10px 14px;border-radius:12px;font-weight:900}
.btn3{background:#16a34a;color:#fff;text-decoration:none;padding:10px 14px;border-radius:12px;font-weight:900}
</style>

</head>
<body>

<div class="card">

<h2>Transaction Receipt</h2>

<?php if($src==="vtu"): ?>

<div class="row"><span class="muted">Service</span><span class="val"><?= htmlspecialchars($tx['service']) ?></span></div>
<div class="row"><span class="muted">Provider</span><span class="val"><?= htmlspecialchars($tx['provider']) ?></span></div>
<div class="row"><span class="muted">Amount</span><span class="val">₦<?= number_format($amount,2) ?></span></div>
<div class="row">
<span class="muted">Status</span>
<span class="badge <?= $status ?>"><?= ucfirst($status) ?></span>
</div>
<div class="row"><span class="muted">Reference</span><span class="val"><?= htmlspecialchars($tx['ref']) ?></span></div>
<div class="row"><span class="muted">Network</span><span class="val"><?= htmlspecialchars($tx['network']) ?></span></div>
<div class="row"><span class="muted">Phone</span><span class="val"><?= htmlspecialchars($tx['phone']) ?></span></div>
<div class="row"><span class="muted">Date</span><span class="val"><?= htmlspecialchars($tx['created_at']) ?></span></div>

<?php if(!empty($tx['provider_message'])): ?>
<div class="row"><span class="muted">Message</span><span class="val"><?= htmlspecialchars($tx['provider_message']) ?></span></div>
<?php endif; ?>

<?php else: ?>

<div class="row"><span class="muted">Service</span><span class="val"><?= htmlspecialchars($tx['service']) ?></span></div>
<div class="row"><span class="muted">Amount</span><span class="val">₦<?= number_format($amount,2) ?></span></div>
<div class="row"><span class="muted">Type</span><span class="val"><?= htmlspecialchars($tx['type']) ?></span></div>
<div class="row">
<span class="muted">Status</span>
<span class="badge <?= $status ?>"><?= ucfirst($status) ?></span>
</div>
<div class="row"><span class="muted">Reference</span><span class="val"><?= htmlspecialchars($tx['reference']) ?></span></div>
<div class="row"><span class="muted">Date</span><span class="val"><?= htmlspecialchars($tx['created_at']) ?></span></div>

<?php if(!empty($tx['description'])): ?>
<div class="row"><span class="muted">Description</span><span class="val"><?= htmlspecialchars($tx['description']) ?></span></div>
<?php endif; ?>

<?php endif; ?>

<div class="btns">
<a class="btn" href="transactions.php">← Back</a>
<a class="btn2" href="dashboard.php">Dashboard</a>
<a class="btn3" href="receipt_pdf.php?src=<?= $src ?>&id=<?= $id ?>">Download PDF</a>
</div>

</div>

</body>
</html>