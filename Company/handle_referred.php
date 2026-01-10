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
        
        // Get jobseeker information
        $stmt = $conn->prepare("SELECT firstname, surname, middlename, email, application_status FROM jobseeker WHERE id = ?");
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
        
        $jobseeker_email = $jobseeker['email'];
        $jobseeker_name = trim(($jobseeker['firstname'] ?? '') . ' ' . ($jobseeker['middlename'] && $jobseeker['middlename'] !== 'n/a' ? $jobseeker['middlename'] . ' ' : '') . ($jobseeker['surname'] ?? ''));
        if (empty($jobseeker_name)) {
            $jobseeker_name = 'Applicant';
        }
        $stmt->close();
    
        if ($action === 'accept') {
            // Update jobseeker's application_status from 'Referred' to 'Accepted'
            $stmt = $conn->prepare("UPDATE jobseeker SET application_status = 'Accepted' WHERE id = ? AND application_status = 'Referred'");
            if (!$stmt) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                exit;
            }
            
            $stmt->bind_param("i", $jobseeker_id);
            
            if ($stmt->execute()) {
                $stmt->close();
                
                // Send acceptance email
                $subject = "Congratulations! You've Been Hired - " . htmlspecialchars($company_name);
                
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
                            <h1>🎉 Congratulations! You've Been Hired!</h1>
                        </div>
                        <div class='content'>
                            <div class='success-icon'>✓</div>
                            <h2 style='color: #1a3876; margin-bottom: 20px;'>Dear " . htmlspecialchars($jobseeker_name) . ",</h2>
                            <p style='margin-bottom: 20px; font-size: 16px;'>We are thrilled to inform you that <strong style='color: #4caf50;'>" . htmlspecialchars($company_name) . "</strong> has accepted your application and would like to offer you a position!</p>
                            <p style='margin-bottom: 20px; font-size: 16px;'>Congratulations on this achievement! The company has recognized your skills and qualifications, and they are excited to welcome you to their team.</p>
                            <p style='margin-top: 30px; font-size: 16px;'>The company will contact you soon regarding the next steps in the hiring process, including onboarding details, start date, and any other relevant information.</p>
                            " . $company_contact_html . "
                            <p style='margin-top: 30px; font-size: 16px;'>We wish you the best of luck in your new role and a successful career with " . htmlspecialchars($company_name) . "!</p>
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
            } else {
                $error_msg = $stmt->error ? $stmt->error : 'Failed to update jobseeker status';
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
            
            // Update jobseeker's application_status from 'Referred' to 'Rejected'
            $stmt = $conn->prepare("UPDATE jobseeker SET application_status = 'Rejected' WHERE id = ? AND application_status = 'Referred'");
            if (!$stmt) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                exit;
            }
            
            $stmt->bind_param("i", $jobseeker_id);
            
            if ($stmt->execute()) {
                $stmt->close();
                
                // Update rejection_reason if column exists
                $check_column = $conn->query("SHOW COLUMNS FROM jobseeker LIKE 'rejection_reason'");
                if ($check_column && $check_column->num_rows > 0) {
                    $stmt_reason = $conn->prepare("UPDATE jobseeker SET rejection_reason = ? WHERE id = ?");
                    if ($stmt_reason) {
                        $stmt_reason->bind_param("si", $rejection_reason, $jobseeker_id);
                        $stmt_reason->execute();
                        $stmt_reason->close();
                    }
                }
                
                // Send rejection email
                $subject = "Update on Your Job Application - WorkConnect";
                
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
                            <p style='margin-bottom: 20px; font-size: 16px;'>Thank you for your interest in working with " . htmlspecialchars($company_name) . ".</p>
                            <p style='margin-bottom: 20px; font-size: 16px;'>After careful consideration, we regret to inform you that we will not be moving forward with your application.</p>
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
                    'message' => $email_sent ? 'Jobseeker rejected and email sent successfully.' : 'Jobseeker rejected. Email notification may not have been sent.'
                ];
            } else {
                $error_msg = $stmt->error ? $stmt->error : 'Failed to update jobseeker status';
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
