<?php
session_start();
require_once 'db.php';
require_once 'company_verification_schema.php';
require_once 'company_peso_schema.php';

ensureCompanyVerificationSchema($conn);
ensureCompanyPesoSchema($conn);

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$message = '';
$success = false;

if ($token === '' || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
    $message = 'Invalid or missing verification link.';
} else {
    $stmt = $conn->prepare("SELECT id, email_verify_expires FROM company_users WHERE email_verify_token = ? AND email_verified = 0 LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $expires = strtotime($row['email_verify_expires'] ?? '');
        if ($expires && time() > $expires) {
            $message = 'This verification link has expired. Please request a new one from the company login page.';
        } else {
            $hasPeso = companyHasPesoColumn($conn);
            $clear = $hasPeso
                ? $conn->prepare('UPDATE company_users SET email_verified = 1, peso_verified = 1, email_verify_token = NULL, email_verify_expires = NULL WHERE id = ?')
                : $conn->prepare('UPDATE company_users SET email_verified = 1, email_verify_token = NULL, email_verify_expires = NULL WHERE id = ?');
            $clear->bind_param("i", $row['id']);
            if ($clear->execute()) {
                $success = true;
                $message = 'Your email has been verified. You can now log in to your company account.';
            } else {
                $message = 'Could not complete verification. Please try again.';
            }
            $clear->close();
        }
    } else {
        $message = 'This link is invalid or your account is already verified.';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel='icon' type='image/png' href='/assets/image/PESO Logo circle.png'>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email verification - WorkConnect</title>
    <link rel="stylesheet" href="../assets/css/Employee-login.css">
</head>
<body>
<div class="login-container" style="max-width: 480px;">
        <div class="logo-section">
            <img src="../assets/image/PESO Logo circle.png" alt="PESO Logo" class="logo">
            <h1 class="brand">WorkConnect</h1>
        </div>
        <h2 class="login-title">Company account</h2>
        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($message); ?></div>
        <?php else: ?>
            <div class="error-message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <div class="signup-link" style="margin-top: 1rem;">
            <a href="login.php">Go to Company Login</a>
        </div>
    </div>
</body>
</html>
