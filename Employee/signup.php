<?php
session_start();
require_once 'db.php';

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
        $error_message = "Password must be at least 8 characters long, contain at least 1 capital letter and 1 number.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM employee_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "An account with this email already exists.";
        } else {
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO employee_users (firstname, lastname, email, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $firstname, $lastname, $email, $hashed_password);
            
            if ($stmt->execute()) {
                // Redirect to login page after successful signup
                header('Location: login.php?success=account_created');
                exit();
            } else {
                $error_message = "Error creating account. Please try again.";
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
    <title>Sign Up - WorkConnect</title>
    <link rel="stylesheet" href="../assets/css/Employee-signup.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="signup-container">
        <div class="logo-section">
            <img src="../assets/image/PESO Logo circle.png" alt="PESO Logo" class="logo">
            <h1 class="brand">WorkConnect</h1>
        </div>
        
        <h2 class="signup-title">Create Your Account</h2>
        
        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="firstname">First Name</label>
                    <input type="text" id="firstname" name="firstname" required 
                           value="<?php echo htmlspecialchars($firstname ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="lastname">Last Name</label>
                    <input type="text" id="lastname" name="lastname" required 
                           value="<?php echo htmlspecialchars($lastname ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" maxlength="40" required 
                       value="<?php echo htmlspecialchars($email ?? ''); ?>">
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
            </div>
            
            <button type="submit" class="signup-btn">Create Account</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
        
        <div class="back-home">
            <a href="home.html">← Back to Home</a>
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
    </style>
</body>
</html>
