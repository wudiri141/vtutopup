<?php
// ============================================================
// FILE: public_html/api/services.php
// URL:  https://vtutopup.com.ng/api/services.php
// ============================================================
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once "../db.php";
require_once "../auth_helper.php";
require_once "../config_vtu.php";
require_once "../send_verification_email.php";

ensureAuthSchema($conn);

function out($ok, $msg, $extra = []) {
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
    exit();
}

function authUser($conn) {
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(\S+)$/i', $h, $m)) return null;
    $stmt = $conn->prepare("SELECT id,fullname,email,phone,wallet,transaction_pin FROM users WHERE api_token=? LIMIT 1");
    $stmt->bind_param("s", $m[1]);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $u;
}

function vtuPost($url, $key, $payload, $timeout = 30) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["Authorization: Token $key","Content-Type: application/json"],
        CURLOPT_POSTFIELDS => json_encode($payload), CURLOPT_TIMEOUT => $timeout,
    ]);
    $raw = curl_exec($ch); $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch); curl_close($ch);
    return ['ok'=>($err===''&&$http>=200&&$http<300),'http_code'=>$http,'error'=>$err?:null,'raw'=>$raw,'data'=>($raw!==false)?json_decode($raw,true):null];
}

$user = authUser($conn);
if (!$user) out(false, "Unauthorized. Please login again.");

$uid    = (int)$user['id'];
$action = trim($_GET['action'] ?? '');
$body   = json_decode(file_get_contents("php://input"), true) ?? [];
$NET_MAP = ['MTN'=>1,'GLO'=>2,'9MOBILE'=>3,'AIRTEL'=>4];
$NET_STR = ['MTN'=>'1','GLO'=>'2','9MOBILE'=>'3','AIRTEL'=>'4'];

// ── DATA PLANS ─────────────────────────────────────────────
if ($action === 'data_plans') {
    $network = strtoupper(trim($_GET['network'] ?? ''));
    if (!isset($NET_MAP[$network])) out(false, "Invalid network.");
    $datatype = trim($_GET['datatype'] ?? '');

    $t = $conn->prepare("SELECT datatype FROM vtu_dataplans WHERE status='On' AND network=? AND datatype<>'' GROUP BY datatype ORDER BY MIN(data_plan_id) ASC");
    $t->bind_param("s",$network); $t->execute();
    $types = array_map(function($r) { return $r['datatype']; }, $t->get_result()->fetch_all(MYSQLI_ASSOC));
    $t->close();

    if ($datatype !== '') {
        $s = $conn->prepare("SELECT data_plan_id,size,datatype,validity_days,sell_price FROM vtu_dataplans WHERE status='On' AND network=? AND datatype=? ORDER BY sell_price ASC");
        $s->bind_param("ss",$network,$datatype);
    } else {
        $s = $conn->prepare("SELECT data_plan_id,size,datatype,validity_days,sell_price FROM vtu_dataplans WHERE status='On' AND network=? ORDER BY data_plan_id ASC");
        $s->bind_param("s",$network);
    }
    $s->execute();
    out(true, "OK", ['types' => $types, 'plans' => $s->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

// ── ELECTRICITY DISCOS ─────────────────────────────────────
if ($action === 'electricity_discos') {
    $res    = $conn->query("SELECT electricity_plan_id,the_electricty_name FROM vtu_electricityplanids ORDER BY electricity_plan_id ASC");
    $discos = [];
    while ($r = $res->fetch_assoc()) $discos[] = $r;
    out(true, "OK", ['discos' => $discos]);
}

// ── VERIFY METER ───────────────────────────────────────────
if ($action === 'verify_meter') {
    $disco = trim($body['disco_name'] ?? '');
    $meter = trim($body['meter_number'] ?? '');
    if (!$disco || !$meter) out(false, "Select disco and enter meter number.");
    $res = vtuPost(VTU_BASE_URL."/api/billpayment/verify/", VTU_API_KEY, ["disco_name"=>$disco,"meter_number"=>$meter], VTU_TIMEOUT);
    if (!$res['ok']) out(false, "Verification network error.");
    $d = $res['data'] ?? [];
    if (strtolower($d['status'] ?? '') !== 'success') out(false, $d['api_response'] ?? "Verification failed.");
    out(true, "Meter verified.", ['customer_name'=>$d['Customer_Name']??$d['name']??'Verified','customer_address'=>$d['Customer_Address']??'']);
}

// ── BUY ELECTRICITY ────────────────────────────────────────
if ($action === 'buy_electricity') {
    $disco     = trim($body['disco_name'] ?? '');
    $meter     = preg_replace('/\D+/', '', trim($body['meter_number'] ?? ''));
    $meterType = in_array(strtolower($body['meter_type']??''), ['prepaid','postpaid']) ? strtolower($body['meter_type']) : 'prepaid';
    $amount    = (float)($body['amount'] ?? 0);
    $pin       = trim($body['pin'] ?? '');

    if (!preg_match('/^\d+$/', $disco))     out(false, "Invalid disco.");
    if (strlen($meter) < 6)                 out(false, "Invalid meter number.");
    if ($amount < 100)                      out(false, "Minimum is ₦100.");
    if (!preg_match('/^\d{4}$/', $pin))     out(false, "Invalid PIN.");

    try {
        $conn->begin_transaction();
        $s = $conn->prepare("SELECT wallet,transaction_pin,fullname,email FROM users WHERE id=? FOR UPDATE");
        $s->bind_param("i",$uid); $s->execute(); $u = $s->get_result()->fetch_assoc(); $s->close();

        if (!$u)                                           { $conn->rollback(); out(false,"User not found."); }
        if (empty($u['transaction_pin']))                  { $conn->rollback(); out(false,"Set a transaction PIN first."); }
        if (!password_verify($pin,$u['transaction_pin']))  { $conn->rollback(); out(false,"Incorrect transaction PIN."); }
        $wallet = (float)$u['wallet'];
        if ($wallet < $amount) { $conn->rollback(); out(false,"Insufficient balance. Wallet: ₦".number_format($wallet,2)); }

        $ref  = "ELEC_".date("YmdHis")."_".rand(100,999);
        $api  = vtuPost(VTU_BASE_URL."/api/billpayment/", VTU_API_KEY, ["disco_name"=>$disco,"meter_number"=>$meter,"MeterType"=>$meterType,"amount"=>(string)$amount], VTU_TIMEOUT);
        $d    = $api['data'] ?? [];
        $st   = strtolower((string)($d['status']??$d['Status']??''));
        $msg  = (string)($d['api_response']??$d['message']??'No response');
        $tok  = (string)($d['token']??$d['electricitytoken']??'');
        $meta = json_encode(['disco'=>$disco,'meter'=>$meter,'type'=>$meterType,'amount'=>$amount,'api'=>$d]);

        if (!$api['ok'] || ($st!=='success' && $st!=='successful')) {
            $fail = $api['ok'] ? $msg : "HTTP ".$api['http_code'];
            $ins  = $conn->prepare("INSERT INTO transactions (user_id,type,service,amount,status,reference,description,meta,created_at) VALUES (?,'debit','electricity',?,'failed',?,?,?,NOW())");
            $ins->bind_param("idsss",$uid,$amount,$ref,$fail,$meta); $ins->execute(); $ins->close();
            $conn->commit(); out(false,"Electricity failed: ".$fail);
        }

        $new = $wallet - $amount;
        $s   = $conn->prepare("UPDATE users SET wallet=? WHERE id=?"); $s->bind_param("di",$new,$uid); $s->execute(); $s->close();
        $desc = $msg.($tok?" | Token: $tok":"");
        $ins  = $conn->prepare("INSERT INTO transactions (user_id,type,service,amount,status,reference,description,meta,created_at) VALUES (?,'debit','electricity',?,'completed',?,?,?,NOW())");
        $ins->bind_param("idsss",$uid,$amount,$ref,$desc,$meta); $ins->execute(); $ins->close();

        sendTransactionEmail($u['email'],$u['fullname'],"Electricity Payment",$amount,$ref);
        $conn->commit();
        out(true,"Electricity payment successful!".($tok?" Token: $tok":""),['ref'=>$ref,'token'=>$tok,'new_wallet'=>$new]);
    } catch (Throwable $e) { $conn->rollback(); out(false,"Server error."); }
}

// ── CABLE PLANS ────────────────────────────────────────────
if ($action === 'cable_plans') {
    $cablename = trim($_GET['cablename'] ?? '');
    $map = ["1"=>"GOTV","2"=>"DSTV","3"=>"STARTIMES","4"=>"SHOWMAX"];
    if (!isset($map[$cablename])) out(false,"Invalid cable provider.");
    $name = $map[$cablename];
    $s = $conn->prepare("SELECT cabletv_plan_id,size,price_for_basicuser FROM vtu_cabletvplans WHERE UPPER(the_cabletv_name)=UPPER(?) ORDER BY cabletv_plan_id ASC");
    $s->bind_param("s",$name); $s->execute();
    $rows  = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();
    $plans = array_map(function($r) {
        return ['id'=>(int)$r['cabletv_plan_id'],'name'=>$r['size'],'price'=>(float)$r['price_for_basicuser']];
    }, $rows);
    out(true,"OK",['plans'=>$plans]);
}

// ── VERIFY SMARTCARD ───────────────────────────────────────
if ($action === 'verify_smartcard') {
    $cablename = trim($body['cablename'] ?? '');
    $smart     = trim($body['smart_card_number'] ?? '');
    if (!$cablename || !$smart) out(false,"Missing fields.");
    $res = vtuPost(VTU_BASE_URL."/api/cablesub/verify/", VTU_API_KEY, ["cablename"=>$cablename,"smart_card_number"=>$smart], VTU_TIMEOUT);
    if (!$res['ok']) out(false,"Verification network error.");
    $d = $res['data'] ?? [];
    if (strtolower($d['status']??'') !== 'success') out(false,$d['api_response']??"Verification failed.");
    out(true,"Smartcard verified.",['customer_name'=>$d['Customer_Name']??$d['name']??'Verified']);
}

// ── BUY CABLE ──────────────────────────────────────────────
if ($action === 'buy_cable') {
    $cablename = trim($body['cablename'] ?? '');
    $smartcard = preg_replace('/\D+/','',trim($body['smart_card_number']??''));
    $cableplan = trim($body['cableplan'] ?? '');
    $pin       = trim($body['pin'] ?? '');

    if (!in_array($cablename,["1","2","3","4"],true)) out(false,"Invalid cable provider.");
    if (strlen($smartcard) < 6)                       out(false,"Invalid smartcard number.");
    if (!preg_match('/^\d+$/', $cableplan))           out(false,"Invalid plan.");
    if (!preg_match('/^\d{4}$/', $pin))               out(false,"Invalid PIN.");

    try {
        $conn->begin_transaction();
        $s = $conn->prepare("SELECT wallet,transaction_pin,fullname,email FROM users WHERE id=? FOR UPDATE");
        $s->bind_param("i",$uid); $s->execute(); $u = $s->get_result()->fetch_assoc(); $s->close();

        if (!$u)                                           { $conn->rollback(); out(false,"User not found."); }
        if (empty($u['transaction_pin']))                  { $conn->rollback(); out(false,"Set a transaction PIN first."); }
        if (!password_verify($pin,$u['transaction_pin']))  { $conn->rollback(); out(false,"Incorrect transaction PIN."); }

        $sp = $conn->prepare("SELECT price_for_basicuser,size FROM vtu_cabletvplans WHERE cabletv_plan_id=? LIMIT 1");
        $sp->bind_param("i",$cableplan); $sp->execute();
        $plan = $sp->get_result()->fetch_assoc(); $sp->close();
        if (!$plan) { $conn->rollback(); out(false,"Plan not found."); }

        $amount = (float)$plan['price_for_basicuser'];
        $wallet = (float)$u['wallet'];
        if ($wallet < $amount) { $conn->rollback(); out(false,"Insufficient balance. Wallet: ₦".number_format($wallet,2).". Plan: ₦".number_format($amount,2)); }

        $ref = "CABLE_".date("YmdHis")."_".rand(100,999);
        $api = vtuPost(VTU_BASE_URL."/api/cablesub/", VTU_API_KEY, ["cablename"=>$cablename,"smart_card_number"=>$smartcard,"cableplan"=>$cableplan], VTU_TIMEOUT);
        $d   = $api['data'] ?? [];
        $st  = strtolower((string)($d['status']??$d['Status']??''));
        $msg = (string)($d['api_response']??$d['message']??'No response');

        if (!$api['ok'] || ($st!=='success' && $st!=='successful')) {
            $conn->rollback(); out(false,"Cable failed: ".($api['ok']?$msg:"HTTP ".$api['http_code']));
        }

        $new     = $wallet - $amount;
        $network = ["1"=>"GOTV","2"=>"DSTV","3"=>"STARTIMES","4"=>"SHOWMAX"][$cablename];
        $s = $conn->prepare("UPDATE users SET wallet=? WHERE id=?"); $s->bind_param("di",$new,$uid); $s->execute(); $s->close();
        $s = $conn->prepare("INSERT INTO vtu_transactions (user_id,service,provider,ref,network,phone,plan_id,amount_sell,amount_cost,profit,status,provider_message,created_at) VALUES (?,'cable','vtunaija',?,?,?,?,?,?,0,'success',?,NOW())");
        $s->bind_param("issssdds",$uid,$ref,$network,$smartcard,$cableplan,$amount,$amount,$msg); $s->execute(); $s->close();

        sendTransactionEmail($u['email'],$u['fullname'],"Cable Subscription",$amount,$ref);
        $conn->commit();
        out(true,"Cable subscription successful!",['ref'=>$ref,'new_wallet'=>$new]);
    } catch (Throwable $e) { $conn->rollback(); out(false,"Server error."); }
}

// ── BUY AIRTIME ────────────────────────────────────────────
if ($action === 'buy_airtime') {
    $network = strtoupper(trim($body['network']??''));
    $phone   = preg_replace('/\D+/','',trim($body['phone']??''));
    $amount  = (float)($body['amount']??0);
    $pin     = trim($body['pin']??'');
    if (!isset($NET_MAP[$network]))     out(false,"Invalid network.");
    if (!preg_match('/^\d{4}$/',$pin))  out(false,"Invalid PIN.");
    if (strlen($phone)<10)              out(false,"Invalid phone number.");
    if ($amount<50)                     out(false,"Minimum is ₦50.");
    try {
        $conn->begin_transaction();
        $s = $conn->prepare("SELECT wallet,transaction_pin FROM users WHERE id=? FOR UPDATE");
        $s->bind_param("i",$uid); $s->execute(); $u=$s->get_result()->fetch_assoc(); $s->close();
        if (!$u||empty($u['transaction_pin'])) { $conn->rollback(); out(false,"User error."); }
        if (!password_verify($pin,$u['transaction_pin'])) { $conn->rollback(); out(false,"Incorrect PIN."); }
        $wallet=(float)$u['wallet'];
        if ($wallet<$amount) { $conn->rollback(); out(false,"Insufficient balance."); }
        $ref="AIR_".date("YmdHis")."_".rand(100,999);
        $s=$conn->prepare("INSERT INTO vtu_transactions (user_id,service,provider,ref,network,phone,plan_id,amount_sell,amount_cost,profit,status,provider_message,created_at) VALUES (?,'airtime','vtunaija',?,?,?,NULL,?,?,0,'pending','',NOW())");
        $s->bind_param("isssddd",$uid,$ref,$network,$phone,$amount,$amount); $s->execute(); $s->close();
        $res=vtuPost(VTU_BASE_URL."/api/topup/",VTU_API_KEY,["network"=>(string)$NET_MAP[$network],"mobile_number"=>$phone,"Ported_number"=>"true","request-id"=>$ref,"amount"=>(string)$amount,"airtime_type"=>"VTU"],VTU_TIMEOUT);
        $d=$res['data']??[]; $st=strtolower((string)($d['status']??$d['Status']??'')); $msg=(string)($d['api_response']??$d['message']??'No response');
        if (!$res['ok']||($st!=='success'&&$st!=='successful')) {
            $fail=$res['ok']?$msg:"HTTP ".$res['http_code'];
            $s=$conn->prepare("UPDATE vtu_transactions SET status='failed',provider_message=? WHERE ref=? LIMIT 1"); $s->bind_param("ss",$fail,$ref); $s->execute(); $s->close();
            $conn->commit(); out(false,"Airtime failed: ".$fail);
        }
        $new=$wallet-$amount;
        $s=$conn->prepare("UPDATE users SET wallet=? WHERE id=?"); $s->bind_param("di",$new,$uid); $s->execute(); $s->close();
        $s=$conn->prepare("UPDATE vtu_transactions SET status='success',provider_message=? WHERE ref=? LIMIT 1"); $s->bind_param("ss",$msg,$ref); $s->execute(); $s->close();
        sendTransactionEmail($user['email'],$user['fullname'],"Airtime Purchase",$amount,$ref);
        $conn->commit();
        out(true,"₦".number_format($amount,2)." airtime sent!",['ref'=>$ref,'new_wallet'=>$new]);
    } catch(Throwable $e){ $conn->rollback(); out(false,"Server error."); }
}

// ── BUY DATA ───────────────────────────────────────────────
if ($action === 'buy_data') {
    $network=strtoupper(trim($body['network']??''));
    $phone=preg_replace('/\D+/','',trim($body['phone']??''));
    $plan_id=trim($body['plan_id']??''); $pin=trim($body['pin']??'');
    if (!isset($NET_MAP[$network])||!$plan_id||!ctype_digit($plan_id)||!preg_match('/^\d{4}$/',$pin)||strlen($phone)<10) out(false,"Invalid input.");
    $s=$conn->prepare("SELECT data_plan_id,sell_price,cost_price FROM vtu_dataplans WHERE data_plan_id=? AND network=? AND status='On' LIMIT 1");
    $s->bind_param("is",$plan_id,$network); $s->execute(); $plan=$s->get_result()->fetch_assoc(); $s->close();
    if (!$plan) out(false,"Plan not available.");
    $sell=(float)$plan['sell_price']; $cost=(float)$plan['cost_price']; $profit=$sell-$cost;
    $s=$conn->prepare("SELECT wallet,transaction_pin FROM users WHERE id=? LIMIT 1"); $s->bind_param("i",$uid); $s->execute(); $u=$s->get_result()->fetch_assoc(); $s->close();
    if (empty($u['transaction_pin'])||!password_verify($pin,$u['transaction_pin'])) out(false,"Incorrect PIN.");
    $wallet=(float)$u['wallet']; if ($wallet<$sell) out(false,"Insufficient balance.");
    $ref="DATA_".date("YmdHis")."_".rand(100,999);
    $s=$conn->prepare("INSERT INTO vtu_transactions (user_id,service,provider,ref,network,phone,plan_id,amount_sell,amount_cost,profit,status,provider_message,created_at) VALUES (?,'data','vtunaija',?,?,?,?,?,?,?,'pending','',NOW())");
    $s->bind_param("issssiddd",$uid,$ref,$network,$phone,$plan_id,$sell,$cost,$profit); $s->execute(); $s->close();
    $s=$conn->prepare("UPDATE users SET wallet=wallet-? WHERE id=? AND wallet>=?"); $s->bind_param("did",$sell,$uid,$sell); $s->execute();
    if ($s->affected_rows<1){ $s->close(); out(false,"Wallet error."); } $s->close();
    $res=vtuPost(VTU_BASE_URL."/api/data/",VTU_API_KEY,["network"=>$NET_STR[$network],"mobile_number"=>$phone,"plan"=>$plan_id,"Ported_number"=>"true","request-id"=>$ref],VTU_TIMEOUT);
    $d=$res['data']??[]; $st=strtolower((string)($d['status']??$d['Status']??'')); $msg=(string)($d['api_response']??$d['message']??'No response');
    if (!$res['ok']||($st!=='success'&&$st!=='successful')) {
        $s=$conn->prepare("UPDATE users SET wallet=wallet+? WHERE id=?"); $s->bind_param("di",$sell,$uid); $s->execute(); $s->close();
        $fail=$res['ok']?$msg:"HTTP ".$res['http_code'];
        $s=$conn->prepare("UPDATE vtu_transactions SET status='failed',provider_message=? WHERE ref=? LIMIT 1"); $s->bind_param("ss",$fail,$ref); $s->execute(); $s->close();
        out(false,"Data failed: ".$fail);
    }
    $s=$conn->prepare("UPDATE vtu_transactions SET status='success',provider_message=? WHERE ref=? LIMIT 1"); $s->bind_param("ss",$msg,$ref); $s->execute(); $s->close();
    $new=$wallet-$sell;
    sendTransactionEmail($user['email'],$user['fullname'],"Data Subscription",$sell,$ref);
    out(true,"Data purchase successful!",['ref'=>$ref,'new_wallet'=>$new]);
}

out(false,"Unknown action.");
