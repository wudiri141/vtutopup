<?php
session_start();
require_once "db.php";
require_once "config_vtu.php";

set_time_limit(300);
header("Content-Type: text/plain; charset=utf-8");

function vtu_post_json($url, $apiKey, $payload, $timeout = 30) {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
      "Authorization: Token {$apiKey}",
      "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => $timeout,
  ]);
  $raw = curl_exec($ch);
  $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err = curl_error($ch);
  curl_close($ch);

  $data = null;
  if ($raw !== false) $data = json_decode($raw, true);

  return ["ok"=>($err==='' && $http>=200 && $http<300), "raw"=>$raw, "data"=>$data, "http"=>$http, "err"=>$err];
}

$url = VTU_BASE_URL . "/api/listcabletvplans/";
$resp = vtu_post_json($url, VTU_API_KEY, new stdClass(), VTU_TIMEOUT);

if(!$resp["ok"]){
  echo "❌ API failed\nHTTP: {$resp['http']}\nERR: {$resp['err']}\nRAW:\n{$resp['raw']}";
  exit();
}

$data = $resp["data"];
if(!is_array($data)){
  echo "❌ Response is not JSON array/object.\nRAW:\n{$resp['raw']}";
  exit();
}

/**
 * Try to locate the plans list key in the response
 */
$plans = null;
$possibleKeys = ["cabletvplans", "CableTvPlans", "CableTVPlans", "plans", "data"];
foreach($possibleKeys as $k){
  if(isset($data[$k]) && is_array($data[$k])){
    $plans = $data[$k];
    break;
  }
}

if($plans === null){
  echo "❌ Could not find cable plans array in API response.\nAvailable keys: ".implode(", ", array_keys($data))."\nRAW:\n{$resp['raw']}";
  exit();
}

echo "✅ Found plans key, total: ".count($plans)."\n";

$stmt = $conn->prepare("
  INSERT INTO vtu_cabletvplans
    (cabletv_plan_id,the_cabletv_name,size,the_cabletv_type_name,
     price_for_basicuser,price_for_premiumuser,
     commission_for_basicuser,commission_for_premiumuser,
     duration,status)
  VALUES (?,?,?,?,?,?,?,?,?,?)
  ON DUPLICATE KEY UPDATE
    the_cabletv_name=VALUES(the_cabletv_name),
    size=VALUES(size),
    the_cabletv_type_name=VALUES(the_cabletv_type_name),
    price_for_basicuser=VALUES(price_for_basicuser),
    price_for_premiumuser=VALUES(price_for_premiumuser),
    commission_for_basicuser=VALUES(commission_for_basicuser),
    commission_for_premiumuser=VALUES(commission_for_premiumuser),
    duration=VALUES(duration),
    status=VALUES(status)
");

$count = 0;

foreach($plans as $p){
  $id = (int)($p["cabletv_plan_id"] ?? $p["id"] ?? 0);
  if($id <= 0) continue;

  $name = (string)($p["the_cabletv_name"] ?? $p["cable"] ?? "");
  $size = (string)($p["size"] ?? $p["name"] ?? "");
  $type = (string)($p["the_cabletv_type_name"] ?? "");
  $pb = (float)($p["price_for_basicuser"] ?? $p["basic_price"] ?? 0);
  $pp = (float)($p["price_for_premiumuser"] ?? $p["premium_price"] ?? 0);
  $cb = (float)($p["commission_for_basicuser"] ?? 0);
  $cp = (float)($p["commission_for_premiumuser"] ?? 0);
  $dur = (string)($p["duration"] ?? "");
  $st  = (string)($p["status"] ?? "On");

  $stmt->bind_param("isssddddss", $id,$name,$size,$type,$pb,$pp,$cb,$cp,$dur,$st);
  $stmt->execute();
  $count++;
}

echo "✅ Synced {$count} cable plans into vtu_cabletvplans\n";
