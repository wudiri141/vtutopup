<?php
session_start();
include "../db.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}

$admin_id = (int)$_SESSION['user_id'];

// Admin check
$chk = $conn->prepare("SELECT role, fullname, email FROM users WHERE id=? LIMIT 1");
$chk->bind_param("i", $admin_id);
$chk->execute();
$admin = $chk->get_result()->fetch_assoc();

if (!$admin || ($admin['role'] ?? 'user') !== 'admin') {
  http_response_code(403);
  die("Access denied");
}

$days = 30;
$since = date("Y-m-d", strtotime("-$days days"));

// ===== KPIs (last 30 days) =====
$kpiStmt = $conn->prepare("
  SELECT
    COALESCE(SUM(amount),0) AS total_volume,
    COALESCE(SUM(CASE WHEN type='credit' THEN amount ELSE 0 END),0) AS total_credit,
    COALESCE(SUM(CASE WHEN type='debit'  THEN amount ELSE 0 END),0) AS total_debit,
    COALESCE(SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END),0) AS failed_count,
    COALESCE(COUNT(*),0) AS tx_count
  FROM transactions
  WHERE created_at >= ?
");
$kpiStmt->bind_param("s", $since);
$kpiStmt->execute();
$kpi = $kpiStmt->get_result()->fetch_assoc();

$total_volume = (float)($kpi['total_volume'] ?? 0);
$total_credit = (float)($kpi['total_credit'] ?? 0);
$total_debit  = (float)($kpi['total_debit'] ?? 0);
$net = $total_credit - $total_debit;

// ===== Daily volume chart (last 30 days) =====
$dailyStmt = $conn->prepare("
  SELECT DATE(created_at) AS day, COALESCE(SUM(amount),0) AS amt
  FROM transactions
  WHERE created_at >= ?
  GROUP BY DATE(created_at)
  ORDER BY day ASC
");
$dailyStmt->bind_param("s", $since);
$dailyStmt->execute();
$dailyRes = $dailyStmt->get_result();

$dailyMap = [];
while($r = $dailyRes->fetch_assoc()){
  $dailyMap[$r['day']] = (float)$r['amt'];
}

// Fill missing days with 0
$labels = [];
$values = [];
for($i=$days; $i>=0; $i--){
  $d = date("Y-m-d", strtotime("-$i days"));
  $labels[] = $d;
  $values[] = $dailyMap[$d] ?? 0;
}

// ===== Credits vs Debits chart (last 30 days) =====
$cdStmt = $conn->prepare("
  SELECT
    COALESCE(SUM(CASE WHEN type='credit' THEN amount ELSE 0 END),0) AS c,
    COALESCE(SUM(CASE WHEN type='debit'  THEN amount ELSE 0 END),0) AS d
  FROM transactions
  WHERE created_at >= ?
");
$cdStmt->bind_param("s", $since);
$cdStmt->execute();
$cd = $cdStmt->get_result()->fetch_assoc();
$credit30 = (float)($cd['c'] ?? 0);
$debit30  = (float)($cd['d'] ?? 0);

// ===== Top services chart (last 30 days) =====
$svcStmt = $conn->prepare("
  SELECT service, COALESCE(SUM(amount),0) AS amt
  FROM transactions
  WHERE created_at >= ?
  GROUP BY service
  ORDER BY amt DESC
  LIMIT 7
");
$svcStmt->bind_param("s", $since);
$svcStmt->execute();
$svcRes = $svcStmt->get_result();

$svcLabels = [];
$svcValues = [];
while($s = $svcRes->fetch_assoc()){
  $svcLabels[] = $s['service'] ?: 'Unknown';
  $svcValues[] = (float)$s['amt'];
}

// ===== Latest transactions =====
$latest = $conn->query("
  SELECT t.id, u.fullname, t.type, t.service, t.amount, t.status, t.reference, t.created_at
  FROM transactions t
  LEFT JOIN users u ON u.id = t.user_id
  ORDER BY t.id DESC
  LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Admin Dashboard</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
:root{
  --primary:#1E9BD7;
  --bg:#F5F7FA;
  --text:#111827;
  --muted:#6b7280;
  --line:#E5E7EB;
  --card:#ffffff;
  --soft:#F1F5F9;
}
*{margin:0;padding:0;box-sizing:border-box;font-family:Inter,system-ui,Arial,sans-serif;}
body{background:var(--bg);color:var(--text);}
.wrap{max-width:1200px;margin:0 auto;padding:18px;}
.topbar{
  background:var(--primary);
  color:#fff;
  padding:16px 18px;
  border-radius:18px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
}
.topbar .left{display:flex;flex-direction:column;gap:4px}
.topbar h1{font-size:18px;font-weight:950;}
.topbar .meta{opacity:.95;font-weight:700;font-size:12px;}
.btn{
  background:rgba(255,255,255,.14);
  border:1px solid rgba(255,255,255,.18);
  color:#fff;
  padding:10px 14px;
  border-radius:14px;
  text-decoration:none;
  font-weight:900;
  display:inline-flex;
  align-items:center;
  gap:8px;
}
.grid{display:grid;gap:14px;margin-top:14px;}
.kpis{grid-template-columns:repeat(4,1fr);}
.card{
  background:var(--card);
  border:1px solid var(--line);
  border-radius:18px;
  padding:16px;
  box-shadow:0 10px 24px rgba(0,0,0,.06);
}
.kpi-title{color:var(--muted);font-weight:900;font-size:12px;margin-bottom:8px;}
.kpi-value{font-size:20px;font-weight:950;letter-spacing:.2px;}
.kpi-sub{margin-top:6px;color:var(--muted);font-weight:800;font-size:12px;}
.charts{grid-template-columns:1.4fr 1fr;}
.chartbox{height:320px;}
.tablewrap{margin-top:14px;}
table{width:100%;border-collapse:collapse;}
th,td{padding:10px;border-bottom:1px solid var(--line);font-size:13px;}
th{text-align:left;background:var(--soft);font-weight:950;}
.badge{
  display:inline-flex;align-items:center;
  padding:6px 10px;border-radius:999px;
  font-weight:950;font-size:12px;
}
.completed{background:#DCFCE7;color:#166534;}
.pending{background:#FEF9C3;color:#854d0e;}
.failed{background:#FEE2E2;color:#991b1b;}
.small{color:var(--muted);font-weight:800;font-size:12px;}
.action a{color:var(--primary);font-weight:950;text-decoration:none;}
@media(max-width:1000px){
  .kpis{grid-template-columns:repeat(2,1fr);}
  .charts{grid-template-columns:1fr;}
  .chartbox{height:300px;}
}
</style>
</head>
<body>
  <div class="wrap">

    <div class="topbar">
      <div class="left">
        <h1>Admin Dashboard</h1>
        <div class="meta">Signed in as <?= htmlspecialchars($admin['fullname'] ?? 'Admin') ?> • Last <?= $days ?> days</div>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a class="btn" href="fund_wallet.php"><i class="fa-solid fa-wallet"></i> Manual Credit/Debit</a>
        <a class="btn" href="../dashboard.php"><i class="fa-solid fa-house"></i> User Dashboard</a>
      </div>
    </div>

    <div class="grid kpis">
      <div class="card">
        <div class="kpi-title">Total Volume (<?= $days ?>d)</div>
        <div class="kpi-value">₦<?= number_format($total_volume, 2) ?></div>
        <div class="kpi-sub"><?= (int)($kpi['tx_count'] ?? 0) ?> transactions</div>
      </div>

      <div class="card">
        <div class="kpi-title">Total Credits (<?= $days ?>d)</div>
        <div class="kpi-value">₦<?= number_format($total_credit, 2) ?></div>
        <div class="kpi-sub">Wallet funding & credits</div>
      </div>

      <div class="card">
        <div class="kpi-title">Total Debits (<?= $days ?>d)</div>
        <div class="kpi-value">₦<?= number_format($total_debit, 2) ?></div>
        <div class="kpi-sub">Purchases & debits</div>
      </div>

      <div class="card">
        <div class="kpi-title">Net (Credit - Debit)</div>
        <div class="kpi-value">₦<?= number_format($net, 2) ?></div>
        <div class="kpi-sub"><?= (int)($kpi['failed_count'] ?? 0) ?> failed tx</div>
      </div>
    </div>

    <div class="grid charts">
      <div class="card">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px;">
          <div style="font-weight:950;">Daily Volume</div>
          <div class="small">Last <?= $days ?> days</div>
        </div>
        <div class="chartbox">
          <canvas id="dailyChart"></canvas>
        </div>
      </div>

      <div class="grid" style="grid-template-rows:1fr 1fr;">
        <div class="card">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px;">
            <div style="font-weight:950;">Credits vs Debits</div>
            <div class="small"><?= $days ?>d</div>
          </div>
          <div class="chartbox" style="height:240px;">
            <canvas id="cdChart"></canvas>
          </div>
        </div>

        <div class="card">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px;">
            <div style="font-weight:950;">Top Services</div>
            <div class="small"><?= $days ?>d</div>
          </div>
          <div class="chartbox" style="height:240px;">
            <canvas id="svcChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="card tablewrap">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px;">
        <div style="font-weight:950;">Latest Transactions</div>
        <a class="btn" style="background:var(--primary);border-color:var(--primary);" href="transactions.php">
          <i class="fa-solid fa-clock-rotate-left"></i> View All (Admin)
        </a>
      </div>

      <div style="overflow:auto;">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>User</th>
              <th>Service</th>
              <th>Type</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Reference</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while($t = $latest->fetch_assoc()): 
              $st = strtolower($t['status'] ?? 'pending');
              if(!in_array($st,['completed','pending','failed'])) $st='pending';
            ?>
              <tr>
                <td><?= (int)$t['id'] ?></td>
                <td><?= htmlspecialchars($t['fullname'] ?? 'Unknown') ?></td>
                <td><?= htmlspecialchars($t['service'] ?? '') ?></td>
                <td><?= htmlspecialchars($t['type'] ?? '') ?></td>
                <td>₦<?= number_format((float)$t['amount'],2) ?></td>
                <td><span class="badge <?= $st ?>"><?= ucfirst($st) ?></span></td>
                <td><?= htmlspecialchars($t['reference'] ?? '') ?></td>
                <td><?= htmlspecialchars($t['created_at'] ?? '') ?></td>
                <td class="action">
                  <a href="receipt.php?id=<?= (int)$t['id'] ?>">View</a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

<script>
const dailyLabels = <?= json_encode($labels) ?>;
const dailyValues = <?= json_encode($values) ?>;

new Chart(document.getElementById('dailyChart'), {
  type: 'line',
  data: {
    labels: dailyLabels,
    datasets: [{
      label: 'Volume (₦)',
      data: dailyValues,
      tension: 0.25,
      fill: true
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { ticks: { maxTicksLimit: 6 } },
      y: { beginAtZero: true }
    }
  }
});

new Chart(document.getElementById('cdChart'), {
  type: 'pie',
  data: {
    labels: ['Credits', 'Debits'],
    datasets: [{
      data: [<?= $credit30 ?>, <?= $debit30 ?>]
    }]
  },
  options: { responsive: true, maintainAspectRatio: false }
});

new Chart(document.getElementById('svcChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($svcLabels) ?>,
    datasets: [{
      label: 'Amount (₦)',
      data: <?= json_encode($svcValues) ?>
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true } }
  }
});
</script>
</body>
</html>
