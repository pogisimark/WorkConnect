<?php
// Start session with security settings
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security headers to prevent session sharing
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once 'db.php';
require_once 'company_verification_schema.php';

ensureCompanyVerificationSchema($conn);

$error_message = '';
$show_verify_swal = false;
$verify_swal_email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error_message = "Email and password are required.";
    } else {
        // Check company credentials
        $stmt = $conn->prepare("SELECT id, company_name, email, password, email_verified FROM company_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                if (isset($user['email_verified']) && (int)$user['email_verified'] !== 1) {
                    $show_verify_swal = true;
                    $verify_swal_email = $email;
                } else {
                    $_SESSION = array();
                    session_regenerate_id(true);
                    $_SESSION['created'] = time();
                    $_SESSION['last_activity'] = time();
                    $_SESSION['company_id'] = $user['id'];
                    $_SESSION['email'] = $email;
                    $_SESSION['company_name'] = $user['company_name'];
                    $_SESSION['logged_in'] = true;
                    
                    $stmt->close();
                    $conn->close();
                    
                    header('Location: dashboard.php');
                    exit();
                }
            } else {
                $error_message = "Invalid email or password.";
            }
        } else {
            $error_message = "Invalid email or password.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="/assets/image/PESO Logo circle.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Login - WorkConnect</title>
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
        
        <h2 class="login-title">Company Login</h2>
        
        <?php
        $show_url_success = ($_SERVER['REQUEST_METHOD'] !== 'POST' && !empty($_GET['success']));
        ?>
        <?php if ($show_url_success && $_GET['success'] === 'verify_sent'): ?>
            <div class="success-message">Account created! Check your email and click the verification link before logging in.</div>
        <?php elseif ($show_url_success && $_GET['success'] === 'account_created'): ?>
            <div class="success-message">Account created successfully! You can now login with your credentials.</div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" maxlength="40" required 
                       value="<?php echo htmlspecialchars($email ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-input-container">
                    <input type="password" id="password" name="password" maxlength="30" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePassword()"></i>
                </div>
            </div>
            
            <div class="forgot-password-link">
                <a href="#" onclick="showForgotPasswordModal()">Forgot your password?</a>
            </div>
            
            <button type="submit" class="login-btn" id="loginBtn">
                <span class="btn-text">Login</span>
                <div class="spinner" id="loginSpinner" style="display: none;">
                    <div class="spinner-inner"></div>
                </div>
            </button>
        </form>
        
        <div class="signup-link">
            Don't have an account? <a href="signup.php">Sign up here</a>
        </div>
        
        <div class="back-home">
            <a href="../index.php">← Back to Home</a>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgotPasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reset Password</h3>
                <span class="close" onclick="closeForgotPasswordModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p>Enter your email address and we'll send you a link to reset your password.</p>
                <form id="forgotPasswordForm">
                    <div class="form-group">
                        <label for="resetEmail">Email Address</label>
                        <input type="email" id="resetEmail" name="email" maxlength="40" required>
                    </div>
                    <button type="submit" class="reset-btn" id="resetBtn">
                        <span class="btn-text">Send Reset Link</span>
                        <div class="spinner" id="resetSpinner" style="display: none;">
                            <div class="spinner-inner"></div>
                        </div>
                    </button>
                </form>
            </div>
        </div>
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

        function showForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').style.display = 'block';
        }

        function closeForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').style.display = 'none';
            document.getElementById('forgotPasswordForm').reset();
            
            // Reset button state
            const resetBtn = document.getElementById('resetBtn');
            const btnText = document.querySelector('#resetBtn .btn-text');
            const spinner = document.getElementById('resetSpinner');
            
            resetBtn.disabled = false;
            btnText.style.display = 'inline';
            spinner.style.display = 'none';
        }

        // Handle forgot password form submission
        document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('resetEmail').value;
            const resetBtn = document.getElementById('resetBtn');
            const btnText = document.querySelector('#resetBtn .btn-text');
            const spinner = document.getElementById('resetSpinner');
            
            if (!email) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please enter your email address.'
                });
                return;
            }

            // Show loading state
            resetBtn.disabled = true;
            btnText.style.display = 'none';
            spinner.style.display = 'flex';

            // Send AJAX request to forgot password handler (Company-specific)
            fetch('forgot_password_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email: email })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Email Sent!',
                        text: 'If an account with that email exists, we\'ve sent you a password reset link.'
                    });
                    closeForgotPasswordModal();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Something went wrong. Please try again.'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Something went wrong. Please try again.'
                });
            })
            .finally(() => {
                // Reset button state
                resetBtn.disabled = false;
                btnText.style.display = 'inline';
                spinner.style.display = 'none';
            });
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('forgotPasswordModal');
            if (event.target === modal) {
                closeForgotPasswordModal();
            }
        }

        // Handle login form submission with loading spinner
        document.querySelector('form[method="POST"]').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('loginBtn');
            const btnText = document.querySelector('.btn-text');
            const spinner = document.getElementById('loginSpinner');
            
            // Show loading state
            loginBtn.disabled = true;
            btnText.style.display = 'none';
            spinner.style.display = 'flex';
        });

        <?php if (!empty($show_verify_swal)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const verifyEmail = <?php echo json_encode($verify_swal_email); ?>;
            const loginBtn = document.getElementById('loginBtn');
            const btnText = document.querySelector('#loginBtn .btn-text');
            const spinner = document.getElementById('loginSpinner');
            if (loginBtn) {
                loginBtn.disabled = false;
                if (btnText) btnText.style.display = 'inline';
                if (spinner) spinner.style.display = 'none';
            }
            Swal.fire({
                icon: 'warning',
                title: 'Verify your email',
                html: 'Please check your email and verify your company account before logging in.<br><br>If you did not receive the message, you can resend the verification link.',
                confirmButtonText: 'OK',
                showDenyButton: true,
                denyButtonText: 'Resend verification email',
                confirmButtonColor: '#1a3876',
                denyButtonColor: '#6c757d'
            }).then(function(result) {
                if (result.isDenied && verifyEmail) {
                    Swal.fire({
                        title: 'Sending verification email...',
                        html: 'Please wait while we send the link.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: function() {
                            Swal.showLoading();
                        }
                    });
                    fetch('resend_verification.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email: verifyEmail })
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        Swal.close();
                        Swal.fire({
                            icon: data.success ? 'success' : 'error',
                            title: data.success ? 'Sent' : 'Error',
                            text: data.message || (data.success ? 'Check your inbox.' : 'Could not resend.')
                        });
                    })
                    .catch(function() {
                        Swal.close();
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Could not resend. Try again later.' });
                    });
                }
            });
        });
        <?php endif; ?>

        // Auto-hide green/red login banners after 2 seconds
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.login-container > .success-message, .login-container > .error-message').forEach(function(el) {
                if (!el.textContent.trim()) return;
                setTimeout(function() {
                    el.style.transition = 'opacity 0.35s ease';
                    el.style.opacity = '0';
                    setTimeout(function() {
                        el.style.display = 'none';
                    }, 350);
                }, 2000);
            });
        });
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

        .forgot-password-link {
            text-align: right;
            margin: 15px 0;
        }

        .forgot-password-link a {
            color: #1a3876;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .forgot-password-link a:hover {
            color: #ffcb05;
            text-decoration: underline;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 0;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .modal-header {
            background: #1a3876;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 18px;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close:hover {
            color: #ffcb05;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-body p {
            margin-bottom: 20px;
            color: #666;
            font-size: 14px;
        }

        .reset-btn {
            width: 100%;
            background: #1a3876;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
        }

        .reset-btn:hover {
            background: #0f2a5c;
        }

        .reset-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .reset-btn {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
        }

        .reset-btn .btn-text {
            transition: opacity 0.3s ease;
        }

        .reset-btn .spinner {
            display: none;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .reset-btn .spinner-inner {
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Loading Spinner Styles */
        .login-btn {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
        }

        .btn-text {
            transition: opacity 0.3s ease;
        }

        .spinner {
            display: none;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .spinner-inner {
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .login-btn:disabled {
            opacity: 0.8;
            cursor: not-allowed;
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
    </style>
</body>
</html>

