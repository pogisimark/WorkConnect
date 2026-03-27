<?php
// Simple email test script
$to = "test@example.com"; // Change this to your email
$subject = "Test Email from WorkConnect";
$message = "
<html>
<head>
    <title>Test Email</title>
</head>
<body>
    <?php wc_render_ec2_logo_header(); ?>
<h1>Email Test</h1>
    <p>This is a test email to verify that the mail function is working.</p>
    <p>If you receive this email, the mail function is configured correctly.</p>
</body>
</html>";

$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: WorkConnect <noreply@workconnect.com>" . "\r\n";

echo "<h2>Email Test Results</h2>";

if (function_exists('mail')) {
    echo "<p>✅ Mail function is available</p>";
    
    if (mail($to, $subject, $message, $headers)) {
        echo "<p>✅ Test email sent successfully to: $to</p>";
        echo "<p>Please check your email inbox.</p>";
    } else {
        echo "<p>❌ Failed to send test email</p>";
        echo "<p>Possible issues:</p>";
        echo "<ul>";
        echo "<li>SMTP server not configured</li>";
        echo "<li>Email address invalid</li>";
        echo "<li>Server restrictions</li>";
        echo "</ul>";
    }
} else {
    echo "<p>❌ Mail function is not available on this server</p>";
    echo "<p>You need to configure SMTP settings or use a mail service.</p>";
}

echo "<h3>Server Information:</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Operating System: " . php_uname() . "</p>";

echo "<h3>Alternative Solutions:</h3>";
echo "<p>1. Configure SMTP in php.ini</p>";
echo "<p>2. Use PHPMailer library</p>";
echo "<p>3. Use a mail service like SendGrid or Mailgun</p>";
?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/ec2_logo_header.php'; ?>

