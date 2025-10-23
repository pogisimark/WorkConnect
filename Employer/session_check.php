<?php
session_start();
header('Content-Type: application/json');
$isMainAdmin = (isset($_SESSION['username']) && $_SESSION['username'] === 'Admin');
$username = $_SESSION['username'] ?? '';
echo json_encode([
    'isMainAdmin' => $isMainAdmin,
    'username' => $username,
    'isLoggedIn' => !empty($username)
]);
