<?php
require_once __DIR__ . '/company_mailer.php';

/**
 * Company: after signup — wait for PESO approval (no login until approved).
 */
function sendCompanySignupPendingEmail(string $toEmail, string $companyName): array {
    $safe = htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8');
    $html = "
    <html><body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
        <h2 style='color: #1a3876;'>Registration received</h2>
        <p>Hello {$safe},</p>
        <p>Thank you for signing up for a <strong>WorkConnect</strong> company account.</p>
        <p>Your registration was received successfully. <strong>Please wait for the PESO (Public Employment Service Office) to verify your account</strong> before you can log in to the company portal.</p>
        <p>You will receive another email once your account has been approved.</p>
        <hr style='margin: 24px 0; border: none; border-top: 1px solid #eee;'>
        <p style='color: #666; font-size: 14px;'>WorkConnect — Public Employment Service Office</p>
    </div></body></html>";
    return workconnect_company_send_html($toEmail, 'WorkConnect — company registration received (pending PESO verification)', $html);
}

/**
 * PESO: notify that a new company registered and needs review.
 */
function sendPesoNewCompanyNotificationEmail(
    string $pesoInboxEmail,
    string $companyName,
    string $companyEmail,
    string $contactNumber,
    string $telephoneNumber,
    string $adminCompaniesUrl
): array {
    $safeName = htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($companyEmail, ENT_QUOTES, 'UTF-8');
    $safeContact = htmlspecialchars($contactNumber !== '' ? $contactNumber : '—', ENT_QUOTES, 'UTF-8');
    $safeTel = htmlspecialchars($telephoneNumber !== '' ? $telephoneNumber : '—', ENT_QUOTES, 'UTF-8');
    $safeUrl = htmlspecialchars($adminCompaniesUrl, ENT_QUOTES, 'UTF-8');
    $html = "
    <html><body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
        <h2 style='color: #1a3876;'>New company registration</h2>
        <p>A new company has signed up on WorkConnect and is <strong>waiting for PESO verification</strong>.</p>
        <table style='border-collapse: collapse; width: 100%; max-width: 480px;'>
            <tr><td style='padding: 6px 0; font-weight: bold; width: 140px;'>Company</td><td>{$safeName}</td></tr>
            <tr><td style='padding: 6px 0; font-weight: bold;'>Email</td><td>{$safeEmail}</td></tr>
            <tr><td style='padding: 6px 0; font-weight: bold;'>Contact #</td><td>{$safeContact}</td></tr>
            <tr><td style='padding: 6px 0; font-weight: bold;'>Telephone</td><td>{$safeTel}</td></tr>
        </table>
        <p style='margin-top: 20px;'><a href='{$safeUrl}' style='background: #1a3876; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px; display: inline-block;'>Open Companies (admin)</a></p>
        <p style='color: #666; font-size: 14px;'>Review pending companies at the bottom of the Companies page and click <strong>Verify</strong> when ready.</p>
    </div></body></html>";
    return workconnect_company_send_html($pesoInboxEmail, 'WorkConnect — new company needs PESO verification', $html);
}

/**
 * Company: PESO approved — can log in.
 */
function sendCompanyPesoApprovedEmail(string $toEmail, string $companyName, string $loginUrl): array {
    $safe = htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8');
    $safeLogin = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');
    $html = "
    <html><body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
        <h2 style='color: #2e7d32;'>Account verified</h2>
        <p>Hello {$safe},</p>
        <p>Your company account on <strong>WorkConnect</strong> has been <strong>verified by PESO</strong>. You can now log in to the company portal using your email and password.</p>
        <p style='text-align: center; margin: 28px 0;'>
            <a href='{$safeLogin}' style='background: #1a3876; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;'>Go to company login</a>
        </p>
        <hr style='margin: 24px 0; border: none; border-top: 1px solid #eee;'>
        <p style='color: #666; font-size: 14px;'>WorkConnect — Public Employment Service Office</p>
    </div></body></html>";
    return workconnect_company_send_html($toEmail, 'WorkConnect — your company account is verified', $html);
}
