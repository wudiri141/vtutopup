<?php

const REFERRAL_BONUS_AMOUNT = 100.00;

function referralColumnExists(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int)($row['total'] ?? 0) > 0;
}

function ensureReferralSchema(mysqli $conn): void
{
    $columns = [
        'referral_code' => "ALTER TABLE users ADD COLUMN referral_code VARCHAR(20) NULL UNIQUE AFTER role",
        'referred_by' => "ALTER TABLE users ADD COLUMN referred_by INT NULL AFTER referral_code",
        'referral_count' => "ALTER TABLE users ADD COLUMN referral_count INT NOT NULL DEFAULT 0 AFTER referred_by",
        'referral_earnings' => "ALTER TABLE users ADD COLUMN referral_earnings DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER referral_count",
    ];

    foreach ($columns as $column => $sql) {
        if (!referralColumnExists($conn, 'users', $column)) {
            $conn->query($sql);
        }
    }

    $conn->query("
        CREATE TABLE IF NOT EXISTS referral_rewards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            referrer_id INT NOT NULL,
            referred_user_id INT NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            status ENUM('pending','paid') NOT NULL DEFAULT 'pending',
            paid_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_referred_user (referred_user_id),
            KEY idx_referrer (referrer_id),
            KEY idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function makeReferralCode(string $fullname, int $userId): string
{
    $namePart = strtoupper(preg_replace('/[^A-Z0-9]/', '', $fullname));
    $namePart = substr($namePart ?: 'VTU', 0, 5);
    return $namePart . str_pad((string)$userId, 5, '0', STR_PAD_LEFT);
}

function ensureUserReferralCode(mysqli $conn, int $userId, string $fullname = ''): string
{
    ensureReferralSchema($conn);

    $stmt = $conn->prepare("SELECT fullname, referral_code FROM users WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return '';
    }

    if (!empty($user['referral_code'])) {
        return (string)$user['referral_code'];
    }

    $baseName = $fullname ?: (string)($user['fullname'] ?? '');
    $code = makeReferralCode($baseName, $userId);

    $tries = 0;
    while ($tries < 5) {
        $candidate = $tries === 0 ? $code : $code . random_int(10, 99);
        $up = $conn->prepare("UPDATE users SET referral_code=? WHERE id=? AND (referral_code IS NULL OR referral_code='')");
        $up->bind_param("si", $candidate, $userId);
        if ($up->execute()) {
            $up->close();
            return $candidate;
        }
        $up->close();
        $tries++;
    }

    return '';
}

function getReferrerIdByCode(mysqli $conn, string $code): ?int
{
    ensureReferralSchema($conn);

    $clean = strtoupper(trim($code));
    if ($clean === '') {
        return null;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE referral_code=? LIMIT 1");
    $stmt->bind_param("s", $clean);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $user ? (int)$user['id'] : null;
}

function attachReferral(mysqli $conn, int $newUserId, ?int $referrerId): void
{
    ensureReferralSchema($conn);

    if (!$referrerId || $referrerId === $newUserId) {
        return;
    }

    $stmt = $conn->prepare("UPDATE users SET referred_by=? WHERE id=? AND referred_by IS NULL");
    $stmt->bind_param("ii", $referrerId, $newUserId);
    $stmt->execute();
    $stmt->close();

    $amount = REFERRAL_BONUS_AMOUNT;
    $status = 'pending';
    $reward = $conn->prepare("
        INSERT IGNORE INTO referral_rewards (referrer_id, referred_user_id, amount, status)
        VALUES (?, ?, ?, ?)
    ");
    $reward->bind_param("iids", $referrerId, $newUserId, $amount, $status);
    $reward->execute();
    $reward->close();
}

function payPendingReferralReward(mysqli $conn, int $referredUserId): void
{
    ensureReferralSchema($conn);

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("
            SELECT id, referrer_id, amount
            FROM referral_rewards
            WHERE referred_user_id=? AND status='pending'
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->bind_param("i", $referredUserId);
        $stmt->execute();
        $reward = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$reward) {
            $conn->commit();
            return;
        }

        $referrerId = (int)$reward['referrer_id'];
        $amount = (float)$reward['amount'];
        $description = "Referral bonus";
        $reference = "REF_" . date("YmdHis") . "_" . $referredUserId;

        $up = $conn->prepare("
            UPDATE users
            SET wallet = wallet + ?,
                referral_count = referral_count + 1,
                referral_earnings = referral_earnings + ?
            WHERE id=?
        ");
        $up->bind_param("ddi", $amount, $amount, $referrerId);
        $up->execute();
        $up->close();

        $tx = $conn->prepare("
            INSERT INTO transactions (user_id, type, service, amount, status, reference, description)
            VALUES (?, 'credit', 'Referral Bonus', ?, 'completed', ?, ?)
        ");
        $tx->bind_param("idss", $referrerId, $amount, $reference, $description);
        $tx->execute();
        $tx->close();

        $paidAt = date('Y-m-d H:i:s');
        $paid = 'paid';
        $mark = $conn->prepare("UPDATE referral_rewards SET status=?, paid_at=? WHERE id=?");
        $mark->bind_param("ssi", $paid, $paidAt, $reward['id']);
        $mark->execute();
        $mark->close();

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        error_log("Referral reward error: " . $e->getMessage());
    }
}

function getReferralStats(mysqli $conn, int $userId): array
{
    ensureReferralSchema($conn);

    $code = ensureUserReferralCode($conn, $userId);

    $stmt = $conn->prepare("
        SELECT referral_count, referral_earnings
        FROM users
        WHERE id=? LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();

    return [
        'code' => $code,
        'count' => (int)($user['referral_count'] ?? 0),
        'earnings' => (float)($user['referral_earnings'] ?? 0),
        'bonus' => REFERRAL_BONUS_AMOUNT,
    ];
}
