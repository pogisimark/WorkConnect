<?php
// Direct PHPMailer test to debug the issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Direct PHPMailer Test</h2>";

// Test 1: Check if PHPMailer can be loaded
echo "<h3>1. Loading PHPMailer</h3>";
try {
    require_once 'vendor/autoload.php';
    require_once 'Employer/email_config.php';
    echo "<p style='color: green;'>✅ PHPMailer loaded successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Failed to load PHPMailer: " . $e->getMessage() . "</p>";
    exit;
}

// Test 2: Check email configuration
echo "<h3>2. Email Configuration</h3>";
echo "<p>SMTP Host: " . SMTP_HOST . "</p>";
echo "<p>SMTP Port: " . SMTP_PORT . "</p>";
echo "<p>SMTP Username: " . SMTP_USERNAME . "</p>";
echo "<p>SMTP Password: " . (strlen(SMTP_PASSWORD) > 0 ? "Set (" . strlen(SMTP_PASSWORD) . " chars)" : "Not set") . "</p>";

// Test 3: Try to create PHPMailer instance
echo "<h3>3. Creating PHPMailer Instance</h3>";
try {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    echo "<p style='color: green;'>✅ PHPMailer instance created</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Failed to create PHPMailer: " . $e->getMessage() . "</p>";
    exit;
}

// Test 4: Configure SMTP settings
echo "<h3>4. Configuring SMTP</h3>";
try {
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;
    echo "<p style='color: green;'>✅ SMTP configured</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Failed to configure SMTP: " . $e->getMessage() . "</p>";
    exit;
}

// Test 5: Try to connect to SMTP server
echo "<h3>5. Testing SMTP Connection</h3>";
try {
    $mail->smtpConnect();
    echo "<p style='color: green;'>✅ SMTP connection successful</p>";
    $mail->smtpClose();
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ SMTP connection failed: " . $e->getMessage() . "</p>";
    echo "<p>Possible issues:</p>";
    echo "<ul>";
    echo "<li>Gmail App Password is incorrect</li>";
    echo "<li>2-Factor Authentication not enabled</li>";
    echo "<li>Server firewall blocking SMTP ports</li>";
    echo "<li>Gmail blocking the connection</li>";
    echo "</ul>";
}

// Test 6: Try to send a test email
echo "<h3>6. Sending Test Email</h3>";
try {
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress('test@example.com'); // Change this to your email
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from WorkConnect';
    $mail->Body = 'This is a test email to verify PHPMailer is working.';
    
    $mail->send();
    echo "<p style='color: green;'>✅ Test email sent successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Failed to send email: " . $e->getMessage() . "</p>";
    echo "<p>Error Info: " . $mail->ErrorInfo . "</p>";
}

echo "<h3>Recommendations:</h3>";
echo "<ul>";
echo "<li>If SMTP connection fails: Check Gmail App Password and 2FA settings</li>";
echo "<li>If email sending fails: Check Gmail credentials and server firewall</li>";
echo "<li>If everything works: The issue might be in the forgot_password_phpmailer.php file</li>";
echo "</ul>";
?>
