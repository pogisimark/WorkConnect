<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Suppress error output
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any unwanted output
ob_start();

header('Content-Type: application/json');

// Check session manually instead of using session_check.php to avoid redirects
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if company is logged in
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['company_id']) || !isset($_SESSION['email']) || !isset($_SESSION['company_name'])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit;
}

require_once 'db.php';
require_once '../Employee/create_notification.php';
require_once __DIR__ . '/../Employer/referrals_schema.php';
require_once __DIR__ . '/../Employer/job_applications_withdraw_helper.php';

// Check if PHPMailer is available and load it
$phpmailer_available = false;
if (file_exists('../vendor/autoload.php')) {
    try {
        require_once '../vendor/autoload.php';
        // Suppress any output from email_config
        ob_start();
        require_once '../Employer/email_config.php';
        ob_end_clean();
        // Check if PHPMailer class exists
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $phpmailer_available = true;
        }
    } catch (Exception $e) {
        // PHPMailer not available, will use fallback
        $phpmailer_available = false;
        ob_end_clean(); // Clean any output from failed require
    } catch (Error $e) {
        // PHPMailer not available, will use fallback
        $phpmailer_available = false;
        ob_end_clean(); // Clean any output from failed require
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['action']) || !isset($input['jobseeker_id'])) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            exit;
        }
        
        $action = $input['action'];
        $jobseeker_id = intval($input['jobseeker_id']);
        $company_id = $_SESSION['company_id'];
        $company_name = $_SESSION['company_name'] ?? 'the company';
        
        // Get company details for email
        $company_columns_check = $conn->query("SHOW COLUMNS FROM company_users");
        $company_columns = [];
        if ($company_columns_check) {
            while ($row = $company_columns_check->fetch_assoc()) {
                $company_columns[] = $row['Field'];
            }
        }
        
        $company_select_fields = ['id', 'company_name', 'email'];
        $company_profile_fields = ['phone', 'address', 'website', 'description'];
        foreach ($company_profile_fields as $field) {
            if (in_array($field, $company_columns)) {
                $company_select_fields[] = $field;
            }
        }
        
        $company_query = "SELECT " . implode(', ', $company_select_fields) . " FROM company_users WHERE id = ?";
        $company_stmt = $conn->prepare($company_query);
        $company_stmt->bind_param("i", $company_id);
        $company_stmt->execute();
        $company_result = $company_stmt->get_result();
        $company_data = $company_result->fetch_assoc();
        $company_stmt->close();
        
        $company_email = $company_data['email'] ?? $_SESSION['email'] ?? '';
        $company_phone = (in_array('phone', $company_columns) && isset($company_data['phone'])) ? $company_data['phone'] : '';
        $company_address = (in_array('address', $company_columns) && isset($company_data['address'])) ? $company_data['address'] : '';
        $company_website = (in_array('website', $company_columns) && isset($company_data['website'])) ? $company_data['website'] : '';
        
        // Get jobseeker information (include user_id for in-app notifications)
        $stmt = $conn->prepare("SELECT firstname, surname, middlename, email, application_status, user_id, referred_to_company_id FROM jobseeker WHERE id = ?");
        if (!$stmt) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            exit;
        }
        
        $stmt->bind_param("i", $jobseeker_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Jobseeker not found']);
            exit;
        }
        
        $jobseeker = $result->fetch_assoc();
        
        // Verify that the jobseeker is in 'Referred' status
        if (strtolower($jobseeker['application_status']) !== 'referred') {
            $stmt->close();
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'This jobseeker is not in Referred status']);
            exit;
        }

        ensure_jobseeker_referrals_table($conn);

        $refStmt = $conn->prepare("SELECT id, status FROM jobseeker_company_referrals WHERE jobseeker_id = ? AND company_id = ?");
        $refStmt->bind_param("ii", $jobseeker_id, $company_id);
        $refStmt->execute();
        $refRow = $refStmt->get_result()->fetch_assoc();
        $refStmt->close();

        if (!$refRow) {
            $legacyCid = isset($jobseeker['referred_to_company_id']) ? (int)$jobseeker['referred_to_company_id'] : 0;
            if ($legacyCid === (int)$company_id) {
                $insLegacy = $conn->prepare("INSERT INTO jobseeker_company_referrals (jobseeker_id, company_id, status) VALUES (?, ?, 'pending')");
                $insLegacy->bind_param("ii", $jobseeker_id, $company_id);
                $insLegacy->execute();
                $insLegacy->close();
                $refRow = ['status' => 'pending'];
            }
        }

        if (!$refRow || strtolower($refRow['status']) !== 'pending') {
            $stmt->close();
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'This jobseeker was not referred to your company, or you have already responded.']);
            exit;
        }
        
        $jobseeker_email = $jobseeker['email'];
        $jobseeker_name = trim(($jobseeker['firstname'] ?? '') . ' ' . ($jobseeker['middlename'] && $jobseeker['middlename'] !== 'n/a' ? $jobseeker['middlename'] . ' ' : '') . ($jobseeker['surname'] ?? ''));
        if (empty($jobseeker_name)) {
            $jobseeker_name = 'Applicant';
        }
        $stmt->close();
        
        // Try to get job posting information if the jobseeker has an application
        $job_details = null;
        $job_title = 'the position';
        
        // Check if company_id column exists in job_postings
        $check_company_id = $conn->query("SHOW COLUMNS FROM job_postings LIKE 'company_id'");
        $has_company_id = $check_company_id && $check_company_id->num_rows > 0;
        
        if ($has_company_id) {
            // Query with company_id filter
            $job_stmt = $conn->prepare("
                SELECT jp.title, jp.company, jp.location, jp.job_type, jp.salary_range, jp.description, jp.requirements 
                FROM job_applications_extended jae
                JOIN job_postings jp ON jae.job_posting_id = jp.id
                WHERE jae.jobseeker_id = ? AND jp.company_id = ?
                ORDER BY jae.applied_date DESC
                LIMIT 1
            ");
            if ($job_stmt) {
                $job_stmt->bind_param("ii", $jobseeker_id, $company_id);
                $job_stmt->execute();
                $job_result = $job_stmt->get_result();
                if ($job_result->num_rows > 0) {
                    $job_details = $job_result->fetch_assoc();
                    $job_title = $job_details['title'] ?? 'the position';
                }
                $job_stmt->close();
            }
        } else {
            // Query without company_id filter (fallback)
            $job_stmt = $conn->prepare("
                SELECT jp.title, jp.company, jp.location, jp.job_type, jp.salary_range, jp.description, jp.requirements 
                FROM job_applications_extended jae
                JOIN job_postings jp ON jae.job_posting_id = jp.id
                WHERE jae.jobseeker_id = ? AND jp.company = ?
                ORDER BY jae.applied_date DESC
                LIMIT 1
            ");
            if ($job_stmt) {
                $job_stmt->bind_param("is", $jobseeker_id, $company_name);
                $job_stmt->execute();
                $job_result = $job_stmt->get_result();
                if ($job_result->num_rows > 0) {
                    $job_details = $job_result->fetch_assoc();
                    $job_title = $job_details['title'] ?? 'the position';
                }
                $job_stmt->close();
            }
        }
    
        if ($action === 'accept') {
            $stmtRef = $conn->prepare("UPDATE jobseeker_company_referrals SET status = 'accepted' WHERE jobseeker_id = ? AND company_id = ? AND status = 'pending'");
            if (!$stmtRef) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                exit;
            }
            $stmtRef->bind_param("ii", $jobseeker_id, $company_id);
            
            if ($stmtRef->execute() && $stmtRef->affected_rows > 0) {
                $stmtRef->close();
                $stmtWd = $conn->prepare("UPDATE jobseeker_company_referrals SET status = 'withdrawn' WHERE jobseeker_id = ? AND company_id != ? AND status = 'pending'");
                if ($stmtWd) {
                    $stmtWd->bind_param("ii", $jobseeker_id, $company_id);
                    $stmtWd->execute();
                    $stmtWd->close();
                }
                // Jobseeker accepted via referral — withdraw all open job applications (recommended jobs)
                withdraw_open_job_applications_for_jobseeker($conn, $jobseeker_id, 0);
                $stmt = $conn->prepare("UPDATE jobseeker SET application_status = 'Accepted' WHERE id = ? AND application_status = 'Referred'");
                $stmt->bind_param("i", $jobseeker_id);
                $stmt->execute();
                $stmt->close();
                
                // Send acceptance email
                $subject = "Congratulations! Your Referral Has Been Considered - WorkConnect";
                
                // Build company contact information section
                $company_contact_html = "";
                if ($company_email || $company_phone || $company_address || $company_website) {
                    $company_contact_html = "<div class='company-details'>
                        <h3 style='color: #1a3876; margin-bottom: 15px; font-size: 1.2rem;'>Company Contact Information</h3>";
                    
                    if ($company_email) {
                        $company_contact_html .= "<div class='contact-item' style='margin-bottom: 10px;'>
                            <strong style='color: #333;'>Email:</strong> 
                            <a href='mailto:" . htmlspecialchars($company_email) . "' style='color: #1a3876; text-decoration: none;'>" . htmlspecialchars($company_email) . "</a>
                        </div>";
                    }
                    
                    if ($company_phone) {
                        $company_contact_html .= "<div class='contact-item' style='margin-bottom: 10px;'>
                            <strong style='color: #333;'>Phone:</strong> 
                            <a href='tel:" . htmlspecialchars($company_phone) . "' style='color: #1a3876; text-decoration: none;'>" . htmlspecialchars($company_phone) . "</a>
                        </div>";
                    }
                    
                    if ($company_address) {
                        $company_contact_html .= "<div class='contact-item' style='margin-bottom: 10px;'>
                            <strong style='color: #333;'>Address:</strong> 
                            <span style='color: #666;'>" . htmlspecialchars($company_address) . "</span>
                        </div>";
                    }
                    
                    if ($company_website) {
                        $company_contact_html .= "<div class='contact-item' style='margin-bottom: 10px;'>
                            <strong style='color: #333;'>Website:</strong> 
                            <a href='" . htmlspecialchars($company_website) . "' target='_blank' style='color: #1a3876; text-decoration: none;'>" . htmlspecialchars($company_website) . "</a>
                        </div>";
                    }
                    
                    $company_contact_html .= "<p style='margin-top: 15px; color: #666; font-size: 14px;'>If you have any questions or need further information, please feel free to contact us using the details above.</p>
                    </div>";
                }
                
                $message = "
                <!DOCTYPE html>
                <html lang='en'>
                <head>
    <link rel="icon" type="image/png" href="/assets/image/PESO Logo circle.png">
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <style>
                        * { margin: 0; padding: 0; box-sizing: border-box; }
                        body { 
                            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                            line-height: 1.7; 
                            color: #2c3e50; 
                            background-color: #f4f6f8;
                        }
                        .email-wrapper {
                            max-width: 600px;
                            margin: 40px auto;
                            background-color: #ffffff;
                            border-radius: 8px;
                            overflow: hidden;
                            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                        }
                        .header {
                            background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
                            color: #ffffff;
                            padding: 40px 30px;
                            text-align: center;
                        }
                        .header h1 {
                            font-size: 28px;
                            font-weight: 600;
                            margin: 0;
                        }
                        .content {
                            padding: 50px 40px;
                            background-color: #ffffff;
                        }
                        .success-icon {
                            text-align: center;
                            font-size: 64px;
                            color: #4caf50;
                            margin-bottom: 20px;
                        }
                        .company-details {
                            background: #f8f9fa;
                            border-radius: 8px;
                            padding: 20px;
                            margin: 25px 0;
                            border-left: 4px solid #1a3876;
                        }
                        .contact-item {
                            color: #666;
                        }
                        .footer {
                            background: #f8f9fa;
                            padding: 30px;
                            text-align: center;
                            color: #666;
                            font-size: 14px;
                        }
                    </style>
                </head>
                <body>
<div class='email-wrapper'>
                        <div class='header'>
                            <h1>🎉 Congratulations!</h1>
                        </div>
                        <div class='content'>
                            <div class='success-icon'>✓</div>
                            <h2 style='color: #1a3876; margin-bottom: 20px;'>Dear " . htmlspecialchars($jobseeker_name) . ",</h2>
                            <p style='margin-bottom: 20px; font-size: 16px;'>Congratulations! We are pleased to inform you that <strong style='color: #4caf50;'>" . htmlspecialchars($company_name) . "</strong> has considered your referral and is interested in your profile.</p>
                            <p style='margin-bottom: 20px; font-size: 16px;'>The company will call or email you shortly for more details regarding the next steps. Please ensure your contact information is up to date and check your email and phone regularly.</p>
                            " . $company_contact_html . "
                            <p style='margin-top: 30px; font-size: 16px;'>We wish you the best of luck and look forward to hearing about your success!</p>
                            <p style='margin-top: 15px; color: #666;'>Best regards,<br><strong>The WorkConnect Team</strong></p>
                        </div>
                        <div class='footer'>
                            <p><strong>WorkConnect</strong></p>
                            <p>Connecting Talent with Opportunity</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                // Send email
                $email_sent = false;
                if ($phpmailer_available && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
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
                        $mail->addAddress($jobseeker_email);
                        $mail->isHTML(true);
                        $mail->CharSet = 'UTF-8';
                        $mail->Subject = $subject;
                        $mail->Body = $message;
                        $mail->send();
                        $email_sent = true;
                    } catch (\Exception $e) {
                        error_log("Email sending failed: " . $e->getMessage());
                    }
                } else {
                    // Fallback to PHP mail()
                    $headers = "MIME-Version: 1.0" . "\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                    $headers .= "From: WorkConnect <noreply@workconnect.com>" . "\r\n";
                    $email_sent = mail($jobseeker_email, $subject, $message, $headers);
                }
                
                $response = [
                    'success' => true,
                    'message' => $email_sent ? 'Jobseeker accepted and email sent successfully.' : 'Jobseeker accepted. Email notification may not have been sent.'
                ];
                if (!empty($jobseeker['user_id'])) {
                    createNotification(
                        (int)$jobseeker['user_id'],
                        'Application Accepted',
                        "Great news! {$company_name} accepted your referred application.",
                        'application'
                    );
                }
            } else {
                $error_msg = $stmtRef->error ? $stmtRef->error : 'Could not update referral (already processed?)';
                $response = ['success' => false, 'message' => $error_msg];
                $stmtRef->close();
            }
            
            // Clean output and send JSON
            ob_clean();
            echo json_encode($response);
            exit;
            
        } elseif ($action === 'reject') {
            if (!isset($input['rejection_reason']) || empty(trim($input['rejection_reason']))) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
                exit;
            }
            
            $rejection_reason_raw = trim($input['rejection_reason']);
            $rejection_reason_html = htmlspecialchars($rejection_reason_raw, ENT_QUOTES, 'UTF-8');
            
            $stmtRef = $conn->prepare("UPDATE jobseeker_company_referrals SET status = 'rejected', rejection_reason = ? WHERE jobseeker_id = ? AND company_id = ? AND status = 'pending'");
            if (!$stmtRef) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                exit;
            }
            $stmtRef->bind_param("sii", $rejection_reason_raw, $jobseeker_id, $company_id);
            
            if (!$stmtRef->execute() || $stmtRef->affected_rows < 1) {
                $stmtRef->close();
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Could not record rejection (referral may already be updated).']);
                exit;
            }
            $stmtRef->close();

            // Sync job_applications_extended so Recommended Jobs shows "Rejected" (not "Applied") for this company's postings
            $checkCol = $conn->query("SHOW COLUMNS FROM job_postings LIKE 'company_id'");
            if ($checkCol && $checkCol->num_rows > 0) {
                $syncStmt = $conn->prepare("UPDATE job_applications_extended jae
                    INNER JOIN job_postings jp ON jae.job_posting_id = jp.id
                    SET jae.status = 'Rejected', jae.viewed_date = COALESCE(jae.viewed_date, NOW()), jae.notes = ?
                    WHERE jae.jobseeker_id = ? AND jp.company_id = ? AND jae.status IN ('Applied', 'Viewed', 'Interview')");
                if ($syncStmt) {
                    $syncStmt->bind_param("sii", $rejection_reason_raw, $jobseeker_id, $company_id);
                    $syncStmt->execute();
                    $syncStmt->close();
                }
            } else {
                $syncStmt = $conn->prepare("UPDATE job_applications_extended jae
                    INNER JOIN job_postings jp ON jae.job_posting_id = jp.id
                    SET jae.status = 'Rejected', jae.viewed_date = COALESCE(jae.viewed_date, NOW()), jae.notes = ?
                    WHERE jae.jobseeker_id = ? AND jp.company = ? AND jae.status IN ('Applied', 'Viewed', 'Interview')");
                if ($syncStmt) {
                    $syncStmt->bind_param("sis", $rejection_reason_raw, $jobseeker_id, $company_name);
                    $syncStmt->execute();
                    $syncStmt->close();
                }
            }

            $cntP = $conn->prepare("SELECT COUNT(*) AS c FROM jobseeker_company_referrals WHERE jobseeker_id = ? AND status = 'pending'");
            $cntP->bind_param("i", $jobseeker_id);
            $cntP->execute();
            $pendingCount = (int)($cntP->get_result()->fetch_assoc()['c'] ?? 0);
            $cntP->close();

            $cntA = $conn->prepare("SELECT COUNT(*) AS c FROM jobseeker_company_referrals WHERE jobseeker_id = ? AND status = 'accepted'");
            $cntA->bind_param("i", $jobseeker_id);
            $cntA->execute();
            $acceptedCount = (int)($cntA->get_result()->fetch_assoc()['c'] ?? 0);
            $cntA->close();

            if ($pendingCount === 0 && $acceptedCount === 0) {
                $stmtJs = $conn->prepare("UPDATE jobseeker SET application_status = 'Rejected', rejection_reason = ? WHERE id = ? AND application_status = 'Referred'");
                if ($stmtJs) {
                    $stmtJs->bind_param("si", $rejection_reason_raw, $jobseeker_id);
                    $stmtJs->execute();
                    $stmtJs->close();
                }
            }
                
                // Referral-specific rejection (different from View Applicants rejection in handle_application.php)
                $subject = 'Update on your referral to ' . $company_name . ' – WorkConnect';
                
                $referral_context = "
                            <div class='referral-box'>
                                <h3 style='color: #1a3876; margin-bottom: 12px; font-size: 1.05rem;'>Your referral</h3>
                                <p style='margin: 0 0 8px; color: #444;'>We referred you to <strong>" . htmlspecialchars($company_name) . "</strong> because we believed your profile could be a good match.</p>
                                <p style='margin: 0; color: #555; font-size: 15px;'><strong>Role / opportunity:</strong> " . htmlspecialchars($job_title) . "</p>";
                if ($job_details) {
                    $referral_context .= "
                                <p style='margin: 8px 0 0; color: #666; font-size: 14px;'>" . htmlspecialchars($job_details['location'] ?? '') . "</p>";
                }
                $referral_context .= "
                            </div>";
                
                $rejection_message = "
                <!DOCTYPE html>
                <html lang='en'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <style>
                        * { margin: 0; padding: 0; box-sizing: border-box; }
                        body { 
                            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                            line-height: 1.7; 
                            color: #2c3e50; 
                            background-color: #f4f6f8;
                        }
                        .email-wrapper {
                            max-width: 600px;
                            margin: 40px auto;
                            background-color: #ffffff;
                            border-radius: 8px;
                            overflow: hidden;
                            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                        }
                        .header {
                            background: linear-gradient(135deg, #1a3876 0%, #2c5aa0 100%);
                            color: #ffffff;
                            padding: 36px 30px;
                            text-align: center;
                        }
                        .header h1 {
                            font-size: 24px;
                            font-weight: 600;
                            margin: 0;
                        }
                        .content {
                            padding: 44px 40px;
                            background-color: #ffffff;
                        }
                        .referral-box {
                            background: #f0f4fb;
                            border-radius: 8px;
                            padding: 20px;
                            margin: 22px 0;
                            border-left: 4px solid #1a3876;
                        }
                        .rejection-reason {
                            background: #fff8e6;
                            border-left: 4px solid #e6a100;
                            padding: 16px 18px;
                            margin: 22px 0;
                            border-radius: 4px;
                        }
                        .rejection-reason h3 {
                            color: #6d4c00;
                            margin-bottom: 10px;
                            font-size: 1rem;
                        }
                        .footer {
                            background: #f8f9fa;
                            padding: 28px;
                            text-align: center;
                            color: #666;
                            font-size: 14px;
                        }
                    </style>
                </head>
                <body>
                    <div class='email-wrapper'>
                        <div class='header'>
                            <h1>Message from WorkConnect</h1>
                        </div>
                        <div class='content'>
                            <h2 style='color: #1a3876; margin-bottom: 18px; font-size: 1.25rem;'>Dear " . htmlspecialchars($jobseeker_name) . ",</h2>
                            <p style='margin-bottom: 18px; font-size: 16px;'>We are sorry to share disappointing news, and we thank you for your patience.</p>
                            <p style='margin-bottom: 18px; font-size: 16px;'>At WorkConnect, we did our part to refer you to an employer we thought would fit your goals. Unfortunately, <strong>" . htmlspecialchars($company_name) . "</strong> has reviewed your information and has decided <strong>not to move forward</strong> with your referral at this time. This outcome reflects their current hiring needs and assessment—not your worth as a candidate.</p>
                            
                            <div class='rejection-reason'>
                                <h3>Feedback shared by the company</h3>
                                <p style='color: #333; margin: 0;'>" . nl2br($rejection_reason_html) . "</p>
                            </div>
                            <p style='margin-top: 26px; font-size: 16px;'><strong>Please don’t be discouraged.</strong> Many strong candidates receive a “no” and succeed elsewhere. We encourage you to keep your profile updated and to keep exploring opportunities through WorkConnect—we remain committed to helping you find the right fit.</p>
                            <p style='margin-top: 18px; font-size: 16px; color: #555;'>If you have questions or would like guidance on next steps, you can reach out to our team. We’re rooting for you.</p>
                            <p style='margin-top: 28px; font-size: 16px; color: #666;'>With support,<br><strong>The WorkConnect Team</strong></p>
                        </div>
                        <div class='footer'>
                            <p><strong>WorkConnect</strong></p>
                            <p>Connecting Talent with Opportunity</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                // Send email
                $email_sent = false;
                if ($phpmailer_available && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
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
                        $mail->addAddress($jobseeker_email);
                    $mail->isHTML(true);
                    $mail->CharSet = 'UTF-8';
                    $mail->Subject = $subject;
                    $mail->Body = $rejection_message;
                    $mail->send();
                    $email_sent = true;
                } catch (\Exception $e) {
                    error_log("Email sending failed: " . $e->getMessage());
                }
            } else {
                // Fallback to PHP mail()
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: WorkConnect <noreply@workconnect.com>" . "\r\n";
                $email_sent = mail($jobseeker_email, $subject, $rejection_message, $headers);
                }
                
                $response = [
                    'success' => true,
                    'message' => $email_sent ? 'Jobseeker rejected and email sent successfully.' : 'Jobseeker rejected. Email notification may not have been sent.'
                ];
                if (!empty($jobseeker['user_id'])) {
                    if ($pendingCount === 0 && $acceptedCount === 0) {
                        createNotification(
                            (int)$jobseeker['user_id'],
                            'Application Rejected',
                            "All referred employers have declined. Last update from {$company_name}.\n\nReason: {$rejection_reason_raw}",
                            'application'
                        );
                    } else {
                        $rr = trim($rejection_reason_raw);
                        createNotification(
                            (int)$jobseeker['user_id'],
                            'Referral update',
                            "{$company_name} declined your referral.\n\nReason: {$rr}.\n\nOther referred employers may still review your profile.",
                            'application'
                        );
                    }
                }
            
            // Clean output and send JSON
            ob_clean();
            echo json_encode($response);
            exit;
        } else {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
        }
    
    } catch (Exception $e) {
        ob_clean();
        error_log("Error in handle_referred.php: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        exit;
    } catch (Error $e) {
        ob_clean();
        error_log("Fatal error in handle_referred.php: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
        exit;
    }
} else {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (isset($conn)) {
    $conn->close();
}
?>
