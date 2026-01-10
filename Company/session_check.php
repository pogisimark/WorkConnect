<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Check if company is logged in, redirect to login if not
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security headers
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Check if company is logged in
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['company_id']) || !isset($_SESSION['email']) || !isset($_SESSION['company_name'])) {
    // Clear any partial session data
    $_SESSION = array();
    session_destroy();
    header('Location: login.php');
    exit();
}

// Check session timeout (30 minutes of inactivity)
$timeout = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    // Session expired
    $_SESSION = array();
    session_destroy();
    header('Location: login.php?expired=1');
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Regenerate session ID periodically for security (every 30 minutes)
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) {
    // Regenerate session ID every 30 minutes
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}
?>

