<?php
session_start();
require_once 'db.php';
require_once 'company_verification_schema.php';
require_once 'company_peso_schema.php';
require_once 'company_signup_mail.php';
require_once 'company_contact_validate.php';

ensureCompanyVerificationSchema($conn);
ensureCompanyPesoSchema($conn);

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $contact_digits = workconnect_normalize_contact_digits($_POST['contact_number'] ?? '');
    $telephone_digits = workconnect_normalize_contact_digits($_POST['telephone_number'] ?? '');

    if (empty($company_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = 'All required fields must be filled.';
    } elseif ($contact_digits === '' && $telephone_digits === '') {
        $error_message = 'Please enter at least a contact number or a telephone number.';
    } elseif ($contact_digits !== '' && !workconnect_contact_mobile_valid($contact_digits)) {
        $error_message = 'Contact number must be 11 digits starting with 09 (format: 09XX-XXX-XXXX).';
    } elseif ($telephone_digits !== '' && !workconnect_telephone_landline_valid($telephone_digits)) {
        $error_message = 'Telephone / landline must be 8 digits starting with 8 (format: 8XXX-XXXX).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error_message = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
        $error_message = 'Password must be at least 8 characters long, contain at least 1 capital letter and 1 number.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } else {
        $stmt = $conn->prepare('SELECT id FROM company_users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error_message = 'An account with this email already exists.';
            $stmt->close();
        } else {
            $stmt->close();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $cn = $contact_digits;
            $tn = $telephone_digits;

            $stmt = $conn->prepare('INSERT INTO company_users (company_name, email, password, contact_number, telephone_number, email_verified, email_verify_token, email_verify_expires, peso_verified) VALUES (?, ?, ?, ?, ?, 0, NULL, NULL, 0)');
            $stmt->bind_param('sssss', $company_name, $email, $hashed_password, $cn, $tn);

            if ($stmt->execute()) {
                $adminCompaniesUrl = workconnect_peso_admin_companies_list_url();

                $sendCompany = sendCompanySignupPendingEmail($email, $company_name);
                if (!$sendCompany['success']) {
                    $newId = (int) $conn->insert_id;
                    if ($newId > 0) {
                        $del = $conn->prepare('DELETE FROM company_users WHERE id = ?');
                        $del->bind_param('i', $newId);
                        $del->execute();
                        $del->close();
                    }
                    $error_message = $sendCompany['message'] . ' Your account was not created.';
                } else {
                    $pesoInbox = workconnect_peso_notification_email();
                    sendPesoNewCompanyNotificationEmail(
                        $pesoInbox,
                        $company_name,
                        $email,
                        $contact_digits,
                        $telephone_digits,
                        $adminCompaniesUrl
                    );
                    header('Location: login.php?success=pending_peso');
                    exit();
                }
            } else {
                $error_message = 'Error creating account. Please try again.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel='icon' type='image/png' href='/assets/image/PESO Logo circle.png'>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Sign Up - WorkConnect</title>
    <link rel="stylesheet" href="../assets/css/Employee-signup.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="signup-container">
        <div class="logo-section">
            <img src="../assets/image/PESO Logo circle.png" alt="PESO Logo" class="logo">
            <h1 class="brand">WorkConnect</h1>
        </div>
        
        <h2 class="signup-title">Create Company Account</h2>
        
        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="company_name">Company Name</label>
                <input type="text" id="company_name" name="company_name" maxlength="40" required 
                       value="<?php echo htmlspecialchars($company_name ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" maxlength="40" required 
                       value="<?php echo htmlspecialchars($email ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="contact_number">Contact number <span style="color:#c62828;">*</span></label>
                <input type="text" id="contact_number" name="contact_number" maxlength="11" inputmode="numeric" autocomplete="tel"
                       pattern="09[0-9]{9}" title="11 digits starting with 09 (09XX-XXX-XXXX)"
                       placeholder="09XX-XXX-XXXX"
                       value="<?php echo htmlspecialchars($_POST['contact_number'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="telephone_number">Telephone / landline <span style="color:#666;font-weight:400;">(optional)</span></label>
                <input type="text" id="telephone_number" name="telephone_number" maxlength="8" inputmode="numeric" autocomplete="tel"
                       pattern="8[0-9]{7}" title="8 digits starting with 8 (8XXX-XXXX)"
                       placeholder="8XXX-XXXX"
                       value="<?php echo htmlspecialchars($_POST['telephone_number'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-input-container">
                    <input type="password" id="password" name="password" maxlength="30" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('password')"></i>
                </div>
                <div class="password-requirements">Minimum 8 characters, 1 capital letter, 1 number</div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-input-container">
                    <input type="password" id="confirm_password" name="confirm_password" maxlength="30" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password')"></i>
                </div>
                <div class="password-requirements" id="passwordMatchStatus" aria-live="polite"></div>
            </div>
            
            <button type="submit" class="signup-btn" id="signupBtn">Create Account</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
        
        <div class="back-home">
            <a href="../index.php">← Back to Home</a>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleIcon = passwordInput.parentElement.querySelector('.password-toggle');
            
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

        function updatePasswordMatch() {
            const pwd = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            const el = document.getElementById('passwordMatchStatus');
            if (!el) return;
            if (confirm.length === 0) {
                el.textContent = '';
                el.style.color = '#666';
                return;
            }
            if (pwd === confirm) {
                el.style.color = '#4caf50';
                el.textContent = '✓ Passwords match';
            } else {
                el.style.color = '#f44336';
                el.textContent = 'Passwords do not match';
            }
        }

        // Add event listener for password validation
        document.getElementById('password').addEventListener('input', function() {
            validatePassword();
            updatePasswordMatch();
        });
        document.getElementById('confirm_password').addEventListener('input', updatePasswordMatch);

        function workconnectDigitsOnlyMax(el, maxLen) {
            if (!el) return;
            el.addEventListener('input', function() {
                var d = this.value.replace(/\D/g, '').slice(0, maxLen);
                if (this.value !== d) {
                    this.value = d;
                }
            });
        }
        workconnectDigitsOnlyMax(document.getElementById('contact_number'), 11);
        workconnectDigitsOnlyMax(document.getElementById('telephone_number'), 8);

        document.querySelector('.signup-container form').addEventListener('submit', function(e) {
            var form = this;
            var c = (document.getElementById('contact_number').value || '').replace(/\D/g, '');
            var t = (document.getElementById('telephone_number').value || '').replace(/\D/g, '');
            if (!c && !t) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Contact required', text: 'Enter at least a contact number or a telephone number.' });
                }
                return false;
            }
            if (c && !/^09\d{9}$/.test(c)) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Invalid contact number', text: 'Use 11 digits starting with 09 (format: 09XX-XXX-XXXX).' });
                }
                return false;
            }
            if (t && !/^8\d{7}$/.test(t)) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Invalid telephone / landline', text: 'Use 8 digits starting with 8 (format: 8XXX-XXXX).' });
                }
                return false;
            }
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                return;
            }
            var btn = document.getElementById('signupBtn');
            if (btn) btn.disabled = true;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Creating your account...',
                    html: 'Please wait while we create your account and send confirmation emails.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: function() {
                        Swal.showLoading();
                    }
                });
            }
        });

        window.addEventListener('pageshow', function(ev) {
            if (ev.persisted) {
                if (typeof Swal !== 'undefined') Swal.close();
                var btn = document.getElementById('signupBtn');
                if (btn) btn.disabled = false;
            }
        });

        // Auto-hide success/error banners after 2 seconds
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.signup-container > .success-message, .signup-container > .error-message').forEach(function(el) {
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
    </style>
</body>
</html>

