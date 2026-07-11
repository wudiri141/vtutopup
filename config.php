<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$DB_HOST = "localhost";
$DB_USER = "hiwfptci_wudiri";
$DB_PASS = "Adamusani141@#£";
$DB_NAME = "hiwfptci_hasatech_db";

$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}