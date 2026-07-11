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

$url = VTU_BASE_URL . "/api/listelectricity/";
$resp = vtu_post_json($url, VTU_API_KEY, new stdClass(), VTU_TIMEOUT);

if(!$resp["ok"]){
  echo "❌ API failed\nHTTP: {$resp['http']}\nERR: {$resp['err']}\nRAW:\n{$resp['raw']}";
  exit();
}

$data = $resp["data"];
if(!is_array($data)){
  echo "❌ Response is not JSON\nRAW:\n{$resp['raw']}";
  exit();
}

$plans = null;
$possibleKeys = ["electricityplanids","ElectricityPlanIds","plans","data"];
foreach($possibleKeys as $k){
  if(isset($data[$k]) && is_array($data[$k])){
    $plans = $data[$k];
    break;
  }
}

if($plans === null){
  echo "❌ Could not find electricity list in response.\nKeys: ".implode(", ", array_keys($data))."\nRAW:\n{$resp['raw']}";
  exit();
}

$stmt = $conn->prepare("
  INSERT INTO vtu_electricityplanids
    (electricity_plan_id, the_electricty_name, commission_for_basicuser, commission_for_premiumuser)
  VALUES (?,?,?,?)
  ON DUPLICATE KEY UPDATE
    the_electricty_name=VALUES(the_electricty_name),
    commission_for_basicuser=VALUES(commission_for_basicuser),
    commission_for_premiumuser=VALUES(commission_for_premiumuser)
");

$count = 0;
foreach($plans as $p){
  $id = (int)($p["electricity_plan_id"] ?? 0);
  if($id <= 0) continue;

  $name = (string)($p["the_electricty_name"] ?? "");
  $cb = (string)($p["commission_for_basicuser"] ?? "");
  $cp = (string)($p["commission_for_premiumuser"] ?? "");

  $stmt->bind_param("isss", $id, $name, $cb, $cp);
  $stmt->execute();
  $count++;
}

echo "✅ Synced {$count} electricity discos into vtu_electricityplanids\n";
