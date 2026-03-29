<?php
require_once __DIR__ . '/company_mailer.php';

/**
 * Send WorkConnect company signup verification email (legacy email-link flow; resend still uses this).
 * @return array{success:bool, message:string}
 */
function sendCompanyVerificationEmail(string $toEmail, string $companyName, string $verifyLink): array {
    $subject = 'Verify your WorkConnect company account';
    $safeName = htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($verifyLink, ENT_QUOTES, 'UTF-8');
    $message = "
    <html>
    <head>
    <link rel='icon' type='image/png' href='/assets/image/PESO Logo circle.png'><title>Verify Email</title></head>
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

    $r = workconnect_company_send_html($toEmail, $subject, $message);
    if ($r['success']) {
        return ['success' => true, 'message' => 'Verification email sent.'];
    }
    return ['success' => false, 'message' => 'Could not send verification email. Please try again later or contact support.'];
}
