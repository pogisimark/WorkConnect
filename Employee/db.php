<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Employee database connection for WorkConnect
$host = "workconnect.ct26qyouyans.ap-southeast-2.rds.amazonaws.com";
$user = "admin";
$pass = "Pogisimark";
$db   = "WorkConnect";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    // Don't use die() as it outputs HTML and breaks JSON responses
    // Log error instead and set connection to null
    error_log("Database connection failed: " . $conn->connect_error);
    $conn = null;
} else {
    // Use Philippines time (UTC+8) for NOW(), CURRENT_TIMESTAMP, etc.
    $conn->query("SET time_zone = '+08:00'");
}
?>
