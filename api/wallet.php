<?php
// ============================================================
// FILE: public_html/api/wallet.php
// URL:  https://vtutopup.com.ng/api/wallet.php
// ============================================================
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once "../db.php";
require_once "../auth_helper.php";

ensureAuthSchema($conn);

function out($ok, $msg, $extra = []) {
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
    exit();
}

// ── Bearer token auth ─────────────────────────────────────
function authUser($conn) {
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(\S+)$/i', $h, $m)) return null;
    $stmt = $conn->prepare("SELECT id,fullname,email,phone,wallet,role FROM users WHERE api_token=? LIMIT 1");
    $stmt->bind_param("s", $m[1]);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $u;
}

$user = authUser($conn);
if (!$user) out(false, "Unauthorized. Please login again.");

$uid    = (int)$user['id'];
$action = trim($_GET['action'] ?? '');

// ══════════════════════════════════════════
// WALLET BALANCE
// ══════════════════════════════════════════
if ($action === 'balance') {
    $stmt = $conn->prepare("SELECT wallet,fullname,virtual_account_number,virtual_bank_name FROM users WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    out(true, "OK", [
        'wallet'                 => (float)$row['wallet'],
        'fullname'               => $row['fullname'],
        'virtual_account_number' => $row['virtual_account_number'] ?? '9161044495',
        'virtual_bank_name'      => $row['virtual_bank_name']      ?? 'OPay',
    ]);
}

// ══════════════════════════════════════════
// TRANSACTIONS (wallet + VTU merged)
// ══════════════════════════════════════════
if ($action === 'transactions') {
    $limit  = min((int)($_GET['limit'] ?? 20), 100);
    $offset = (max(1,(int)($_GET['page'] ?? 1)) - 1) * $limit;

    // Wallet (fund/credit) transactions
    $s1 = $conn->prepare("
        SELECT 'wallet' AS source, id, service, amount,
               status, reference, description, created_at,
               '' AS network, '' AS phone
        FROM transactions
        WHERE user_id=?
        ORDER BY id DESC LIMIT ? OFFSET ?
    ");
    $s1->bind_param("iii", $uid, $limit, $offset);
    $s1->execute();
    $tx1 = $s1->get_result()->fetch_all(MYSQLI_ASSOC);
    $s1->close();

    // VTU (airtime/data/cable/electricity) transactions
    $s2 = $conn->prepare("
        SELECT 'vtu' AS source, id, service, amount_sell AS amount,
               status, ref AS reference, provider_message AS description,
               created_at, network, phone
        FROM vtu_transactions
        WHERE user_id=?
        ORDER BY id DESC LIMIT ? OFFSET ?
    ");
    $s2->bind_param("iii", $uid, $limit, $offset);
    $s2->execute();
    $tx2 = $s2->get_result()->fetch_all(MYSQLI_ASSOC);
    $s2->close();

    $all = array_merge($tx1, $tx2);
    usort($all, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    out(true, "OK", ['transactions' => array_slice($all, 0, $limit)]);
}

out(false, "Unknown action.");
