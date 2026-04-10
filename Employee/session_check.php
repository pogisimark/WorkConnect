<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Check if user is logged in, redirect to login if not
require_once 'session_init.php';
require_once 'db.php';
require_once __DIR__ . '/../jobseeker_expiry_helper.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    workconnect_ensure_jobseeker_expiry_schema($conn);
    workconnect_touch_employee_activity($conn, (int)$_SESSION['user_id']);
}
?>
