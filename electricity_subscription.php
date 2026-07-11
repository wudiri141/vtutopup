<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT fullname, wallet, transaction_pin FROM users WHERE id=? LIMIT 1");
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
    $stmt = $conn->prepare("SELECT id FROM transactions WHERE user_id=? AND reference=? LIMIT 1");
    $stmt->bind_param("is", $user_id, $ref);
    $stmt->execute();
    $tx = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($tx) {
      $receiptId = (int)$tx['id'];
    }
  }

  if ($receiptId > 0) {
    $receiptLink = "receipt.php?src=core&id=" . $receiptId;
    $pdfLink = "receipt_pdf.php?src=core&id=" . $receiptId;

    $msg = "✅ Electricity purchase successful! Ref: {$refSafe}<br>
            <a href='" . htmlspecialchars($receiptLink, ENT_QUOTES, 'UTF-8') . "'>View Receipt</a>
            &nbsp;|&nbsp;
            <a href='" . htmlspecialchars($pdfLink, ENT_QUOTES, 'UTF-8') . "'>Download PDF</a>";
  } else {
    $msg = "✅ Electricity purchase successful! Ref: {$refSafe}";
  }
}

if (isset($_GET['error'])) {
  $msg = "❌ " . htmlspecialchars($_GET['error'] ?? '', ENT_QUOTES, 'UTF-8');
}

// Load discos directly from DB
$discos = [];
$discoError = "";

try {
  $res = $conn->query("SELECT electricity_plan_id, the_electricty_name FROM vtu_electricityplanids ORDER BY electricity_plan_id ASC");
  while ($r = $res->fetch_assoc()) {
    $discos[] = $r;
  }
} catch (Throwable $e) {
  $discoError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Electricity</title>
<link rel="stylesheet" href="optionstyles.css">
</head>
<body>

<div class="airtime-card">
  <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

  <div class="page-header">
    <h2>Pay Electricity</h2>
    <p class="wallet-balance">Wallet Balance: ₦<?= number_format($wallet, 2) ?></p>
  </div>

  <?php if (!$pinSet): ?>
    <p style="background:#fff7ed;border:1px solid #fed7aa;padding:10px;border-radius:10px;color:#7c2d12;">
      Set Transaction PIN first: <a href="account.php"><b>Go to Account</b></a>
    </p>
  <?php endif; ?>

  <?php if ($msg): ?>
    <div style="margin-bottom:12px; padding:10px; border-radius:8px; background:#f1f5f9;">
      <?= $msg ?>
    </div>
  <?php endif; ?>

  <?php if ($discoError): ?>
    <div style="margin-bottom:12px; padding:10px; border-radius:8px; background:#fee2e2; color:#991b1b;">
      ❌ Disco load error: <?= htmlspecialchars($discoError, ENT_QUOTES, 'UTF-8') ?>
      <div style="font-size:12px;margin-top:6px;">Make sure table <b>vtu_electricityplanids</b> exists and you ran sync.</div>
    </div>
  <?php endif; ?>

  <?php if (empty($discos) && !$discoError): ?>
    <div style="margin-bottom:12px; padding:10px; border-radius:8px; background:#fff7ed; border:1px solid #fed7aa; color:#7c2d12;">
      ⚠️ No discos found. Run: <b>sync_electricityplans.php</b>
    </div>
  <?php endif; ?>

  <form method="POST" action="process_electricity_subscription.php" id="electricForm">

    <label>Disco</label>
    <select name="disco_name" id="disco_name" required onchange="clearVerify()">
      <option value="">-- Select Disco --</option>
      <?php foreach ($discos as $d): ?>
        <option value="<?= (int)$d['electricity_plan_id'] ?>">
          <?= htmlspecialchars($d['the_electricty_name'], ENT_QUOTES, 'UTF-8') ?> (ID: <?= (int)$d['electricity_plan_id'] ?>)
        </option>
      <?php endforeach; ?>
    </select>

    <label>Meter Number</label>
    <input type="text" name="meter_number" id="meter_number" required oninput="clearVerify()">

    <label>Meter Type</label>
    <select name="MeterType" id="MeterType">
      <option value="prepaid" selected>Prepaid</option>
      <option value="postpaid">Postpaid</option>
    </select>

    <label>Amount</label>
    <input type="number" name="amount" min="100" required>

    <div style="display:flex; gap:10px; margin-top:10px;">
      <button type="button" onclick="verifyMeter()" style="flex:1;">Verify Meter</button>
      <button type="submit" id="payBtn" style="flex:1;" <?= !$pinSet ? 'disabled style="opacity:.6;cursor:not-allowed;"' : 'disabled' ?>>Pay →</button>
    </div>

    <div id="verifyBox" style="margin-top:12px; padding:10px; border-radius:10px; background:#f1f5f9; display:none;"></div>

  </form>
</div>

<?php include "pin_modal.php"; ?>
<script>
PinModal.bind('#electricForm', { title:'Electricity Payment', sub:'Enter PIN to continue.' });

function clearVerify(){
  const box = document.getElementById("verifyBox");
  box.style.display = "none";
  box.innerHTML = "";
  document.getElementById("payBtn").disabled = true;
}

async function verifyMeter(){
  const disco = document.getElementById("disco_name").value;
  const meter = document.getElementById("meter_number").value.trim();
  const box = document.getElementById("verifyBox");

  if(!disco || !meter){
    box.style.display = "block";
    box.innerHTML = "❌ Select disco and enter meter number.";
    return;
  }

  box.style.display = "block";
  box.innerHTML = "⏳ Verifying...";

  try{
    const res = await fetch("verify_electricity.php", {
      method:"POST",
      headers: {"Content-Type":"application/json"},
      body: JSON.stringify({disco_name: disco, meter_number: meter})
    });
    const data = await res.json();

    if(data.ok){
      box.innerHTML = `✅ Verified: <b>${data.name}</b><br>${data.address ? data.address : ""}`;
      document.getElementById("payBtn").disabled = false;
    }else{
      box.innerHTML = "❌ " + (data.message || "Verification failed");
      document.getElementById("payBtn").disabled = true;
    }
  }catch(e){
    box.innerHTML = "❌ Verification error.";
    document.getElementById("payBtn").disabled = true;
  }
}
</script>

</body>
</html>