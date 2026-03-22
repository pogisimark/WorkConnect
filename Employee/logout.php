<?php
// Destroy session and redirect to login
require_once 'session_init.php';

// Destroy all session data
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?>
