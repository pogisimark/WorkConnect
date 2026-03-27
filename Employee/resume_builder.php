<?php
// Resume builder is intentionally disabled.
require_once 'session_check.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

header('Location: dashboard.php');
exit();
