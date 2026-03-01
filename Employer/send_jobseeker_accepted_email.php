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

$host = "workconnect.ct26qyouyans.ap-southeast-2.rds.amazonaws.com";
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
    $company_name = isset($input['company_name']) ? $conn->real_escape_string($input['company_name']) : 'the employer';
    
    // Get jobseeker data
    $sql = "SELECT firstname, surname, email FROM jobseeker WHERE id = $jobseeker_id";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $jobseeker = $result->fetch_assoc();
        $jobseeker_email = trim($jobseeker['email'] ?? '');
        $jobseeker_name = $jobseeker['firstname'] . ' ' . $jobseeker['surname'];
        if (empty($jobseeker_email) || !filter_var($jobseeker_email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Jobseeker has no valid email address. Cannot send notification.']);
            $conn->close();
            exit;
        }
        
        $subject = "WorkConnect - Your Application Has Been Forwarded to " . $company_name;
        
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
                    -webkit-font-smoothing: antialiased;
                    -moz-osx-font-smoothing: grayscale;
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
                    padding: 40px 30px;
                    text-align: center;
                }
                .header h1 {
                    font-size: 28px;
                    font-weight: 600;
                    letter-spacing: 0.5px;
                    margin: 0;
                }
                .header .tagline {
                    font-size: 14px;
                    opacity: 0.9;
                    margin-top: 8px;
                    font-weight: 300;
                }
                .content {
                    padding: 50px 40px;
                    background-color: #ffffff;
                }
                .success-badge {
                    text-align: center;
                    margin-bottom: 30px;
                }
                .success-icon {
                    display: inline-block;
                    width: 80px;
                    height: 80px;
                    background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
                    border-radius: 50%;
                    line-height: 80px;
                    font-size: 40px;
                    color: #ffffff;
                    font-weight: bold;
                    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
                }
                .success-title {
                    font-size: 24px;
                    font-weight: 600;
                    color: #1a3876;
                    text-align: center;
                    margin-bottom: 25px;
                    letter-spacing: -0.5px;
                }
                .greeting {
                    font-size: 16px;
                    color: #2c3e50;
                    margin-bottom: 20px;
                    font-weight: 500;
                }
                .message-text {
                    font-size: 15px;
                    color: #555555;
                    margin-bottom: 18px;
                    line-height: 1.8;
                }
                .highlight-box {
                    background: linear-gradient(135deg, #e8f5e9 0%, #f1f8f4 100%);
                    border-left: 4px solid #4caf50;
                    padding: 20px 25px;
                    margin: 25px 0;
                    border-radius: 4px;
                }
                .highlight-box p {
                    margin: 0;
                    font-size: 15px;
                    color: #2c3e50;
                    font-weight: 500;
                }
                .info-section {
                    background-color: #f8f9fa;
                    border-radius: 6px;
                    padding: 20px;
                    margin: 25px 0;
                    border: 1px solid #e9ecef;
                }
                .info-section p {
                    margin: 0;
                    font-size: 14px;
                    color: #6c757d;
                    line-height: 1.7;
                }
                .company-name {
                    font-weight: 600;
                    color: #1976d2;
                    font-size: 16px;
                }
                .closing {
                    margin-top: 35px;
                    padding-top: 25px;
                    border-top: 1px solid #e9ecef;
                }
                .closing p {
                    font-size: 15px;
                    color: #2c3e50;
                    margin-bottom: 8px;
                }
                .signature {
                    font-weight: 600;
                    color: #1a3876;
                    font-size: 16px;
                }
                .footer {
                    background-color: #1a3876;
                    color: #ffffff;
                    padding: 30px;
                    text-align: center;
                    font-size: 12px;
                    line-height: 1.6;
                }
                .footer p {
                    margin: 8px 0;
                    opacity: 0.9;
                }
                .footer .copyright {
                    margin-top: 15px;
                    padding-top: 15px;
                    border-top: 1px solid rgba(255, 255, 255, 0.2);
                    opacity: 0.8;
                }
                .divider {
                    height: 1px;
                    background: linear-gradient(to right, transparent, #e9ecef, transparent);
                    margin: 30px 0;
                }
                @media only screen and (max-width: 600px) {
                    .email-wrapper {
                        margin: 0;
                        border-radius: 0;
                    }
                    .content {
                        padding: 35px 25px;
                    }
                    .header {
                        padding: 30px 20px;
                    }
                    .header h1 {
                        font-size: 24px;
                    }
                    .success-title {
                        font-size: 20px;
                    }
                }
            </style>
        </head>
        <body>
            <div class='email-wrapper'>
                <div class='header'>
                    <h1>WorkConnect</h1>
                    <div class='tagline'>Connecting Talent with Opportunity</div>
                </div>
                
                <div class='content'>
                    <div class='success-badge'>
                        <div class='success-icon'>✓</div>
                    </div>
                    
                    <h2 class='success-title'>Application Forwarded Successfully</h2>
                    
                    <p class='greeting'>Dear " . htmlspecialchars($jobseeker_name) . ",</p>
                    
                    <p class='message-text'>
                        Great news! Your job application has been reviewed and accepted by our team. We are pleased to inform you that your application has been successfully forwarded to <span class='company-name'>" . htmlspecialchars($company_name) . "</span> for their consideration.
                    </p>
                    
                    <div class='highlight-box'>
                        <p>✓ Your application is now with the employer and under their review.</p>
                    </div>
                    
                    <p class='message-text'>
                        The company will review your qualifications and profile. They may contact you directly through the email address or contact number you provided in your application if they are interested in proceeding with your candidacy.
                    </p>
                    
                    <div class='info-section'>
                        <p><strong>What's Next?</strong></p>
                        <p style='margin-top: 10px;'>Please wait for further communication from <span class='company-name'>" . htmlspecialchars($company_name) . "</span>. They will reach out to you if there are any updates regarding your application. Please ensure your contact information remains up to date and check your email regularly.</p>
                    </div>
                    
                    <p class='message-text'>
                        We appreciate your patience during this process. If you have any questions or need to update your contact information, please log in to your WorkConnect dashboard.
                    </p>
                    
                    <div class='divider'></div>
                    
                    <div class='closing'>
                        <p>Best regards,</p>
                        <p class='signature'>The WorkConnect Team</p>
                    </div>
                </div>
                
                <div class='footer'>
                    <p><strong>WorkConnect</strong></p>
                    <p>Department of Labor and Employment</p>
                    <p>National Skills Registration Program</p>
                    <div class='copyright'>
                        <p>This is an automated message. Please do not reply to this email.</p>
                        <p>&copy; " . date('Y') . " WorkConnect. All rights reserved.</p>
                    </div>
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
                echo json_encode(['success' => true, 'message' => 'Email sent successfully to jobseeker']);
                
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
                echo json_encode(['success' => true, 'message' => 'Email sent successfully to jobseeker']);
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

