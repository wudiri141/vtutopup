<?php
session_start();
require_once "db.php";

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

    $msg = "✅ Cable subscription successful! Ref: {$refSafe}<br>
            <a href='" . htmlspecialchars($receiptLink, ENT_QUOTES, 'UTF-8') . "'>View Receipt</a>
            &nbsp;|&nbsp;
            <a href='" . htmlspecialchars($pdfLink, ENT_QUOTES, 'UTF-8') . "'>Download PDF</a>";
  } else {
    $msg = "✅ Cable subscription successful! Ref: {$refSafe}";
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cable Subscription</title>
  <link rel="stylesheet" href="optionstyles.css">
  <style>
    .hint{font-size:12px;color:#64748b;font-weight:700;margin-top:6px}
  </style>
</head>
<body>

<div class="airtime-card">
  <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

  <div class="page-header">
    <h2>Cable Subscription</h2>
    <p class="wallet-balance">Wallet Balance: ₦<?= number_format($wallet,2) ?></p>
  </div>

  <?php if(!$pinSet): ?>
    <p style="background:#fff7ed;border:1px solid #fed7aa;padding:10px;border-radius:10px;color:#7c2d12;">
      Set Transaction PIN first: <a href="account.php"><b>Go to Account</b></a>
    </p>
  <?php endif; ?>

  <?php if($msg): ?>
    <div style="margin-bottom:12px; padding:10px; border-radius:8px; background:#f1f5f9;">
      <?= $msg ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="process_cable_subscription.php" id="cableForm">

    <label for="cablename">Select Cable Provider</label>
    <select name="cablename" id="cablename" required onchange="loadCablePlans(); clearVerify();">
      <option value="">-- Select Provider --</option>
      <option value="1">GOTV</option>
      <option value="2">DSTV</option>
      <option value="3">STARTIMES</option>
      <option value="4">SHOWMAX</option>
    </select>

    <label for="smart_card_number">IUC/ICU/Phone Number</label>
    <input type="text" name="smart_card_number" id="smart_card_number" required oninput="clearVerify()">

    <div style="display:flex; gap:10px; margin-top:10px;">
      <button type="button" onclick="verifyCable()" style="flex:1;">Verify Number</button>
      <button type="submit" id="buyBtn" style="flex:1;" <?= !$pinSet ? 'disabled style="opacity:.6;cursor:not-allowed;"' : 'disabled' ?>>Subscribe Cable →</button>
    </div>

    <div id="verifyBox" style="margin-top:12px; padding:10px; border-radius:10px; background:#f1f5f9; display:none;"></div>

    <label for="cableplan" style="margin-top:14px;">Select Plan</label>
    <select name="cableplan" id="cableplan" required>
      <option value="">-- Select Plan --</option>
    </select>

  </form>
</div>

<script>
async function loadCablePlans(){
  const cablename = document.getElementById("cablename").value;
  const planSelect = document.getElementById("cableplan");
  planSelect.innerHTML = '<option value="">Loading plans...</option>';

  if(!cablename){
    planSelect.innerHTML = '<option value="">-- Select Plan --</option>';
    return;
  }

  try{
    const res = await fetch("fetch_cable_plans.php?cablename=" + encodeURIComponent(cablename));
    const data = await res.json();

    planSelect.innerHTML = '<option value="">-- Select Plan --</option>';

    if(data.ok && Array.isArray(data.plans)){
      data.plans.forEach(p => {
        const opt = document.createElement("option");
        opt.value = p.id;
        opt.textContent = `${p.name} - ₦${p.price}`;
        planSelect.appendChild(opt);
      });
    }else{
      planSelect.innerHTML = '<option value="">No plans found</option>';
    }
  }catch(e){
    planSelect.innerHTML = '<option value="">Error loading plans</option>';
  }
}
</script>

<?php include "pin_modal.php"; ?>
<script>
PinModal.bind('#cableForm', { title:'Cable Subscription', sub:'Enter PIN to confirm cable subscription.' });

function clearVerify(){
  document.getElementById("verifyBox").style.display = "none";
  document.getElementById("verifyBox").innerHTML = "";
  document.getElementById("buyBtn").disabled = true;
}

async function verifyCable(){
  const cablename = document.getElementById("cablename").value;
  const smart = document.getElementById("smart_card_number").value.trim();
  const box = document.getElementById("verifyBox");

  if(!cablename || !smart){
    box.style.display = "block";
    box.innerHTML = "❌ Select provider and enter smartcard/IUC/ICU/phone.";
    return;
  }

  box.style.display = "block";
  box.innerHTML = "⏳ Verifying...";

  try{
    const res = await fetch("verify_cable.php", {
      method: "POST",
      headers: {"Content-Type":"application/json"},
      body: JSON.stringify({cablename, smart_card_number: smart})
    });
    const data = await res.json();

    if(data.ok){
      box.innerHTML = `✅ Verified: <b>${data.name}</b>`;
      document.getElementById("buyBtn").disabled = false;
    }else{
      box.innerHTML = "❌ Verification failed: " + (data.message || "Try again");
      document.getElementById("buyBtn").disabled = true;
    }
  }catch(e){
    box.innerHTML = "❌ Verification error. Check server.";
    document.getElementById("buyBtn").disabled = true;
  }
}
</script>
</body>
</html>