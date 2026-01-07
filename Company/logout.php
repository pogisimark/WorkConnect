<?php
// Destroy session and redirect to login
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// Destroy all session data
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?>

