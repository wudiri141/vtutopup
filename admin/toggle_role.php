<?php
session_start();
include "../db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
  die("Access denied");
}

$id = (int)($_GET['id'] ?? 0);
$role = $_GET['role'] ?? '';

if (!in_array($role, ['admin','user']) || $id <= 0) {
  die("Invalid request");
}

$stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
$stmt->bind_param("si", $role, $id);
$stmt->execute();

header("Location: users.php");
exit();
