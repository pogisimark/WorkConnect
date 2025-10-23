<?php
require_once 'db.php';

// Set content type to JSON
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email address is required.']);
        exit();
    }
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id, firstname, email FROM employee_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Generate reset token
        $reset_token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour
        
        // Store reset token in database
        $stmt = $conn->prepare("INSERT INTO password_resets (user_id, email, token, expires_at, created_at) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at), created_at = NOW()");
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
            <body>
                <h2>Password Reset Request</h2>
                <p>Hello " . htmlspecialchars($user['firstname']) . ",</p>
                <p>You have requested to reset your password for your WorkConnect account.</p>
                <p>Click the link below to reset your password:</p>
                <p><a href='" . $reset_link . "' style='background: #1a3876; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
                <p>Or copy and paste this link in your browser:</p>
                <p>" . $reset_link . "</p>
                <p><strong>This link will expire in 1 hour.</strong></p>
                <p>If you did not request this password reset, please ignore this email.</p>
                <br>
                <p>Best regards,<br>WorkConnect Team</p>
            </body>
            </html>
            ";
            
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: WorkConnect <noreply@workconnect.com>" . "\r\n";
            
            if (mail($email, $subject, $message, $headers)) {
                echo json_encode(['success' => true, 'message' => 'Password reset link has been sent to your email.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again later.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to process reset request. Please try again.']);
        }
        $stmt->close();
    } else {
        // For security, don't reveal if email exists or not
        echo json_encode(['success' => true, 'message' => 'If an account with that email exists, we\'ve sent you a password reset link.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>
