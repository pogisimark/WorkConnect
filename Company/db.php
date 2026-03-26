<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Company database connection for WorkConnect
$host = "workconnect.cp28esmqk7aq.ap-southeast-2.rds.amazonaws.com";
$user = "admin";
$pass = "Pogisimark";
$db   = "WorkConnect";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    error_log("Company DB Connection failed: " . $conn->connect_error);
    $conn = null;
} else {
    $conn->query("SET time_zone = '+08:00'");
}
?>

