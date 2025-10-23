<?php
// Test the forgot password email functionality
require_once 'db.php';
require_once '../vendor/autoload.php';
require_once '../Employer/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

echo "<h2>Testing Forgot Password Email Functionality</h2>";

// Test database connection
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
    exit();
} else {
    echo "<p style='color: green;'>✅ Database connection successful</p>";
}

// Test PHPMailer configuration
echo "<h3>PHPMailer Configuration Test</h3>";
echo "<p>SMTP Host: " . SMTP_HOST . "</p>";
echo "<p>SMTP Port: " . SMTP_PORT . "</p>";
echo "<p>SMTP Username: " . SMTP_USERNAME . "</p>";
echo "<p>From Email: " . SMTP_FROM_EMAIL . "</p>";

// Test email sending
echo "<h3>Email Sending Test</h3>";

$test_email = "test@example.com"; // Change this to your email for testing
$subject = "Test Email from WorkConnect Forgot Password";
$message = "
<html>
<head>
    <title>Test Email</title>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
        <h2 style='color: #1a3876;'>Test Email</h2>
        <p>This is a test email to verify that the PHPMailer configuration is working correctly.</p>
        <p>If you receive this email, the forgot password functionality should work properly.</p>
        <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
        <p style='color: #666; font-size: 14px;'>Best regards,<br>WorkConnect Team</p>
    </div>
</body>
</html>
";

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;
    
    // Recipients
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress($test_email);
    
    // Content
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = $subject;
    $mail->Body    = $message;
    
    $mail->send();
    echo "<p style='color: green;'>✅ Test email sent successfully to: $test_email</p>";
    echo "<p>Please check your email inbox.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Failed to send test email</p>";
    echo "<p>Error: " . $mail->ErrorInfo . "</p>";
    echo "<p>Possible issues:</p>";
    echo "<ul>";
    echo "<li>Gmail App Password is incorrect</li>";
    echo "<li>2-Factor Authentication not enabled on Gmail</li>";
    echo "<li>SMTP settings are wrong</li>";
    echo "<li>Network/firewall issues</li>";
    echo "</ul>";
}

// Test the forgot password handler
echo "<h3>Testing Forgot Password Handler</h3>";
echo "<p>Testing JSON response...</p>";

$test_data = json_encode(['email' => 'test@example.com']);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/forgot_password_phpmailer.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $test_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response) {
    $json_response = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<p style='color: green;'>✅ JSON response is valid</p>";
        echo "<p>Success: " . ($json_response['success'] ? 'Yes' : 'No') . "</p>";
        echo "<p>Message: " . htmlspecialchars($json_response['message']) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Invalid JSON response</p>";
        echo "<p>Raw response: " . htmlspecialchars($response) . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ No response from handler</p>";
}

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #1a3876; }
p { margin: 5px 0; }
ul { margin: 10px 0; padding-left: 20px; }
</style>
