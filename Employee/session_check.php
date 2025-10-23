<?php
// Check if user is logged in, redirect to login if not
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}
?>
