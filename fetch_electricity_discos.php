<?php
session_start();
require_once "db.php";

header("Content-Type: application/json; charset=utf-8");

try{
  if (!isset($_SESSION['user_id'])) {
    echo json_encode(["ok"=>false,"message"=>"not_logged_in","discos"=>[]]); exit();
  }

  // If your table name differs, change it here
  $sql = "SELECT electricity_plan_id, the_electricty_name FROM vtu_electricityplanids ORDER BY electricity_plan_id ASC";
  $res = $conn->query($sql);

  $discos = [];
  while($r = $res->fetch_assoc()){
    $discos[] = [
      "id" => (int)$r["electricity_plan_id"],
      "name" => (string)$r["the_electricty_name"]
    ];
  }

  echo json_encode(["ok"=>true,"discos"=>$discos]);
}catch(Throwable $e){
  echo json_encode(["ok"=>false,"message"=>"server_error","error"=>$e->getMessage(),"discos"=>[]]);
}
