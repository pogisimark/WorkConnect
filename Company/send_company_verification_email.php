<?php
/**
 * Send WorkConnect company signup verification email.
 * @return array{success:bool, message:string}
 */
function sendCompanyVerificationEmail(string $toEmail, string $companyName, string $verifyLink): array {
    $subject = 'Verify your WorkConnect company account';
    $safeName = htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($verifyLink, ENT_QUOTES, 'UTF-8');
    $message = "
    <html>
    <head><title>Verify Email</title></head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #1a3876;'>Verify your company email</h2>
            <p>Hello {$safeName},</p>
            <p>Thank you for registering a company account on WorkConnect. Please verify your email address to activate your account and log in.</p>
            <div style='text-align: center; margin: 30px 0;'>
                <a href='{$safeLink}' style='background: #1a3876; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>Verify my email</a>
            </div>
            <p>Or copy and paste this link into your browser:</p>
            <p style='word-break: break-all; background: #f5f5f5; padding: 10px; border-radius: 4px;'>{$safeLink}</p>
            <p><strong>This link expires in 48 hours.</strong></p>
            <p>If you did not register, you can ignore this email.</p>
            <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
            <p style='color: #666; font-size: 14px;'>Public Employment Service Office — WorkConnect</p>
        </div>
    </body>
    </html>";

    $phpmailer_available = false;
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
        if (file_exists(__DIR__ . '/../Employer/email_config.php')) {
            require_once __DIR__ . '/../Employer/email_config.php';
            $phpmailer_available = defined('SMTP_HOST') && defined('SMTP_USERNAME');
        }
    }

    if ($phpmailer_available) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->send();
            return ['success' => true, 'message' => 'Verification email sent.'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Could not send verification email. Please try again later or contact support.'];
        }
    }

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: WorkConnect <noreply@workconnect.local>\r\n";
    if (@mail($toEmail, $subject, $message, $headers)) {
        return ['success' => true, 'message' => 'Verification email sent.'];
    }
    return ['success' => false, 'message' => 'Could not send verification email. Configure SMTP in Employer/email_config.php.'];
}
