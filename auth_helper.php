<?php

function authColumnExists(mysqli $conn, string $table, string $column): bool
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

function ensureAuthSchema(mysqli $conn): void
{
    $columns = [
        'reset_token' => "ALTER TABLE users ADD COLUMN reset_token VARCHAR(128) NULL",
        'reset_expires' => "ALTER TABLE users ADD COLUMN reset_expires DATETIME NULL",
        'login_otp' => "ALTER TABLE users ADD COLUMN login_otp VARCHAR(10) NULL",
        'login_otp_expires' => "ALTER TABLE users ADD COLUMN login_otp_expires DATETIME NULL",
        'last_otp_verified_at' => "ALTER TABLE users ADD COLUMN last_otp_verified_at DATETIME NULL",
        'password_changed_at' => "ALTER TABLE users ADD COLUMN password_changed_at DATETIME NULL",
        'api_token' => "ALTER TABLE users ADD COLUMN api_token VARCHAR(128) NULL",
    ];

    foreach ($columns as $column => $sql) {
        if (!authColumnExists($conn, 'users', $column)) {
            $conn->query($sql);
        }
    }
}
