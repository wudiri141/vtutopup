<?php
session_start();
include "db.php";
require_once "referral_helper.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

/* ===== User ===== */
$stmt = $conn->prepare("SELECT fullname, wallet, profile, virtual_account_number, virtual_bank_name FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$fullname = $user['fullname'] ?? "User";
$wallet   = (float)($user['wallet'] ?? 0.00);
$profile  = $user['profile'] ?? "profile.png";
$vaNumber = $user['virtual_account_number'] ?? "";
$vaBank   = $user['virtual_bank_name'] ?? "";
$referral = getReferralStats($conn, $user_id);
$referralLink = "https://vtutopup.com.ng/register.php?ref=" . urlencode($referral['code']);

$_SESSION['wallet'] = $wallet;
$_SESSION['fullname'] = $fullname;
$_SESSION['profile'] = $profile;

/* ===== Recent Transactions (edit table/columns if needed) =====
   Expected table: transactions(id, user_id, service, amount, status, created_at)
*/
// ===== Recent Transactions (Dashboard) =====
$tx = [];

$txStmt = $conn->prepare("
  SELECT id, type, service, amount, status, reference, created_at
  FROM transactions
  WHERE user_id = ?
  ORDER BY id DESC
  LIMIT 5
");
$txStmt->bind_param("i", $user_id);
$txStmt->execute();
$txRes = $txStmt->get_result();

while($row = $txRes->fetch_assoc()){
  $tx[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Inter',system-ui,sans-serif;}
body{background:#F5F7FA;color:#1F2937;}
:root{
  --text:#1F2937;
  --muted:#6b7280;
  --line:#E5E7EB;
  --soft:#F1F5F9;
  --primary:#1E9BD7;
}

.header{background:#fff;padding:16px 30px;border-bottom:1px solid #E5E7EB;display:flex;align-items:center;gap:20px;position:fixed;top:0;left:0;right:0;height:70px;z-index:1000;}
.menu-icon{font-size:22px;cursor:pointer;}
.header h1{font-size:20px;font-weight:600;}

.sidebar{position:fixed;top:70px;left:0;bottom:0;width:250px;background:#fff;border-right:1px solid #E5E7EB;padding:20px;transition:width .3s ease,left .3s ease;overflow:hidden;z-index:999;}
.sidebar.collapsed{width:60px;}
.sidebar .profile{text-align:center;margin-bottom:25px;}
.sidebar .profile img{width:70px;height:70px;border-radius:50%;}
.sidebar .profile h3{margin-top:10px;font-size:16px;}
.sidebar.collapsed .profile img,.sidebar.collapsed .profile h3{display:none;}
.menu ul{list-style:none;}
.menu ul li a{display:flex;align-items:center;gap:12px;padding:12px 14px;margin-bottom:6px;border-radius:10px;text-decoration:none;color:#374151;font-size:14px;transition:background .2s;}
.menu ul li a i{min-width:20px;text-align:center;}
.menu ul li a:hover,.menu ul li a.active{background:#E0F2FE;color:#0284C7;}
.sidebar.collapsed .menu ul li a span{display:none;}
.sidebar.collapsed .menu ul li a i{margin:0 auto;}

.content{margin-left:250px;padding:100px 30px 30px;transition:margin-left .3s ease;}
.content.expanded{margin-left:60px;}
.container{max-width:1200px;margin:auto;}

.grid{display:grid;gap:20px;}
.services-grid{grid-template-columns:repeat(auto-fit,minmax(220px,1fr));}
.card{background:#fff;border-radius:16px;padding:22px;box-shadow:0 8px 25px rgba(0,0,0,.08);text-align:center;}
.card i{font-size:26px;color:var(--primary);margin-bottom:10px;}
.card h3{font-size:15px;margin-bottom:10px;}

.btn-primary{display:inline-block;background:var(--primary);color:#fff;padding:10px 18px;border-radius:10px;font-weight:600;border:none;cursor:pointer;text-decoration:none;}
.btn-primary:hover{background:#1684b8;}

.account-info{background:var(--primary);color:#fff;border-radius:20px;padding:25px 30px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:15px;}
.balance-row{display:flex;align-items:center;gap:12px;}
.eye-btn{background:rgba(255,255,255,.15);border:none;color:#fff;width:38px;height:38px;border-radius:50%;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;}

.va-box{background:#fff;border-radius:14px;padding:16px 18px;margin-bottom:25px;box-shadow:0 6px 20px rgba(0,0,0,.06);border:1px solid var(--line);}
.va-box h3{margin-bottom:8px;font-size:16px;}
.va-box .row{display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;}
.va-pill{background:var(--soft);padding:10px 12px;border-radius:12px;font-weight:700;}

.ref-box{background:#fff;border:1px solid var(--line);border-radius:18px;padding:18px;margin-bottom:25px;box-shadow:0 8px 25px rgba(0,0,0,.06);}
.ref-head{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;flex-wrap:wrap;margin-bottom:14px;}
.ref-head h3{font-size:16px;font-weight:900;margin-bottom:4px;}
.ref-head p{font-size:13px;color:var(--muted);}
.ref-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:14px;}
.ref-stat{background:var(--soft);border-radius:14px;padding:12px;}
.ref-stat span{display:block;font-size:12px;color:var(--muted);margin-bottom:5px;}
.ref-stat b{font-size:16px;}
.ref-copy{display:flex;gap:10px;flex-wrap:wrap;}
.ref-copy input{flex:1;min-width:220px;border:1px solid var(--line);border-radius:12px;padding:12px;font-weight:800;color:#0f172a;background:#F8FAFC;}
.ref-copy button{border:none;background:var(--primary);color:#fff;border-radius:12px;padding:12px 16px;font-weight:900;cursor:pointer;}

.footer{margin-top:30px;padding:20px;text-align:center;font-size:13px;color:var(--muted);}

/* ===== Quick Actions ===== */
.dash-extra{
  margin: 24px 0 0;
  padding: 24px;
  background: #ffffff;
  border-radius: 18px;
  border: 1px solid var(--line);
  box-shadow:0 8px 25px rgba(0,0,0,.06);
}
.dash-extra h3{font-size:18px;font-weight:900;margin-bottom:6px;}
.dash-sub{color:var(--muted);font-size:14px;margin-bottom:18px;}
.dash-actions{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;}
.dash-card{
  background: var(--soft);
  border-radius: 16px;
  padding: 22px 16px;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  gap: 10px;
  text-decoration:none;
  color: var(--text);
  font-weight: 800;
  transition: .25s;
  border:1px solid transparent;
}
.dash-card i{font-size:24px;color:var(--primary);}
.dash-card:hover{
  transform: translateY(-6px);
  box-shadow: 0 12px 25px rgba(0,0,0,.08);
  background:#fff;
  border-color: var(--line);
}

/* ===== Recent Transactions ===== */
.tx-box{
  margin-top: 18px;
  background:#fff;
  border:1px solid var(--line);
  border-radius:18px;
  padding:22px;
  box-shadow:0 8px 25px rgba(0,0,0,.06);
}
.tx-head{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  margin-bottom:16px;
}
.tx-title{
  display:flex;
  align-items:center;
  gap:10px;
  color:#111827;
}
.tx-title i{color:var(--primary);}
.tx-head h3{font-size:16px;font-weight:900;}
.tx-link{text-decoration:none;font-weight:800;color:var(--primary);font-size:13px;}

.tx-empty{text-align:center;padding:26px 12px;}
.tx-empty-icon{
  width:70px;height:70px;border-radius:50%;
  background:#E0F2FE;display:flex;align-items:center;justify-content:center;
  margin:0 auto 14px;color:var(--primary);font-size:26px;
}
.tx-empty h4{font-size:16px;font-weight:900;margin-bottom:6px;}
.tx-empty p{color:var(--muted);font-size:13px;margin-bottom:14px;}
.tx-btn{
  display:inline-flex;align-items:center;gap:10px;
  background:var(--primary);color:#fff;text-decoration:none;
  padding:10px 16px;border-radius:12px;font-weight:900;font-size:13px;
}

.tx-list{display:flex;flex-direction:column;gap:12px;}
.tx-item{
  border:1px solid var(--line);
  border-radius:14px;
  padding:14px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
}
.tx-left{display:flex;flex-direction:column;gap:6px;}
.tx-badge{
  font-size:12px;font-weight:900;
  background:var(--soft);
  padding:6px 10px;border-radius:999px;width:fit-content;
}
.tx-date{font-size:12px;color:var(--muted);}
.tx-right{text-align:right;display:flex;flex-direction:column;gap:6px;}
.tx-amt{font-weight:900;}
.tx-status{
  font-size:12px;font-weight:900;
  padding:6px 10px;border-radius:999px;width:fit-content;margin-left:auto;
}
.tx-status.completed{background:#DCFCE7;color:#166534;}
.tx-status.pending{background:#FEF9C3;color:#854d0e;}
.tx-status.failed{background:#FEE2E2;color:#991b1b;}

@media(max-width:768px){
  .sidebar{left:-250px;}
  .sidebar.active{left:0;}
  .content{margin-left:0;padding-top:100px;}
}
@media(max-width:900px){
  .dash-actions{grid-template-columns:repeat(2,1fr);}
}
@media(max-width:520px){
  .dash-actions{grid-template-columns:1fr;}
  .ref-stats{grid-template-columns:1fr;}
}
/* White Fund Wallet Button */
.btn-white-primary{
  background:#ffffff;
  color:#0f172a;
  padding:10px 18px;
  border-radius:10px;
  font-weight:700;
  border:1px solid #e5e7eb;
  text-decoration:none;
  display:inline-flex;
  align-items:center;
  gap:8px;
  box-shadow:0 8px 18px rgba(0,0,0,.12);
  transition:.2s;
}

.btn-white-primary i{
  color:#1E9BD7;
}

.btn-white-primary:hover{
  transform:translateY(-2px);
  background:#f8fafc;
}

</style>
</head>

<body>
<div class="header">
  <i class="fas fa-bars menu-icon"></i>
  <h1>Dashboard</h1>
</div>

<div class="sidebar" id="sidebar">
  <div class="profile">
    <img src="<?= htmlspecialchars($profile) ?>" alt="Profile">
    <h3><?= htmlspecialchars($fullname) ?></h3>
  </div>
  <div class="menu">
    <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<ul>
  <?php
  // Check role once
  $roleStmt = $conn->prepare("SELECT role FROM users WHERE id=?");
  $roleStmt->bind_param("i", $_SESSION['user_id']);
  $roleStmt->execute();
  $r = $roleStmt->get_result()->fetch_assoc();
  ?>
  <?php if (($_SESSION['role'] ?? 'user') === 'admin'): ?>
  <li>
    <a href="admin/dashboard.php" style="background:#fff3cd;color:#92400e;">
      <i class="fas fa-shield-alt"></i>
      <span>Admin Dashboard</span>
    </a>
      <a href="admin/users.php">Users</a>
  </a>
  </li>
<?php endif; ?>
  
  <li>
    <a href="dashboard.php" class="<?= $currentPage=='dashboard.php' ? 'active' : '' ?>">
      <i class="fas fa-home"></i><span>Dashboard</span>
    </a>
  </li>

  <li>
    <a href="airtime_subscription.php">
      <i class="fas fa-phone"></i><span>Airtime</span>
    </a>
  </li>

  <li>
    <a href="data_subscription.php">
      <i class="fas fa-wifi"></i><span>Data</span>
    </a>
  </li>

  <li>
    <a href="cable_subscription.php">
      <i class="fas fa-tv"></i><span>Cable TV</span>
    </a>
  </li>

  <li>
    <a href="electricity_subscription.php">
      <i class="fas fa-bolt"></i><span>Electricity</span>
    </a>
  </li>

  <li>
    <a href="transactions.php">
      <i class="fas fa-history"></i><span>Transactions</span>
    </a>
  </li>

  <li>
    <a href="account.php">
      <i class="fas fa-user"></i><span>Account</span>
    </a>
  </li>

  <li>
    <a href="logout.php">
      <i class="fas fa-sign-out-alt"></i><span>Logout</span>
    </a>
  </li>
</ul>

  </div>
</div>

<div class="content" id="content">
  <div class="container">

    <!-- Account Overview -->
    <div class="account-info">
      <div>
        <h2 style="margin-bottom:6px;">Account Overview</h2>
        <div class="balance-row">
          <h1 id="walletBalance">₦<?= number_format($wallet, 2) ?></h1>
          <button class="eye-btn" onclick="toggleBalance()" title="Hide/Show Balance">
            <i class="fas fa-eye" id="eyeIcon"></i>
          </button>
        </div>
        <div style="opacity:.9;margin-top:6px;">Available Balance</div>
      </div>

      <!-- Replaced Generate Account with Fund Wallet -->
    <a class="btn-white-primary" href="fund_wallet.php">
      <i class="fa-solid fa-wallet"></i> Fund Wallet
      </a>
    </div>

    <!-- Virtual Account Box (shows if already generated in DB) -->
    <div class="va-box" id="vaBox" style="<?= $vaNumber ? '' : 'display:none;' ?>">
      <h3>Your Virtual Account</h3>
      <div class="row">
        <div class="va-pill" id="vaBank"><?= htmlspecialchars($vaBank ?: 'Bank') ?></div>
        <div class="va-pill" id="vaNumber"><?= htmlspecialchars($vaNumber ?: '0000000000') ?></div>
      </div>
      <div style="margin-top:10px;color:#64748b;font-size:13px;">
        Transfer to this account to fund your wallet.
      </div>
    </div>

    <div class="ref-box">
      <div class="ref-head">
        <div>
          <h3>Refer and Earn</h3>
          <p>Earn ₦<?= number_format((float)$referral['bonus'], 2) ?> when your invited user verifies their email.</p>
        </div>
      </div>
      <div class="ref-stats">
        <div class="ref-stat"><span>Your Code</span><b><?= htmlspecialchars($referral['code'], ENT_QUOTES, 'UTF-8') ?></b></div>
        <div class="ref-stat"><span>Total Referrals</span><b><?= (int)$referral['count'] ?></b></div>
        <div class="ref-stat"><span>Total Earned</span><b>₦<?= number_format((float)$referral['earnings'], 2) ?></b></div>
      </div>
      <div class="ref-copy">
        <input id="referralLink" value="<?= htmlspecialchars($referralLink, ENT_QUOTES, 'UTF-8') ?>" readonly>
        <button type="button" onclick="copyReferralLink()"><i class="fa-solid fa-copy"></i> Copy Link</button>
      </div>
    </div>
<!-- Recent Transactions -->
<!-- Recent Transactions -->
<div class="tx-box">
  <div class="tx-head">
    <div class="tx-title">
      <i class="fa-solid fa-clock-rotate-left"></i>
      <h3>Recent Transactions</h3>
    </div>
    <a class="tx-link" href="transactions.php">View all</a>
  </div>

  <?php if (empty($tx)): ?>
    <div class="tx-empty">
      <div class="tx-empty-icon"><i class="fa-solid fa-right-left"></i></div>
      <h4>No Transactions Yet</h4>
      <p>Your transaction history will appear here</p>
      <a href="data_subscription.php" class="tx-btn">
        <i class="fa-solid fa-bolt"></i> Start a Transaction
      </a>
    </div>
  <?php else: ?>
    <div class="tx-list">
      <?php foreach ($tx as $t):
        $statusClass = strtolower(trim($t['status'] ?? 'pending'));
        if (!in_array($statusClass, ['completed','pending','failed'])) {
          $statusClass = 'pending';
        }

        $sign = (strtolower($t['type'] ?? '') === 'credit') ? '+' : '-';
      ?>
        <!-- CLICKABLE TRANSACTION -->
        <a href="receipt.php?src=core&id=<?= (int)$t['id'] ?>"
           class="tx-item"
           style="text-decoration:none;color:inherit;">

          <div class="tx-left">
            <div class="tx-badge"><?= htmlspecialchars($t['service'] ?? $t['type'] ?? 'Transaction', ENT_QUOTES, 'UTF-8') ?></div>
            <div class="tx-date">
              <?= htmlspecialchars($t['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            </div>
          </div>

          <div class="tx-right">
            <div class="tx-amt">
              <?= $sign ?>₦<?= number_format((float)$t['amount'], 2) ?>
            </div>
            <div class="tx-status <?= $statusClass ?>">
              <?= ucfirst($statusClass) ?>
            </div>
          </div>

        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>


    <!-- Services -->
    <h2 style="margin:20px 0 15px;">Services</h2>
    <div class="grid services-grid">
      <div class="card">
        <i class="fas fa-mobile-alt"></i>
        <h3>Airtime TopUp</h3>
        <a href="airtime_subscription.php" class="btn-primary">Buy Now</a>
      </div>
      <div class="card">
        <i class="fas fa-wifi"></i>
        <h3>Buy Data</h3>
        <a href="data_subscription.php" class="btn-primary">Get Data</a>
      </div>
      <div class="card">
        <i class="fas fa-bolt"></i>
        <h3>Electricity Bills</h3>
        <a href="electricity_subscription.php" class="btn-primary">Pay Now</a>
      </div>
      <div class="card">
        <i class="fas fa-tv"></i>
        <h3>Cable Subscription</h3>
        <a href="cable_subscription.php" class="btn-primary">Subscribe</a>
      </div>
    </div>

    <footer class="footer">
      <p>&copy; <?= date('Y') ?> VTU TOPUP. All rights reserved.</p>
    </footer>

  </div>
</div>

<script>
document.querySelector('.menu-icon').addEventListener('click', function() {
  const sidebar = document.getElementById("sidebar");
  const content = document.getElementById("content");

  if (window.innerWidth <= 768) {
    sidebar.classList.toggle("active");
  } else {
    sidebar.classList.toggle("collapsed");
    content.classList.toggle("expanded");
  }
});

let balanceVisible = true;
let realBalance = "₦<?= number_format($wallet, 2) ?>";

function toggleBalance() {
  const balance = document.getElementById("walletBalance");
  const icon = document.getElementById("eyeIcon");

  if (balanceVisible) {
    balance.innerText = "₦******";
    icon.classList.replace("fa-eye", "fa-eye-slash");
  } else {
    balance.innerText = realBalance;
    icon.classList.replace("fa-eye-slash", "fa-eye");
  }
  balanceVisible = !balanceVisible;
}

function copyReferralLink() {
  const input = document.getElementById("referralLink");
  input.select();
  input.setSelectionRange(0, 99999);
  navigator.clipboard.writeText(input.value).catch(() => document.execCommand("copy"));
}
</script>

</body>
</html>
