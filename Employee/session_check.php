<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Check if user is logged in, redirect to login if not
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}
?>
