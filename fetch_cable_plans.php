<?php
session_start();
require_once "db.php";

header("Content-Type: application/json; charset=utf-8");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
  // If you want to allow loading even when not logged in, comment this out.
  if (!isset($_SESSION['user_id'])) {
    echo json_encode(["ok"=>false, "error"=>"not_logged_in", "plans"=>[]]);
    exit();
  }

  $cablename = trim($_GET['cablename'] ?? '');
  $map = ["1"=>"GOTV","2"=>"DSTV","3"=>"STARTIMES","4"=>"SHOWMAX"];

  if (!isset($map[$cablename])) {
    echo json_encode(["ok"=>false, "error"=>"invalid_cablename", "plans"=>[]]);
    exit();
  }

  $name = $map[$cablename];

$stmt = $conn->prepare("
  SELECT cabletv_plan_id, size, price_for_basicuser
  FROM vtu_cabletvplans
  WHERE UPPER(the_cabletv_name) = UPPER(?)
  ORDER BY cabletv_plan_id ASC
");

  $stmt->bind_param("s", $name);
  $stmt->execute();
  $res = $stmt->get_result();

  $plans = [];
  while ($r = $res->fetch_assoc()) {
    $plans[] = [
      "id" => (int)$r["cabletv_plan_id"],
      "name" => (string)$r["size"],
      "price" => number_format((float)$r["price_for_basicuser"], 2)
    ];
  }

  echo json_encode(["ok"=>true, "plans"=>$plans]);
} catch (Throwable $e) {
  echo json_encode([
    "ok"=>false,
    "error"=>"server_error",
    "message"=>$e->getMessage(),
    "plans"=>[]
  ]);
}
