<?php

// Database credentials
$DB_HOST = "localhost";
$DB_USER = "vtutopup_vtuuser";
$DB_PASS = "Adamusani141@121";
$DB_NAME = "vtutopup_vtutopup";

// Create connection
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Optional: Set charset (VERY IMPORTANT)
$conn->set_charset("utf8mb4");
?>