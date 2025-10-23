<?php
// Simple email test to check what's working on the server
header('Content-Type: text/html; charset=UTF-8');

echo "<h2>Server Email Configuration Test</h2>";

// Test 1: Check if mail function exists
echo "<h3>1. PHP Mail Function Test</h3>";
if (function_exists('mail')) {
    echo "<p style='color: green;'>✅ PHP mail() function is available</p>";
} else {
    echo "<p style='color: red;'>❌ PHP mail() function is NOT available</p>";
}

// Test 2: Check if PHPMailer is available
echo "<h3>2. PHPMailer Availability Test</h3>";
if (file_exists('vendor/autoload.php')) {
    echo "<p style='color: green;'>✅ PHPMailer is installed</p>";
    try {
        require_once 'vendor/autoload.php';
        echo "<p style='color: green;'>✅ PHPMailer autoloader works</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ PHPMailer autoloader failed: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ PHPMailer is NOT installed (vendor/autoload.php not found)</p>";
}

// Test 3: Check email configuration
echo "<h3>3. Email Configuration Test</h3>";
if (file_exists('Employer/email_config.php')) {
    echo "<p style='color: green;'>✅ Email config file exists</p>";
    try {
        require_once 'Employer/email_config.php';
        echo "<p>SMTP Host: " . (defined('SMTP_HOST') ? SMTP_HOST : 'Not defined') . "</p>";
        echo "<p>SMTP Port: " . (defined('SMTP_PORT') ? SMTP_PORT : 'Not defined') . "</p>";
        echo "<p>SMTP Username: " . (defined('SMTP_USERNAME') ? SMTP_USERNAME : 'Not defined') . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Email config failed: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Email config file not found</p>";
}

// Test 4: Test basic mail function
echo "<h3>4. Basic Mail Function Test</h3>";
$test_email = "test@example.com";
$subject = "Test Email from WorkConnect Server";
$message = "This is a test email to verify server email functionality.";
$headers = "From: WorkConnect <noreply@workconnect.com>\r\n";

if (mail($test_email, $subject, $message, $headers)) {
    echo "<p style='color: green;'>✅ Basic mail() function works</p>";
} else {
    echo "<p style='color: red;'>❌ Basic mail() function failed</p>";
    echo "<p>This means the server's mail system is not configured properly.</p>";
}

// Test 5: Check server configuration
echo "<h3>5. Server Configuration</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Operating System: " . php_uname() . "</p>";

// Test 6: Check if our fixed files exist
echo "<h3>6. Fixed Email Files Test</h3>";
$files_to_check = [
    'Employee/forgot_password_phpmailer.php',
    'Employer/send_jobseeker_email.php', 
    'Employer/send_email_with_phpmailer.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $file exists</p>";
    } else {
        echo "<p style='color: red;'>❌ $file missing</p>";
    }
}

echo "<h3>Recommendations:</h3>";
echo "<ul>";
echo "<li>If PHPMailer is not installed: Run 'composer install' on the server</li>";
echo "<li>If basic mail() fails: Configure Postfix or use a mail service</li>";
echo "<li>If everything works: The email functions should work now</li>";
echo "</ul>";
?>
