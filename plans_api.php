<?php
session_start();
include "db.php";

$network = strtoupper(trim($_GET['network'] ?? ''));
$datatype = trim($_GET['datatype'] ?? '');
$allowed = ['MTN','AIRTEL','GLO','9MOBILE'];

if (!in_array($network, $allowed, true)) {
    header("Content-Type: application/json");
    echo json_encode(["types"=>[], "plans"=>[]]);
    exit();
}

$typeStmt = $conn->prepare("
    SELECT datatype
    FROM vtu_dataplans
    WHERE status='On' AND network=? AND datatype<>''
    GROUP BY datatype
    ORDER BY MIN(data_plan_id) ASC
");
$typeStmt->bind_param("s", $network);
$typeStmt->execute();
$typeRes = $typeStmt->get_result();
$types = [];
while ($row = $typeRes->fetch_assoc()) {
    $types[] = $row['datatype'];
}
$typeStmt->close();

if ($datatype !== '') {
    $stmt = $conn->prepare("
        SELECT data_plan_id, size, datatype, validity_days, sell_price
        FROM vtu_dataplans
        WHERE status='On' AND network=? AND datatype=?
        ORDER BY sell_price ASC
    ");
    $stmt->bind_param("ss", $network, $datatype);
} else {
    $stmt = $conn->prepare("
        SELECT data_plan_id, size, datatype, validity_days, sell_price
        FROM vtu_dataplans
        WHERE status='On' AND network=?
        ORDER BY data_plan_id ASC
    ");
    $stmt->bind_param("s", $network);
}
$stmt->execute();
$res = $stmt->get_result();

$plans = [];
while ($row = $res->fetch_assoc()) {
    $row['sell_price'] = number_format((float)$row['sell_price'], 2, '.', '');
    $plans[] = $row;
}

header("Content-Type: application/json");
echo json_encode(["types"=>$types, "plans"=>$plans]);
