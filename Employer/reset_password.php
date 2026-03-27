<?php
require_once 'db.php';

// Get username from URL parameter
$reset_username = isset($_GET['username']) ? trim($_GET['username']) : '';

// SECURITY: Prevent reset of super admin account
// The super admin account (username: "Admin") can only be changed manually in source code
if (strtolower($reset_username) === 'admin') {
    header('Location: login.html?error=super_admin_protected');
    exit;
}

// Verify username exists
if ($reset_username) {
    $stmt = $conn->prepare("SELECT id, username FROM admin_accounts WHERE username = ?");
    $stmt->bind_param('s', $reset_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        header('Location: login.html?error=invalid_username');
        exit;
    }
    $stmt->close();
} else {
    header('Location: login.html?error=no_username');
    exit;
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SECURITY: Double-check to prevent super admin reset via POST
    if (strtolower($reset_username) === 'admin') {
        header('Location: login.html?error=super_admin_protected');
        exit;
    }
    
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        $error_message = 'All fields are required.';
    } elseif (strlen($new_password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } else {
        // Check if new password is same as current password
        $stmt = $conn->prepare("SELECT password FROM admin_accounts WHERE username = ?");
        $stmt->bind_param('s', $reset_username);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        
        if ($admin && password_verify($new_password, $admin['password'])) {
            $error_message = 'New password cannot be the same as your current password.';
        } else {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password in database
            $stmt = $conn->prepare("UPDATE admin_accounts SET password = ? WHERE username = ?");
            $stmt->bind_param('ss', $hashed_password, $reset_username);
            
            if ($stmt->execute()) {
                // Redirect to login page with success message
                header('Location: login.html?success=password_reset');
                exit;
            } else {
                $error_message = 'Failed to reset password. Please try again.';
            }
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
    <link rel='icon' href='/assets/image/PESO Logo circle.png'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #1a3876 0%, #2c5aa0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .reset-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            margin: 20px;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            height: 60px;
            margin-bottom: 10px;
        }

        .brand {
            font-size: 1.8rem;
            font-weight: bold;
            color: #1a3876;
            margin: 0;
        }

        .reset-title {
            text-align: center;
            color: #1a3876;
            font-size: 1.5rem;
            margin-bottom: 30px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .password-wrapper {
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 12px 45px 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #1a3876;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
            font-size: 1.1rem;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: #1a3876;
        }

        .password-toggle:focus {
            outline: none;
        }

        .reset-btn {
            width: 100%;
            background: #1a3876;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            margin-bottom: 20px;
        }

        .reset-btn:hover {
            background: #2c5aa0;
        }

        .back-login {
            text-align: center;
            margin-top: 20px;
        }

        .back-login a {
            color: #1a3876;
            text-decoration: none;
            font-weight: 500;
        }

        .back-login a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #fcc;
        }

        .success-message {
            background: #efe;
            color: #363;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #cfc;
        }

        .username-display {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
            color: #666;
            font-weight: 500;
        }

        /* Mobile Responsive - App-like Design */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .reset-container {
                padding: 30px 20px;
                margin: 10px;
                max-width: 100%;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            }
            
            .logo {
                height: 60px;
                margin-bottom: 12px;
            }
            
            .brand {
                font-size: 1.8rem;
                font-weight: bold;
            }
            
            .reset-title {
                font-size: 1.4rem;
                margin-bottom: 25px;
            }
            
            .form-group {
                margin-bottom: 18px;
            }
            
            .form-group input {
                padding: 12px 45px 12px 14px;
                font-size: 1rem;
            }
            
            .password-toggle {
                right: 10px;
                font-size: 1rem;
            }
            
            .reset-btn {
                padding: 12px;
                font-size: 1rem;
                margin-bottom: 18px;
            }
            
            .username-display {
                padding: 12px;
                font-size: 0.95rem;
                margin-bottom: 18px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 8px;
            }
            
            .reset-container {
                padding: 25px 16px;
                margin: 8px;
                max-width: 100%;
                border-radius: 10px;
            }
            
            .logo {
                height: 55px;
                margin-bottom: 10px;
            }
            
            .brand {
                font-size: 1.6rem;
                font-weight: bold;
            }
            
            .reset-title {
                font-size: 1.3rem;
                margin-bottom: 20px;
            }
            
            .form-group {
                margin-bottom: 16px;
            }
            
            .form-group input {
                padding: 10px 40px 10px 12px;
                font-size: 0.95rem;
            }
            
            .password-toggle {
                right: 8px;
                font-size: 0.95rem;
            }
            
            .reset-btn {
                padding: 10px;
                font-size: 0.95rem;
                margin-bottom: 16px;
            }
            
            .username-display {
                padding: 10px;
                font-size: 0.9rem;
                margin-bottom: 16px;
            }
            
            .back-login {
                margin-top: 16px;
            }
            
            .back-login a {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
<div class="reset-container">
        <div class="logo-section">
            <img src="../assets/image/PESO Logo circle.png" alt="PESO Logo" class="logo">
            <h1 class="brand">WorkConnect</h1>
        </div>
        
        <h2 class="reset-title">Reset Password</h2>
        
        <div class="username-display">
            Resetting password for: <strong><?php echo htmlspecialchars($reset_username); ?></strong>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <form method="POST" id="resetPasswordForm">
            <div class="form-group">
                <label for="new_password">New Password</label>
                <div class="password-wrapper">
                    <input type="password" id="new_password" name="new_password" maxlength="30" required minlength="6">
                    <button type="button" class="password-toggle" id="toggleNewPassword" aria-label="Show password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" maxlength="30" required minlength="6">
                    <button type="button" class="password-toggle" id="toggleConfirmPassword" aria-label="Show password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="reset-btn">Reset Password</button>
        </form>
        
        <div class="back-login">
            <a href="login.html">← Back to Login</a>
        </div>
    </div>

    <script>
    // Password toggle functionality
    function setupPasswordToggle(toggleId, inputId) {
        const toggle = document.getElementById(toggleId);
        const input = document.getElementById(inputId);
        const icon = toggle.querySelector('i');
        
        toggle.addEventListener('click', function() {
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                toggle.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                toggle.setAttribute('aria-label', 'Show password');
            }
        });
    }
    
    // Setup password toggles
    setupPasswordToggle('toggleNewPassword', 'new_password');
    setupPasswordToggle('toggleConfirmPassword', 'confirm_password');
    
    // Password confirmation validation
    document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
        var newPassword = document.getElementById('new_password').value;
        var confirmPassword = document.getElementById('confirm_password').value;
        
        if (newPassword.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters long.');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match.');
            return;
        }
    });
    
    // Real-time password matching
    document.getElementById('confirm_password').addEventListener('input', function() {
        var newPassword = document.getElementById('new_password').value;
        var confirmPassword = this.value;
        
        if (confirmPassword && newPassword !== confirmPassword) {
            this.style.borderColor = '#c33';
        } else {
            this.style.borderColor = '#e1e5e9';
        }
    });
    </script>
</body>
</html>
