<?php
header('Content-Type: application/json');

// Check if PHPMailer is available
$phpmailer_available = false;
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
    require_once 'email_config.php';
    $phpmailer_available = true;
}

// Use statements must be at the top level
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$host = "workconnect.cp28esmqk7aq.ap-southeast-2.rds.amazonaws.com";
$user = "admin";
$pass = "Pogisimark";
$db   = "WorkConnect";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['jobseeker_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing jobseeker ID']);
        exit;
    }
    
    $jobseeker_id = intval($input['jobseeker_id']);
    $rejection_reason = isset($input['rejection_reason']) ? $conn->real_escape_string($input['rejection_reason']) : 'No specific reason provided.';
    
    // Get jobseeker data
    $sql = "SELECT firstname, surname, middlename, email FROM jobseeker WHERE id = $jobseeker_id";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $jobseeker = $result->fetch_assoc();
        $jobseeker_email = trim($jobseeker['email'] ?? '');
        $jobseeker_name = trim(($jobseeker['firstname'] ?? '') . ' ' . ($jobseeker['middlename'] && $jobseeker['middlename'] !== 'n/a' ? $jobseeker['middlename'] . ' ' : '') . ($jobseeker['surname'] ?? ''));
        if (empty($jobseeker_name)) {
            $jobseeker_name = 'Applicant';
        }
        if (empty($jobseeker_email) || !filter_var($jobseeker_email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Jobseeker has no valid email address. Cannot send rejection notification.']);
            $conn->close();
            exit;
        }
        
        $subject = "Update on Your Job Application - WorkConnect";
        
        $message = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
    <link rel='icon' type='image/png' href='/assets/image/PESO Logo circle.png'>
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
                .rejection-reason {
                    background: #fff3cd;
                    border-left: 4px solid #ffc107;
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 4px;
                }
                .rejection-reason h3 {
                    color: #856404;
                    margin-bottom: 10px;
                    font-size: 1rem;
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
                    <p style='margin-bottom: 20px; font-size: 16px;'>Thank you for your interest in applying through WorkConnect.</p>
                    <p style='margin-bottom: 20px; font-size: 16px;'>After careful consideration, we regret to inform you that we will not be moving forward with your application at this time.</p>
                    <div class='rejection-reason'>
                        <h3>Reason for Rejection:</h3>
                        <p style='color: #333; margin: 0;'>" . nl2br(htmlspecialchars($rejection_reason)) . "</p>
                    </div>
                    <p style='margin-top: 30px; font-size: 16px;'>We appreciate the time and effort you invested in your application. We encourage you to continue exploring other opportunities on WorkConnect.</p>
                    <p style='margin-top: 20px; font-size: 16px;'>If you wish to re-apply, please note that <strong>re-submission of your NSRP form will be available again 24 hours</strong> after this notification. You may then submit an updated application through your WorkConnect account.</p>
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
        
        // Send email using available method
        if ($phpmailer_available) {
            // Create PHPMailer instance
            $mail = new PHPMailer(true);
            
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USERNAME;
                $mail->Password   = SMTP_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = SMTP_PORT;
                
                // Recipients
                $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                $mail->addAddress($jobseeker_email);
                
                // Content
                $mail->isHTML(true);
                $mail->CharSet = 'UTF-8';
                $mail->Subject = $subject;
                $mail->Body    = $message;
                
                $mail->send();
                echo json_encode(['success' => true, 'message' => 'Rejection email sent successfully to jobseeker']);
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Email could not be sent. Error: ' . $mail->ErrorInfo]);
            }
        } else {
            // Fallback to basic PHP mail() function
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: WorkConnect <noreply@workconnect.com>" . "\r\n";
            $headers .= "Reply-To: noreply@workconnect.com" . "\r\n";
            
            if (mail($jobseeker_email, $subject, $message, $headers)) {
                echo json_encode(['success' => true, 'message' => 'Rejection email sent successfully to jobseeker']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again later.']);
            }
        }
        
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Jobseeker not found']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

$conn->close();
?>
