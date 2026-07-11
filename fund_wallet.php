<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT fullname, wallet, profile, virtual_account_number, virtual_bank_name FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$fullname = $user['fullname'] ?? "User";
$wallet   = (float)($user['wallet'] ?? 0.00);
$profile  = $user['profile'] ?? "profile.png";
$vaNumber = $user['virtual_account_number'] ?? "";
$vaBank   = $user['virtual_bank_name'] ?? "";

$_SESSION['wallet'] = $wallet;
$_SESSION['fullname'] = $fullname;
$_SESSION['profile'] = $profile;

// Optional: If you want to “simulate” funding for testing only (remove in production)
if (isset($_GET['test_fund']) && is_numeric($_GET['test_fund'])) {
  $amt = (float)$_GET['test_fund'];
  if ($amt > 0) {
    $conn->begin_transaction();
    try {
      // Update wallet
      $up = $conn->prepare("UPDATE users SET wallet = wallet + ? WHERE id=?");
      $up->bind_param("di", $amt, $user_id);
      $up->execute();

      // Insert transaction record
      $ref = "TEST_" . time();
      $desc = "Test wallet funding";
      $ins = $conn->prepare("INSERT INTO transactions (user_id, type, service, amount, status, reference, description) VALUES (?, 'credit', 'Fund Wallet', ?, 'completed', ?, ?)");
      $ins->bind_param("idss", $user_id, $amt, $ref, $desc);
      $ins->execute();

      $conn->commit();
      header("Location: fund_wallet.php?success=1");
      exit();
    } catch (Exception $e) {
      $conn->rollback();
      header("Location: fund_wallet.php?error=1");
      exit();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Fund Wallet</title>
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
  color:#fff;
  border-radius:18px;
  padding:18px 18px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
}
.topbar .left{display:flex;align-items:center;gap:12px;}
.iconbtn{
  width:42px;height:42px;border-radius:14px;
  display:flex;align-items:center;justify-content:center;
  background:rgba(255,255,255,.14);
  color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.18);
}
.topbar h1{font-size:18px;font-weight:900;}
.balance-card{
  margin-top:14px;
  background:var(--primary);
  color:#fff;
  border-radius:18px;
  padding:18px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
}
.balance-card small{opacity:.9;font-weight:700;}
.balance-row{display:flex;align-items:center;gap:10px;margin-top:6px;}
.balance-row .amt{font-size:24px;font-weight:950;letter-spacing:.3px;}
.eye{
  width:38px;height:38px;border-radius:14px;
  background:rgba(255,255,255,.14);
  border:1px solid rgba(255,255,255,.18);
  color:#fff;
  display:flex;align-items:center;justify-content:center;
  cursor:pointer;
}
.methods{
  margin-top:18px;
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:14px;
}
.method{
  background:#fff;border:1px solid var(--line);
  border-radius:18px;padding:16px;
  display:flex;flex-direction:column;align-items:center;gap:10px;
  text-decoration:none;color:var(--text);
  box-shadow:0 10px 22px rgba(0,0,0,.06);
}
.method .micon{
  width:54px;height:54px;border-radius:16px;
  display:flex;align-items:center;justify-content:center;
  color:#fff;font-size:22px;font-weight:900;
}
.m1{background:#2563eb;}
.m2{background:#059669;}
.m3{background:#7c3aed;}
.method span{font-weight:900;font-size:13px;}
.cardbox{
  margin-top:16px;
  background:#fff;border:1px solid var(--line);
  border-radius:22px;padding:18px;
  box-shadow:0 10px 24px rgba(0,0,0,.06);
}
.bankhead{
  display:flex;align-items:center;gap:12px;margin-bottom:12px;
}
.banklogo{
  width:46px;height:46px;border-radius:16px;
  background:var(--primary);
  display:flex;align-items:center;justify-content:center;
  color:#fff;font-weight:950;
}
.bankname{font-weight:950;}
.badge{
  display:inline-flex;
  align-items:center;
  padding:6px 10px;border-radius:999px;
  background:#dcfce7;color:#166534;
  font-weight:900;font-size:12px;
  margin-top:6px;
}
.rows{margin-top:14px;display:flex;flex-direction:column;gap:12px;}
.row{
  display:flex;justify-content:space-between;gap:12px;
  padding:12px;border-radius:16px;background:var(--soft);
  border:1px solid #e9eef5;
}
.row .l{color:var(--muted);font-weight:800;font-size:13px;}
.row .r{font-weight:950;}
.notice{
  margin-top:12px;
  background:#fff7ed;border-left:4px solid #f59e0b;
  padding:12px;border-radius:14px;color:#7c2d12;
  font-size:13px;font-weight:700;
}
.copybtn{
  margin-top:14px;
  width:100%;
  background:var(--primary);
  color:#fff;
  border:none;
  border-radius:14px;
  padding:12px 14px;
  font-weight:950;
  cursor:pointer;
  display:flex;
  align-items:center;
  justify-content:center;
  gap:10px;
  box-shadow:0 12px 24px rgba(213,58,152,.25);
}
.msg{
  margin-top:12px;
  padding:10px 12px;
  border-radius:14px;
  font-weight:800;
  font-size:13px;
}
.ok{background:#dcfce7;color:#166534;border:1px solid #bbf7d0;}
.err{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;}
@media(max-width:850px){
  .methods{grid-template-columns:1fr;}
}
</style>
</head>
<body>

  <div class="wrap">

    <div class="topbar">
      <div class="left">
        <a class="iconbtn" href="dashboard.php" title="Back"><i class="fa-solid fa-arrow-left"></i></a>
        <div>
          <h1>Fund Wallet</h1>
          <div style="opacity:.9;font-weight:800;font-size:12px;">Hello, <?= htmlspecialchars($fullname) ?></div>
        </div>
      </div>
      <div class="iconbtn" title="Refresh" onclick="location.reload()"><i class="fa-solid fa-rotate-right"></i></div>
    </div>

    <?php if(isset($_GET['success'])): ?>
      <div class="msg ok">Wallet funded successfully (test).</div>
    <?php elseif(isset($_GET['error'])): ?>
      <div class="msg err">Funding failed. Try again.</div>
    <?php endif; ?>

    <div class="balance-card">
      <div>
        <small>Available Balance</small>
        <div class="balance-row">
          <div class="amt" id="bal">₦<?= number_format($wallet, 2) ?></div>
          <button class="eye" onclick="toggleBal()" title="Hide/Show"><i class="fa-solid fa-eye" id="eye"></i></button>
        </div>
      </div>
    </div>

    <div class="methods">
      <a class="method" href="#bank">
        <div class="micon m1"><i class="fa-solid fa-building-columns"></i></div>
        <span>Bank Transfer</span>
      </a>
      <a class="method" href="#card">
        <div class="micon m2"><i class="fa-solid fa-credit-card"></i></div>
        <span>Card Payment</span>
      </a>
      <a class="method" href="#manual">
        <div class="micon m3"><i class="fa-solid fa-money-bill-transfer"></i></div>
        <span>Manual Transfer</span>
      </a>
    </div>

    <div class="cardbox" id="bank">
      <div class="bankhead">
        <div class="banklogo"><i class="fa-solid fa-landmark"></i></div>
        <div>
          <div class="bankname"><?= htmlspecialchars($vaBank ?: "Virtual Bank") ?></div>
          <div class="badge">Account Ready</div>
        </div>
      </div>

      <div class="rows">
        <div class="row">
          <div class="l">Bank Name</div>
          <div class="r" id="bankName"><?= htmlspecialchars($vaBank ?: "Adamu Adamu Sani  opay") ?></div>
        </div>
        <div class="row">
          <div class="l">Account Number</div>
          <div class="r" id="acctNo"><?= htmlspecialchars($vaNumber ?: "9161044495") ?></div>
        </div>
      </div>

      <div class="notice">
        Automated bank transfer may attract small charges depending on your bank.
      </div>

      <button class="copybtn" onclick="copyAcct()">
        <i class="fa-solid fa-copy"></i> Copy Account No
      </button>
    </div>

<script>
let visible = true;
const real = "₦<?= number_format($wallet, 2) ?>";

function toggleBal(){
  const bal = document.getElementById("bal");
  const eye = document.getElementById("eye");
  if(visible){
    bal.textContent = "₦******";
    eye.classList.replace("fa-eye","fa-eye-slash");
  }else{
    bal.textContent = real;
    eye.classList.replace("fa-eye-slash","fa-eye");
  }
  visible = !visible;
}

async function copyAcct(){
  const acct = document.getElementById("acctNo").textContent.trim();
  try{
    await navigator.clipboard.writeText(acct);
    alert("Account number copied!");
  }catch(e){
    alert("Copy failed. Please copy manually: " + acct);
  }
}
</script>
</body>
</html>
