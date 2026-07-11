<?php
session_start();
include "../db.php";

/* ===== ADMIN ONLY ===== */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
  die("Access denied");
}

/* ===== SEARCH ===== */
$search = trim($_GET['q'] ?? '');

$sql = "
  SELECT id, fullname, email, phone, wallet, role
  FROM users
";

if ($search !== '') {
  $sql .= " WHERE fullname LIKE ? OR email LIKE ? OR phone LIKE ? ";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);

if ($search !== '') {
  $like = "%$search%";
  $stmt->bind_param("sss", $like, $like, $like);
}

$stmt->execute();
$users = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin • Users</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body{font-family:Inter,Arial;background:#f5f7fa;padding:20px}
.header{
  display:flex;justify-content:space-between;align-items:center;
  margin-bottom:14px
}
.header h2{margin:0}
.card{
  background:#fff;padding:16px;border-radius:16px;
  box-shadow:0 10px 25px rgba(0,0,0,.08)
}
.search{
  display:flex;gap:10px;margin-bottom:12px
}
input{
  padding:10px;border-radius:10px;border:1px solid #ddd;
  width:100%
}
button{
  padding:10px 14px;border:none;border-radius:10px;
  background:#1E9BD7;color:#fff;font-weight:800;cursor:pointer
}
table{width:100%;border-collapse:collapse}
th,td{
  padding:10px;border-bottom:1px solid #eee;
  font-size:13px;text-align:left
}
th{background:#f1f5f9;font-weight:900}
.badge{
  padding:6px 10px;border-radius:999px;
  font-size:12px;font-weight:800
}
.admin{background:#dcfce7;color:#166534}
.user{background:#e5e7eb;color:#374151}
.actions a{
  text-decoration:none;margin-right:6px;
  font-size:12px;font-weight:800;
  padding:6px 10px;border-radius:8px
}
.credit{background:#e0f2fe;color:#0369a1}
.view{background:#ede9fe;color:#5b21b6}
.rolebtn{background:#fff3cd;color:#92400e}
.back{
  display:inline-block;margin-bottom:12px;
  text-decoration:none;font-weight:800;color:#1E9BD7
}
@media(max-width:900px){
  table{font-size:12px}
}
</style>
</head>
<body>

<a class="back" href="dashboard.php">← Admin Dashboard</a>

<div class="header">
  <h2>Users</h2>
</div>

<div class="card">
  <form class="search" method="GET">
    <input type="text" name="q" placeholder="Search name, email or phone" value="<?= htmlspecialchars($search) ?>">
    <button>Search</button>
  </form>

  <div style="overflow:auto;">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Full Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Wallet</th>
          <th>Role</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while($u = $users->fetch_assoc()): ?>
          <tr>
            <td><?= (int)$u['id'] ?></td>
            <td><?= htmlspecialchars($u['fullname']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['phone']) ?></td>
            <td>₦<?= number_format($u['wallet'],2) ?></td>
            <td>
              <span class="badge <?= $u['role']==='admin'?'admin':'user' ?>">
                <?= ucfirst($u['role']) ?>
              </span>
            </td>
            <td class="actions">
              <a class="credit" href="fund_wallet.php?identity=<?= urlencode($u['email']) ?>">
                Wallet
              </a>

              <a class="view" href="../transactions.php?user=<?= (int)$u['id'] ?>">
                Transactions
              </a>

              <?php if ($u['role'] !== 'admin'): ?>
                <a class="rolebtn" href="toggle_role.php?id=<?= (int)$u['id'] ?>&role=admin">
                  Make Admin
                </a>
              <?php else: ?>
                <a class="rolebtn" href="toggle_role.php?id=<?= (int)$u['id'] ?>&role=user">
                  Remove Admin
                </a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
