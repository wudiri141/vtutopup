<?php
session_start();
require_once "config_vtu.php";

header("Content-Type: application/json; charset=utf-8");

if(!isset($_SESSION['user_id'])){
  echo json_encode(["ok"=>false,"message"=>"Not logged in"]); exit();
}

$raw = file_get_contents("php://input");
$body = json_decode($raw, true);

$disco = trim($body['disco_name'] ?? '');
$meter = trim($body['meter_number'] ?? '');

if($disco === '' || $meter === ''){
  echo json_encode(["ok"=>false,"message"=>"Missing fields"]); exit();
}

function vtu_post($endpoint, $payload){
  $url = VTU_BASE_URL . $endpoint;
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
      "Authorization: Token " . VTU_API_KEY,
      "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => VTU_TIMEOUT
  ]);
  $res = curl_exec($ch);
  $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err = curl_error($ch);
  curl_close($ch);

  $data = json_decode($res, true);
  return ["ok"=>($err=='' && $http>=200 && $http<300), "data"=>$data, "raw"=>$res];
}

$resp = vtu_post("/api/billpayment/verify/", [
  "disco_name" => $disco,
  "meter_number" => $meter
]);

if(!$resp["ok"]){
  echo json_encode(["ok"=>false,"message"=>"Network/API error"]); exit();
}

$d = $resp["data"];
if(strtolower($d["status"] ?? "") !== "success"){
  echo json_encode(["ok"=>false,"message"=>$d["api_response"] ?? "Verification failed"]); exit();
}

echo json_encode([
  "ok"=>true,
  "name"=>$d["Customer_Name"] ?? ($d["name"] ?? "Verified"),
  "address"=>$d["Customer_Address"] ?? ""
]);
