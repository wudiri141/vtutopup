<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT fullname, wallet FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$wallet = (float)($user['wallet'] ?? 0);

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
    $receiptLink = "../receipt.php?src=vtu&id=" . $receiptId;
    $pdfLink = "../receipt_pdf.php?src=vtu&id=" . $receiptId;

    $msg = "✅ Data purchase successful! Ref: {$refSafe}<br>
            <a href='" . htmlspecialchars($receiptLink, ENT_QUOTES, 'UTF-8') . "'>View Receipt</a>
            &nbsp;|&nbsp;
            <a href='" . htmlspecialchars($pdfLink, ENT_QUOTES, 'UTF-8') . "'>Download PDF</a>";
  } else {
    $msg = "✅ Data purchase successful! Ref: {$refSafe}";
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
  <title>Buy Data</title>
  <link rel="stylesheet" href="../optionstyles.css">
</head>
<body>

<div class="airtime-card">
  <a href="dashboard.php" class="back-link">Back to Dashboard</a>

  <div class="page-header">
    <h2>Buy Data</h2>
    <p class="wallet-balance">Wallet Balance: ₦<?= number_format($wallet, 2) ?></p>
  </div>

  <?php if($msg): ?>
    <div style="margin-bottom:12px; padding:10px; border-radius:8px; background:#f1f5f9;">
      <?= $msg ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="process_data_subscription.php" id="dataForm">
    <label for="network">Select Network</label>
    <select name="network" id="network" required>
      <option value="">-- Select Network --</option>
      <option value="MTN">MTN</option>
      <option value="AIRTEL">AIRTEL</option>
      <option value="GLO">GLO</option>
      <option value="9MOBILE">9MOBILE</option>
    </select>

    <label for="datatype">Select Data Type</label>
    <select name="datatype" id="datatype" required disabled>
      <option value="">-- Select Data Type --</option>
    </select>

    <label for="plan_id">Select Plan</label>
    <select name="plan_id" id="plan_id" required>
      <option value="">-- Select Plan --</option>
    </select>

    <label for="phone">Phone Number</label>
    <input type="text" name="phone" id="phone" placeholder="080xxxxxxxx" required>

    <div id="priceBox" style="margin:10px 0; padding:10px; border-radius:8px; background:#f8fafc; display:none;">
      <div><b>Amount:</b> ₦<span id="sellPrice">0</span></div>
      <div style="font-size:12px; opacity:.8;">This is the basic-user selling price charged from the wallet.</div>
    </div>

    <button type="submit">Purchase Data →</button>
  </form>
</div>

<script>
let allPlans = [];

async function loadPlans(network) {
  const planSelect = document.getElementById("plan_id");
  const typeSelect = document.getElementById("datatype");
  const box = document.getElementById("priceBox");
  allPlans = [];
  typeSelect.innerHTML = '<option value="">-- Select Data Type --</option>';
  planSelect.innerHTML = '<option value="">-- Select Plan --</option>';
  typeSelect.disabled = true;
  planSelect.disabled = true;
  box.style.display = "none";

  if (!network) return;

  const res = await fetch("../plans_api.php?network=" + encodeURIComponent(network));
  const data = await res.json();
  allPlans = data.plans || [];

  (data.types || []).forEach(t => {
    const opt = document.createElement("option");
    opt.value = t;
    opt.textContent = t;
    typeSelect.appendChild(opt);
  });

  typeSelect.disabled = (data.types || []).length === 0;
}

function renderPlans(datatype) {
  const planSelect = document.getElementById("plan_id");
  const box = document.getElementById("priceBox");
  planSelect.innerHTML = '<option value="">-- Select Plan --</option>';
  box.style.display = "none";

  if (!datatype) {
    planSelect.disabled = true;
    return;
  }

  allPlans.filter(p => p.datatype === datatype).forEach(p => {
    const opt = document.createElement("option");
    opt.value = p.data_plan_id;
    opt.textContent = `${p.size} - ₦${p.sell_price} / ${p.validity_days} days`;
    opt.setAttribute("data-sell", p.sell_price);
    planSelect.appendChild(opt);
  });

  planSelect.disabled = false;
}

document.getElementById("network").addEventListener("change", function() {
  loadPlans(this.value);
});

document.getElementById("datatype").addEventListener("change", function() {
  renderPlans(this.value);
});

document.getElementById("plan_id").addEventListener("change", function() {
  const opt = this.options[this.selectedIndex];
  const sell = opt ? opt.getAttribute("data-sell") : null;
  const box = document.getElementById("priceBox");
  if (sell) {
    document.getElementById("sellPrice").textContent = sell;
    box.style.display = "block";
  } else {
    box.style.display = "none";
  }
});
</script>

<?php include "../pin_modal.php"; ?>
<script>
  PinModal.bind('#dataForm', { title:'Data Purchase', sub:'Enter PIN to buy data.' });
</script>

</body>
</html>
