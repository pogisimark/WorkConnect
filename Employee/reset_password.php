<?php
require_once 'db.php';

$error_message = '';
$success_message = '';
$token = $_GET['token'] ?? '';

// Verify token
if (!empty($token)) {
    $stmt = $conn->prepare("SELECT pr.*, eu.id as user_id FROM password_resets pr 
                           JOIN employee_users eu ON pr.user_id = eu.id 
                           WHERE pr.token = ? AND pr.expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error_message = "Invalid or expired reset token.";
    }
    $stmt->close();
} else {
    $error_message = "No reset token provided.";
}

// Handle password reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($error_message)) {
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        $error_message = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d).{8,}$/', $new_password)) {
        $error_message = "Password must be at least 8 characters long, contain at least 1 capital letter and 1 number.";
    } else {
        // Get user info
        $stmt = $conn->prepare("SELECT pr.user_id FROM password_resets pr WHERE pr.token = ? AND pr.expires_at > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            $user_id = $user_data['user_id'];
            
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE employee_users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                // Delete used token
                $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                $stmt->bind_param("s", $token);
                $stmt->execute();
                
                $success_message = "Password has been reset successfully. You can now login with your new password.";
            } else {
                $error_message = "Failed to update password. Please try again.";
            }
        } else {
            $error_message = "Invalid or expired reset token.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - WorkConnect</title>
    <link rel="stylesheet" href="../assets/css/Employee-login.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <img src="../assets/image/PESO Logo circle.png" alt="PESO Logo" class="logo">
            <h1 class="brand">WorkConnect</h1>
        </div>
        
        <h2 class="login-title">Reset Password</h2>
        
        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <div class="back-to-login">
                <a href="login.php">← Back to Login</a>
            </div>
        <?php elseif (empty($error_message)): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="password-input-container">
                        <input type="password" id="password" name="password" maxlength="30" required>
                        <i class="fas fa-eye password-toggle" onclick="togglePassword()"></i>
                    </div>
                    <div class="password-requirements">Minimum 8 characters, 1 capital letter, 1 number</div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-input-container">
                        <input type="password" id="confirm_password" name="confirm_password" maxlength="30" required>
                        <i class="fas fa-eye password-toggle" onclick="toggleConfirmPassword()"></i>
                    </div>
                </div>
                
                <button type="submit" class="login-btn">Reset Password</button>
            </form>
            
            <div class="back-to-login">
                <a href="login.php">← Back to Login</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        function toggleConfirmPassword() {
            const passwordInput = document.getElementById('confirm_password');
            const toggleIcons = document.querySelectorAll('.password-toggle');
            const toggleIcon = toggleIcons[1]; // Second toggle icon
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Password validation
        function validatePassword() {
            const password = document.getElementById('password').value;
            const requirements = document.querySelector('.password-requirements');
            
            if (password.length === 0) {
                requirements.style.color = '#666';
                requirements.textContent = 'Minimum 8 characters, 1 capital letter, 1 number';
                return false;
            }
            
            const hasLength = password.length >= 8;
            const hasCapital = /[A-Z]/.test(password);
            const hasNumber = /\d/.test(password);
            
            if (hasLength && hasCapital && hasNumber) {
                requirements.style.color = '#4caf50';
                requirements.textContent = '✓ Password meets all requirements';
                return true;
            } else {
                requirements.style.color = '#f44336';
                let missing = [];
                if (!hasLength) missing.push('8+ characters');
                if (!hasCapital) missing.push('1 capital letter');
                if (!hasNumber) missing.push('1 number');
                requirements.textContent = 'Missing: ' + missing.join(', ');
                return false;
            }
        }

        // Add event listener for password validation
        document.getElementById('password').addEventListener('input', validatePassword);
    </script>

    <style>
        .password-input-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            font-size: 16px;
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: #1a3876;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }

        .back-to-login a {
            color: #1a3876;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .back-to-login a:hover {
            color: #ffcb05;
            text-decoration: underline;
        }

        .password-requirements {
            font-size: 12px;
            margin-top: 5px;
            transition: color 0.3s ease;
        }
    </style>
</body>
</html>
