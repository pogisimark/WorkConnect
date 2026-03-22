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
require_once __DIR__ . '/../Employer/job_applications_withdraw_helper.php';
require_once __DIR__ . '/../Employer/referrals_schema.php';

// Check if PHPMailer is available and load it
$phpmailer_available = false;
if (file_exists('../vendor/autoload.php')) {
    try {
        require_once '../vendor/autoload.php';
        require_once '../Employer/email_config.php';
        // Check if PHPMailer class exists
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $phpmailer_available = true;
        }
    } catch (Exception $e) {
        // PHPMailer not available, will use fallback
        $phpmailer_available = false;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['action']) || !isset($input['application_id']) || !isset($input['jobseeker_id'])) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            exit;
        }
        
        $action = $input['action'];
        $application_id = intval($input['application_id']);
        $jobseeker_id = intval($input['jobseeker_id']);
        $job_id = intval($input['job_id'] ?? 0);
        $job_title = $conn->real_escape_string($input['job_title'] ?? 'the position');
        $company_name = $_SESSION['company_name'] ?? 'the company';
        
        // Get jobseeker information (include user_id for in-app notifications)
        $stmt = $conn->prepare("SELECT firstname, surname, middlename, email, user_id FROM jobseeker WHERE id = ?");
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
        $jobseeker_email = trim($jobseeker['email'] ?? '');
        $jobseeker_name = trim(($jobseeker['firstname'] ?? '') . ' ' . ($jobseeker['middlename'] && $jobseeker['middlename'] !== 'n/a' ? $jobseeker['middlename'] . ' ' : '') . ($jobseeker['surname'] ?? ''));
        if (empty($jobseeker_name)) {
            $jobseeker_name = 'Applicant';
        }
        $jobseeker_email_valid = !empty($jobseeker_email) && filter_var($jobseeker_email, FILTER_VALIDATE_EMAIL);
        $stmt->close();
    
    // Get job details
    $job_details = null;
    if ($job_id > 0) {
        $job_stmt = $conn->prepare("SELECT title, company, location, job_type, salary_range, description, requirements FROM job_postings WHERE id = ?");
        if ($job_stmt) {
            $job_stmt->bind_param("i", $job_id);
            $job_stmt->execute();
            $job_result = $job_stmt->get_result();
            if ($job_result->num_rows > 0) {
                $job_details = $job_result->fetch_assoc();
            }
            $job_stmt->close();
        }
    }
    
    if ($action === 'accept') {
        // Update application status to Accepted in job_applications_extended
        $stmt = $conn->prepare("UPDATE job_applications_extended SET status = 'Accepted', viewed_date = NOW() WHERE id = ?");
        if (!$stmt) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            exit;
        }
        
        $stmt->bind_param("i", $application_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Update jobseeker NSRP row to Accepted (Pending / Referred / Rejected).
            // Rejected = NSRP was declined first; a company can still accept them via Recommended Jobs.
            $rejCol = @$conn->query("SHOW COLUMNS FROM jobseeker LIKE 'rejection_reason'");
            $hasRejectionReason = $rejCol && $rejCol->num_rows > 0;
            if ($hasRejectionReason) {
                $stmt_jobseeker = $conn->prepare(
                    "UPDATE jobseeker SET application_status = 'Accepted', rejection_reason = NULL WHERE id = ? AND application_status IN ('Pending', 'Referred', 'Rejected')"
                );
            } else {
                $stmt_jobseeker = $conn->prepare(
                    "UPDATE jobseeker SET application_status = 'Accepted' WHERE id = ? AND application_status IN ('Pending', 'Referred', 'Rejected')"
                );
            }
            if ($stmt_jobseeker) {
                $stmt_jobseeker->bind_param("i", $jobseeker_id);
                $stmt_jobseeker->execute();
                $stmt_jobseeker->close();
            }
            // Accepted via job posting — close other applications and pending NSRP/company referrals
            withdraw_open_job_applications_for_jobseeker($conn, $jobseeker_id, $application_id);
            ensure_jobseeker_referrals_table($conn);
            withdraw_pending_referrals_for_jobseeker($conn, $jobseeker_id);
            
            // Send acceptance email - professional congratulatory message
            $subject = "Congratulations! You Have Been Accepted - " . htmlspecialchars($company_name) . " - WorkConnect";
            
            $message = "
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
                    .job-details {
                        background: #f8f9fa;
                        border-radius: 8px;
                        padding: 20px;
                        margin: 20px 0;
                    }
                    .job-details h3 {
                        color: #1a3876;
                        margin-bottom: 15px;
                        font-size: 1.2rem;
                    }
                    .detail-item {
                        margin-bottom: 10px;
                        color: #666;
                    }
                    .detail-item strong {
                        color: #333;
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
                        <p style='margin-bottom: 20px; font-size: 16px;'>We are delighted to inform you that <strong style='color: #4caf50;'>" . htmlspecialchars($company_name) . "</strong> has accepted your application. Congratulations! You have been selected for the position you applied for.</p>
                        <div class='job-details'>
                            <h3>Job Position Details</h3>
                            <div class='detail-item'><strong>Position:</strong> " . htmlspecialchars($job_title) . "</div>";
            
            if ($job_details) {
                $message .= "
                            <div class='detail-item'><strong>Company:</strong> " . htmlspecialchars($job_details['company'] ?? $company_name) . "</div>
                            <div class='detail-item'><strong>Location:</strong> " . htmlspecialchars($job_details['location'] ?? 'N/A') . "</div>
                            <div class='detail-item'><strong>Job Type:</strong> " . htmlspecialchars($job_details['job_type'] ?? 'N/A') . "</div>";
                if (!empty($job_details['salary_range'])) {
                    $message .= "<div class='detail-item'><strong>Salary Range:</strong> ₱" . htmlspecialchars($job_details['salary_range']) . "</div>";
                }
                if (!empty($job_details['description'])) {
                    $message .= "<div class='detail-item' style='margin-top: 15px;'><strong>Job Description:</strong><br>" . nl2br(htmlspecialchars($job_details['description'])) . "</div>";
                }
                if (!empty($job_details['requirements'])) {
                    $message .= "<div class='detail-item' style='margin-top: 15px;'><strong>Requirements:</strong><br>" . nl2br(htmlspecialchars($job_details['requirements'])) . "</div>";
                }
            }
            
            $message .= "
                        </div>
                        <p style='margin-top: 30px; font-size: 16px;'>The company will contact you shortly regarding the next steps in the hiring process, including onboarding details and your start date. Please ensure your contact information is up to date.</p>
                        <p style='margin-top: 15px; font-size: 16px;'>We congratulate you on this achievement and wish you every success in your new role with " . htmlspecialchars($company_name) . ".</p>
                    </div>
                    <div class='footer'>
                        <p><strong>WorkConnect</strong></p>
                        <p>Connecting Talent with Opportunity</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            // Send email only if jobseeker has a valid email address
            $email_sent = false;
            if ($jobseeker_email_valid) {
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
                        error_log("Accept email failed (jobseeker_id=$jobseeker_id): " . $e->getMessage());
                    }
                } else {
                    $headers = "MIME-Version: 1.0" . "\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                    $headers .= "From: WorkConnect <noreply@workconnect.com>" . "\r\n";
                    $email_sent = @mail($jobseeker_email, $subject, $message, $headers);
                }
            }
            
            $response = [
                'success' => true,
                'message' => $email_sent ? 'Application accepted and email sent successfully.' : ($jobseeker_email_valid ? 'Application accepted. Email notification may not have been sent.' : 'Application accepted. Jobseeker has no valid email; notification not sent.')
            ];
            if (!empty($jobseeker['user_id'])) {
                createNotification(
                    (int)$jobseeker['user_id'],
                    'Application Accepted',
                    "Congratulations! {$company_name} accepted your application for {$job_title}.",
                    'application'
                );
            }
        } else {
            $error_msg = $stmt->error ? $stmt->error : 'Failed to update application status';
            $response = ['success' => false, 'message' => $error_msg];
            $stmt->close();
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
        
        $rejection_reason = $conn->real_escape_string(trim($input['rejection_reason']));
        
        // Update application status to Rejected and save reason in notes
        $stmt = $conn->prepare("UPDATE job_applications_extended SET status = 'Rejected', viewed_date = NOW(), notes = ? WHERE id = ?");
        if (!$stmt) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            exit;
        }
        
        $stmt->bind_param("si", $rejection_reason, $application_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Do NOT change jobseeker.application_status here (admin "Pending Jobseekers" stays as-is).
            // Only company → Referred rejections (handle_referred.php) update that field to Rejected.
            
            // Send rejection email
            $subject = "Update on Your Job Application - WorkConnect";
            
            $message = "
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
                        background: linear-gradient(135deg, #f44336 0%, #c62828 100%);
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
                    .job-details {
                        background: #f8f9fa;
                        border-radius: 8px;
                        padding: 20px;
                        margin: 20px 0;
                    }
                    .job-details h3 {
                        color: #1a3876;
                        margin-bottom: 15px;
                        font-size: 1.2rem;
                    }
                    .rejection-reason {
                        background: #fff3cd;
                        border-left: 4px solid #ffc107;
                        padding: 15px;
                        margin: 20px 0;
                        border-radius: 4px;
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
                        <h1>Application Update</h1>
                    </div>
                    <div class='content'>
                        <h2 style='color: #1a3876; margin-bottom: 20px;'>Dear " . htmlspecialchars($jobseeker_name) . ",</h2>
                        <p style='margin-bottom: 20px; font-size: 16px;'>Thank you for your interest in the position at " . htmlspecialchars($company_name) . ".</p>
                        <p style='margin-bottom: 20px; font-size: 16px;'>After careful consideration, we regret to inform you that we will not be moving forward with your application for the following position:</p>
                        <div class='job-details'>
                            <h3>Position Applied For</h3>
                            <div><strong>" . htmlspecialchars($job_title) . "</strong></div>";
            
            if ($job_details) {
                $message .= "<div style='margin-top: 10px; color: #666;'>" . htmlspecialchars($job_details['company'] ?? $company_name) . " - " . htmlspecialchars($job_details['location'] ?? '') . "</div>";
            }
            
            $message .= "
                        </div>
                        <div class='rejection-reason'>
                            <h3 style='color: #856404; margin-bottom: 10px; font-size: 1rem;'>Reason for Rejection:</h3>
                            <p style='color: #333; margin: 0;'>" . nl2br(htmlspecialchars($rejection_reason)) . "</p>
                        </div>
                        <p style='margin-top: 30px; font-size: 16px;'>We appreciate the time and effort you invested in your application. We encourage you to continue exploring other opportunities on WorkConnect.</p>
                        <p style='margin-top: 15px; color: #666;'>Best of luck in your job search!</p>
                    </div>
                    <div class='footer'>
                        <p><strong>WorkConnect</strong></p>
                        <p>Connecting Talent with Opportunity</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            // Send email only if jobseeker has a valid email address
            $email_sent = false;
            if ($jobseeker_email_valid) {
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
                        error_log("Reject email failed (jobseeker_id=$jobseeker_id): " . $e->getMessage());
                    }
                } else {
                    $headers = "MIME-Version: 1.0" . "\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                    $headers .= "From: WorkConnect <noreply@workconnect.com>" . "\r\n";
                    $email_sent = @mail($jobseeker_email, $subject, $message, $headers);
                }
            }
            
            $response = [
                'success' => true,
                'message' => $email_sent ? 'Application rejected and email sent successfully.' : ($jobseeker_email_valid ? 'Application rejected. Email notification may not have been sent.' : 'Application rejected. Jobseeker has no valid email; notification not sent.')
            ];
            if (!empty($jobseeker['user_id'])) {
                createNotification(
                    (int)$jobseeker['user_id'],
                    'Application Rejected',
                    "Your application for {$job_title} was rejected.\n\nReason: {$rejection_reason}",
                    'application'
                );
            }
        } else {
            $error_msg = $stmt->error ? $stmt->error : 'Failed to update application status';
            $response = ['success' => false, 'message' => $error_msg];
            $stmt->close();
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
        error_log("Error in handle_application.php: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        exit;
    } catch (Error $e) {
        ob_clean();
        error_log("Fatal error in handle_application.php: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
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
