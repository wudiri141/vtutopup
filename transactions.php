<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = (int)$_SESSION['user_id'];

$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;

$tab = $_GET['status'] ?? 'all';
$search = trim($_GET['q'] ?? '');

$allowedTabs = ['all','completed','pending','failed'];
if (!in_array($tab, $allowedTabs)) $tab = 'all';

function normalizeStatus($s) {
  $s = strtolower(trim((string)$s));
  if ($s === 'success') return 'completed';
  if ($s === 'completed') return 'completed';
  if ($s === 'failed') return 'failed';
  return 'pending';
}

function esc($v){
  return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function matchSearch($row, $q) {
  if ($q === '') return true;
  $q = strtolower($q);

  $hay = strtolower(
    ($row['source'] ?? '') . ' ' .
    ($row['service'] ?? '') . ' ' .
    ($row['ref'] ?? '') . ' ' .
    ($row['reference'] ?? '') . ' ' .
    ($row['description'] ?? '') . ' ' .
    ($row['network'] ?? '') . ' ' .
    ($row['phone'] ?? '') . ' ' .
    ($row['plan_id'] ?? '') . ' ' .
    ($row['provider_message'] ?? '')
  );

  return strpos($hay, $q) !== false;
}

$walletRows = [];
$sql1 = "SELECT id, type, service, amount, status, reference, description, created_at
         FROM transactions
         WHERE user_id = ?
         ORDER BY id DESC
         LIMIT 300";
$stmt1 = $conn->prepare($sql1);
$stmt1->bind_param("i", $user_id);
$stmt1->execute();
$res1 = $stmt1->get_result();

while ($r = $res1->fetch_assoc()) {
  $walletRows[] = [
    "source" => "core",
    "id" => (int)$r["id"],
    "service" => $r["service"] ?? "wallet",
    "type" => $r["type"] ?? "debit",
    "amount" => (float)($r["amount"] ?? 0),
    "status_ui" => normalizeStatus($r["status"] ?? "pending"),
    "created_at" => $r["created_at"] ?? "",
    "timestamp" => strtotime($r["created_at"] ?? "") ?: 0,
    "reference" => $r["reference"] ?? "",
    "description" => $r["description"] ?? "",
    "ref" => "",
    "network" => "",
    "phone" => "",
    "plan_id" => "",
    "amount_sell" => null,
    "amount_cost" => null,
    "profit" => null,
    "provider_message" => "",
  ];
}

$vtuRows = [];
$sql2 = "SELECT id, service, provider, ref, network, phone, plan_id,
                amount_sell, amount_cost, profit, status, provider_message, created_at
         FROM vtu_transactions
         WHERE user_id = ?
         ORDER BY id DESC
         LIMIT 300";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$res2 = $stmt2->get_result();

while ($r = $res2->fetch_assoc()) {
  $sell = (float)($r["amount_sell"] ?? 0);
  $vtuRows[] = [
    "source" => "vtu",
    "id" => (int)$r["id"],
    "service" => $r["service"] ?? "vtu",
    "type" => "debit",
    "amount" => $sell,
    "status_ui" => normalizeStatus($r["status"] ?? "pending"),
    "created_at" => $r["created_at"] ?? "",
    "timestamp" => strtotime($r["created_at"] ?? "") ?: 0,
    "reference" => "",
    "description" => "",
    "ref" => $r["ref"] ?? "",
    "network" => $r["network"] ?? "",
    "phone" => $r["phone"] ?? "",
    "plan_id" => $r["plan_id"] ?? "",
    "amount_sell" => (float)($r["amount_sell"] ?? 0),
    "amount_cost" => (float)($r["amount_cost"] ?? 0),
    "profit" => (float)($r["profit"] ?? 0),
    "provider_message" => $r["provider_message"] ?? "",
  ];
}

$all = array_merge($walletRows, $vtuRows);

if ($tab !== 'all') {
  $all = array_values(array_filter($all, function($row) use ($tab) {
    return ($row['status_ui'] ?? 'pending') === $tab;
  }));
}

if ($search !== '') {
  $all = array_values(array_filter($all, function($row) use ($search) {
    return matchSearch($row, $search);
  }));
}

usort($all, function($a, $b) {
  return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
});

$total = count($all);
$totalPages = max(1, (int)ceil($total / $limit));
if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $limit;
$rows = array_slice($all, $offset, $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Transactions</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
:root{
  --primary:#1E9BD7;
  --bg:#F5F7FA;
  --text:#111827;
  --muted:#6b7280;
  --line:#E5E7EB;
  --soft:#F1F5F9;
}
*{margin:0;padding:0;box-sizing:border-box;font-family:Inter,system-ui,Arial,sans-serif;}
body{background:var(--bg);color:var(--text);}
.wrap{max-width:1000px;margin:0 auto;padding:22px 16px 40px;}
.topbar{
  background:var(--primary);
  color:#fff;border-radius:18px;padding:18px;
  display:flex;align-items:center;justify-content:space-between;gap:12px;
}
.left{display:flex;align-items:center;gap:12px;}
.iconbtn{
  width:42px;height:42px;border-radius:14px;
  display:flex;align-items:center;justify-content:center;
  background:rgba(255,255,255,.14);
  color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.18);
  cursor:pointer;
}
h1{font-size:18px;font-weight:950;}
.card{
  margin-top:14px;background:#fff;border:1px solid var(--line);
  border-radius:22px;padding:18px;
  box-shadow:0 10px 24px rgba(0,0,0,.06);
}
.title{
  display:flex;align-items:center;gap:10px;
  font-weight:950;color:var(--primary);
}
.tabs{display:flex;gap:10px;flex-wrap:wrap;margin:14px 0;}
.tab{
  padding:10px 14px;border-radius:999px;
  background:var(--soft);border:1px solid #e9eef5;
  text-decoration:none;color:#64748b;font-weight:900;font-size:13px;
}
.tab.active{background:var(--primary);border-color:var(--primary);color:#fff;}
.search{
  display:flex;align-items:center;gap:10px;
  border:1px solid var(--line);border-radius:14px;
  padding:12px 14px;background:#fff;
}
.search input{
  border:none;outline:none;width:100%;
  font-weight:700;color:var(--text);
}
.list{margin-top:16px;display:flex;flex-direction:column;gap:12px;}
.item{
  border:1px solid var(--line);
  border-radius:16px;padding:14px;
  display:flex;justify-content:space-between;gap:12px;
}
.l{display:flex;flex-direction:column;gap:6px;}
.badgeRow{display:flex;gap:8px;flex-wrap:wrap;align-items:center;}
.badge{
  width:fit-content;
  background:var(--soft);
  padding:6px 10px;border-radius:999px;
  font-weight:950;font-size:12px;
  text-transform:uppercase;
}
.badge2{
  width:fit-content;
  background:#eef2ff;
  color:#3730a3;
  padding:6px 10px;border-radius:999px;
  font-weight:950;font-size:12px;
  text-transform:uppercase;
  border:1px solid #e0e7ff;
}
.meta{color:var(--muted);font-size:12px;font-weight:800;}
.r{text-align:right;display:flex;flex-direction:column;gap:6px;align-items:flex-end;}
.amt{font-weight:950;}
.status{
  width:fit-content;
  padding:6px 10px;border-radius:999px;
  font-weight:950;font-size:12px;
}
.completed{background:#DCFCE7;color:#166534;}
.pending{background:#FEF9C3;color:#854d0e;}
.failed{background:#FEE2E2;color:#991b1b;}
.linksRow{display:flex;gap:8px;flex-wrap:wrap;margin-top:6px;}
.linksRow a{
  text-decoration:none;
  font-size:12px;
  font-weight:900;
  color:#1E9BD7;
}
.empty{
  text-align:center;padding:28px 10px;
}
.empty .ic{
  width:70px;height:70px;border-radius:50%;
  background:#E0F2FE;color:#0284C7;
  display:flex;align-items:center;justify-content:center;
  margin:0 auto 14px;font-size:26px;
}
.empty h3{font-weight:950;margin-bottom:6px;}
.empty p{color:var(--muted);font-weight:800;font-size:13px;margin-bottom:14px;}
.btn{
  display:inline-flex;align-items:center;gap:10px;
  background:var(--primary);color:#fff;text-decoration:none;
  padding:10px 16px;border-radius:12px;font-weight:950;font-size:13px;
}
.pager{
  margin-top:18px;
  display:flex;justify-content:space-between;align-items:center;gap:10px;
}
.pager a{
  padding:10px 14px;border-radius:12px;
  border:1px solid var(--line);
  text-decoration:none;color:#111827;font-weight:900;
  background:#fff;
}
.pager .muted{color:var(--muted);font-weight:900;font-size:13px;}
</style>
</head>
<body>
<div class="wrap">

  <div class="topbar">
    <div class="left">
      <a class="iconbtn" href="dashboard.php" title="Back"><i class="fa-solid fa-arrow-left"></i></a>
      <div>
        <h1>Transactions</h1>
        <div style="opacity:.9;font-weight:800;font-size:12px;">Wallet + VTU History</div>
      </div>
    </div>
    <div class="iconbtn" title="Refresh" onclick="location.reload()"><i class="fa-solid fa-rotate-right"></i></div>
  </div>

  <div class="card">
    <div class="title"><i class="fa-solid fa-clock-rotate-left"></i> Recent Transactions</div>

    <div class="tabs">
      <a class="tab <?= $tab==='all'?'active':'' ?>" href="transactions.php?status=all">All</a>
      <a class="tab <?= $tab==='completed'?'active':'' ?>" href="transactions.php?status=completed">Completed</a>
      <a class="tab <?= $tab==='pending'?'active':'' ?>" href="transactions.php?status=pending">Pending</a>
      <a class="tab <?= $tab==='failed'?'active':'' ?>" href="transactions.php?status=failed">Failed</a>
    </div>

    <form class="search" method="get" action="transactions.php">
      <input type="hidden" name="status" value="<?= esc($tab) ?>">
      <i class="fa-solid fa-magnifying-glass" style="color:#94a3b8;"></i>
      <input name="q" value="<?= esc($search) ?>" placeholder="Search (service, ref, phone, network, description)..." />
    </form>

    <?php if (empty($rows)): ?>
      <div class="empty">
        <div class="ic"><i class="fa-solid fa-right-left"></i></div>
        <h3>No Transactions Yet</h3>
        <p>Your history will appear here</p>
        <a class="btn" href="data_subscription.php"><i class="fa-solid fa-bolt"></i> Start a Transaction</a>
      </div>
    <?php else: ?>
      <div class="list">
        <?php foreach($rows as $t):
          $st = $t['status_ui'] ?? 'pending';
          if(!in_array($st,['completed','pending','failed'])) $st='pending';

          $isVtu = ($t['source'] ?? '') === 'vtu';
          $service = $t['service'] ?? 'service';
          $receiptLink = 'receipt.php?src=' . urlencode($isVtu ? 'vtu' : 'core') . '&id=' . (int)($t['id'] ?? 0);
          $pdfLink = 'receipt_pdf.php?src=' . urlencode($isVtu ? 'vtu' : 'core') . '&id=' . (int)($t['id'] ?? 0);
        ?>
          <div class="item">
            <div class="l">
              <div class="badgeRow">
                <div class="badge"><?= esc($service) ?></div>
                <div class="badge2"><?= $isVtu ? 'VTU' : 'WALLET' ?></div>
              </div>

              <div class="meta">
                <?= esc($t['created_at'] ?? '') ?>
                <?php if ($isVtu && !empty($t['ref'])): ?>
                  • Ref: <?= esc($t['ref']) ?>
                <?php elseif (!$isVtu && !empty($t['reference'])): ?>
                  • Ref: <?= esc($t['reference']) ?>
                <?php endif; ?>
              </div>

              <?php if ($isVtu): ?>
                <div class="meta">
                  <?= !empty($t['network']) ? esc($t['network'])." • " : "" ?>
                  <?= !empty($t['phone']) ? esc($t['phone']) : "" ?>
                  <?= !empty($t['plan_id']) ? " • Plan: ".esc($t['plan_id']) : "" ?>
                </div>
                <?php if(!empty($t['provider_message'])): ?>
                  <div class="meta"><?= esc($t['provider_message']) ?></div>
                <?php endif; ?>
              <?php else: ?>
                <?php if(!empty($t['description'])): ?>
                  <div class="meta"><?= esc($t['description']) ?></div>
                <?php endif; ?>
              <?php endif; ?>

              <div class="linksRow">
                <a href="<?= esc($receiptLink) ?>">View Receipt</a>
                <a href="<?= esc($pdfLink) ?>">Download PDF</a>
              </div>
            </div>

            <div class="r">
              <div class="amt">
                <?php
                  $sign = ($t['type'] ?? 'debit') === 'credit' ? '+' : '-';
                  echo $sign . "₦" . number_format((float)$t['amount'], 2);
                ?>
              </div>

              <?php if ($isVtu): ?>
                <div class="meta">
                  Cost: ₦<?= number_format((float)($t['amount_cost'] ?? 0), 2) ?>
                  • Profit: ₦<?= number_format((float)($t['profit'] ?? 0), 2) ?>
                </div>
              <?php endif; ?>

              <div class="status <?= esc($st) ?>"><?= ucfirst($st) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="pager">
        <div class="muted">Page <?= $page ?> of <?= $totalPages ?> • Total: <?= $total ?></div>
        <div style="display:flex;gap:10px;">
          <?php if($page > 1): ?>
            <a href="?status=<?= urlencode($tab) ?>&q=<?= urlencode($search) ?>&page=<?= $page-1 ?>">Previous</a>
          <?php endif; ?>
          <?php if($page < $totalPages): ?>
            <a href="?status=<?= urlencode($tab) ?>&q=<?= urlencode($search) ?>&page=<?= $page+1 ?>">Next</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

  </div>

</div>
</body>
</html>