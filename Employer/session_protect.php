<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
if (!isset($_SESSION['username'])) {
    header('Location: login.html');
    exit;
}
