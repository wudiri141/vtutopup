<?php
session_start();
require_once "../db.php";

/**
 * ===== ADMIN PROTECTION =====
 * Use whatever you already store for admin:
 * - $_SESSION['role'] == 'admin'
 * - or $_SESSION['is_admin'] == 1
 */
if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php"); exit();
}

$role = $_SESSION['role'] ?? '';
$isAdmin = ($role === 'admin') || (($_SESSION['is_admin'] ?? 0) == 1);

if (!$isAdmin) {
  http_response_code(403);
  exit("Unauthorized");
}

/** ===== FILTERS ===== */
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

$status = strtolower(trim($_GET['status'] ?? 'all'));
$allowed = ['all','completed','pending','failed'];
if(!in_array($status, $allowed)) $status = 'all';

$q = trim($_GET['q'] ?? '');

/** ===== BUILD QUERY ===== */
$where = "1=1";
$params = [];
$types  = "";

if ($status !== 'all') {
  $where .= " AND t.status=?";
  $params[] = $status;
  $types .= "s";
}

if ($q !== '') {
  // Search user fullname + service + reference + description
  $where .= " AND (
      u.fullname LIKE ?
      OR t.service LIKE ?
      OR t.reference LIKE ?
      OR t.description LIKE ?
      OR t.type LIKE ?
  )";
  $like = "%{$q}%";
  $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
  $types .= "sssss";
}

$sql = "
  SELECT t.id, t.user_id, t.type, t.service, t.amount, t.status, t.reference, t.description, t.created_at,
         u.fullname
  FROM transactions t
  JOIN users u ON u.id = t.user_id
  WHERE $where
  ORDER BY t.id DESC
  LIMIT ? OFFSET ?
";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$rows = $res->fetch_all(MYSQLI_ASSOC);

/** ===== COUNT FOR PAGINATION ===== */
$countSql = "
  SELECT COUNT(*) as total
  FROM transactions t
  JOIN users u ON u.id = t.user_id
  WHERE $where
";
$countStmt = $conn->prepare($countSql);
if ($types !== "ii") {
  // reuse same params except limit/offset
  $countParams = array_slice($params, 0, count($params) - 2);
  $countTypes  = substr($types, 0, strlen($types) - 2);
  if($countTypes !== ''){
    $countStmt->bind_param($countTypes, ...$countParams);
  }
}
$countStmt->execute();
$total = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);

$hasPrev = $page > 1;
$hasNext = ($page * $limit) < $total;

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function badgeClass($st){
  $st = strtolower(trim((string)$st));
  if($st==='completed') return 'completed';
  if($st==='failed') return 'failed';
  return 'pending';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Admin – Transactions</title>
<style>
  :root{
    --bg:#f5f7fa; --card:#fff; --line:#e5e7eb; --soft:#f1f5f9;
    --text:#111827; --muted:#6b7280; --primary:#1E9BD7;
  }
  *{box-sizing:border-box;font-family:Inter,system-ui,Arial,sans-serif}
  body{margin:0;background:var(--bg);color:var(--text);padding:18px}
  .wrap{max-width:1200px;margin:0 auto}
  .top{
    background:var(--card);border:1px solid var(--line);border-radius:16px;
    padding:14px 16px;display:flex;justify-content:space-between;gap:12px;align-items:center;
  }
  h2{margin:0;font-size:18px;font-weight:950}
  .sub{color:var(--muted);font-weight:800;font-size:12px;margin-top:2px}
  .actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .btn{
    background:var(--primary);color:#fff;text-decoration:none;
    padding:10px 12px;border-radius:12px;font-weight:950;font-size:13px;display:inline-block
  }
  .btn2{
    background:#111827;color:#fff;text-decoration:none;
    padding:10px 12px;border-radius:12px;font-weight:950;font-size:13px;display:inline-block
  }
  .card{
    margin-top:12px;background:var(--card);border:1px solid var(--line);
    border-radius:16px;padding:14px
  }
  .filters{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .search{
    display:flex;gap:10px;align-items:center;background:#fff;border:1px solid var(--line);
    border-radius:12px;padding:10px 12px;flex:1;min-width:260px
  }
  .search input{border:none;outline:none;width:100%;font-weight:800}
  select{
    border:1px solid var(--line);border-radius:12px;padding:10px 12px;
    font-weight:850;background:#fff
  }
  table{width:100%;border-collapse:collapse;background:#fff;margin-top:12px}
  th,td{padding:10px;border-bottom:1px solid var(--line);font-size:13px;vertical-align:top}
  th{background:var(--soft);text-align:left;font-weight:950}
  .badge{padding:6px 10px;border-radius:999px;font-weight:950;font-size:12px;display:inline-block}
  .completed{background:#DCFCE7;color:#166534}
  .pending{background:#FEF9C3;color:#854d0e}
  .failed{background:#FEE2E2;color:#991b1b}
  .muted{color:var(--muted);font-weight:800;font-size:12px}
  .pager{display:flex;gap:10px;margin-top:12px}
  .pager a{
    text-decoration:none;border:1px solid var(--line);background:#fff;
    padding:10px 12px;border-radius:12px;font-weight:950;color:#111827
  }
</style>
</head>
<body>
<div class="wrap">

  <div class="top">
    <div>
      <h2>All Transactions</h2>
      <div class="sub">Showing <?= (int)count($rows) ?> of <?= (int)$total ?> (page <?= (int)$page ?>)</div>
    </div>

    <div class="actions">
      <a class="btn2" href="dashboard.php">Admin Dashboard</a>
      <a class="btn" href="transactions.php">Refresh</a>
    </div>
  </div>

  <div class="card">
    <form class="filters" method="get" action="">
      <div class="search">
        <span class="muted">Search:</span>
        <input name="q" value="<?= h($q) ?>" placeholder="Name, service, reference, description..." />
      </div>

      <select name="status">
        <option value="all" <?= $status==='all'?'selected':'' ?>>All</option>
        <option value="completed" <?= $status==='completed'?'selected':'' ?>>Completed</option>
        <option value="pending" <?= $status==='pending'?'selected':'' ?>>Pending</option>
        <option value="failed" <?= $status==='failed'?'selected':'' ?>>Failed</option>
      </select>

      <button class="btn" type="submit">Apply</button>
    </form>

    <table>
      <tr>
        <th>ID</th>
        <th>User</th>
        <th>Service</th>
        <th>Amount</th>
        <th>Type</th>
        <th>Status</th>
        <th>Reference</th>
        <th>Date</th>
      </tr>

      <?php if(empty($rows)): ?>
        <tr><td colspan="8" class="muted">No transactions found.</td></tr>
      <?php else: ?>
        <?php foreach($rows as $t): ?>
          <tr>
            <td><?= (int)$t['id'] ?></td>
            <td>
              <div style="font-weight:950;"><?= h($t['fullname']) ?></div>
              <div class="muted">User ID: <?= (int)$t['user_id'] ?></div>
            </td>
            <td>
              <div style="font-weight:950;"><?= h($t['service']) ?></div>
              <?php if(!empty($t['description'])): ?>
                <div class="muted"><?= h($t['description']) ?></div>
              <?php endif; ?>
            </td>
            <td style="font-weight:950;">₦<?= number_format((float)$t['amount'],2) ?></td>
            <td><?= h($t['type']) ?></td>
            <td>
              <span class="badge <?= badgeClass($t['status']) ?>"><?= h(ucfirst($t['status'])) ?></span>
            </td>
            <td><?= h($t['reference']) ?></td>
            <td class="muted"><?= h($t['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </table>

    <div class="pager">
      <?php if($hasPrev): ?>
        <a href="?page=<?= $page-1 ?>&status=<?= h($status) ?>&q=<?= urlencode($q) ?>">← Previous</a>
      <?php endif; ?>
      <?php if($hasNext): ?>
        <a href="?page=<?= $page+1 ?>&status=<?= h($status) ?>&q=<?= urlencode($q) ?>">Next →</a>
      <?php endif; ?>
    </div>
  </div>

</div>
</body>
</html>
