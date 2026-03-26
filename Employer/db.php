<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// db.php - shared DB connection for WorkConnect
$host = "workconnect.cp28esmqk7aq.ap-southeast-2.rds.amazonaws.com";
$user = "admin";
$pass = "Pogisimark";
$db   = "WorkConnect";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    // Use Philippines time (UTC+8) for NOW(), CURRENT_TIMESTAMP, etc.
    $conn->query("SET time_zone = '+08:00'");
}
?>
