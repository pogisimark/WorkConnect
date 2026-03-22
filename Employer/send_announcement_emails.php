<?php
/**
 * Send announcement email to all jobseekers with valid email addresses.
 * Called when an announcement is published (create, update, or change status).
 */
function sendAnnouncementEmailsToJobseekers($title, $description) {
    global $conn;
    
    if (!$conn || $conn->connect_error) {
        error_log("sendAnnouncementEmailsToJobseekers: Database connection error");
        return ['sent' => 0, 'total' => 0];
    }
    
    // Get all jobseeker emails (distinct, valid only)
    $stmt = $conn->prepare("SELECT DISTINCT email FROM jobseeker WHERE email IS NOT NULL AND TRIM(email) != '' AND email LIKE '%@%'");
    if (!$stmt || !$stmt->execute()) {
        error_log("sendAnnouncementEmailsToJobseekers: Failed to fetch jobseeker emails");
        return ['sent' => 0, 'total' => 0];
    }
    
    $result = $stmt->get_result();
    $emails = [];
    while ($row = $result->fetch_assoc()) {
        $email = trim($row['email']);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emails[] = $email;
        }
    }
    $stmt->close();
    
    $total = count($emails);
    if ($total === 0) {
        return ['sent' => 0, 'total' => 0];
    }
    
    // Load PHPMailer
    $phpmailer_available = false;
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            require_once __DIR__ . '/email_config.php';
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                $phpmailer_available = true;
            }
        } catch (Exception $e) {
            error_log("sendAnnouncementEmailsToJobseekers: PHPMailer load failed - " . $e->getMessage());
        }
    }
    
    $description_snippet = strlen($description) > 300 ? substr($description, 0, 300) . '...' : $description;
    $description_html = nl2br(htmlspecialchars($description_snippet));
    
    $subject = "New Announcement: " . htmlspecialchars($title) . " - WorkConnect";
    
    $message = "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.7; color: #2c3e50; background-color: #f4f6f8; }
            .email-wrapper { max-width: 600px; margin: 40px auto; background-color: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #1a3876 0%, #2c5aa0 100%); color: #fff; padding: 40px 30px; text-align: center; }
            .header h1 { font-size: 28px; font-weight: 600; margin: 0; }
            .header .tagline { font-size: 14px; opacity: 0.9; margin-top: 8px; }
            .content { padding: 40px 30px; }
            .announcement-title { font-size: 22px; font-weight: 600; color: #1a3876; margin-bottom: 20px; }
            .announcement-body { font-size: 15px; color: #555; line-height: 1.8; }
            .cta-box { background: #e8f0fe; border-left: 4px solid #1a3876; padding: 20px; margin: 25px 0; border-radius: 4px; }
            .cta-box p { margin: 0; font-size: 15px; color: #333; }
            .footer { background: #f8f9fa; padding: 25px; text-align: center; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='email-wrapper'>
            <div class='header'>
                <h1>WorkConnect</h1>
                <div class='tagline'>Connecting Talent with Opportunity</div>
            </div>
            <div class='content'>
                <h2 class='announcement-title'>" . htmlspecialchars($title) . "</h2>
                <div class='announcement-body'>" . $description_html . "</div>
                <div class='cta-box'>
                    <p><strong>Log in to your WorkConnect dashboard</strong> to view the full announcement and stay updated on job opportunities.</p>
                </div>
            </div>
            <div class='footer'>
                <p><strong>WorkConnect</strong> | Public Employment Service Office</p>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>&copy; " . date('Y') . " WorkConnect. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $sent = 0;
    
    if ($phpmailer_available) {
        foreach ($emails as $to_email) {
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = SMTP_PORT;
                $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                $mail->addAddress($to_email);
                $mail->isHTML(true);
                $mail->CharSet = 'UTF-8';
                $mail->Subject = $subject;
                $mail->Body = $message;
                $mail->send();
                $sent++;
            } catch (Exception $e) {
                error_log("sendAnnouncementEmailsToJobseekers: Failed to send to $to_email - " . $e->getMessage());
            }
        }
    } else {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: WorkConnect <noreply@workconnect.com>\r\n";
        foreach ($emails as $to_email) {
            if (@mail($to_email, $subject, $message, $headers)) {
                $sent++;
            }
        }
    }
    
    return ['sent' => $sent, 'total' => $total];
}
