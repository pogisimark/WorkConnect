<?php
/**
 * Shared HTML email sender for company flows (SMTP via Employer/email_config.php).
 * @return array{success:bool, message:string}
 */
function workconnect_company_send_html(string $toEmail, string $subject, string $htmlBody): array {
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
            $mail->Body    = $htmlBody;
            $mail->send();
            return ['success' => true, 'message' => 'Sent.'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Could not send email.'];
        }
    }

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: WorkConnect <noreply@workconnect.local>\r\n";
    if (@mail($toEmail, $subject, $htmlBody, $headers)) {
        return ['success' => true, 'message' => 'Sent.'];
    }
    return ['success' => false, 'message' => 'Could not send email. Configure SMTP in Employer/email_config.php.'];
}

/**
 * Full URL to the admin Companies page for outbound emails (PESO “Open Companies” button).
 * Always the live site so the link is never localhost.
 * Change the path if production is not under /WorkConnect/.
 */
function workconnect_peso_admin_companies_list_url(): string {
    return 'https://www.workconnect.site/WorkConnect/Employer/companies_list.php';
}

/**
 * Company login URL for outbound emails (“Go to company login” after PESO approval).
 * Always the live site so the link is never localhost.
 */
function workconnect_public_company_login_url(): string {
    return 'https://www.workconnect.site/WorkConnect/Company/login.php';
}

/** PESO inbox for new company notifications (same system Gmail). */
function workconnect_peso_notification_email(): string {
    if (file_exists(__DIR__ . '/../Employer/email_config.php')) {
        require_once __DIR__ . '/../Employer/email_config.php';
        if (defined('SMTP_FROM_EMAIL') && SMTP_FROM_EMAIL !== '') {
            return SMTP_FROM_EMAIL;
        }
    }
    return 'workconnect576@gmail.com';
}
