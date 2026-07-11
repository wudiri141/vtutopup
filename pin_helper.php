<?php
function redirect_back_with_error(string $msg): void {
  $back = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
  $sep = (strpos($back, '?') !== false) ? '&' : '?';
  header("Location: {$back}{$sep}error=" . urlencode($msg));
  exit();
}

function require_pin_ok(mysqli $conn, int $user_id, string $pin): void {
  $pin = trim($pin);

  // If pin missing or invalid format
  if ($pin === '' || !preg_match('/^\d{4}$/', $pin)) {
    redirect_back_with_error("Enter your 4-digit transaction PIN.");
  }

  $stmt = $conn->prepare("SELECT transaction_pin_hash, pin_attempts, pin_locked_until FROM users WHERE id=? LIMIT 1");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $u = $stmt->get_result()->fetch_assoc();

  if (!$u) {
    header("Location: login.php?error=" . urlencode("User not found."));
    exit();
  }

  // If user has no pin set: force to account page
  if (empty($u['transaction_pin_hash'])) {
    header("Location: account.php?setpin=1");
    exit();
  }

  // Lock check
  if (!empty($u['pin_locked_until']) && strtotime($u['pin_locked_until']) > time()) {
    header("Location: account.php?error=" . urlencode("PIN locked. Try again later."));
    exit();
  }

  // Verify pin
  if (!password_verify($pin, $u['transaction_pin_hash'])) {
    $attempts = (int)$u['pin_attempts'] + 1;
    $locked_until = null;

    // lock after 5 wrong tries for 10 minutes
    if ($attempts >= 5) {
      $locked_until = date("Y-m-d H:i:s", time() + 600);
      $attempts = 0;
    }

    if ($locked_until) {
      $up = $conn->prepare("UPDATE users SET pin_attempts=?, pin_locked_until=? WHERE id=?");
      $up->bind_param("isi", $attempts, $locked_until, $user_id);
    } else {
      $up = $conn->prepare("UPDATE users SET pin_attempts=? WHERE id=?");
      $up->bind_param("ii", $attempts, $user_id);
    }
    $up->execute();

    redirect_back_with_error("Incorrect transaction PIN.");
  }

  // OK reset attempts
  $up = $conn->prepare("UPDATE users SET pin_attempts=0, pin_locked_until=NULL WHERE id=?");
  $up->bind_param("i", $user_id);
  $up->execute();
}
