<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/vtu_services.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function ensureVtuDataPlansSchema(mysqli $conn): void {
    $conn->query("
        CREATE TABLE IF NOT EXISTS vtu_dataplans (
            data_plan_id INT NOT NULL PRIMARY KEY,
            network VARCHAR(32) NOT NULL,
            size VARCHAR(120) NOT NULL,
            datatype VARCHAR(80) NOT NULL DEFAULT '',
            validity_days INT NOT NULL DEFAULT 0,
            cost_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            sell_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            provider_basic_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            provider_premium_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            basic_commission DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            premium_commission DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            status VARCHAR(20) NOT NULL DEFAULT 'On',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_network_type_status (network, datatype, status),
            KEY idx_sell_price (sell_price)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $columns = [
        "datatype" => "ALTER TABLE vtu_dataplans ADD COLUMN datatype VARCHAR(80) NOT NULL DEFAULT '' AFTER size",
        "validity_days" => "ALTER TABLE vtu_dataplans ADD COLUMN validity_days INT NOT NULL DEFAULT 0 AFTER datatype",
        "cost_price" => "ALTER TABLE vtu_dataplans ADD COLUMN cost_price DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER validity_days",
        "sell_price" => "ALTER TABLE vtu_dataplans ADD COLUMN sell_price DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER cost_price",
        "provider_basic_price" => "ALTER TABLE vtu_dataplans ADD COLUMN provider_basic_price DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER sell_price",
        "provider_premium_price" => "ALTER TABLE vtu_dataplans ADD COLUMN provider_premium_price DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER provider_basic_price",
        "basic_commission" => "ALTER TABLE vtu_dataplans ADD COLUMN basic_commission DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER provider_premium_price",
        "premium_commission" => "ALTER TABLE vtu_dataplans ADD COLUMN premium_commission DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER basic_commission",
        "status" => "ALTER TABLE vtu_dataplans ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'On' AFTER premium_commission",
    ];

    foreach ($columns as $sql) {
        try { $conn->query($sql); } catch (Throwable $e) {}
    }
}

ensureVtuDataPlansSchema($conn);

$res = vtu_list_data_plans();
if (!$res['ok']) {
    die("Sync failed: " . ($res['error'] ?? 'Unknown error'));
}

$plans = $res['data']['dataplans'] ?? [];
$count = 0;

$conn->query("UPDATE vtu_dataplans SET status='Off'");

foreach ($plans as $p) {
    $id       = (int)($p['data_plan_id'] ?? 0);
    $network  = strtoupper(trim((string)($p['the_network_name'] ?? '')));
    $size     = trim((string)($p['size'] ?? ''));
    $datatype = trim((string)($p['the_datatype_name'] ?? ''));
    $validity = (int)($p['duration'] ?? 0);

    $basic_price = (float)($p['price_for_basicuser'] ?? 0);
    $prem_price  = (float)($p['price_for_premiumuser'] ?? 0);
    $basic_comm  = (float)($p['commission_for_basicuser'] ?? 0);
    $prem_comm   = (float)($p['commission_for_premiumuser'] ?? 0);

    // You are premium: cost is premium price
    $cost_price = $prem_price;

    // You want to sell like basic: sell is basic price
    $sell_price = $basic_price;

    $provider_status = trim((string)($p['status'] ?? 'On'));
    $status = strcasecmp($provider_status, 'On') === 0 ? 'On' : 'Off';

    if ($id <= 0 || $network === '' || $size === '') {
        continue;
    }

    $stmt = $conn->prepare("
        INSERT INTO vtu_dataplans
        (data_plan_id, network, size, datatype, validity_days,
         cost_price, sell_price, provider_basic_price, provider_premium_price,
         basic_commission, premium_commission, status)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
          network=VALUES(network),
          size=VALUES(size),
          datatype=VALUES(datatype),
          validity_days=VALUES(validity_days),
          cost_price=VALUES(cost_price),
          sell_price=VALUES(sell_price),
          provider_basic_price=VALUES(provider_basic_price),
          provider_premium_price=VALUES(provider_premium_price),
          basic_commission=VALUES(basic_commission),
          premium_commission=VALUES(premium_commission),
          status=VALUES(status)
    ");

    // 12 params: i s s s i d d d d d d s
$stmt->bind_param(
    "isssidddddds",
    $id, $network, $size, $datatype, $validity,
    $cost_price, $sell_price, $basic_price, $prem_price,
    $basic_comm, $prem_comm, $status
);


    $stmt->execute();
    $count++;
}

echo "✅ Synced {$count} data plans into vtu_dataplans";
