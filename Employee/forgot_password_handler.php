<?php
// Disable error reporting to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any unexpected output
ob_start();

require_once 'db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Function to send JSON response and exit
function sendResponse($success, $message) {
    // Clear any output buffer
    ob_clean();
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

try {
    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        sendResponse(false, 'Invalid request method.');
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendResponse(false, 'Invalid JSON input.');
    }
    
    $email = trim($input['email'] ?? '');
    
    if (empty($email)) {
        sendResponse(false, 'Email address is required.');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, 'Please enter a valid email address.');
    }
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id, firstname, email FROM employee_users WHERE email = ?");
    if (!$stmt) {
        sendResponse(false, 'Database error. Please try again later.');
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Generate reset token
        $reset_token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // First, ensure the password_resets table exists
        $create_table_sql = "CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(255) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_token (token),
            INDEX idx_user_id (user_id),
            INDEX idx_expires_at (expires_at)
        )";
        
        if (!$conn->query($create_table_sql)) {
            sendResponse(false, 'Database setup error. Please try again later.');
        }
        
        // Store reset token in database
        $stmt = $conn->prepare("INSERT INTO password_resets (user_id, email, token, expires_at, created_at) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at), created_at = NOW()");
        
        if (!$stmt) {
            sendResponse(false, 'Database error. Please try again later.');
        }
        
        $stmt->bind_param("isss", $user['id'], $email, $reset_token, $expires_at);
        
        if ($stmt->execute()) {
            // Send email with reset link
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $reset_token;
            
            $subject = "Password Reset Request - WorkConnect";
            $message = "
            <html>
            <head>
                <title>Password Reset Request</title>
            </head>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #1a3876;'>Password Reset Request</h2>
                    <p>Hello " . htmlspecialchars($user['firstname']) . ",</p>
                    <p>You have requested to reset your password for your WorkConnect account.</p>
                    <p>Click the button below to reset your password:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . $reset_link . "' style='background: #1a3876; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a>
                    </div>
                    <p>Or copy and paste this link in your browser:</p>
                    <p style='word-break: break-all; background: #f5f5f5; padding: 10px; border-radius: 4px;'>" . $reset_link . "</p>
                    <p><strong>This link will expire in 1 hour.</strong></p>
                    <p>If you did not request this password reset, please ignore this email.</p>
                    <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
                    <p style='color: #666; font-size: 14px;'>Best regards,<br>WorkConnect Team</p>
                </div>
            </body>
            </html>
            ";
            
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: WorkConnect <noreply@workconnect.com>" . "\r\n";
            $headers .= "Reply-To: noreply@workconnect.com" . "\r\n";
            
            if (mail($email, $subject, $message, $headers)) {
                sendResponse(true, 'Password reset link has been sent to your email.');
            } else {
                sendResponse(false, 'Failed to send email. Please try again later.');
            }
        } else {
            sendResponse(false, 'Failed to process reset request. Please try again.');
        }
        $stmt->close();
    } else {
        // For security, don't reveal if email exists or not
        sendResponse(true, 'If an account with that email exists, we\'ve sent you a password reset link.');
    }
    
} catch (Exception $e) {
    sendResponse(false, 'An error occurred. Please try again later.');
} finally {
    if (isset($conn)) {
        $conn->close();
    }
    // Clean any output buffer
    ob_end_clean();
}
?>
