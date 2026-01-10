<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Company database connection for WorkConnect
$host = "workconnect.ct26qyouyans.ap-southeast-2.rds.amazonaws.com";
$user = "admin";
$pass = "Pogisimark";
$db   = "WorkConnect";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    // Don't use die() as it outputs HTML - log error instead
    error_log("Company DB Connection failed: " . $conn->connect_error);
    $conn = null;
}
?>

