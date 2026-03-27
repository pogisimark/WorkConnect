<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Start session and check if user is logged in
require_once 'session_init.php';

// Check if user is logged in, if not redirect to login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Check if this is being loaded in an iframe (from dashboard)
$isIframe = isset($_GET['session_id']) && isset($_GET['user_id']) && isset($_GET['token']);

if ($isIframe) {
    // Validate session token for iframe security
    // Use a more lenient validation - check if session_id and user_id match
    $expected_session_id = $_GET['session_id'] ?? '';
    $expected_user_id = $_GET['user_id'] ?? '';
    
    if ($expected_session_id !== session_id() || $expected_user_id != $_SESSION['user_id']) {
        die('Invalid session parameters');
    }
    
    // Additional token validation (optional - can be removed if too strict)
    $expected_token = hash('sha256', session_id() . $_SESSION['user_id'] . 'workconnect');
    $provided_token = $_GET['token'] ?? '';
    
    // Allow some flexibility in token validation
    if ($provided_token && $expected_token !== $provided_token) {
        // Try with a slightly different token format
        $alt_token = hash('sha256', session_id() . $_SESSION['user_id']);
        if ($alt_token !== $provided_token) {
            // For now, just log the mismatch but don't block access
            error_log("Token mismatch for user {$_SESSION['user_id']}");
        }
    }
} else {
    // Check if accessed through dashboard with proper session parameters
    $required_params = ['session_id', 'user_id', 'token'];
    $missing_params = [];

    foreach ($required_params as $param) {
        if (!isset($_GET[$param])) {
            $missing_params[] = $param;
        }
    }

    // If missing required parameters, redirect to dashboard
    if (!empty($missing_params)) {
        header("Location: dashboard.php");
        exit();
    }

    // Validate session token for security
    $expected_token = hash('sha256', session_id() . $_SESSION['user_id'] . time());
    $provided_token = $_GET['token'];

// Allow some tolerance for token timing (within 1 hour)
$token_valid = false;
for ($i = 0; $i < 3600; $i++) { // Check tokens from last hour
    $test_token = hash('sha256', session_id() . $_SESSION['user_id'] . (time() - $i));
    if ($test_token === $provided_token) {
        $token_valid = true;
        break;
    }
}

if (!$token_valid) {
    header("Location: dashboard.php");
    exit();
}

    // Validate user_id matches session
    if ($_GET['user_id'] != $_SESSION['user_id']) {
        header("Location: dashboard.php");
        exit();
    }

    // Store session info for form processing
    $current_session_id = $_GET['session_id'];
    $current_user_id = $_GET['user_id'];
}

// Check if PHPMailer is available
$phpmailer_available = false;
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../Employer/email_config.php';
    $phpmailer_available = true;
}

// Database connection and backend processing
$host = "workconnect.cp28esmqk7aq.ap-southeast-2.rds.amazonaws.com";
$user = "admin";
$pass = "Pogisimark";
$db   = "WorkConnect";

// Create connection with timeout and retry logic
$conn = new mysqli($host, $user, $pass, $db);

// Check if user has existing NRSP form and its status
$userId = $_SESSION['user_id'];
$existingNRSP = null;
$canEditNRSP = false;
$canSubmitNRSP = true;
$nrspStatus = null;
$nrspSubmissionDate = null;
$rejectionDate = null;
$cooldownRemaining = null;
$isPending = false;
$isRejected = false;
$autoLoadForm = false;

$stmt = $conn->prepare("SELECT id, application_status, submission_date, submission_month, submission_year, created_at, updated_at, resume_file, esignature_file FROM jobseeker WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $existingNRSP = $result->fetch_assoc();
    $nrspStatus = $existingNRSP['application_status'] ?? null;
    $statusLower = strtolower($nrspStatus ?? '');
    $existingResumeFile = $existingNRSP['resume_file'] ?? null;
    $existingEsignatureFile = $existingNRSP['esignature_file'] ?? null;
    
    // Format submission date
    if (!empty($existingNRSP['submission_date'])) {
        $nrspSubmissionDate = date('F d, Y', strtotime($existingNRSP['submission_date']));
    } elseif (!empty($existingNRSP['submission_year']) && !empty($existingNRSP['submission_month'])) {
        $nrspSubmissionDate = date('F Y', mktime(0, 0, 0, (int)$existingNRSP['submission_month'], 1, (int)$existingNRSP['submission_year']));
    } elseif (!empty($existingNRSP['created_at'])) {
        $nrspSubmissionDate = date('F d, Y', strtotime($existingNRSP['created_at']));
    }
    
    // Check status
    $isPending = ($statusLower === 'pending');
    $isRejected = ($statusLower === 'rejected');
    $isReferred = ($statusLower === 'referred');
    
    // Edit/Submit rules:
    // - New form: Submit button, no duplicate check when creating
    // - Pending: Can edit, Save button (update only), no duplicate check
    // - Referred: Cannot edit, form locked (view only)
    // - Accepted: Cannot edit, form locked (view only)
    // - Rejected: Can edit, Re-submit button locked 24hrs then enabled, no duplicate check when resubmitting
    $canEditNRSP = false;
    $canSubmitNRSP = true;
    
    if ($statusLower === 'accepted') {
        $canEditNRSP = false;
        $canSubmitNRSP = false;
        $autoLoadForm = true;
    } elseif ($isReferred) {
        $canEditNRSP = false;
        $canSubmitNRSP = false;
        $autoLoadForm = true;
    } elseif ($isPending) {
        $canEditNRSP = true;
        $canSubmitNRSP = true;
        $autoLoadForm = true;
    } elseif ($isRejected) {
        $canEditNRSP = true; // Can edit when rejected (or leave as is)
        $rejectionDate = !empty($existingNRSP['updated_at']) ? strtotime($existingNRSP['updated_at']) : strtotime($existingNRSP['created_at']);
        $currentTime = time();
        $timeSinceRejection = $currentTime - $rejectionDate;
        $cooldownPeriod = 24 * 60 * 60; // 24 hours in seconds
        
        if ($timeSinceRejection < $cooldownPeriod) {
            $canSubmitNRSP = false;
            $cooldownRemaining = $cooldownPeriod - $timeSinceRejection;
        } else {
            $canSubmitNRSP = true;
        }
        $autoLoadForm = true;
    } else {
        $canEditNRSP = true;
        $canSubmitNRSP = true;
        $autoLoadForm = true; // Always auto-populate when existing NRSP
    }
}
$stmt->close();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
$conn->options(MYSQLI_OPT_READ_TIMEOUT, 30);

if ($conn->connect_error) {
    // Log the error
    error_log("Database connection failed: " . $conn->connect_error);
    
    // Clear any output and return JSON error
    if (ob_get_level()) {
        ob_clean();
    }
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please try again.']);
    exit;
}

// Set charset to prevent encoding issues
$conn->set_charset("utf8mb4");

function getval($key, $default = 'n/a') {
    return isset($_POST[$key]) && $_POST[$key] !== '' ? $_POST[$key] : $default;
}

function getbool($key) {
    return isset($_POST[$key]) ? 1 : 0;
}

function sendJsonResponse($success, $message, $data = null) {
    // Clear any previous output
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Set proper headers
    header('Content-Type: application/json');
    http_response_code($success ? 200 : 400);
    
    // Prepare response
    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response);
    exit;
}

function sendSubmissionConfirmationEmail($to_email, $firstname, $surname) {
    global $phpmailer_available;
    
    $subject = "WorkConnect - Job Application Form Submission Successful";
    
    // Email body with professional HTML formatting
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
                
                <h2 class='success-title'>Application Received Successfully</h2>
                
                <p class='greeting'>Dear " . htmlspecialchars($firstname . ' ' . $surname) . ",</p>
                
                <p class='message-text'>
                    Thank you for submitting your job application form to WorkConnect. We have successfully received and processed your registration.
                </p>
                
                <div class='highlight-box'>
                    <p>✓ Your application is now under review by our team.</p>
                </div>
                
                <p class='message-text'>
                    Our recruitment team will carefully review your application and qualifications. We appreciate the time and effort you've invested in completing the registration process.
                </p>
                
                <div class='info-section'>
                    <p><strong>What's Next?</strong></p>
                    <p style='margin-top: 10px;'>We will contact you through the email address you provided if there are any updates regarding your application. Please ensure your contact information remains up to date.</p>
                </div>
                
                <p class='message-text'>
                    Thank you for your interest in joining the WorkConnect platform. We look forward to the possibility of working with you.
                </p>
                
                <div class='divider'></div>
                
                <div class='closing'>
                    <p>Best regards,</p>
                    <p class='signature'>The WorkConnect Team</p>
                </div>
            </div>
            
            <div class='footer'>
                <p><strong>WorkConnect</strong></p>
                <p>Public Employment Service Office</p>
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
        // Use PHPMailer to send email
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            
            // Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($to_email);
            
            // Content
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body    = $message;
            
            $mail->send();
            error_log("Confirmation email sent successfully to: " . $to_email);
            return true;
            
        } catch (Exception $e) {
            error_log("Failed to send confirmation email using PHPMailer to: " . $to_email . " Error: " . $mail->ErrorInfo);
            return false;
        }
    } else {
        // Fallback to basic PHP mail() function
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: WorkConnect <noreply@workconnect.com>" . "\r\n";
        $headers .= "Reply-To: noreply@workconnect.com" . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        $mail_sent = @mail($to_email, $subject, $message, $headers);
        
        if (!$mail_sent) {
            error_log("Failed to send confirmation email to: " . $to_email);
        }
        
        return $mail_sent;
    }
}

// Handle POST requests (form submission)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Debug: Log that we received a POST request
    error_log("POST request received");
    
    // Simple test response first
    if (isset($_POST['test'])) {
        sendJsonResponse(true, 'Test successful');
    }
    
    // Check for existing NRSP form - we'll determine if this is an update or new submission later
    // This early check only prevents new submissions when there's an accepted form
    $checkStmt = $conn->prepare("SELECT id, application_status, updated_at, created_at FROM jobseeker WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $checkStmt->bind_param("i", $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $existingForm = $checkResult->fetch_assoc();
        $existingStatus = strtolower($existingForm['application_status'] ?? '');
        
        // Only prevent NEW submissions if status is Accepted (updates are handled later)
        // For pending/rejected forms, we'll allow updates (checked later in the code)
        if ($existingStatus === 'accepted') {
            $checkStmt->close();
            sendJsonResponse(false, 'Your NRSP form has already been accepted and sent to companies. You cannot submit a new form.');
        }
    }
    $checkStmt->close();
    
    // Rate limiting: Check if user has submitted recently
    $current_time = time();
    $last_submission = isset($_SESSION['last_submission']) ? $_SESSION['last_submission'] : 0;
    $time_diff = $current_time - $last_submission;
    
    // Allow only one submission per 30 seconds
    if ($time_diff < 30) {
        sendJsonResponse(false, 'Please wait ' . (30 - $time_diff) . ' seconds before submitting again.');
    }
    
    // Update last submission time
    $_SESSION['last_submission'] = $current_time;
    
    // Check for existing files first (from database or hidden input)
    $existingResumeFile = null;
    $existingEsignatureFile = null;
    
    // Check hidden inputs first (set by JavaScript)
    if (!empty($_POST['existing_resume_file'])) {
        $existingResumeFile = $conn->real_escape_string($_POST['existing_resume_file']);
    }
    if (!empty($_POST['existing_esignature_file'])) {
        $existingEsignatureFile = $conn->real_escape_string($_POST['existing_esignature_file']);
    }
    
    // If not in POST, check database
    if (empty($existingResumeFile) || empty($existingEsignatureFile)) {
        $checkFilesStmt = $conn->prepare("SELECT resume_file, esignature_file FROM jobseeker WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $checkFilesStmt->bind_param("i", $userId);
        $checkFilesStmt->execute();
        $filesResult = $checkFilesStmt->get_result();
        if ($filesResult->num_rows > 0) {
            $filesRow = $filesResult->fetch_assoc();
            if (empty($existingResumeFile)) {
                $existingResumeFile = $filesRow['resume_file'] ?? null;
            }
            if (empty($existingEsignatureFile)) {
                $existingEsignatureFile = $filesRow['esignature_file'] ?? null;
            }
        }
        $checkFilesStmt->close();
    }
    
    $resume_filename = !empty($existingResumeFile) ? $existingResumeFile : ''; // Keep existing file by default
    if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] == UPLOAD_ERR_OK) {
        $allowed_ext = ['pdf', 'doc', 'docx'];
        $allowed_mime_types = [
            'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $file_info = pathinfo($_FILES['resume_file']['name']);
        $ext = strtolower($file_info['extension']);
        $file_size = $_FILES['resume_file']['size'];
        $mime_type = $_FILES['resume_file']['type'];
        
        // Validate file extension
        if (!in_array($ext, $allowed_ext)) {
            sendJsonResponse(false, 'Invalid file type. Please upload PDF, DOC, or DOCX files only.');
        }
        
        // Validate MIME type
        if (!in_array($mime_type, $allowed_mime_types)) {
            sendJsonResponse(false, 'Invalid file type detected. Please upload a valid file.');
        }
        
        // Validate file size
        if ($file_size > $max_size) {
            sendJsonResponse(false, 'File size too large. Maximum size is 5MB.');
        }
        
        $resume_filename = uniqid('resume_') . '.' . $ext;
        $resume_dir = __DIR__ . '/../uploads/resumes/';
        if (!is_dir($resume_dir)) { mkdir($resume_dir, 0777, true); }
        $resume_filepath = $resume_dir . $resume_filename;
        
        if (!move_uploaded_file($_FILES['resume_file']['tmp_name'], $resume_filepath)) {
            sendJsonResponse(false, 'Failed to upload file. Please try again.');
        }
    } else if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        sendJsonResponse(false, 'File upload error. Please try again.');
    } else if (empty($resume_filename) && empty($existingResumeFile)) {
        // Only require resume if no existing file and no new file uploaded
        sendJsonResponse(false, 'Resume file is required.');
    }

    // Get submission date
    $submission_date = date('Y-m-d');
    $submission_month = date('n');
    $submission_year = date('Y');
    
    // Handle e-signature upload
    $esignature_filename = !empty($existingEsignatureFile) ? $existingEsignatureFile : ''; // Keep existing file by default
    if (isset($_FILES['esignature']) && $_FILES['esignature']['error'] == UPLOAD_ERR_OK) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        $allowed_mime_types = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'
        ];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        $file_info = pathinfo($_FILES['esignature']['name']);
        $ext = strtolower($file_info['extension']);
        $file_size = $_FILES['esignature']['size'];
        $mime_type = $_FILES['esignature']['type'];
        
        // Validate file extension
        if (!in_array($ext, $allowed_ext)) {
            sendJsonResponse(false, 'Invalid e-signature file type. Please upload JPG, PNG, or GIF files only.');
        }
        
        // Validate MIME type
        if (!in_array($mime_type, $allowed_mime_types)) {
            sendJsonResponse(false, 'Invalid e-signature file type detected. Please upload a valid image file.');
        }
        
        // Validate file size
        if ($file_size > $max_size) {
            sendJsonResponse(false, 'E-signature file size too large. Maximum size is 2MB.');
        }
        
        $esignature_filename = uniqid('esignature_') . '.' . $ext;
        $esignature_dir = __DIR__ . '/../uploads/esignatures/';
        if (!is_dir($esignature_dir)) { mkdir($esignature_dir, 0777, true); }
        $esignature_filepath = $esignature_dir . $esignature_filename;
        
        if (!move_uploaded_file($_FILES['esignature']['tmp_name'], $esignature_filepath)) {
            sendJsonResponse(false, 'Failed to upload e-signature file. Please try again.');
        }
    } else if (isset($_FILES['esignature']) && $_FILES['esignature']['error'] !== UPLOAD_ERR_NO_FILE) {
        sendJsonResponse(false, 'E-signature file upload error. Please try again.');
    } else if (empty($esignature_filename) && empty($existingEsignatureFile)) {
        // Only require esignature if no existing file and no new file uploaded
        sendJsonResponse(false, 'E-signature file is required.');
    }
    
    // Personal Information
    $surname = $conn->real_escape_string(getval('surname', ''));
    $firstname = $conn->real_escape_string(getval('firstname', ''));
    $middlename = $conn->real_escape_string(getval('middlename', ''));
    $suffix = $conn->real_escape_string(getval('suffix', ''));
    
    // Server-side duplicate check - ONLY for NEW submissions, NOT for updates
    // This check will be done later after we determine if it's an update or new submission
    
    // Continue with all other form fields...
    $dob = $conn->real_escape_string(getval('dob'));
    $sex = $conn->real_escape_string(getval('sex'));
    $religion = $conn->real_escape_string(getval('religion'));
    $civilstatus = $conn->real_escape_string(getval('civilstatus'));
    $street = $conn->real_escape_string(getval('street'));
    $barangay = $conn->real_escape_string(getval('barangay'));
    $municipality = $conn->real_escape_string(getval('municipality'));
    $province = $conn->real_escape_string(getval('province'));
    $tin = $conn->real_escape_string(getval('tin'));
    $height = $conn->real_escape_string(getval('height'));
    $contact = $conn->real_escape_string(getval('contact'));
    $email = $conn->real_escape_string(getval('email'));
    $hasDisability = getbool('hasDisability');
    $disability_speech = getbool('disability_speech');
    $disability_hearing = getbool('disability_hearing');
    $disability_visual = getbool('disability_visual');
    $disability_mental = getbool('disability_mental');
    $disability_others = getbool('disability_others');
    $disability_other = $conn->real_escape_string(getval('disability_other'));

    // Employment Status / Type
    $employed = getbool('employed');
    $employment_type_wage = getbool('employment_type_wage');
    $employment_type_self = getbool('employment_type_self');
    $self_employed_specify = $conn->real_escape_string(getval('self_employed_specify'));
    $self_type_voluntary = getbool('self_type_voluntary');
    $self_type_vendor = getbool('self_type_vendor');
    $self_type_homebased = getbool('self_type_homebased');
    $self_type_transport = getbool('self_type_transport');
    $self_type_domestic = getbool('self_type_domestic');
    $self_type_fisherfolk = getbool('self_type_fisherfolk');
    $self_type_freelancer = getbool('self_type_freelancer');
    $self_type_artisan = getbool('self_type_artisan');
    $self_type_others = getbool('self_type_others');
    $other_jobs = $conn->real_escape_string(getval('other_jobs'));
    $unemployed = getbool('unemployed');
    $unemployed_months = $conn->real_escape_string(getval('unemployed_months'));
    $unemployed_type_first = getbool('unemployed_type_first');
    $unemployed_type_local = getbool('unemployed_type_local');
    $unemployed_type_resigned = getbool('unemployed_type_resigned');
    $unemployed_type_finished = getbool('unemployed_type_finished');
    $unemployed_type_public = getbool('unemployed_type_public');
    $unemployed_type_retired = getbool('unemployed_type_retired');
    $unemployed_type_terminated = getbool('unemployed_type_terminated');
    $unemployed_type_terminated_abroad = getbool('unemployed_type_terminated_abroad');
    $unemployed_type_others = getbool('unemployed_type_others');
    $terminated_country = $conn->real_escape_string(getval('terminated_country'));
    $unemployed_other_specify = $conn->real_escape_string(getval('unemployed_other_specify'));
    $ofw = $conn->real_escape_string(getval('ofw'));
    $ofw_country = $conn->real_escape_string(getval('ofw_country'));
    $returnee = $conn->real_escape_string(getval('returnee'));
    $deployment_country = $conn->real_escape_string(getval('deployment_country'));
    $return_month = $conn->real_escape_string(getval('return_month'));
    $return_year = $conn->real_escape_string(getval('return_year'));
    $abroad = $conn->real_escape_string(getval('abroad'));
    $beneficiary = $conn->real_escape_string(getval('beneficiary'));
    $household_id = $conn->real_escape_string(getval('household_id'));

    // Job Preference
    $occupation1 = $conn->real_escape_string(getval('occupation1'));
    $occupation2 = $conn->real_escape_string(getval('occupation2'));
    $occupation3 = $conn->real_escape_string(getval('occupation3'));
    $fulltime = getbool('fulltime');
    $parttime = getbool('parttime');
    $local1 = $conn->real_escape_string(getval('local1'));
    $local2 = $conn->real_escape_string(getval('local2'));
    $local3 = $conn->real_escape_string(getval('local3'));
    $overseas1 = $conn->real_escape_string(getval('overseas1'));
    $overseas2 = $conn->real_escape_string(getval('overseas2'));
    $overseas3 = $conn->real_escape_string(getval('overseas3'));

    // Language/Dialect Proficiency
    $english_read = getbool('english_read');
    $english_write = getbool('english_write');
    $english_speak = getbool('english_speak');
    $english_understand = getbool('english_understand');
    $filipino_read = getbool('filipino_read');
    $filipino_write = getbool('filipino_write');
    $filipino_speak = getbool('filipino_speak');
    $filipino_understand = getbool('filipino_understand');
    $mandarin_read = getbool('mandarin_read');
    $mandarin_write = getbool('mandarin_write');
    $mandarin_speak = getbool('mandarin_speak');
    $mandarin_understand = getbool('mandarin_understand');
    $other_language = $conn->real_escape_string(getval('other_language'));
    $other_read = getbool('other_read');
    $other_write = getbool('other_write');
    $other_speak = getbool('other_speak');
    $other_understand = getbool('other_understand');

    // Educational Background
    $inschool = $conn->real_escape_string(getval('inschool'));
    $level = $conn->real_escape_string(getval('level'));
    $course = $conn->real_escape_string(getval('course'));
    $year_graduated = $conn->real_escape_string(getval('year_graduated'));
    $level_reached = $conn->real_escape_string(getval('level_reached'));
    $last_attended = $conn->real_escape_string(getval('last_attended'));

    // Technical/Vocational and Other Training
    $training_course_1 = $conn->real_escape_string(getval('training_course_1'));
    $training_hours_1 = $conn->real_escape_string(getval('training_hours_1'));
    $training_institution_1 = $conn->real_escape_string(getval('training_institution_1'));
    $training_skills_1 = $conn->real_escape_string(getval('training_skills_1'));
    $training_cert_1 = $conn->real_escape_string(getval('training_cert_1'));
    $training_course_2 = $conn->real_escape_string(getval('training_course_2'));
    $training_hours_2 = $conn->real_escape_string(getval('training_hours_2'));
    $training_institution_2 = $conn->real_escape_string(getval('training_institution_2'));
    $training_skills_2 = $conn->real_escape_string(getval('training_skills_2'));
    $training_cert_2 = $conn->real_escape_string(getval('training_cert_2'));
    $training_course_3 = $conn->real_escape_string(getval('training_course_3'));
    $training_hours_3 = $conn->real_escape_string(getval('training_hours_3'));
    $training_institution_3 = $conn->real_escape_string(getval('training_institution_3'));
    $training_skills_3 = $conn->real_escape_string(getval('training_skills_3'));
    $training_cert_3 = $conn->real_escape_string(getval('training_cert_3'));

    // Eligibility/Professional License
    $eligibility_1 = $conn->real_escape_string(getval('eligibility_1'));
    $eligibility_date_1 = $conn->real_escape_string(getval('eligibility_date_1'));
    $eligibility_2 = $conn->real_escape_string(getval('eligibility_2'));
    $eligibility_date_2 = $conn->real_escape_string(getval('eligibility_date_2'));
    $prc_1 = $conn->real_escape_string(getval('prc_1'));
    $prc_valid_1 = $conn->real_escape_string(getval('prc_valid_1'));
    $prc_2 = $conn->real_escape_string(getval('prc_2'));
    $prc_valid_2 = $conn->real_escape_string(getval('prc_valid_2'));

    // Work Experience
    $company_name_1 = $conn->real_escape_string(getval('company_name_1'));
    $company_address_1 = $conn->real_escape_string(getval('company_address_1'));
    $position_1 = $conn->real_escape_string(getval('position_1'));
    $months_1 = $conn->real_escape_string(getval('months_1'));
    $status_1 = $conn->real_escape_string(getval('status_1'));
    $company_name_2 = $conn->real_escape_string(getval('company_name_2'));
    $company_address_2 = $conn->real_escape_string(getval('company_address_2'));
    $position_2 = $conn->real_escape_string(getval('position_2'));
    $months_2 = $conn->real_escape_string(getval('months_2'));
    $status_2 = $conn->real_escape_string(getval('status_2'));
    $company_name_3 = $conn->real_escape_string(getval('company_name_3'));
    $company_address_3 = $conn->real_escape_string(getval('company_address_3'));
    $position_3 = $conn->real_escape_string(getval('position_3'));
    $months_3 = $conn->real_escape_string(getval('months_3'));
    $status_3 = $conn->real_escape_string(getval('status_3'));

    // Other Skills Acquired
    $skill_auto_mechanic = getbool('skill_auto_mechanic');
    $skill_electrician = getbool('skill_electrician');
    $skill_photography = getbool('skill_photography');
    $skill_beautician = getbool('skill_beautician');
    $skill_embroidery = getbool('skill_embroidery');
    $skill_plumbing = getbool('skill_plumbing');
    $skill_carpentry = getbool('skill_carpentry');
    $skill_gardening = getbool('skill_gardening');
    $skill_sewing = getbool('skill_sewing');
    $skill_computer = getbool('skill_computer');
    $skill_masonry = getbool('skill_masonry');
    $skill_stenography = getbool('skill_stenography');
    $skill_domestic = getbool('skill_domestic');
    $skill_painter = getbool('skill_painter');
    $skill_tailoring = getbool('skill_tailoring');
    $skill_driver = getbool('skill_driver');
    $skill_painting = getbool('skill_painting');
    $skill_others = $conn->real_escape_string(getval('skill_others'));

    // Get user_id from session
    $user_id = $_SESSION['user_id'];
    
    // Check if user has existing form to update
    $checkExistingStmt = $conn->prepare("SELECT id, application_status FROM jobseeker WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $checkExistingStmt->bind_param("i", $user_id);
    $checkExistingStmt->execute();
    $existingResult = $checkExistingStmt->get_result();
    $existingFormId = null;
    $isUpdate = false;
    
    if ($existingResult->num_rows > 0) {
        $existingForm = $existingResult->fetch_assoc();
        $existingStatus = strtolower($existingForm['application_status'] ?? '');
        $existingFormId = $existingForm['id'];
        
        // Check if status is Pending - allow update
        if ($existingStatus === 'pending') {
            $isUpdate = true;
        }
        // Check if status is Rejected - allow resubmit only after cooldown
        elseif ($existingStatus === 'rejected') {
            // Check 24-hour cooldown for rejected applications
            $rejectionTime = !empty($existingForm['updated_at']) ? strtotime($existingForm['updated_at']) : strtotime($existingForm['created_at']);
            $currentTime = time();
            $timeSinceRejection = $currentTime - $rejectionTime;
            $cooldownPeriod = 24 * 60 * 60; // 24 hours in seconds
            
            if ($timeSinceRejection < $cooldownPeriod) {
                $remainingSeconds = $cooldownPeriod - $timeSinceRejection;
                $remainingHours = floor($remainingSeconds / 3600);
                $remainingMinutes = floor(($remainingSeconds % 3600) / 60);
                $checkExistingStmt->close();
                sendJsonResponse(false, "You cannot resubmit your form yet. Please wait {$remainingHours} hour(s) and {$remainingMinutes} minute(s) before resubmitting.");
            } else {
                // Cooldown passed, allow resubmit
                $isUpdate = true;
            }
        }
        // If status is Accepted, don't allow update (already checked earlier)
    }
    $checkExistingStmt->close();
    
    // Server-side duplicate check - ONLY for NEW submissions, NOT for updates
    if (!$isUpdate) {
        // Only check for duplicates when creating a NEW form, not when updating
        try {
            $duplicate_check_sql = "SELECT id, surname, firstname, middlename, suffix 
                                   FROM jobseeker 
                                   WHERE LOWER(surname) = LOWER(?) 
                                   AND LOWER(firstname) = LOWER(?) 
                                   AND COALESCE(NULLIF(NULLIF(middlename, ''), 'n/a'), '') = COALESCE(NULLIF(NULLIF(?, ''), 'n/a'), '')
                                   AND COALESCE(NULLIF(NULLIF(suffix, ''), 'n/a'), '') = COALESCE(NULLIF(NULLIF(?, ''), 'n/a'), '')";
            
            $duplicate_stmt = $conn->prepare($duplicate_check_sql);
            if (!$duplicate_stmt) {
                error_log("Duplicate check prepare failed: " . $conn->error);
                sendJsonResponse(false, 'Database prepare error: ' . $conn->error);
            }
            
            $duplicate_stmt->bind_param("ssss", $surname, $firstname, $middlename, $suffix);
            $duplicate_stmt->execute();
            $duplicate_result = $duplicate_stmt->get_result();
            
            if ($duplicate_result->num_rows > 0) {
                $existing_record = $duplicate_result->fetch_assoc();
                
                // Format the existing name for display
                $existing_name = $existing_record['firstname'];
                if (!empty($existing_record['middlename']) && $existing_record['middlename'] !== 'n/a') {
                    $existing_name .= ' ' . $existing_record['middlename'];
                }
                $existing_name .= ' ' . $existing_record['surname'];
                if (!empty($existing_record['suffix']) && $existing_record['suffix'] !== 'n/a') {
                    $existing_name .= ' ' . $existing_record['suffix'];
                }
                
                $duplicate_stmt->close();
                sendJsonResponse(false, 'Duplicate entry detected! A record with the same name combination already exists.', [
                    'duplicate_info' => [
                        'existing_name' => $existing_name
                    ]
                ]);
            }
            $duplicate_stmt->close();
        } catch (Exception $e) {
            error_log("Duplicate check error: " . $e->getMessage());
            sendJsonResponse(false, 'Duplicate check failed: ' . $e->getMessage());
        }
    }
    
    // Determine if this is a "save" (pending) or "resubmit" (rejected)
    $isResubmit = false;
    if ($isUpdate && $existingFormId) {
        $checkStatusStmt = $conn->prepare("SELECT application_status FROM jobseeker WHERE id = ?");
        $checkStatusStmt->bind_param("i", $existingFormId);
        $checkStatusStmt->execute();
        $statusResult = $checkStatusStmt->get_result();
        if ($statusResult->num_rows > 0) {
            $statusRow = $statusResult->fetch_assoc();
            $currentStatus = strtolower($statusRow['application_status'] ?? '');
            $isResubmit = ($currentStatus === 'rejected'); // Resubmit if rejected, save if pending
        }
        $checkStatusStmt->close();
    }
    
    // Handle resume and esignature file updates (only if new files uploaded)
    $resumeUpdate = !empty($resume_filename) ? "resume_file = '$resume_filename', " : "";
    $esignatureUpdate = !empty($esignature_filename) ? "esignature_file = '$esignature_filename', " : "";
    
    // Status update: Keep "Pending" if saving, change to "Pending" if resubmitting
    $statusUpdate = $isResubmit ? "application_status = 'Pending'" : "application_status = application_status"; // Keep current status if saving

    // Build SQL - Use UPDATE if existing form, INSERT if new
    if ($isUpdate && $existingFormId) {
        // UPDATE existing form
        $sql = "UPDATE jobseeker SET 
            surname = '$surname', firstname = '$firstname', middlename = '$middlename', suffix = '$suffix', dob = '$dob', sex = '$sex', religion = '$religion', civilstatus = '$civilstatus', street = '$street', barangay = '$barangay', municipality = '$municipality', province = '$province', tin = '$tin', height = '$height', contact = '$contact', email = '$email',
            hasDisability = $hasDisability, disability_speech = $disability_speech, disability_hearing = $disability_hearing, disability_visual = $disability_visual, disability_mental = $disability_mental, disability_others = $disability_others, disability_other = '$disability_other',
            employed = $employed, employment_type_wage = $employment_type_wage, employment_type_self = $employment_type_self, self_employed_specify = '$self_employed_specify', self_type_voluntary = $self_type_voluntary, self_type_vendor = $self_type_vendor, self_type_homebased = $self_type_homebased, self_type_transport = $self_type_transport, self_type_domestic = $self_type_domestic, self_type_fisherfolk = $self_type_fisherfolk, self_type_freelancer = $self_type_freelancer, self_type_artisan = $self_type_artisan, self_type_others = $self_type_others, other_jobs = '$other_jobs',
            unemployed = $unemployed, unemployed_months = '$unemployed_months', unemployed_type_first = $unemployed_type_first, unemployed_type_local = $unemployed_type_local, unemployed_type_resigned = $unemployed_type_resigned, unemployed_type_finished = $unemployed_type_finished, unemployed_type_public = $unemployed_type_public, unemployed_type_retired = $unemployed_type_retired, unemployed_type_terminated = $unemployed_type_terminated, unemployed_type_terminated_abroad = $unemployed_type_terminated_abroad, unemployed_type_others = $unemployed_type_others, terminated_country = '$terminated_country', unemployed_other_specify = '$unemployed_other_specify',
            ofw = '$ofw', ofw_country = '$ofw_country', returnee = '$returnee', deployment_country = '$deployment_country', return_month = '$return_month', return_year = '$return_year', abroad = '$abroad', beneficiary = '$beneficiary', household_id = '$household_id',
            occupation1 = '$occupation1', occupation2 = '$occupation2', occupation3 = '$occupation3', fulltime = $fulltime, parttime = $parttime, local1 = '$local1', local2 = '$local2', local3 = '$local3', overseas1 = '$overseas1', overseas2 = '$overseas2', overseas3 = '$overseas3',
            english_read = $english_read, english_write = $english_write, english_speak = $english_speak, english_understand = $english_understand, filipino_read = $filipino_read, filipino_write = $filipino_write, filipino_speak = $filipino_speak, filipino_understand = $filipino_understand,
            mandarin_read = $mandarin_read, mandarin_write = $mandarin_write, mandarin_speak = $mandarin_speak, mandarin_understand = $mandarin_understand, other_language = '$other_language', other_read = $other_read, other_write = $other_write, other_speak = $other_speak, other_understand = $other_understand,
            inschool = '$inschool', level = '$level', course = '$course', year_graduated = '$year_graduated', level_reached = '$level_reached', last_attended = '$last_attended',
            training_course_1 = '$training_course_1', training_hours_1 = '$training_hours_1', training_institution_1 = '$training_institution_1', training_skills_1 = '$training_skills_1', training_cert_1 = '$training_cert_1',
            training_course_2 = '$training_course_2', training_hours_2 = '$training_hours_2', training_institution_2 = '$training_institution_2', training_skills_2 = '$training_skills_2', training_cert_2 = '$training_cert_2',
            training_course_3 = '$training_course_3', training_hours_3 = '$training_hours_3', training_institution_3 = '$training_institution_3', training_skills_3 = '$training_skills_3', training_cert_3 = '$training_cert_3',
            eligibility_1 = '$eligibility_1', eligibility_date_1 = '$eligibility_date_1', eligibility_2 = '$eligibility_2', eligibility_date_2 = '$eligibility_date_2', prc_1 = '$prc_1', prc_valid_1 = '$prc_valid_1', prc_2 = '$prc_2', prc_valid_2 = '$prc_valid_2',
            company_name_1 = '$company_name_1', company_address_1 = '$company_address_1', position_1 = '$position_1', months_1 = '$months_1', status_1 = '$status_1',
            company_name_2 = '$company_name_2', company_address_2 = '$company_address_2', position_2 = '$position_2', months_2 = '$months_2', status_2 = '$status_2',
            company_name_3 = '$company_name_3', company_address_3 = '$company_address_3', position_3 = '$position_3', months_3 = '$months_3', status_3 = '$status_3',
            skill_auto_mechanic = $skill_auto_mechanic, skill_electrician = $skill_electrician, skill_photography = $skill_photography, skill_beautician = $skill_beautician, skill_embroidery = $skill_embroidery, skill_plumbing = $skill_plumbing, skill_carpentry = $skill_carpentry, skill_gardening = $skill_gardening, skill_sewing = $skill_sewing, skill_computer = $skill_computer, skill_masonry = $skill_masonry, skill_stenography = $skill_stenography, skill_domestic = $skill_domestic, skill_painter = $skill_painter, skill_tailoring = $skill_tailoring, skill_driver = $skill_driver, skill_painting = $skill_painting, skill_others = '$skill_others',
            {$resumeUpdate}{$esignatureUpdate}submission_date = '$submission_date', submission_month = $submission_month, submission_year = $submission_year, $statusUpdate
            WHERE id = $existingFormId";
    } else {
        // INSERT new form
        $sql = "INSERT INTO jobseeker (
            user_id, surname, firstname, middlename, suffix, dob, sex, religion, civilstatus, street, barangay, municipality, province, tin, height, contact, email,
            hasDisability, disability_speech, disability_hearing, disability_visual, disability_mental, disability_others, disability_other,
            employed, employment_type_wage, employment_type_self, self_employed_specify, self_type_voluntary, self_type_vendor, self_type_homebased, self_type_transport, self_type_domestic, self_type_fisherfolk, self_type_freelancer, self_type_artisan, self_type_others, other_jobs,
            unemployed, unemployed_months, unemployed_type_first, unemployed_type_local, unemployed_type_resigned, unemployed_type_finished, unemployed_type_public, unemployed_type_retired, unemployed_type_terminated, unemployed_type_terminated_abroad, unemployed_type_others, terminated_country, unemployed_other_specify,
            ofw, ofw_country, returnee, deployment_country, return_month, return_year, abroad, beneficiary, household_id,
            occupation1, occupation2, occupation3, fulltime, parttime, local1, local2, local3, overseas1, overseas2, overseas3,
            english_read, english_write, english_speak, english_understand, filipino_read, filipino_write, filipino_speak, filipino_understand,
            mandarin_read, mandarin_write, mandarin_speak, mandarin_understand, other_language, other_read, other_write, other_speak, other_understand,
            inschool, level, course, year_graduated, level_reached, last_attended,
            training_course_1, training_hours_1, training_institution_1, training_skills_1, training_cert_1,
            training_course_2, training_hours_2, training_institution_2, training_skills_2, training_cert_2,
            training_course_3, training_hours_3, training_institution_3, training_skills_3, training_cert_3,
            eligibility_1, eligibility_date_1, eligibility_2, eligibility_date_2, prc_1, prc_valid_1, prc_2, prc_valid_2,
            company_name_1, company_address_1, position_1, months_1, status_1,
            company_name_2, company_address_2, position_2, months_2, status_2,
            company_name_3, company_address_3, position_3, months_3, status_3,
            skill_auto_mechanic, skill_electrician, skill_photography, skill_beautician, skill_embroidery, skill_plumbing, skill_carpentry, skill_gardening, skill_sewing, skill_computer, skill_masonry, skill_stenography, skill_domestic, skill_painter, skill_tailoring, skill_driver, skill_painting, skill_others,
            resume_file, esignature_file, submission_date, submission_month, submission_year, application_status
        ) VALUES (
            $user_id, '$surname', '$firstname', '$middlename', '$suffix', '$dob', '$sex', '$religion', '$civilstatus', '$street', '$barangay', '$municipality', '$province', '$tin', '$height', '$contact', '$email',
            $hasDisability, $disability_speech, $disability_hearing, $disability_visual, $disability_mental, $disability_others, '$disability_other',
            $employed, $employment_type_wage, $employment_type_self, '$self_employed_specify', $self_type_voluntary, $self_type_vendor, $self_type_homebased, $self_type_transport, $self_type_domestic, $self_type_fisherfolk, $self_type_freelancer, $self_type_artisan, $self_type_others, '$other_jobs',
            $unemployed, '$unemployed_months', $unemployed_type_first, $unemployed_type_local, $unemployed_type_resigned, $unemployed_type_finished, $unemployed_type_public, $unemployed_type_retired, $unemployed_type_terminated, $unemployed_type_terminated_abroad, $unemployed_type_others, '$terminated_country', '$unemployed_other_specify',
            '$ofw', '$ofw_country', '$returnee', '$deployment_country', '$return_month', '$return_year', '$abroad', '$beneficiary', '$household_id',
            '$occupation1', '$occupation2', '$occupation3', $fulltime, $parttime, '$local1', '$local2', '$local3', '$overseas1', '$overseas2', '$overseas3',
            $english_read, $english_write, $english_speak, $english_understand, $filipino_read, $filipino_write, $filipino_speak, $filipino_understand,
            $mandarin_read, $mandarin_write, $mandarin_speak, $mandarin_understand, '$other_language', $other_read, $other_write, $other_speak, $other_understand,
            '$inschool', '$level', '$course', '$year_graduated', '$level_reached', '$last_attended',
            '$training_course_1', '$training_hours_1', '$training_institution_1', '$training_skills_1', '$training_cert_1',
            '$training_course_2', '$training_hours_2', '$training_institution_2', '$training_skills_2', '$training_cert_2',
            '$training_course_3', '$training_hours_3', '$training_institution_3', '$training_skills_3', '$training_cert_3',
            '$eligibility_1', '$eligibility_date_1', '$eligibility_2', '$eligibility_date_2', '$prc_1', '$prc_valid_1', '$prc_2', '$prc_valid_2',
            '$company_name_1', '$company_address_1', '$position_1', '$months_1', '$status_1',
            '$company_name_2', '$company_address_2', '$position_2', '$months_2', '$status_2',
            '$company_name_3', '$company_address_3', '$position_3', '$months_3', '$status_3',
            $skill_auto_mechanic, $skill_electrician, $skill_photography, $skill_beautician, $skill_embroidery, $skill_plumbing, $skill_carpentry, $skill_gardening, $skill_sewing, $skill_computer, $skill_masonry, $skill_stenography, $skill_domestic, $skill_painter, $skill_tailoring, $skill_driver, $skill_painting, '$skill_others',
            '$resume_filename', '$esignature_filename', '$submission_date', $submission_month, $submission_year, 'Pending'
        )";
    }

    // Start transaction for atomic operation
    $conn->autocommit(FALSE);
    
    try {
        if ($conn->query($sql) === TRUE) {
            // Commit the transaction
            $conn->commit();
            $conn->autocommit(TRUE);
            
            // Send confirmation email ONLY for NEW submissions, NOT for updates
            if (!$isUpdate) {
                // Only send email for new form submissions
                $user_email = $email; // Email from form submission
                $user_firstname = $firstname;
                $user_surname = $surname;
                
                // Send email notification (non-blocking - don't fail if email fails)
                try {
                    sendSubmissionConfirmationEmail($user_email, $user_firstname, $user_surname);
                } catch (Exception $email_error) {
                    // Log email error but don't fail the submission
                    error_log("Email sending error: " . $email_error->getMessage());
                }
            }
            
            if ($isUpdate) {
                $message = $isResubmit ? 'Your NSRP form has been resubmitted successfully! Status changed to Pending.' : 'Your NSRP form has been saved successfully!';
            } else {
                $message = 'Registration saved successfully!';
            }

            // Create in-app notification for the submitter.
            $checkTypeColumn = $conn->query("SHOW COLUMNS FROM notifications LIKE 'type'");
            $hasTypeColumn = $checkTypeColumn && $checkTypeColumn->num_rows > 0;
            $notifTitle = $isResubmit ? 'NSRP Form Resubmitted' : ($isUpdate ? 'NSRP Form Updated' : 'NSRP Form Submitted');
            $notifMessage = $isResubmit
                ? 'Your NSRP form has been resubmitted and is now pending review.'
                : ($isUpdate ? 'Your NSRP form changes were saved successfully.' : 'Your NSRP form was submitted successfully and is pending review.');
            if ($hasTypeColumn) {
                $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'nrsp')");
                if ($notifStmt) {
                    $notifStmt->bind_param("iss", $user_id, $notifTitle, $notifMessage);
                    $notifStmt->execute();
                    $notifStmt->close();
                }
            } else {
                $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
                if ($notifStmt) {
                    $notifStmt->bind_param("iss", $user_id, $notifTitle, $notifMessage);
                    $notifStmt->execute();
                    $notifStmt->close();
                }
            }
            sendJsonResponse(true, $message);
        } else {
            // Rollback on error
            $conn->rollback();
            $conn->autocommit(TRUE);
            error_log("Database insert error: " . $conn->error);
            sendJsonResponse(false, 'Database error: ' . $conn->error);
        }
    } catch (Exception $e) {
        // Rollback on exception
        $conn->rollback();
        $conn->autocommit(TRUE);
        error_log("Database insert exception: " . $e->getMessage());
        sendJsonResponse(false, 'Database insert failed: ' . $e->getMessage());
    }
}

// Close database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>WorkConnect - Job Application Form</title>
  <link rel="stylesheet" href="../assets/css/Employee-apply.css">
  <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
  <script>
    // Render alerts on the parent dashboard when loaded in iframe.
    (function () {
      if (window.self === window.top) return;
      const localFire = Swal.fire.bind(Swal);
      Swal.fire = function () {
        try {
          if (window.top && typeof window.top.showGlobalSwal === 'function') {
            return window.top.showGlobalSwal.apply(window.top, arguments);
          }
        } catch (e) {
          // Fall back to local modal when parent access is unavailable.
        }
        return localFire.apply(Swal, arguments);
      };
    })();
  </script>
  
  <style>
    /* Make searchable selects visually consistent with existing inputs */
    .ts-wrapper.single .ts-control,
    .ts-wrapper.single .ts-control input {
      font-size: 14px !important;
      line-height: 1.4 !important;
      color: #333 !important;
    }
    .ts-wrapper.single .ts-control {
      min-height: 34px !important;
      border: 1px solid #ccc !important;
      border-radius: 3px !important;
      box-shadow: none !important;
      padding: 6px 10px !important;
    }
    .ts-wrapper.single.input-active .ts-control {
      border-color: #1976d2 !important;
      box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.12) !important;
    }
    .ts-dropdown {
      font-size: 14px !important;
      border: 1px solid #ccc !important;
      border-radius: 3px !important;
    }
    .ts-dropdown .option {
      padding: 8px 10px !important;
    }
    .pref-section-title {
      font-weight: 700;
    }
    .stacked-pref-inputs {
      display: grid;
      grid-template-columns: 1fr;
      gap: 10px;
      width: 100%;
    }
    .stacked-pref-inputs input,
    .stacked-pref-inputs select,
    .stacked-pref-inputs .ts-wrapper {
      width: 100% !important;
      box-sizing: border-box;
    }
    .local-location-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 6px;
      align-items: center;
      margin-bottom: 4px;
      width: 100%;
    }
    .local-location-index {
      font-size: 13px;
      color: #555;
      text-align: center;
      font-weight: 600;
    }

    <?php if ($isIframe): ?>
    /* Iframe-specific styles — parent dashboard scrolls; no inner vertical scroll on body */
    body {
      margin: 0;
      padding: 0;
      background: #fff;
      overflow-x: hidden;
      overflow-y: visible;
    }
    html {
      overflow-y: visible;
    }
    
    .progress-indicator {
      margin-bottom: 20px;
      padding: 15px;
      background: #f8f9fa;
      border-radius: 8px;
      border: 1px solid #e0e0e0;
      display: flex !important;
      flex-direction: column !important;
      justify-content: flex-start !important;
      align-items: stretch !important;
    }
    <?php else: ?>
    /* Standalone styles */
    .progress-indicator {
      margin-bottom: 30px;
      padding: 20px;
      background: #f8f9fa;
      border-radius: 8px;
      border: 1px solid #e0e0e0;
      display: flex !important;
      flex-direction: column !important;
      justify-content: flex-start !important;
      align-items: stretch !important;
    }
    <?php endif; ?>
    
    /* Specific CSS for Personal Information section only */
    #section1_1 .form-row {
      display: flex !important;
      flex-wrap: nowrap !important;
      gap: 12px !important;
      width: 100% !important;
      align-items: flex-end !important;
    }
    
    #section1_1 .form-row .form-group {
      flex: 1 !important;
      min-width: 0 !important;
      max-width: none !important;
    }
    
    #section1_1 .form-row input[type="text"],
    #section1_1 .form-row input[type="email"],
    #section1_1 .form-row input[type="date"],
    #section1_1 .form-row select {
      width: 100% !important;
      min-width: 0 !important;
    }
    
    /* Specific CSS for Language section only */
    #section2_2 .form-row {
      display: flex !important;
      flex-wrap: nowrap !important;
      gap: 12px !important;
      width: 100% !important;
      align-items: flex-start !important;
    }
    
    #section2_2 .form-row .form-group {
      flex: 1 !important;
      min-width: 0 !important;
      max-width: none !important;
    }
    
    #section2_2 .form-row input[type="text"],
    #section2_2 .form-row input[type="checkbox"] {
      width: 100% !important;
      min-width: 0 !important;
    }
    
    /* Style for "Select All" checkboxes in Language section */
    #section2_2 .form-group .select-all-label {
      font-weight: bold !important;
      color: #1a3876 !important;
      margin-bottom: 8px !important;
      padding: 4px 8px !important;
      background: #f0f4ff !important;
      border-radius: 4px !important;
      border: 1px solid #1a3876 !important;
      display: flex !important;
      align-items: center !important;
    }
    
    #section2_2 .form-group .select-all-label:hover {
      background: #e0e8ff !important;
      cursor: pointer !important;
    }
    
    /* Fix checkbox and label alignment in Language section */
    #section2_2 .form-group label {
      display: flex !important;
      align-items: center !important;
      gap: 6px !important;
      margin-bottom: 8px !important;
      font-size: 0.9rem !important;
    }
    
    #section2_2 .form-group input[type="checkbox"] {
      width: auto !important;
      margin: 0 !important;
      flex-shrink: 0 !important;
    }
    
    #section2_2 .form-group input[type="text"] {
      margin-bottom: 8px !important;
    }

    /* Keep Course/Strand dropdown same look/width as Level dropdown */
    #section2_3 #courseField .ts-wrapper {
      width: 100% !important;
      display: block !important;
    }
    #section2_3 #courseField .ts-control {
      width: 100% !important;
      min-height: 34px !important;
      border: 1px solid #ccc !important;
      border-radius: 3px !important;
      box-sizing: border-box !important;
    }
    #section2_3 #courseField .ts-control input {
      font-size: 14px !important;
      line-height: 1.4 !important;
    }
    
    /* Specific CSS for Eligibility section only */
    #section3_2 .form-row {
      display: flex !important;
      flex-wrap: nowrap !important;
      gap: 12px !important;
      width: 100% !important;
      align-items: flex-end !important;
    }
    
    #section3_2 .form-row .form-group {
      flex: 1 !important;
      min-width: 0 !important;
      max-width: none !important;
    }
    
    #section3_2 .form-row input[type="text"] {
      width: 100% !important;
      min-width: 0 !important;
    }

    /* Employment step: keep specify inputs full-width without overlap */
    #employedFields,
    #selfTypeFields {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 6px 10px;
    }
    #employedFields label,
    #selfTypeFields label {
      flex: 0 1 auto;
      max-width: 170px;
    }
    #employedFields input[name="self_employed_specify"],
    #selfTypeFields input[name="other_jobs"] {
      flex: 0 0 70%;
      width: 70%;
      max-width: 420px;
      min-width: 0;
      margin-left: 0;
      margin-top: 4px;
      box-sizing: border-box;
    }
    #unemployedFields input[name="unemployed_months"],
    #unemployedTypeFields input[name="terminated_country"],
    #unemployedTypeFields input[name="unemployed_other_specify"] {
      flex: 0 0 70%;
      width: 70%;
      max-width: 420px;
      min-width: 0;
      margin-left: 0;
      margin-top: 4px;
      box-sizing: border-box;
    }
    #returneeFields #deployment_country,
    #returneeFields .ts-wrapper {
      flex: 0 0 70%;
      width: 70%;
      max-width: 420px;
      min-width: 0;
      box-sizing: border-box;
    }
    #ofwCountryGroup #ofw_country,
    #ofwCountryGroup .ts-wrapper,
    #householdIdGroup #household_id {
      flex: 0 0 70%;
      width: 70%;
      max-width: 420px;
      min-width: 0;
      box-sizing: border-box;
    }
    #ofwCountryGroup,
    #householdIdGroup {
      display: inline-flex;
      align-items: center;
      flex: 0 0 70%;
      width: 70%;
      max-width: 420px;
      min-width: 220px;
    }
    
    
    .progress-bar {
      width: 100%;
      height: 6px;
      background: #e0e0e0;
      border-radius: 3px;
      overflow: hidden;
    }
    
    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, #1976d2, #42a5f5);
      border-radius: 3px;
      transition: width 0.3s ease;
      width: 10%;
    }
    
    .progress-fill.section1 { width: 10%; }
    .progress-fill.section2 { width: 20%; }
    .progress-fill.section3 { width: 30%; }
    .progress-fill.section4 { width: 40%; }
    .progress-fill.section5 { width: 50%; }
    .progress-fill.section6 { width: 60%; }
    .progress-fill.section7 { width: 70%; }
    .progress-fill.section8 { width: 80%; }
    .progress-fill.section9 { width: 90%; }
    .progress-fill.section10 { width: 100%; }
    .mobile-review-label {
      display: none;
    }
    
    .progress-steps {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      flex-wrap: wrap;
      gap: 10px;
    }
    
    .step {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      opacity: 0.5;
      transition: all 0.3s ease;
      flex: 1;
      min-width: 80px;
      max-width: 100px;
      cursor: pointer;
      padding: 5px;
      border-radius: 8px;
      position: relative;
    }
    
    .step:hover {
      opacity: 0.9;
      background-color: rgba(25, 118, 210, 0.15);
      transform: translateY(-2px);
    }
    
    .step:active {
      transform: translateY(0);
    }
    
    .step.active {
      opacity: 1;
    }
    
    .step.completed {
      opacity: 1;
    }
    
    .step.completed:hover {
      background-color: rgba(76, 175, 80, 0.15);
    }
    
    .step-label {
      font-size: 10px;
    }
    
    .step.active {
      opacity: 1;
    }
    
    .step.completed {
      opacity: 1;
    }
    
    .step-number {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #e0e0e0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 16px;
      color: #666;
      margin-bottom: 8px;
      transition: all 0.3s ease;
    }
    
    .step.active .step-number {
      background: #1976d2;
      color: white;
    }
    
    .step.completed .step-number {
      background: #4caf50;
      color: white;
    }
    
    .step-label {
      font-size: 12px;
      color: #666;
      font-weight: 500;
    }
    
    .step.active .step-label {
      color: #1976d2;
      font-weight: 600;
    }
    
    .step.completed .step-label {
      color: #4caf50;
      font-weight: 600;
    }
    
    .step-number-label {
      font-size: 10px;
      color: #999;
      font-weight: 500;
      margin-top: 2px;
    }
    
    .step.active .step-number-label {
      color: #1976d2;
      font-weight: 600;
    }
    
    .step.completed .step-number-label {
      color: #4caf50;
      font-weight: 600;
    }
    
    /* Adjust form container max width */
    .apply-form-container {
      max-width: 1000px;
      margin: 0 auto;
      padding: 20px;
    }
    
    /* Mobile-friendly responsive adjustments */
    @media (max-width: 768px) {
      body {
        padding: 10px !important;
        margin: 0 !important;
        font-size: 14px !important;
      }
      
      .apply-form-container {
        padding: 10px !important;
        max-width: 100% !important;
        margin: 0 !important;
      }
      
      .progress-indicator {
        margin-bottom: 15px !important;
        padding: 10px !important;
      }
      
      .progress-steps {
        flex-direction: column !important;
        gap: 8px !important;
        padding: 10px !important;
        margin-bottom: 10px !important;
      }
      
      .step {
        flex-direction: column !important;
        text-align: center !important;
        padding: 8px !important;
        background: #f8f9fa !important;
        border-radius: 8px !important;
        margin-bottom: 5px !important;
        align-items: center !important;
        gap: 4px !important;
      }
      
      .step-number {
        margin-right: 0 !important;
        margin-bottom: 4px !important;
        width: 30px !important;
        height: 30px !important;
        font-size: 12px !important;
      }
      
      .step-label {
        font-size: 11px !important;
        font-weight: 500 !important;
      }
      
      .step-number-label {
        font-size: 8px !important;
        margin-top: 1px !important;
        line-height: 1.2 !important;
        text-align: center !important;
        width: 100% !important;
      }
      
      /* Mobile step content layout */
      .step > div:not(.step-number) {
        display: flex !important;
        flex-direction: column !important;
        gap: 2px !important;
        align-items: center !important;
        text-align: center !important;
      }
      
      /* Form sections mobile optimization */
      .form-section {
        padding: 10px !important;
        margin: 5px 0 !important;
      }
      
      /* Educational background section mobile optimization */
      #section2_3 .form-row {
        flex-direction: column !important;
        gap: 8px !important;
        margin-bottom: 10px !important;
      }
      
      #section2_3 .form-group {
        width: 100% !important;
        margin-bottom: 8px !important;
      }
      
      #section2_3 .form-group label {
        font-size: 0.85rem !important;
        margin-bottom: 4px !important;
      }
      
      #section2_3 select,
      #section2_3 input {
        padding: 8px !important;
        font-size: 13px !important;
        width: 100% !important;
      }
      
      /* Compact layout for "If Undergraduate" section */
      #section2_3 .form-row:has(.form-group) {
        display: flex !important;
        flex-direction: row !important;
        gap: 10px !important;
        align-items: flex-end !important;
      }
      
      #section2_3 .form-row:has(.form-group) .form-group {
        flex: 1 !important;
        margin-bottom: 0 !important;
      }
      
      .form-title {
        font-size: 1.1rem !important;
        line-height: 1.2 !important;
        margin-bottom: 10px !important;
        text-align: center !important;
      }
      
      fieldset {
        border: 1px solid #ddd !important;
        border-radius: 8px !important;
        padding: 15px !important;
        margin: 15px 0 !important;
      }
      
      legend {
        font-size: 1rem !important;
        font-weight: bold !important;
        padding: 0 10px !important;
        color: #1a3876 !important;
      }
      
      /* Form rows mobile layout */
      .form-row {
        display: flex !important;
        flex-direction: column !important;
        gap: 10px !important;
        margin-bottom: 15px !important;
      }
      
      .form-group {
        width: 100% !important;
        margin-bottom: 10px !important;
      }
      
      .form-group label {
        display: block !important;
        margin-bottom: 5px !important;
        font-weight: 600 !important;
        font-size: 0.9rem !important;
        color: #333 !important;
      }
      
      input[type="text"], 
      input[type="email"], 
      input[type="date"], 
      input[type="file"],
      select, 
      textarea {
        width: 100% !important;
        padding: 10px !important;
        border: 1px solid #ddd !important;
        border-radius: 6px !important;
        font-size: 14px !important;
        box-sizing: border-box !important;
        -webkit-appearance: none !important;
        appearance: none !important;
      }
      
      select {
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e") !important;
        background-repeat: no-repeat !important;
        background-position: right 10px center !important;
        background-size: 16px !important;
        padding-right: 35px !important;
      }

      #employedFields input[name="self_employed_specify"],
      #selfTypeFields input[name="other_jobs"] {
        width: 100% !important;
        min-width: 100% !important;
        margin-left: 0 !important;
        margin-top: 8px;
      }

      /* Mobile clarity: left-align only checkbox labels (do not force hidden labels visible) */
      #employedFields,
      #selfTypeFields,
      #unemployedTypeFields {
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: flex-start !important;
        gap: 6px 10px !important;
      }
      #employedFields label:has(input[type="checkbox"]),
      #selfTypeFields label:has(input[type="checkbox"]),
      #unemployedTypeFields label:has(input[type="checkbox"]) {
        display: inline-flex !important;
        justify-content: flex-start !important;
        align-items: center !important;
        width: auto !important;
        text-align: left !important;
        margin-bottom: 6px !important;
      }

      /* Mobile: keep OFW country / 4Ps field full width */
      #ofwCountryGroup,
      #householdIdGroup,
      #ofwCountryGroup .ts-wrapper,
      #householdIdGroup input {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 100% !important;
      }

      /* Mobile: widen "Others" language dropdown */
      #other_language,
      #other_language-ts-control,
      #section2_2 .form-group .ts-wrapper,
      #section2_2 .form-group .ts-control {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 100% !important;
        box-sizing: border-box !important;
      }

      /* Mobile readability for technical training + work experience */
      #section3_1 .tech-training-grid,
      #section3_3 .work-experience-grid {
        grid-template-columns: 1fr !important;
        gap: 8px !important;
      }
      #section3_1 .tech-training-grid .header,
      #section3_3 .work-experience-grid > div:not(.mobile-review-label) {
        display: none !important;
      }
      .mobile-review-label {
        display: block !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        color: #2f3b52 !important;
        margin: 2px 0 2px !important;
        line-height: 1.2 !important;
      }

      /* Present Address TomSelect fields: force full-width on mobile */
      #province,
      #municipality,
      #barangay,
      #province-ts-control,
      #municipality-ts-control,
      #barangay-ts-control,
      #section1_1 .form-group .ts-wrapper,
      #section1_1 .form-group .ts-control {
        width: 100% !important;
        min-width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
      }
      
      /* Checkbox and radio button styling */
      input[type="checkbox"], 
      input[type="radio"] {
        width: 18px !important;
        height: 18px !important;
        margin-right: 8px !important;
        vertical-align: middle !important;
      }
      
      label {
        display: flex !important;
        align-items: center !important;
        margin-bottom: 8px !important;
        font-size: 0.9rem !important;
        cursor: pointer !important;
      }
      
      /* Form actions mobile */
      .form-actions {
        display: flex !important;
        justify-content: space-between !important;
        gap: 8px !important;
        margin-top: 10px !important;
        padding: 10px 0 !important;
      }
      
      .back-btn, .next-btn {
        flex: 1 !important;
        padding: 12px 20px !important;
        font-size: 14px !important;
        border-radius: 6px !important;
        border: none !important;
        cursor: pointer !important;
        font-weight: 600 !important;
        transition: all 0.3s ease !important;
      }
      
      .back-btn {
        background: #6c757d !important;
        color: white !important;
      }
      
      .next-btn {
        background: #1a3876 !important;
        color: white !important;
      }
      
      .next-btn:disabled {
        background: #6c757d !important;
        color: #fff !important;
        cursor: not-allowed !important;
        opacity: 0.6 !important;
      }
      
      .back-btn:hover, .next-btn:hover:not(:disabled) {
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
      }
      
      /* Grid layouts mobile */
      .tech-training-grid {
        display: block !important;
      }
      
      .tech-training-grid .header {
        font-weight: bold !important;
        background: #f8f9fa !important;
        padding: 8px !important;
        margin-bottom: 5px !important;
        border-radius: 4px !important;
        font-size: 0.85rem !important;
      }
      
      .tech-training-grid input {
        width: 100% !important;
        margin-bottom: 10px !important;
        padding: 8px !important;
        border: 1px solid #ddd !important;
        border-radius: 4px !important;
      }
      
      /* Work experience grid mobile */
      div[style*="grid-template-columns"] {
        display: block !important;
      }
      
      div[style*="grid-template-columns"] > div {
        margin-bottom: 10px !important;
        padding: 8px !important;
        background: #f8f9fa !important;
        border-radius: 4px !important;
        font-weight: bold !important;
        font-size: 0.85rem !important;
      }
      
      div[style*="grid-template-columns"] input {
        width: 100% !important;
        margin-bottom: 10px !important;
        padding: 8px !important;
        border: 1px solid #ddd !important;
        border-radius: 4px !important;
      }
      
      /* E-signature upload mobile */
      .esignature-upload-container {
        margin: 15px 0 !important;
      }
      
      .esignature-label {
        padding: 20px 15px !important;
        min-height: 100px !important;
        text-align: center !important;
      }
      
      .esignature-icon {
        font-size: 2rem !important;
        margin-bottom: 8px !important;
      }
      
      .esignature-text {
        font-size: 1rem !important;
        margin-bottom: 4px !important;
      }
      
      .esignature-subtext {
        font-size: 0.8rem !important;
      }
      
      .esignature-preview {
        flex-direction: column !important;
        text-align: center !important;
        gap: 8px !important;
      }
      
      .esignature-preview img {
        max-width: 80px !important;
        max-height: 50px !important;
      }
      
      .esignature-filename {
        font-size: 0.8rem !important;
      }
      
      .esignature-instructions {
        padding: 10px 12px !important;
        font-size: 0.8rem !important;
        flex-direction: column !important;
        text-align: center !important;
        gap: 5px !important;
      }
      
      /* Resume upload mobile */
      .resume-upload-row {
        flex-direction: column !important;
        gap: 10px !important;
      }
      
      .resume-upload-label {
        font-size: 1rem !important;
        margin-bottom: 8px !important;
      }
      
      .resume-upload-input {
        width: 100% !important;
        padding: 10px !important;
        border: 2px dashed #007bff !important;
        border-radius: 8px !important;
        background: #f8f9ff !important;
        cursor: pointer !important;
      }
      
      .resume-upload-hint {
        font-size: 0.8rem !important;
        color: #666 !important;
        text-align: center !important;
      }
    }
    
    @media (max-width: 480px) {
      body {
        padding: 5px !important;
        font-size: 13px !important;
      }
      
      .apply-form-container {
        padding: 5px !important;
      }
      
      .progress-indicator {
        margin-bottom: 10px !important;
        padding: 8px !important;
      }
      
      .progress-steps {
        padding: 8px !important;
        gap: 6px !important;
        margin-bottom: 8px !important;
      }
      
      .step {
        padding: 6px !important;
      }
      
      .step-number {
        width: 25px !important;
        height: 25px !important;
        font-size: 10px !important;
        margin-right: 10px !important;
      }
      
      .step-label {
        font-size: 10px !important;
      }
      
      .step-number-label {
        font-size: 7px !important;
        margin-top: 1px !important;
        line-height: 1.1 !important;
        text-align: center !important;
        width: 100% !important;
        display: block !important;
      }
      
      /* Ensure consistent column layout for small mobile */
      .step {
        flex-direction: column !important;
        text-align: center !important;
        align-items: center !important;
      }
      
      .form-section {
        padding: 8px !important;
        margin: 3px 0 !important;
      }
      
      /* Educational background section mobile optimization for smaller screens */
      #section2_3 .form-row {
        gap: 6px !important;
        margin-bottom: 8px !important;
      }
      
      #section2_3 .form-group {
        margin-bottom: 6px !important;
      }
      
      #section2_3 .form-group label {
        font-size: 0.8rem !important;
        margin-bottom: 3px !important;
      }
      
      #section2_3 select,
      #section2_3 input {
        padding: 6px !important;
        font-size: 12px !important;
      }
      
      /* Compact layout for "If Undergraduate" section on smaller screens */
      #section2_3 .form-row:has(.form-group) {
        gap: 8px !important;
      }
      
      #section2_3 .form-row:has(.form-group) .form-group {
        flex: 1 !important;
        margin-bottom: 0 !important;
      }
      
      .form-title {
        font-size: 1rem !important;
        line-height: 1.2 !important;
        margin-bottom: 8px !important;
      }
      
      fieldset {
        padding: 8px !important;
        margin: 5px 0 !important;
      }
      
      legend {
        font-size: 0.9rem !important;
        padding: 0 8px !important;
      }
      
      .form-row {
        gap: 8px !important;
        margin-bottom: 12px !important;
      }
      
      .form-group {
        margin-bottom: 8px !important;
      }
      
      .form-group label {
        font-size: 0.8rem !important;
        margin-bottom: 4px !important;
      }
      
      input[type="text"], 
      input[type="email"], 
      input[type="date"], 
      input[type="file"],
      select, 
      textarea {
        padding: 8px !important;
        font-size: 13px !important;
      }
      
      input[type="checkbox"], 
      input[type="radio"] {
        width: 16px !important;
        height: 16px !important;
        margin-right: 6px !important;
      }
      
      label {
        font-size: 0.8rem !important;
        margin-bottom: 6px !important;
      }
      
      .form-actions {
        gap: 6px !important;
        margin-top: 8px !important;
        padding: 8px 0 !important;
      }
      
      .back-btn, .next-btn {
        padding: 10px 15px !important;
        font-size: 13px !important;
      }
      
      .tech-training-grid .header {
        padding: 6px !important;
        font-size: 0.8rem !important;
      }
      
      .tech-training-grid input {
        padding: 6px !important;
        margin-bottom: 8px !important;
      }
      
      div[style*="grid-template-columns"] > div {
        padding: 6px !important;
        font-size: 0.8rem !important;
        margin-bottom: 8px !important;
      }
      
      div[style*="grid-template-columns"] input {
        padding: 6px !important;
        margin-bottom: 8px !important;
      }
      
      .esignature-label {
        padding: 15px 10px !important;
        min-height: 80px !important;
      }
      
      .esignature-icon {
        font-size: 1.5rem !important;
        margin-bottom: 6px !important;
      }
      
      .esignature-text {
        font-size: 0.9rem !important;
        margin-bottom: 3px !important;
      }
      
      .esignature-subtext {
        font-size: 0.75rem !important;
      }
      
      .esignature-preview img {
        max-width: 60px !important;
        max-height: 40px !important;
      }
      
      .esignature-filename {
        font-size: 0.75rem !important;
      }
      
      .esignature-instructions {
        padding: 8px 10px !important;
        font-size: 0.75rem !important;
      }
      
      .resume-upload-label {
        font-size: 0.9rem !important;
        margin-bottom: 6px !important;
      }
      
      .resume-upload-input {
        padding: 8px !important;
      }
      
      .resume-upload-hint {
        font-size: 0.75rem !important;
      }
      
      /* Extra small mobile SweetAlert adjustments */
      .swal2-popup {
        width: 95% !important;
        max-width: 350px !important;
        font-size: 13px !important;
        padding: 15px !important;
      }
      
      .swal2-title {
        font-size: 16px !important;
        margin-bottom: 12px !important;
      }
      
      .swal2-html-container {
        font-size: 13px !important;
        line-height: 1.3 !important;
        margin: 12px 0 !important;
      }
      
      .swal2-confirm {
        font-size: 13px !important;
        padding: 8px 16px !important;
        margin-top: 12px !important;
      }
    }
    
    /* Professional E-Signature Upload Styling */
    .esignature-upload-container {
      margin: 20px 0 !important;
      padding: 0 !important;
    }
    
    .esignature-upload-wrapper {
      position: relative;
      margin-bottom: 12px;
    }
    
    .esignature-label {
      display: flex !important;
      flex-direction: column !important;
      align-items: center !important;
      justify-content: center !important;
      padding: 30px 20px !important;
      border: 2px dashed #007bff !important;
      border-radius: 12px !important;
      background: linear-gradient(135deg, #f8f9ff 0%, #e3f2fd 100%) !important;
      cursor: pointer !important;
      transition: all 0.3s ease !important;
      text-align: center !important;
      min-height: 120px !important;
      position: relative !important;
    }
    
    .esignature-label:hover {
      border-color: #0056b3 !important;
      background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%) !important;
      transform: translateY(-2px) !important;
      box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15) !important;
    }
    
    .esignature-icon {
      font-size: 2.5rem !important;
      margin-bottom: 8px !important;
      display: block !important;
    }
    
    .esignature-text {
      font-size: 1.1rem !important;
      font-weight: 600 !important;
      color: #2c3e50 !important;
      margin-bottom: 4px !important;
      display: block !important;
    }
    
    .esignature-subtext {
      font-size: 0.9rem !important;
      color: #6c757d !important;
      display: block !important;
    }
    
    .esignature-input {
      position: absolute !important;
      top: 0 !important;
      left: 0 !important;
      width: 100% !important;
      height: 100% !important;
      opacity: 0 !important;
      cursor: pointer !important;
      z-index: 2 !important;
    }
    
    .esignature-preview {
      position: relative;
      margin-top: 15px;
      padding: 15px;
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 8px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .esignature-preview img {
      max-width: 60px;
      max-height: 40px;
      border-radius: 4px;
      border: 1px solid #dee2e6;
      object-fit: contain;
    }
    
    .esignature-filename {
      flex: 1;
      font-size: 0.9rem;
      color: #495057;
      font-weight: 500;
    }
    
    .esignature-remove {
      background: #dc3545;
      color: white;
      border: none;
      border-radius: 50%;
      width: 24px;
      height: 24px;
      cursor: pointer;
      font-size: 14px;
      font-weight: bold;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background-color 0.2s ease;
    }
    
    .esignature-remove:hover {
      background: #c82333;
    }
    
    .esignature-instructions {
      display: flex !important;
      align-items: center !important;
      gap: 8px !important;
      padding: 12px 16px !important;
      background: #e7f3ff !important;
      border: 1px solid #b3d9ff !important;
      border-radius: 8px !important;
      font-size: 0.85rem !important;
      color: #0066cc !important;
    }
    
    .esignature-info-icon {
      font-size: 1rem !important;
      flex-shrink: 0 !important;
    }
    
    /* Required field asterisk styling */
    .required-asterisk {
      color: #dc3545;
      font-weight: bold;
      margin-left: 2px;
    }
    
    /* Disabled submit button styling */
    .next-btn:disabled,
    button[type="submit"]:disabled {
      background: #6c757d !important;
      color: #fff !important;
      cursor: not-allowed !important;
      opacity: 0.6 !important;
      pointer-events: none !important;
    }
    
    .next-btn:hover:not(:disabled) {
      transform: translateY(-1px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    /* Hide SweetAlert checkbox on mobile */
    @media (max-width: 768px) {
      .swal2-checkbox {
        display: none !important;
      }
      
      .swal2-checkbox + label {
        display: none !important;
      }
      
      .swal2-html-container input[type="checkbox"] {
        display: none !important;
      }
      
      .swal2-html-container label {
        display: none !important;
      }
      
      /* Hide any checkbox-related elements in SweetAlert */
      .swal2-popup input[type="checkbox"],
      .swal2-popup .swal2-checkbox,
      .swal2-popup .swal2-checkbox + label {
        display: none !important;
      }
      
      /* Mobile SweetAlert sizing adjustments */
      .swal2-popup {
        width: 90% !important;
        max-width: 400px !important;
        font-size: 14px !important;
        padding: 20px !important;
      }
      
      .swal2-title {
        font-size: 18px !important;
        margin-bottom: 15px !important;
      }
      
      .swal2-html-container {
        font-size: 14px !important;
        line-height: 1.4 !important;
        margin: 15px 0 !important;
      }
      
      .swal2-confirm {
        font-size: 14px !important;
        padding: 10px 20px !important;
        margin-top: 15px !important;
      }
    }
  </style>
</head>
<body>  
    
    <!-- Main Content: Jobseeker Registration Form -->
      
      <!-- Progress Indicator -->
      <div class="progress-indicator">
        <div class="progress-steps">
          <div class="step active" id="step1Indicator">
            <div class="step-number">I</div>
            <div class="step-label">Personal Info</div>
            <div class="step-number-label">Step 1</div>
          </div>
          <div class="step" id="step2Indicator">
            <div class="step-number">II</div>
            <div class="step-label">Employment</div>
            <div class="step-number-label">Step 2</div>
          </div>
          <div class="step" id="step3Indicator">
            <div class="step-number">III</div>
            <div class="step-label">Job Preference</div>
            <div class="step-number-label">Step 3</div>
          </div>
          <div class="step" id="step4Indicator">
            <div class="step-number">IV</div>
            <div class="step-label">Language</div>
            <div class="step-number-label">Step 4</div>
          </div>
          <div class="step" id="step5Indicator">
            <div class="step-number">V</div>
            <div class="step-label">Education</div>
            <div class="step-number-label">Step 5</div>
          </div>
          <div class="step" id="step6Indicator">
            <div class="step-number">VI</div>
            <div class="step-label">Training</div>
            <div class="step-number-label">Step 6</div>
          </div>
          <div class="step" id="step7Indicator">
            <div class="step-number">VII</div>
            <div class="step-label">Eligibility</div>
            <div class="step-number-label">Step 7</div>
          </div>
          <div class="step" id="step8Indicator">
            <div class="step-number">VIII</div>
            <div class="step-label">Experience</div>
            <div class="step-number-label">Step 8</div>
          </div>
          <div class="step" id="step9Indicator">
            <div class="step-number">IX</div>
            <div class="step-label">Skills</div>
            <div class="step-number-label">Step 9</div>
          </div>
          <div class="step" id="step10Indicator">
            <div class="step-number">X</div>
            <div class="step-label">Resume</div>
            <div class="step-number-label">Step 10</div>
          </div>
        </div>
        <div class="progress-bar">
          <div class="progress-fill" id="progressFill"></div>
        </div>
      </div>
      
      <form class="jobseeker-form" id="jobseekerForm" action="" method="POST" novalidate>
        <div id="formMessage" style="margin-bottom:15px;color:green;font-weight:bold;"></div>
        <!-- Step 1 -->
        <div id="step1Section">
          <!-- Section 1.1: Personal Information -->
          <div id="section1_1" class="form-section">
            <div class="form-date" id="form-date"></div>
            <h3 class="form-title">
              Republic of the Philippines<br>
              Department of Labor and Employment<br>
              NATIONAL SKILLS REGISTRATION PROGRAM<br>
              JOBSEEKER REGISTRATION FORM
            </h3>
            <fieldset>
              <legend>I. PERSONAL INFORMATION</legend>
            <div class="form-row">
              <div class="form-group">
                <label for="surname">SURNAME<span class="required-asterisk">*</span></label>
                <input type="text" id="surname" name="surname" value="<?php echo htmlspecialchars($_SESSION['lastname'] ?? ''); ?>" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{2,40}" maxlength="40" required readonly style="background-color: #f5f5f5; cursor: not-allowed;">
              </div>
              <div class="form-group">
                <label for="firstname">FIRST NAME<span class="required-asterisk">*</span></label>
                <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($_SESSION['firstname'] ?? ''); ?>" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{2,40}" maxlength="40" required readonly style="background-color: #f5f5f5; cursor: not-allowed;">
              </div>
              <div class="form-group">
                <label for="middlename">MIDDLE NAME</label>
                <input type="text" id="middlename" name="middlename" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,40}" maxlength="40">
              </div>
              <div class="form-group">
                <label for="suffix">SUFFIX</label>
                <input type="text" id="suffix" name="suffix" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\.]{0,40}" maxlength="40">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="dob">DATE OF BIRTH<span class="required-asterisk">*</span></label>
                <input type="date" id="dob" name="dob" max="2006-12-31" required>
              </div>
              <div class="form-group">
                <label for="sex">SEX<span class="required-asterisk">*</span></label>
                <select id="sex" name="sex" required>
                  <option value="">Select</option>
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                </select>
              </div>
              <div class="form-group">
                <label for="religion">RELIGION</label>
                <input type="text" id="religion" name="religion" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{2,40}" maxlength="40">
              </div>
              <div class="form-group">
                <label for="civilstatus">CIVIL STATUS<span class="required-asterisk">*</span></label>
                <select id="civilstatus" name="civilstatus" required>
                  <option value="">Select</option>
                  <option value="single">Single</option>
                  <option value="married">Married</option>
                  <option value="widowed">Widowed</option>
                  <option value="divorced">Divorced</option>
                  <option value="separated">Separated</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group wide">
                <label for="address"><strong>PRESENT ADDRESS</strong></label>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="province">Province<span class="required-asterisk">*</span></label>
                <select id="province" name="province" required>
                  <option value="" selected disabled hidden>Select Province</option>
                </select>
              </div>
              <div class="form-group">
                <label for="municipality">Municipality/City<span class="required-asterisk">*</span></label>
                <select id="municipality" name="municipality" required>
                  <option value="" selected disabled hidden>Select Municipality/City</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="barangay">Barangay<span class="required-asterisk">*</span></label>
                <select id="barangay" name="barangay" required>
                  <option value="" selected disabled hidden>Select Barangay</option>
                </select>
              </div>
              <div class="form-group">
                <label for="street">House no./Street/Village<span class="required-asterisk">*</span></label>
                <input type="text" id="street" name="street" pattern=".{2,50}" maxlength="50">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="tin">TIN</label>
                <input type="text" id="tin" name="tin" pattern="[0-9]{3}-[0-9]{3}-[0-9]{3}(-[0-9]{3})?" placeholder="123-456-789 or 123-456-789-012">
              </div>
              <div class="form-group">
                <label for="height">HEIGHT (FT.)</label>
                <input type="text" id="height" name="height" pattern="[0-9']{1,5}" placeholder="5'6 or 5'10">
              </div>
              <div class="form-group">
                <label for="contact">CONTACT NUMBER<span class="required-asterisk">*</span></label>
                <input type="text" id="contact" name="contact" pattern="[0-9]{4}-[0-9]{3}-[0-9]{4}" required placeholder="0912-345-6789">
              </div>
              <div class="form-group">
                <label for="email">E-MAIL<span class="required-asterisk">*</span></label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" maxlength="40" required readonly style="background-color: #f5f5f5; cursor: not-allowed;">
              </div>
            </div>
            <div class="form-row">
              <label>
                <input type="checkbox" id="hasDisability" name="hasDisability">
                <strong>DISABILITY</strong>
              </label>
              <div class="checkbox-group" id="disabilityFields" style="pointer-events: none; opacity: 0.6;">
                <label><input type="checkbox" name="disability_speech" value="speech" disabled> Speech</label>
                <label><input type="checkbox" name="disability_hearing" value="hearing" disabled> Hearing</label>
                <label><input type="checkbox" name="disability_visual" value="visual" disabled> Visual</label>
                <label><input type="checkbox" name="disability_mental" value="mental" disabled> Mental</label>
                <label><input type="checkbox" name="disability_others" value="others" disabled> Others</label>
                <input type="text" name="disability_other" placeholder="Please specify" style="min-width:120px;" disabled>
              </div>
            </div>
            </fieldset>
            <div class="form-actions">
              <button type="button" class="next-btn" onclick="showNextSection()">Next</button>
            </div>
          </div>
          
          <!-- Section 1.2: Employment Status -->
          <div id="section1_2" class="form-section" style="display:none;">
            <fieldset>
              <legend>II. EMPLOYMENT STATUS / TYPE</legend>
            <div class="form-row">
              <label>
                <input type="checkbox" name="employed" id="employed">
                <strong>Employed<span class="required-asterisk">*</span></strong>
              </label>
            </div>
            <div class="form-row indent" id="employedFields" style="pointer-events: none; opacity: 0.6;">
              <label><input type="checkbox" name="employment_type_wage" value="wage" disabled> Wage employed</label>
              <label><input type="checkbox" name="employment_type_self" value="self" disabled> Self-employed</label>
              <input type="text" name="self_employed_specify" placeholder="If self-employed, specify" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,50}" maxlength="50" disabled style="display: none;">
            </div>
            <div class="form-row indent" id="selfTypeFields" style="pointer-events: none; opacity: 0.6;">
              <label><input type="checkbox" name="self_type_voluntary" value="voluntary" disabled> Voluntary/PhilHealth</label>
              <label><input type="checkbox" name="self_type_vendor" value="vendor" disabled> Vendor / Retailer</label>
              <label><input type="checkbox" name="self_type_homebased" value="homebased" disabled> Home-based worker</label>
              <label><input type="checkbox" name="self_type_transport" value="transport" disabled> Transport</label>
              <label><input type="checkbox" name="self_type_domestic" value="domestic" disabled> Domestic Worker</label>
              <label><input type="checkbox" name="self_type_fisherfolk" value="fisherfolk" disabled> Fisherfolk</label>
              <label><input type="checkbox" name="self_type_freelancer" value="freelancer" disabled> Freelancer</label>
              <label><input type="checkbox" name="self_type_artisan" value="artisan" disabled> Artisan/Craft Worker</label>
              <label><input type="checkbox" name="self_type_others" value="others" disabled> Others</label>
              <input type="text" name="other_jobs" placeholder="If others, specify" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,50}" maxlength="50" disabled>
            </div>
            <div class="form-row">
              <label>
                <input type="checkbox" name="unemployed" id="unemployed">
                <strong>Unemployed<span class="required-asterisk">*</span></strong>
              </label>
            </div>
            <div class="form-row indent" id="unemployedFields" style="pointer-events: none; opacity: 0.6;">
              <label for="unemployed_months">How long have you been looking for work? (months)</label>
              <input type="text" id="unemployed_months" name="unemployed_months" pattern="[0-9]{0,30}" maxlength="30" disabled>
            </div>
            <div class="form-row indent" id="unemployedTypeFields" style="pointer-events: none; opacity: 0.6;">
              <label><input type="checkbox" name="unemployed_type_first" value="first" disabled> First-time Jobseeker/Graduate</label>
              <label><input type="checkbox" name="unemployed_type_local" value="local" disabled> Terminated/Laid off due to calamity</label>
              <label><input type="checkbox" name="unemployed_type_resigned" value="resigned" disabled> Resigned</label>
              <label><input type="checkbox" name="unemployed_type_finished" value="finished" disabled> Finished contract (OFW)</label>
              <!-- <label><input type="checkbox" name="unemployed_type_public" value="public" disabled> Public Contract</label> -->
              <label><input type="checkbox" name="unemployed_type_retired" value="retired" disabled> Retired</label>
              <label><input type="checkbox" name="unemployed_type_terminated" value="terminated" disabled> Terminated/Laid off (local)</label>
              <label><input type="checkbox" name="unemployed_type_terminated_abroad" value="terminated_abroad" disabled> Terminated/Laid off (abroad)</label>
              <label for="terminated_country" style="display: none;">Specify country:</label>
              <input type="text" id="terminated_country" name="terminated_country" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,50}" maxlength="50" disabled style="display: none;">
              <label><input type="checkbox" name="unemployed_type_others" value="others" disabled> Others:</label> 
              <label for="unemployed_other_specify" style="display: none;"> Please specify:</label>
              <input type="text" id="unemployed_other_specify" name="unemployed_other_specify" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ0-9\s\-\.]{0,50}" maxlength="50" disabled style="display: none;">
            </div>
            <div class="form-row">
              <label>Are you an OFW?<span class="required-asterisk">*</span></label>
              <label><input type="radio" name="ofw" value="yes" id="ofwYes"> Yes</label>
              <label><input type="radio" name="ofw" value="no" id="ofwNo"> No</label>
              <span id="ofwCountryGroup" style="display:none;">
                <select id="ofw_country" name="ofw_country"></select>
              </span>
            </div>
            <div class="form-row">
              <label>Are you a returnee (OFW)?<span class="required-asterisk">*</span></label>
              <label><input type="radio" name="returnee" value="yes" id="returneeYes"> Yes</label>
              <label><input type="radio" name="returnee" value="no" id="returneeNo"> No</label>
            </div>
            <div class="form-row" id="returneeFields" style="display: none;">
              <label for="deployment_country">Latest country of deployment:<span class="required-asterisk">*</span></label>
              <select id="deployment_country" name="deployment_country"></select>
            </div>
            <div class="form-row" id="returneeReturnFields" style="display: none;">
              <div class="form-group">
                <label for="return_month">Month of return to Philippines:<span class="required-asterisk">*</span></label>
                <select id="return_month" name="return_month">
                  <option value="">Select Month</option>
                  <option value="January">January</option>
                  <option value="February">February</option>
                  <option value="March">March</option>
                  <option value="April">April</option>
                  <option value="May">May</option>
                  <option value="June">June</option>
                  <option value="July">July</option>
                  <option value="August">August</option>
                  <option value="September">September</option>
                  <option value="October">October</option>
                  <option value="November">November</option>
                  <option value="December">December</option>
                </select>
              </div>
              <div class="form-group">
                <label for="return_year">Year of return to Philippines:<span class="required-asterisk">*</span></label>
                <select id="return_year" name="return_year">
                  <option value="">Select Year</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <label>Are you a 4Ps beneficiary?<span class="required-asterisk">*</span></label>
              <label><input type="radio" name="beneficiary" value="yes" id="beneficiaryYes"> Yes</label>
              <label><input type="radio" name="beneficiary" value="no" id="beneficiaryNo"> No</label>
              <span id="householdIdGroup" style="display:none;">
                <input type="text" id="household_id" name="household_id" placeholder="If yes, provide Household ID No." pattern="[A-Za-z0-9\-]{0,20}">
              </span>
            </div>
            </fieldset>
            <div class="form-actions">
              <button type="button" class="back-btn" onclick="showPreviousSection()">Back</button>
              <button type="button" class="next-btn" onclick="showNextSection()">Next</button>
            </div>
          </div>
        </div>

        <!-- Step 2 -->
        <div id="step2Section" style="display:none;">
          <!-- Section 2.1: Job Preference -->
          <div id="section2_1" class="form-section">
            <fieldset>
              <legend>III. JOB PREFERENCE</legend>
            <div class="form-row">
              <label class="pref-section-title">PREFERRED OCCUPATION<span class="required-asterisk">*</span></label>
              <label><input type="checkbox" name="fulltime"> Full-time</label>
              <label><input type="checkbox" name="parttime"> Part-time</label>
            </div>
            <div class="form-row stacked-pref-inputs">
              <select id="occupation1" name="occupation1" required>
                <option value="" selected disabled hidden>1. Select preferred occupation</option>
              </select>
              <select id="occupation2" name="occupation2">
                <option value="" selected disabled hidden>2. Select preferred occupation (optional)</option>
              </select>
              <select id="occupation3" name="occupation3">
                <option value="" selected disabled hidden>3. Select preferred occupation (optional)</option>
              </select>
            </div>
            <div class="form-row">
              <label class="pref-section-title">PREFERRED WORK LOCATION</label>
            </div>
            <div class="form-row">
              <label class="pref-section-title">Local (specify cities/municipalities):<span class="required-asterisk">*</span></label>
              <div class="local-location-row">
                <select id="local1_province" class="local-province-select">
                  <option value="" selected disabled hidden>1. Select province</option>
                </select>
                <select id="local1_city" class="local-city-select">
                  <option value="" selected disabled hidden>1. Select municipality/city</option>
                </select>
                <input type="hidden" name="local1" id="local1" required>
              </div>
              <div class="local-location-row">
                <select id="local2_province" class="local-province-select">
                  <option value="" selected disabled hidden>2. Select province (optional)</option>
                </select>
                <select id="local2_city" class="local-city-select">
                  <option value="" selected disabled hidden>2. Select municipality/city (optional)</option>
                </select>
                <input type="hidden" name="local2" id="local2">
              </div>
              <div class="local-location-row">
                <select id="local3_province" class="local-province-select">
                  <option value="" selected disabled hidden>3. Select province (optional)</option>
                </select>
                <select id="local3_city" class="local-city-select">
                  <option value="" selected disabled hidden>3. Select municipality/city (optional)</option>
                </select>
                <input type="hidden" name="local3" id="local3">
              </div>
            </div>
            <div class="form-row">
              <label class="pref-section-title">Overseas (specify countries):</label>
            </div>
            <div class="form-row stacked-pref-inputs">
              <select id="overseas1" name="overseas1">
                <option value="" selected disabled hidden>1. Select country (optional)</option>
              </select>
              <select id="overseas2" name="overseas2">
                <option value="" selected disabled hidden>2. Select country (optional)</option>
              </select>
              <select id="overseas3" name="overseas3">
                <option value="" selected disabled hidden>3. Select country (optional)</option>
              </select>
            </div>
            </fieldset>
            <div class="form-actions">
              <button type="button" class="back-btn" onclick="showPreviousSection()">Back</button>
              <button type="button" class="next-btn" onclick="showNextSection()">Next</button>
            </div>
          </div>
          
          <!-- Section 2.2: Language Proficiency -->
          <div id="section2_2" class="form-section" style="display:none;">
            <fieldset>
              <legend>IV. LANGUAGE / DIALECT PROFICIENCY <span style="font-weight:normal;">(check if applicable)</span></legend>
            <div class="form-row">
              <div class="form-group">
                <label>English</label>
                <label class="select-all-label"><input type="checkbox" id="english_select_all" onchange="toggleLanguageGroup('english', this.checked)"> Select All</label>
                <label><input type="checkbox" name="english_read">Read</label>
                <label><input type="checkbox" name="english_write">Write</label>
                <label><input type="checkbox" name="english_speak">Speak</label>
                <label><input type="checkbox" name="english_understand">Understand</label>
              </div>
              <div class="form-group">
                <label>Filipino</label>
                <label class="select-all-label"><input type="checkbox" id="filipino_select_all" onchange="toggleLanguageGroup('filipino', this.checked)"> Select All</label>
                <label><input type="checkbox" name="filipino_read">Read</label>
                <label><input type="checkbox" name="filipino_write">Write</label>
                <label><input type="checkbox" name="filipino_speak">Speak</label>
                <label><input type="checkbox" name="filipino_understand">Understand</label>
              </div>
              <div class="form-group">
                <label>Mandarin</label>
                <label class="select-all-label"><input type="checkbox" id="mandarin_select_all" onchange="toggleLanguageGroup('mandarin', this.checked)"> Select All</label>
                <label><input type="checkbox" name="mandarin_read">Read</label>
                <label><input type="checkbox" name="mandarin_write">Write</label>
                <label><input type="checkbox" name="mandarin_speak">Speak</label>
                <label><input type="checkbox" name="mandarin_understand">Understand</label>
              </div>
              <div class="form-group">
                <label>Others</label>
                <select id="other_language" name="other_language">
                  <option value="" selected disabled hidden>Specify language/dialect</option>
                </select>
                <label class="select-all-label"><input type="checkbox" id="other_select_all" onchange="toggleLanguageGroup('other', this.checked)" disabled> Select All</label>
                <label><input type="checkbox" name="other_read" disabled>Read</label>
                <label><input type="checkbox" name="other_write" disabled>Write</label>
                <label><input type="checkbox" name="other_speak" disabled>Speak</label>
                <label><input type="checkbox" name="other_understand" disabled>Understand</label>
              </div>
            </div>
            </fieldset>
            <div class="form-actions">
              <button type="button" class="back-btn" onclick="showPreviousSection()">Back</button>
              <button type="button" class="next-btn" onclick="showNextSection()">Next</button>
            </div>
          </div>
          
          <!-- Section 2.3: Educational Background -->
          <div id="section2_3" class="form-section" style="display:none;">
            <fieldset>
              <legend>V. EDUCATIONAL BACKGROUND</legend>
            <div class="form-row">
              <label>Currently in School?<span class="required-asterisk">*</span></label>
              <label><input type="radio" name="inschool" value="yes"> Yes</label>
              <label><input type="radio" name="inschool" value="no"> No</label>
            </div>
            <div class="form-row">
              <label>Level<span class="required-asterisk">*</span></label>
              <select name="level" id="levelSelect" onchange="toggleCourseField()">
                <option value="">Select</option>
                <option>Elementary</option>
                <option>Secondary (Non-K12)</option>
                <option>Secondary (K-12)</option>
                <option>Tertiary</option>
                <option>Graduate Studies / Post-graduate</option>
              </select>
            </div>
            <div class="form-row" id="courseField" style="display: none;">
              <label>Course/Strand</label>
              <select id="course" name="course">
                <option value="" selected disabled hidden>Select or type course/strand</option>
              </select>
            </div>
            <div class="form-row">
              <label>Year Graduated</label>
              <input type="text" name="year_graduated" pattern="[0-9]*" maxlength="10" placeholder="e.g., 2023">
            </div>
            <div class="form-row">
              <label>If Undergraduate</label>
              <div class="form-group">
                <label for="level_reached">Level Reached</label>
                <select name="level_reached" id="level_reached">
                  <option value="">Select</option>
                  <option>Elementary</option>
                  <option>Secondary (Non-K12)</option>
                  <option>Secondary (K-12)</option>
                  <option>Tertiary</option>
                  <option>Graduate Studies / Post-graduate</option>
                </select>
              </div>
              <div class="form-group">
                <label for="last_attended">Year Last Attended</label>
                <input type="text" name="last_attended" id="last_attended" placeholder="e.g., 2023" pattern="[0-9]*" maxlength="10">
              </div>
            </div>
            </fieldset>
            <div class="form-actions">
              <button type="button" class="back-btn" onclick="showPreviousSection()">Back</button>
              <button type="button" class="next-btn" onclick="showNextSection()">Next</button>
            </div>
          </div>
        </div>

        <!-- Step 3 -->
        <div id="step3Section" style="display:none;">
          <!-- Section 3.1: Technical Training -->
          <div id="section3_1" class="form-section">
            <fieldset>
              <legend>VI. TECHNICAL/VOCATIONAL AND OTHER TRAINING <span style="font-weight:normal;">(Include courses taken as part of college education/Leave blank if not applicable)</span></legend>
            <div class="tech-training-grid">
              <div class="header">Training/Vocational Course</div>
              <div class="header">Hours of Training</div>
              <div class="header">Training Institution</div>
              <div class="header">Skills Acquired</div>
              <div class="header">Certificates Received</div>
              <input type="text" name="training_course_1" placeholder="Course 1" pattern=".{0,40}" maxlength="40">
              <input type="text" name="training_hours_1" placeholder="Hours" pattern="[0-9]*" maxlength="10">
              <input type="text" name="training_institution_1" placeholder="Institution" pattern=".{0,40}" maxlength="40">
              <input type="text" name="training_skills_1" placeholder="Skills" pattern=".{0,40}" maxlength="40">
              <input type="text" name="training_cert_1" placeholder="Certificate" pattern=".{0,40}" maxlength="40">
              <input type="text" name="training_course_2" placeholder="Course 2" pattern=".{0,40}" maxlength="40">
              <input type="text" name="training_hours_2" placeholder="Hours" pattern="[0-9]*" maxlength="10">
              <input type="text" name="training_institution_2" placeholder="Institution" pattern=".{0,40}" maxlength="40">
              <input type="text" name="training_skills_2" placeholder="Skills" pattern=".{0,40}" maxlength="40">
              <input type="text" name="training_cert_2" placeholder="Certificate" pattern=".{0,40}" maxlength="40">
              <input type="text" name="training_course_3" placeholder="Course 3" pattern=".{0,40}" maxlength="40">
              <input type="text" name="training_hours_3" placeholder="Hours" pattern="[0-9]*" maxlength="10">
              <input type="text" name="training_institution_3" placeholder="Institution" pattern=".{0,40}" maxlength="40">
              <input type="text" name="training_skills_3" placeholder="Skills" pattern=".{0,40}" maxlength="40">
              <input type="text" name="training_cert_3" placeholder="Certificate" pattern=".{0,40}" maxlength="40">
            </div>
            </fieldset>
            <div class="form-actions">
              <button type="button" class="back-btn" onclick="showPreviousSection()">Back</button>
              <button type="button" class="next-btn" onclick="showNextSection()">Next</button>
            </div>
          </div>
          
          <!-- Section 3.2: Eligibility -->
          <div id="section3_2" class="form-section" style="display:none;">
            <fieldset>
              <legend>VII. ELIGIBILITY/PROFESSIONAL LICENSE<span style="font-weight:normal;">(Leave blank if not applicable)</span></legend>
            <div class="form-row">
              <div class="form-group">
                <label>Eligibility (Civil Service)</label>
                <input type="text" name="eligibility_1" placeholder="Eligibility 1" pattern=".{0,40}" maxlength="40">
              </div>
              <div class="form-group">
                <label>Date Taken</label>
                <input type="date" name="eligibility_date_1">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <input type="text" name="eligibility_2" placeholder="Eligibility 2" pattern=".{0,40}" maxlength="40">
              </div>
              <div class="form-group">
                <input type="date" name="eligibility_date_2">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Professional License (PRC)</label>
                <input type="text" name="prc_1" placeholder="PRC License 1" pattern=".{0,40}" maxlength="40">
              </div>
              <div class="form-group">
                <label>Valid Until</label>
                <input type="date" name="prc_valid_1">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <input type="text" name="prc_2" placeholder="PRC License 2" pattern=".{0,40}" maxlength="40">
              </div>
              <div class="form-group">
                <input type="date" name="prc_valid_2">
              </div>
            </div>
            </fieldset>
            <div class="form-actions">
              <button type="button" class="back-btn" onclick="showPreviousSection()">Back</button>
              <button type="button" class="next-btn" onclick="showNextSection()">Next</button>
            </div>
          </div>
          
          <!-- Section 3.3: Work Experience -->
          <div id="section3_3" class="form-section" style="display:none;">
            <fieldset>
              <legend>VIII. WORK EXPERIENCE <span style="font-weight:normal;">(limit to 10 year period, start with the most recent employment/Leave blank if not applicable)</span></legend>
            <div class="work-experience-grid" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 10px;">
              <div style="font-weight:bold;">Company Name</div>
              <div style="font-weight:bold;">Address</div>
              <div style="font-weight:bold;">Position</div>
              <div style="font-weight:bold;">Number of Months</div>
              <div style="font-weight:bold;">Status</div>
              <input type="text" name="company_name_1" placeholder="Company Name" style="width:100%;height:38px;" pattern=".{0,50}" maxlength="50">
              <input type="text" name="company_address_1" placeholder="Address" style="width:100%;height:38px;" pattern="[A-Za-z0-9\s\-\.()]*" maxlength="50">
              <input type="text" name="position_1" placeholder="Position" style="width:100%;height:38px;" pattern="[A-Za-z0-9\s\-\.()]*" maxlength="50">
              <input type="text" name="months_1" placeholder="Months" style="width:100%;height:38px;" pattern="[0-9]*" maxlength="10">
              <select name="status_1" style="width:100%;height:38px;">
                <option value="" selected disabled hidden>Status</option>
                <option value="Permanent">Permanent</option>
                <option value="Contractual">Contractual</option>
                <option value="Part-time">Part-time</option>
                <option value="Probationary">Probationary</option>
              </select>
              <input type="text" name="company_name_2" placeholder="Company Name" style="width:100%;height:38px;" pattern=".{0,50}" maxlength="50">
              <input type="text" name="company_address_2" placeholder="Address" style="width:100%;height:38px;" pattern="[A-Za-z0-9\s\-\.()]*" maxlength="50">
              <input type="text" name="position_2" placeholder="Position" style="width:100%;height:38px;" pattern="[A-Za-z0-9\s\-\.()]*" maxlength="50">
              <input type="text" name="months_2" placeholder="Months" style="width:100%;height:38px;" pattern="[0-9]*" maxlength="10">
              <select name="status_2" style="width:100%;height:38px;">
                <option value="" selected disabled hidden>Status</option>
                <option value="Permanent">Permanent</option>
                <option value="Contractual">Contractual</option>
                <option value="Part-time">Part-time</option>
                <option value="Probationary">Probationary</option>
              </select>
              <input type="text" name="company_name_3" placeholder="Company Name" style="width:100%;height:38px;" pattern=".{0,50}" maxlength="50">
              <input type="text" name="company_address_3" placeholder="Address" style="width:100%;height:38px;" pattern="[A-Za-z0-9\s\-\.()]*" maxlength="50">
              <input type="text" name="position_3" placeholder="Position" style="width:100%;height:38px;" pattern="[A-Za-z0-9\s\-\.()]*" maxlength="50">
              <input type="text" name="months_3" placeholder="Months" style="width:100%;height:38px;" pattern="[0-9]*" maxlength="10">
              <select name="status_3" style="width:100%;height:38px;">
                <option value="" selected disabled hidden>Status</option>
                <option value="Permanent">Permanent</option>
                <option value="Contractual">Contractual</option>
                <option value="Part-time">Part-time</option>
                <option value="Probationary">Probationary</option>
              </select>
            </div>
            </fieldset>
            <div class="form-actions">
              <button type="button" class="back-btn" onclick="showPreviousSection()">Back</button>
              <button type="button" class="next-btn" onclick="showNextSection()">Next</button>
            </div>
          </div>
          
          <!-- Section 3.4: Other Skills -->
          <div id="section3_4" class="form-section" style="display:none;">
            <fieldset>
              <legend>IX. OTHER SKILLS ACQUIRED <span style="font-weight:normal;">(without certificate)</span></legend>
            <div class="form-row">
              <label><input type="checkbox" name="skill_auto_mechanic"> Auto mechanic</label>
              <label><input type="checkbox" name="skill_electrician"> Electrician</label>
              <label><input type="checkbox" name="skill_photography"> Photography</label>
              <label><input type="checkbox" name="skill_beautician"> Beautician</label>
              <label><input type="checkbox" name="skill_embroidery"> Embroidery</label>
              <label><input type="checkbox" name="skill_plumbing"> Plumbing</label>
              <label><input type="checkbox" name="skill_carpentry"> Carpentry work</label>
              <label><input type="checkbox" name="skill_gardening"> Gardening</label>
              <label><input type="checkbox" name="skill_sewing"> Sewing dresses</label>
              <label><input type="checkbox" name="skill_computer"> Computer literature</label>
              <label><input type="checkbox" name="skill_masonry"> Masonry</label>
              <label><input type="checkbox" name="skill_stenography"> Stenography</label>
              <label><input type="checkbox" name="skill_domestic"> Domestic chores</label>
              <label><input type="checkbox" name="skill_painter"> Painter/Artist</label>
              <label><input type="checkbox" name="skill_tailoring"> Tailoring</label>
              <label><input type="checkbox" name="skill_driver"> Driver</label>
              <label><input type="checkbox" name="skill_painting"> Painting job</label>
            </div>
            <div class="form-row">
              <label>Others: <span style="font-weight:normal;">(Separate by commas ",")</span></label>
              <input type="text" name="skill_others" placeholder="Others" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ,\s]*">
            </div>
          </fieldset>
          <fieldset>
            <legend>CERTIFICATION/AUTHORIZATION</legend>
            <div class="form-row">
              <p>
                This is to certify that all data/information that I have provided in this form are true to the best of my knowledge. This is also to authorize DOLE to include my profile in the PESO Employment Information System and use my personal information for the employment facilitation. I am also aware that DOLE is not obliged to seek employment on my behalf.
              </p>
            </div>
            <div class="form-row esignature-upload-container">
              <div class="esignature-upload-wrapper">
                <label for="esignature" class="esignature-label">
                  <i class="esignature-icon">✍️</i>
                  <span class="esignature-text">Upload Your E-Signature<span class="required-asterisk">*</span></span>
                  <span class="esignature-subtext">Click to select your signature image</span>
                </label>
                <input type="file" id="esignature" name="esignature" accept="image/*" class="esignature-input" <?php echo (empty($existingEsignatureFile)) ? 'required' : ''; ?>>
                <div class="esignature-preview" id="esignaturePreview" style="display: none;">
                  <img id="esignatureImage" src="" alt="Signature Preview">
                  <span class="esignature-filename" id="esignatureFilename"></span>
                  <button type="button" class="esignature-remove" id="esignatureRemove">×</button>
                </div>
              </div>
              <div class="esignature-instructions">
                <i class="esignature-info-icon">ℹ️</i>
                <span>Accepted formats: JPG, PNG, GIF, BMP, WEBP • Maximum file size: 2MB</span>
              </div>
            </div>
            </fieldset>
            <div class="form-actions">
              <button type="button" class="back-btn" onclick="showPreviousSection()">Back</button>
              <button type="button" class="next-btn" onclick="showNextSection()">Next</button>
            </div>
          </div>
          
          <!-- Section 3.5: Resume Upload -->
          <div id="section3_5" class="form-section" style="display:none;">
            <fieldset style="margin-top:24px;">
              <legend>Resume Upload</legend>
            <div class="form-row resume-upload-row">
              <label for="resume_file" class="resume-upload-label"><strong>Upload your resume:</strong></label>
              <input type="file" id="resume_file" name="resume_file" class="resume-upload-input" accept=".pdf,.doc,.docx" <?php echo (empty($existingResumeFile)) ? 'required' : ''; ?>>
              <span class="resume-upload-hint">Accepted formats: PDF, DOC, DOCX only. Max size: 5MB.</span>
            </div>
            </fieldset>
            <div class="form-actions">
              <button type="button" class="back-btn" onclick="showPreviousSection()">Back</button>
              <button type="submit" id="submitNRSPBtn" class="next-btn" <?php echo (!$canSubmitNRSP) ? 'disabled' : ''; ?>>
                <?php 
                  if ($isPending) {
                    echo 'Save';
                  } elseif ($isRejected) {
                    echo 'Re-submit';
                  } else {
                    echo 'Submit';
                  }
                ?>
              </button>
            </div>
          </div>
        </div>
      </form>
      
      <!-- Pass PHP variables to JavaScript -->
      <script>
        const NRSP_STATUS = <?php echo json_encode($nrspStatus); ?>;
        const CAN_SUBMIT_NRSP = <?php echo json_encode($canSubmitNRSP); ?>;
        const CAN_EDIT_NRSP = <?php echo json_encode($canEditNRSP); ?>;
        const IS_PENDING = <?php echo json_encode($isPending); ?>;
        const IS_REJECTED = <?php echo json_encode($isRejected); ?>;
        const AUTO_LOAD_FORM = <?php echo json_encode($autoLoadForm); ?>;
        const COOLDOWN_REMAINING = <?php echo json_encode($cooldownRemaining); ?>;
        const EXISTING_RESUME_FILE = <?php echo json_encode($existingResumeFile ?? null); ?>;
        const EXISTING_ESIGNATURE_FILE = <?php echo json_encode($existingEsignatureFile ?? null); ?>;
      </script>
      
      <!-- Existing NRSP Form Status Section -->
      <?php if ($existingNRSP): ?>
      <div class="nrsp-status-section" style="margin-top: 40px; padding: 25px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid #233a8b;">
        <h3 style="margin: 0 0 20px 0; color: #233a8b; display: flex; align-items: center; gap: 10px;">
          <i class="fas fa-file-check"></i> Your Existing NRSP Form
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
          <div>
            <strong style="color: #666; font-size: 0.9rem; display: block; margin-bottom: 5px;">Status:</strong>
            <span class="status-badge status-<?php 
              $statusClass = 'pending';
              if (!empty($nrspStatus)) {
                $statusLower = strtolower($nrspStatus);
                if ($statusLower === 'accepted') {
                  $statusClass = 'accepted';
                } elseif ($statusLower === 'rejected') {
                  $statusClass = 'rejected';
                } elseif ($statusLower === 'referred') {
                  $statusClass = 'referred';
                } else {
                  $statusClass = 'pending';
                }
              }
              echo $statusClass;
            ?>" style="display: inline-block; padding: 6px 12px; border-radius: 20px; font-weight: bold; font-size: 0.9rem; text-transform: uppercase;">
              <?php echo !empty($nrspStatus) ? htmlspecialchars($nrspStatus) : 'Pending Review'; ?>
            </span>
          </div>
          <?php if ($nrspSubmissionDate): ?>
          <div>
            <strong style="color: #666; font-size: 0.9rem; display: block; margin-bottom: 5px;">Submitted:</strong>
            <span style="color: #333;"><?php echo htmlspecialchars($nrspSubmissionDate); ?></span>
          </div>
          <?php endif; ?>
        </div>
        
        <?php if ($isPending): ?>
        <div style="padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px; margin-bottom: 15px;">
          <i class="fas fa-clock"></i> 
          <strong>Form Under Review:</strong> Your NRSP form is currently pending review. You can edit and save your changes. The form will remain in pending status.
        </div>
        <?php endif; ?>
        
        <?php if ($isRejected && $cooldownRemaining !== null): ?>
        <div style="padding: 15px; background: #f8d7da; border-left: 4px solid #dc3545; border-radius: 5px; margin-bottom: 15px;">
          <i class="fas fa-lock"></i> 
          <strong>Form locked.</strong> Re-submission of your NSRP form will be available again 24 hours after your application was declined. Please return after that period to resubmit.
        </div>
        <?php elseif ($isRejected && $cooldownRemaining === null): ?>
        <div style="padding: 15px; background: #d1ecf1; border-left: 4px solid #17a2b8; border-radius: 5px; margin-bottom: 15px;">
          <i class="fas fa-check-circle"></i> 
          <strong>Resubmission Available:</strong> Resubmission is available now. You can resubmit your NRSP form.
        </div>
        <?php endif; ?>
        
        <?php if (!$canEditNRSP && !$isRejected): ?>
          <div style="padding-top: 15px; border-top: 1px solid #e0e0e0;">
            <div style="margin-bottom: 15px; color: #856404; font-size: 0.9rem; background: #fff3cd; padding: 12px; border-radius: 5px; border-left: 4px solid #ffc107;">
              <i class="fas fa-lock"></i> 
              <strong>Form Locked:</strong> Your NRSP form has been accepted or referred to companies. 
              Editing is no longer allowed to maintain data integrity.
            </div>
          </div>
        <?php endif; ?>
      </div>
      <style>
        .status-badge.status-pending {
          background: #fff3cd;
          color: #856404;
        }
        .status-badge.status-accepted {
          background: #d4edda;
          color: #155724;
        }
        .status-badge.status-rejected {
          background: #f8d7da;
          color: #721c24;
        }
        .status-badge.status-referred {
          background: #cce5ff;
          color: #004085;
        }
      </style>
      <?php endif; ?>

<script>
  // Display realtime day, month, year at upper right of the form
  function updateFormDate() {
    const dateElem = document.getElementById('form-date');
    const now = new Date();
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    dateElem.textContent = now.toLocaleDateString(undefined, options);
  }
  updateFormDate();
  setInterval(updateFormDate, 60000);

  // Step navigation logic
  // Helper to toggle required attributes only for visible step
  function setRequiredForStep(stepId) {
    const allSteps = ['step1Section', 'step2Section', 'step3Section'];
    allSteps.forEach(id => {
      const section = document.getElementById(id);
      if (!section) return;
      const requiredFields = section.querySelectorAll('[required]');
      requiredFields.forEach(f => f.removeAttribute('required'));
    });
    // Set required only for visible step
    const visibleSection = document.getElementById(stepId);
    if (visibleSection) {
      const requiredFields = visibleSection.querySelectorAll('[data-always-required]');
      requiredFields.forEach(f => f.setAttribute('required', 'required'));
    }
  }

  function showStep1() {
    document.getElementById('step1Section').style.display = '';
    document.getElementById('step2Section').style.display = 'none';
    document.getElementById('step3Section').style.display = 'none';
    setRequiredForStep('step1Section');
    // Show first section of step 1
    showFormSection('section1_1');
    // Auto scroll to top
    scrollToTop();
  }
  function showStep2() {
    document.getElementById('step1Section').style.display = 'none';
    document.getElementById('step2Section').style.display = '';
    document.getElementById('step3Section').style.display = 'none';
    setRequiredForStep('step2Section');
    // Show first section of step 2
    showFormSection('section2_1');
    // Auto scroll to top
    scrollToTop();
  }
  function showStep3() {
    document.getElementById('step1Section').style.display = 'none';
    document.getElementById('step2Section').style.display = 'none';
    document.getElementById('step3Section').style.display = '';
    setRequiredForStep('step3Section');
    // Show first section of step 3
    showFormSection('section3_1');
    // Auto scroll to top
    scrollToTop();
  }
  
  // Section navigation functions
  function showFormSection(sectionId) {
    // Hide all sections first
    const allSections = document.querySelectorAll('.form-section');
    allSections.forEach(section => {
      section.style.display = 'none';
    });
    
    // Show the requested section
    const targetSection = document.getElementById(sectionId);
    if (targetSection) {
      targetSection.style.display = 'block';
    }
    
    // Update progress indicator
    updateProgressIndicator();
    
    // Scroll to top of the form within iframe only
    setTimeout(() => {
      // Only scroll within the iframe, don't affect parent window
      window.scrollTo({ top: 0, behavior: 'smooth' });
      document.documentElement.scrollTop = 0;
      document.body.scrollTop = 0;
    }, 100);
    // Tell parent dashboard to resize iframe height to full content (single scrollbar on parent)
    setTimeout(() => {
      try {
        if (window.parent && window.parent !== window) {
          window.parent.postMessage({ type: 'workconnect-resize-apply', source: 'apply' }, '*');
        }
      } catch (e) {}
    }, 160);
  }

  // Address cascading dropdowns (Province -> Municipality/City -> Barangay)
  const LOCATION_API_BASE = 'https://psgc.gitlab.io/api';
  const OCCUPATION_OPTIONS = [
    'Any',
    'Accountant','Accounting Clerk','Administrative Aide','Administrative Assistant','Agricultural Technician',
    'Architect','Auto Mechanic','Baker','Bank Teller','Barber','Bartender','Bookkeeper','Business Analyst',
    'Call Center Agent','Caregiver','Carpenter','Cashier','Chef','Civil Engineer','Computer Technician',
    'Construction Worker','Content Creator','Cook','Customer Service Representative','Data Analyst',
    'Delivery Rider','Dentist','Driver','Electrician','Electronics Technician','Farmer','Financial Advisor',
    'Food Service Crew','Forklift Operator','Graphic Designer','Hair Stylist','Heavy Equipment Operator',
    'Hotel Receptionist','HR Assistant','Industrial Engineer','IT Support Specialist','Janitor','Laborer',
    'Machine Operator','Mason','Medical Technologist','Merchandiser','Motorcycle Mechanic','Nurse',
    'Office Staff','Operations Supervisor','Painter','Pharmacist','Photographer','Plumber',
    'Production Operator','Programmer','Project Manager','Quality Assurance Specialist','Receptionist',
    'Retail Sales Associate','Safety Officer','Sales Clerk','Sales Representative','Security Guard',
    'Seamstress','Service Crew','Software Developer','Store Manager','Teacher','Technician',
    'Tourism Officer','Trainer','Truck Driver','Virtual Assistant','Waiter/Waitress','Warehouse Staff',
    'Web Developer','Welder'
  ];
  const COUNTRY_OPTIONS = [
    'Afghanistan','Albania','Algeria','Andorra','Angola','Antigua and Barbuda','Argentina','Armenia','Australia','Austria',
    'Azerbaijan','Bahamas','Bahrain','Bangladesh','Barbados','Belarus','Belgium','Belize','Benin','Bhutan',
    'Bolivia','Bosnia and Herzegovina','Botswana','Brazil','Brunei','Bulgaria','Burkina Faso','Burundi','Cabo Verde','Cambodia',
    'Cameroon','Canada','Central African Republic','Chad','Chile','China','Colombia','Comoros','Congo','Costa Rica',
    'Croatia','Cuba','Cyprus','Czech Republic','Democratic Republic of the Congo','Denmark','Djibouti','Dominica','Dominican Republic','Ecuador',
    'Egypt','El Salvador','Equatorial Guinea','Eritrea','Estonia','Eswatini','Ethiopia','Fiji','Finland','France',
    'Gabon','Gambia','Georgia','Germany','Ghana','Greece','Grenada','Guatemala','Guinea','Guinea-Bissau',
    'Guyana','Haiti','Honduras','Hungary','Iceland','India','Indonesia','Iran','Iraq','Ireland',
    'Israel','Italy','Jamaica','Japan','Jordan','Kazakhstan','Kenya','Kiribati','Kuwait','Kyrgyzstan',
    'Laos','Latvia','Lebanon','Lesotho','Liberia','Libya','Liechtenstein','Lithuania','Luxembourg','Madagascar',
    'Malawi','Malaysia','Maldives','Mali','Malta','Marshall Islands','Mauritania','Mauritius','Mexico','Micronesia',
    'Moldova','Monaco','Mongolia','Montenegro','Morocco','Mozambique','Myanmar','Namibia','Nauru','Nepal',
    'Netherlands','New Zealand','Nicaragua','Niger','Nigeria','North Korea','North Macedonia','Norway','Oman','Pakistan',
    'Palau','Panama','Papua New Guinea','Paraguay','Peru','Philippines','Poland','Portugal','Qatar','Romania',
    'Russia','Rwanda','Saint Kitts and Nevis','Saint Lucia','Saint Vincent and the Grenadines','Samoa','San Marino','Sao Tome and Principe','Saudi Arabia','Senegal',
    'Serbia','Seychelles','Sierra Leone','Singapore','Slovakia','Slovenia','Solomon Islands','Somalia','South Africa','South Korea',
    'South Sudan','Spain','Sri Lanka','Sudan','Suriname','Sweden','Switzerland','Syria','Taiwan','Tajikistan',
    'Tanzania','Thailand','Timor-Leste','Togo','Tonga','Trinidad and Tobago','Tunisia','Turkey','Turkmenistan','Tuvalu',
    'Uganda','Ukraine','United Arab Emirates','United Kingdom','United States','Uruguay','Uzbekistan','Vanuatu','Vatican City','Venezuela',
    'Vietnam','Yemen','Zambia','Zimbabwe'
  ];
  const LANGUAGE_DIALECT_OPTIONS = [
    'Aklanon','Arabic','Bicolano','Bisaya/Cebuano','Chavacano','Chinese','English','Filipino',
    'French','German','Hiligaynon','Ibanag','Ifugao','Ilocano','Ilonggo','Iranun','Itawis',
    'Japanese','Kapampangan','Kinaray-a','Maguindanaon','Malay','Mandarin','Maranao','Pangasinan',
    'Portuguese','Sama','Sanskrit','Spanish','Surigaonon','Tagalog','Tausug','Tboli','Waray','Yakan'
  ];
  const SHS_STRAND_OPTIONS = [
    'STEM','ABM','HUMSS','GAS','TVL-Home Economics','TVL-ICT','TVL-Industrial Arts','TVL-Agri-Fishery Arts','Sports','Arts and Design'
  ];
  const COURSE_ONLY_OPTIONS = [
    'BS Accountancy','BS Accounting Information System','BS Accounting Technology','BS Agribusiness','BS Agriculture','BS Architecture',
    'BS Biology','BS Business Administration','BS Civil Engineering','BS Communication','BS Computer Engineering','BS Computer Science',
    'BS Criminology','BS Customs Administration','BS Dentistry','BS Early Childhood Education','BS Economics','BS Education',
    'BS Electrical Engineering','BS Electronics Engineering','BS Elementary Education','BS Entrepreneurship','BS Environmental Science',
    'BS Fisheries','BS Food Technology','BS Forestry','BS Geodetic Engineering','BS Hospitality Management','BS Hotel and Restaurant Management',
    'BS Human Resource Management','BS Industrial Engineering','BS Information Systems','BS Information Technology','BS Interior Design',
    'BS International Studies','BS Journalism','BS Legal Management','BS Management Accounting','BS Marine Biology','BS Marine Engineering',
    'BS Marine Transportation','BS Marketing Management','BS Mass Communication','BS Mathematics','BS Mechanical Engineering','BS Medical Technology',
    'BS Midwifery','BS Mining Engineering','BS Nursing','BS Nutrition and Dietetics','BS Occupational Therapy','BS Office Administration',
    'BS Pharmacy','BS Physical Therapy','BS Physics','BS Political Science','BS Psychology','BS Public Administration','BS Radiologic Technology',
    'BS Real Estate Management','BS Secondary Education','BS Social Work','BS Sociology','BS Tourism Management','BS Veterinary Medicine',
    'Master in Public Administration','Master of Arts in Education','Master of Business Administration','Master of Science in Information Technology',
    'Doctor of Education','Doctor of Philosophy'
  ];
  const PH_PROVINCES = [
    { code: '012800000', name: 'Ilocos Norte' },
    { code: '012900000', name: 'Ilocos Sur' },
    { code: '013300000', name: 'La Union' },
    { code: '015500000', name: 'Pangasinan' },
    { code: '020900000', name: 'Batanes' },
    { code: '021500000', name: 'Cagayan' },
    { code: '023100000', name: 'Isabela' },
    { code: '025000000', name: 'Nueva Vizcaya' },
    { code: '025700000', name: 'Quirino' },
    { code: '030800000', name: 'Bataan' },
    { code: '031400000', name: 'Bulacan' },
    { code: '034900000', name: 'Nueva Ecija' },
    { code: '035400000', name: 'Pampanga' },
    { code: '036900000', name: 'Tarlac' },
    { code: '037100000', name: 'Zambales' },
    { code: '037700000', name: 'Aurora' },
    { code: '041000000', name: 'Batangas' },
    { code: '042100000', name: 'Cavite' },
    { code: '043400000', name: 'Laguna' },
    { code: '045600000', name: 'Quezon' },
    { code: '045800000', name: 'Rizal' },
    { code: '174000000', name: 'Marinduque' },
    { code: '175100000', name: 'Occidental Mindoro' },
    { code: '175200000', name: 'Oriental Mindoro' },
    { code: '175300000', name: 'Palawan' },
    { code: '175900000', name: 'Romblon' },
    { code: '050500000', name: 'Albay' },
    { code: '051600000', name: 'Camarines Norte' },
    { code: '051700000', name: 'Camarines Sur' },
    { code: '052000000', name: 'Catanduanes' },
    { code: '054100000', name: 'Masbate' },
    { code: '056200000', name: 'Sorsogon' },
    { code: '060400000', name: 'Aklan' },
    { code: '060600000', name: 'Antique' },
    { code: '061900000', name: 'Capiz' },
    { code: '063000000', name: 'Iloilo' },
    { code: '064500000', name: 'Negros Occidental' },
    { code: '067900000', name: 'Guimaras' },
    { code: '071200000', name: 'Bohol' },
    { code: '072200000', name: 'Cebu' },
    { code: '074600000', name: 'Negros Oriental' },
    { code: '076100000', name: 'Siquijor' },
    { code: '082600000', name: 'Eastern Samar' },
    { code: '083700000', name: 'Leyte' },
    { code: '084800000', name: 'Northern Samar' },
    { code: '086000000', name: 'Samar' },
    { code: '086400000', name: 'Southern Leyte' },
    { code: '087800000', name: 'Biliran' },
    { code: '097200000', name: 'Zamboanga Del Norte' },
    { code: '097300000', name: 'Zamboanga Del Sur' },
    { code: '098300000', name: 'Zamboanga Sibugay' },
    { code: '101300000', name: 'Bukidnon' },
    { code: '101800000', name: 'Camiguin' },
    { code: '103500000', name: 'Lanao Del Norte' },
    { code: '104200000', name: 'Misamis Occidental' },
    { code: '104300000', name: 'Misamis Oriental' },
    { code: '112300000', name: 'Davao Del Norte' },
    { code: '112400000', name: 'Davao Del Sur' },
    { code: '112500000', name: 'Davao Oriental' },
    { code: '118200000', name: 'Davao De Oro' },
    { code: '118600000', name: 'Davao Occidental' },
    { code: '124700000', name: 'Cotabato' },
    { code: '126300000', name: 'South Cotabato' },
    { code: '126500000', name: 'Sultan Kudarat' },
    { code: '128000000', name: 'Sarangani' },
    { code: '140100000', name: 'Abra' },
    { code: '141100000', name: 'Benguet' },
    { code: '142700000', name: 'Ifugao' },
    { code: '143200000', name: 'Kalinga' },
    { code: '144400000', name: 'Mountain Province' },
    { code: '148100000', name: 'Apayao' },
    { code: '160200000', name: 'Agusan Del Norte' },
    { code: '160300000', name: 'Agusan Del Sur' },
    { code: '166700000', name: 'Surigao Del Norte' },
    { code: '166800000', name: 'Surigao Del Sur' },
    { code: '168500000', name: 'Dinagat Islands' },
    { code: '150700000', name: 'Basilan' },
    { code: '153600000', name: 'Lanao Del Sur' },
    { code: '153800000', name: 'Maguindanao' },
    { code: '156600000', name: 'Sulu' },
    { code: '157000000', name: 'Tawi-Tawi' },
    { code: '130000000', name: 'Metro Manila (NCR)' }
  ];
  let addressInitPromise = null;
  let provinceCityCache = {};
  let cityBarangayCache = {};

  function normalizeText(value) {
    return (value || '').toString().trim().toLowerCase();
  }

  function formatOccupationValue(value) {
    if (!value || String(value).toLowerCase() === 'n/a') return '';
    const cleaned = (value || '')
      .replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, ' ')
      .replace(/\s+/g, ' ')
      .trim()
      .toLowerCase();
    if (!cleaned || cleaned === 'n a') return '';
    return cleaned.charAt(0).toUpperCase() + cleaned.slice(1);
  }

  function setOptions(selectEl, options, placeholder) {
    if (!selectEl) return;
    const ts = selectEl.tomselect;
    if (ts) {
      ts.settings.placeholder = placeholder;
      ts.clear(true);
      ts.clearOptions();
      options.forEach(opt => {
        ts.addOption({ value: opt.name, text: opt.name, code: opt.code || '' });
      });
      ts.setValue('', true);
      ts.setTextboxValue('');
      ts.inputState();
      ts.refreshOptions(false);
      return;
    }

    selectEl.innerHTML = '';
    const ph = document.createElement('option');
    ph.value = '';
    ph.textContent = placeholder;
    ph.disabled = true;
    ph.hidden = true;
    ph.selected = true;
    selectEl.appendChild(ph);
    options.forEach(opt => {
      const option = document.createElement('option');
      option.value = opt.name;
      option.textContent = opt.name;
      if (opt.code) option.dataset.code = opt.code;
      selectEl.appendChild(option);
    });
  }

  function ensureSelectValue(selectEl, value) {
    if (!selectEl || !value) return;
    const target = normalizeText(value);
    const existing = Array.from(selectEl.options).find(opt => normalizeText(opt.value) === target || normalizeText(opt.textContent) === target);
    if (existing) {
      if (selectEl.tomselect) {
        selectEl.tomselect.setValue(existing.value, true);
      } else {
        selectEl.value = existing.value;
      }
      return;
    }
    const fallback = document.createElement('option');
    fallback.value = value;
    fallback.textContent = value;
    fallback.dataset.custom = '1';
    selectEl.appendChild(fallback);
    if (selectEl.tomselect) {
      selectEl.tomselect.addOption({ value: value, text: value });
      selectEl.tomselect.setValue(value, true);
    } else {
      selectEl.value = value;
    }
  }

  function getSelectedCode(selectEl) {
    if (!selectEl) return '';
    const selectedValue = selectEl.value;
    if (!selectedValue) return '';
    if (selectEl.tomselect && selectEl.tomselect.options[selectedValue]) {
      return selectEl.tomselect.options[selectedValue].code || '';
    }
    const selected = selectEl.options[selectEl.selectedIndex];
    return selected ? (selected.dataset.code || '') : '';
  }

  async function fetchJson(url) {
    const resp = await fetch(url);
    if (!resp.ok) throw new Error('Location API request failed');
    return resp.json();
  }

  async function loadProvinces() {
    const provinceSelect = document.getElementById('province');
    if (!provinceSelect) return;
    const allProvinces = [...PH_PROVINCES].sort((a, b) => a.name.localeCompare(b.name));
    setOptions(provinceSelect, allProvinces, 'Select Province');
  }

  async function loadMunicipalitiesByProvinceCode(provinceCode) {
    const municipalitySelect = document.getElementById('municipality');
    const barangaySelect = document.getElementById('barangay');
    if (!municipalitySelect || !barangaySelect) return;

    setOptions(municipalitySelect, [], 'Select Municipality/City');
    setOptions(barangaySelect, [], 'Select Barangay');

    if (!provinceCode) return;
    if (!provinceCityCache[provinceCode]) {
      if (provinceCode === '130000000') {
        provinceCityCache[provinceCode] = await fetchJson(`${LOCATION_API_BASE}/regions/130000000/cities-municipalities/`);
      } else {
        provinceCityCache[provinceCode] = await fetchJson(`${LOCATION_API_BASE}/provinces/${provinceCode}/cities-municipalities/`);
      }
    }
    const cities = provinceCityCache[provinceCode]
      .map(c => ({ code: c.code, name: c.name }))
      .sort((a, b) => a.name.localeCompare(b.name));
    setOptions(municipalitySelect, cities, 'Select Municipality/City');
  }

  async function loadMunicipalitiesByProvinceCodeForSelect(provinceCode, citySelectEl) {
    if (!citySelectEl) return;
    setOptions(citySelectEl, [], 'Select Municipality/City');
    if (!provinceCode) return;
    if (!provinceCityCache[provinceCode]) {
      if (provinceCode === '130000000') {
        provinceCityCache[provinceCode] = await fetchJson(`${LOCATION_API_BASE}/regions/130000000/cities-municipalities/`);
      } else {
        provinceCityCache[provinceCode] = await fetchJson(`${LOCATION_API_BASE}/provinces/${provinceCode}/cities-municipalities/`);
      }
    }
    const cities = provinceCityCache[provinceCode]
      .map(c => ({ code: c.code, name: c.name }))
      .sort((a, b) => a.name.localeCompare(b.name));
    setOptions(citySelectEl, cities, 'Select Municipality/City');
  }

  async function loadBarangaysByCityCode(cityCode) {
    const barangaySelect = document.getElementById('barangay');
    if (!barangaySelect) return;
    setOptions(barangaySelect, [], 'Select Barangay');
    if (!cityCode) return;
    if (!cityBarangayCache[cityCode]) {
      cityBarangayCache[cityCode] = await fetchJson(`${LOCATION_API_BASE}/cities-municipalities/${cityCode}/barangays/`);
    }
    const barangays = cityBarangayCache[cityCode]
      .map(b => ({ code: b.code, name: b.name }))
      .sort((a, b) => a.name.localeCompare(b.name));
    setOptions(barangaySelect, barangays, 'Select Barangay');
  }

  async function initAddressDropdowns() {
    if (addressInitPromise) return addressInitPromise;
    addressInitPromise = (async () => {
      const provinceSelect = document.getElementById('province');
      const municipalitySelect = document.getElementById('municipality');
      const barangaySelect = document.getElementById('barangay');
      if (!provinceSelect || !municipalitySelect || !barangaySelect) return;

      if (!provinceSelect.tomselect) {
        new TomSelect(provinceSelect, {
          create: false,
          allowEmptyOption: true,
          placeholder: 'Select Province',
          closeAfterSelect: true,
          openOnFocus: true,
          maxOptions: 10000,
          searchField: ['text'],
          sortField: { field: 'text', direction: 'asc' },
          onItemAdd: function() {
            this.close();
            this.setTextboxValue('');
            this.blur();
          }
        });
      }
      if (!municipalitySelect.tomselect) {
        new TomSelect(municipalitySelect, {
          create: false,
          allowEmptyOption: true,
          placeholder: 'Select Municipality/City',
          closeAfterSelect: true,
          openOnFocus: true,
          maxOptions: 10000,
          searchField: ['text'],
          sortField: { field: 'text', direction: 'asc' },
          onItemAdd: function() {
            this.close();
            this.setTextboxValue('');
            this.blur();
          }
        });
      }
      if (!barangaySelect.tomselect) {
        new TomSelect(barangaySelect, {
          create: false,
          allowEmptyOption: true,
          placeholder: 'Select Barangay',
          closeAfterSelect: true,
          openOnFocus: true,
          maxOptions: 10000,
          searchField: ['text'],
          sortField: { field: 'text', direction: 'asc' },
          onItemAdd: function() {
            this.close();
            this.setTextboxValue('');
            this.blur();
          }
        });
      }

      await loadProvinces();

      provinceSelect.addEventListener('change', async function () {
        const provinceCode = getSelectedCode(this);
        await loadMunicipalitiesByProvinceCode(provinceCode);
      });

      municipalitySelect.addEventListener('change', async function () {
        const cityCode = getSelectedCode(this);
        await loadBarangaysByCityCode(cityCode);
      });
    })();
    return addressInitPromise;
  }

  function splitLocalValue(value) {
    const raw = (value || '').trim();
    if (!raw) return { city: '', province: '' };
    const parts = raw.split(',').map(p => p.trim()).filter(Boolean);
    if (parts.length >= 2) {
      return { city: parts[0], province: parts[parts.length - 1] };
    }
    return { city: raw, province: '' };
  }

  function updateLocalHiddenValue(index) {
    const provinceSelect = document.getElementById(`local${index}_province`);
    const citySelect = document.getElementById(`local${index}_city`);
    const hiddenInput = document.getElementById(`local${index}`);
    if (!hiddenInput) return;

    const province = provinceSelect ? (provinceSelect.value || '').trim() : '';
    const city = citySelect ? (citySelect.value || '').trim() : '';
    if (city && province) {
      hiddenInput.value = `${city}, ${province}`;
    } else {
      hiddenInput.value = '';
    }
    syncLocalLocationDuplicateOptions();
  }

  function refreshTomSelectDisabledOptions(selectEl, disabledValues) {
    if (!selectEl || !selectEl.tomselect) return;
    const ts = selectEl.tomselect;
    const currentValue = (selectEl.value || '').trim();
    const disabledSet = new Set(disabledValues || []);

    Object.keys(ts.options).forEach((key) => {
      const isDisabled = disabledSet.has(key) && key !== currentValue;
      if (ts.options[key].disabled !== isDisabled) {
        ts.updateOption(key, { ...ts.options[key], disabled: isDisabled });
      }
    });

    ts.refreshOptions(false);
  }

  function syncOccupationDuplicateOptions() {
    const selects = ['occupation1', 'occupation2', 'occupation3']
      .map((id) => document.getElementById(id))
      .filter(Boolean);
    const selectedValues = selects
      .map((el) => (el.value || '').trim())
      .filter(Boolean);

    selects.forEach((selectEl) => {
      const currentValue = (selectEl.value || '').trim();
      const disabledValues = selectedValues.filter((val) => val !== currentValue);
      refreshTomSelectDisabledOptions(selectEl, disabledValues);
    });
  }

  function syncOverseasDuplicateOptions() {
    const selects = ['overseas1', 'overseas2', 'overseas3']
      .map((id) => document.getElementById(id))
      .filter(Boolean);
    const selectedValues = selects
      .map((el) => (el.value || '').trim())
      .filter(Boolean);

    selects.forEach((selectEl) => {
      const currentValue = (selectEl.value || '').trim();
      const disabledValues = selectedValues.filter((val) => val !== currentValue);
      refreshTomSelectDisabledOptions(selectEl, disabledValues);
    });
  }

  function syncLocalLocationDuplicateOptions() {
    const selectedPairs = [];
    for (let i = 1; i <= 3; i++) {
      const hiddenInput = document.getElementById(`local${i}`);
      const combined = hiddenInput ? (hiddenInput.value || '').trim() : '';
      if (combined) selectedPairs.push(combined);
    }

    for (let i = 1; i <= 3; i++) {
      const provinceSelect = document.getElementById(`local${i}_province`);
      const citySelect = document.getElementById(`local${i}_city`);
      if (!provinceSelect || !citySelect || !citySelect.tomselect) continue;

      const province = (provinceSelect.value || '').trim();
      const currentCombined = province && citySelect.value
        ? `${citySelect.value.trim()}, ${province}`
        : '';

      const blockedCities = new Set();
      selectedPairs.forEach((pair) => {
        if (!pair || pair === currentCombined) return;
        const parsed = splitLocalValue(pair);
        if (province && normalizeText(parsed.province) === normalizeText(province)) {
          blockedCities.add(parsed.city);
        }
      });

      refreshTomSelectDisabledOptions(citySelect, Array.from(blockedCities));
    }
  }

  async function initLocalWorkLocationDropdowns() {
    await initAddressDropdowns();
    for (let i = 1; i <= 3; i++) {
      const provinceSelect = document.getElementById(`local${i}_province`);
      const citySelect = document.getElementById(`local${i}_city`);
      if (!provinceSelect || !citySelect) continue;
      const provincePlaceholder = i === 1
        ? '1. Select province'
        : `${i}. Select province (optional)`;
      const cityPlaceholder = i === 1
        ? '1. Select municipality/city'
        : `${i}. Select municipality/city (optional)`;

      setOptions(provinceSelect, [...PH_PROVINCES].sort((a, b) => a.name.localeCompare(b.name)), provincePlaceholder);
      setOptions(citySelect, [], cityPlaceholder);

      if (!provinceSelect.tomselect) {
        new TomSelect(provinceSelect, {
          create: false,
          allowEmptyOption: true,
          placeholder: provincePlaceholder,
          closeAfterSelect: true,
          openOnFocus: true,
          maxOptions: 10000,
          searchField: ['text'],
          sortField: { field: 'text', direction: 'asc' },
          onChange: async function() {
            this.close();
            this.setTextboxValue('');
            this.blur();
            const code = getSelectedCode(provinceSelect);
            await loadMunicipalitiesByProvinceCodeForSelect(code, citySelect);
            syncLocalLocationDuplicateOptions();
            updateLocalHiddenValue(i);
          }
        });
      }

      if (!citySelect.tomselect) {
        new TomSelect(citySelect, {
          create: function(input) {
            const city = formatOccupationValue(input);
            return city ? { value: city, text: city } : false;
          },
          createOnBlur: true,
          allowEmptyOption: true,
          placeholder: cityPlaceholder,
          closeAfterSelect: true,
          openOnFocus: true,
          maxOptions: 10000,
          searchField: ['text'],
          sortField: { field: 'text', direction: 'asc' },
          onChange: function() {
            this.close();
            this.setTextboxValue('');
            this.blur();
            updateLocalHiddenValue(i);
          }
        });
      }
    }
    syncLocalLocationDuplicateOptions();
  }

  async function applySavedLocalValues(local1, local2, local3) {
    await initLocalWorkLocationDropdowns();
    const values = [local1, local2, local3];
    for (let i = 1; i <= 3; i++) {
      const val = values[i - 1] || '';
      if (!val) continue;

      const provinceSelect = document.getElementById(`local${i}_province`);
      const citySelect = document.getElementById(`local${i}_city`);
      const hiddenInput = document.getElementById(`local${i}`);
      const parts = splitLocalValue(val);

      if (provinceSelect && parts.province) {
        ensureSelectValue(provinceSelect, parts.province);
        const provinceCode = getSelectedCode(provinceSelect);
        await loadMunicipalitiesByProvinceCodeForSelect(provinceCode, citySelect);
      }
      if (citySelect && parts.city) {
        ensureSelectValue(citySelect, parts.city);
      }
      if (hiddenInput) {
        hiddenInput.value = val;
      }
    }
    syncLocalLocationDuplicateOptions();
  }

  function initOccupationDropdowns() {
    ['occupation1', 'occupation2', 'occupation3'].forEach((id, index) => {
      const selectEl = document.getElementById(id);
      if (!selectEl || selectEl.tomselect) return;

      const ts = new TomSelect(selectEl, {
        create: function(input) {
          const formatted = formatOccupationValue(input);
          return formatted ? { value: formatted, text: formatted } : false;
        },
        createOnBlur: true,
        persist: true,
        allowEmptyOption: true,
        closeAfterSelect: true,
        openOnFocus: true,
        maxOptions: 10000,
        placeholder: index === 0
          ? 'Select preferred occupation'
          : 'Select preferred occupation (optional)',
        searchField: ['text'],
        sortField: { field: 'text', direction: 'asc' },
        onItemAdd: function(value) {
          const formatted = formatOccupationValue(value);
          if (formatted && formatted !== value) {
            this.removeItem(value, true);
            this.addOption({ value: formatted, text: formatted });
            this.setValue(formatted, true);
          }
          this.close();
          this.setTextboxValue('');
          this.blur();
          syncOccupationDuplicateOptions();
        },
        onChange: function() {
          syncOccupationDuplicateOptions();
        }
      });

      OCCUPATION_OPTIONS.forEach(occupation => {
        const formatted = formatOccupationValue(occupation);
        ts.addOption({ value: formatted, text: formatted });
      });
      ts.refreshOptions(false);
    });
    syncOccupationDuplicateOptions();
  }

  function initOverseasDropdowns() {
    ['overseas1', 'overseas2', 'overseas3'].forEach((id, index) => {
      const selectEl = document.getElementById(id);
      if (!selectEl || selectEl.tomselect) return;

      const ts = new TomSelect(selectEl, {
        create: false,
        allowEmptyOption: true,
        closeAfterSelect: true,
        openOnFocus: true,
        maxOptions: 10000,
        placeholder: index === 0 ? 'Select country' : 'Select country (optional)',
        searchField: ['text'],
        sortField: { field: 'text', direction: 'asc' },
        onItemAdd: function() {
          this.close();
          this.setTextboxValue('');
          this.blur();
          syncOverseasDuplicateOptions();
        },
        onChange: function() {
          syncOverseasDuplicateOptions();
        }
      });

      COUNTRY_OPTIONS.forEach(country => {
        ts.addOption({ value: country, text: country });
      });
      ts.refreshOptions(false);
    });
    syncOverseasDuplicateOptions();
  }

  function initDeploymentCountryDropdown() {
    const selectEl = document.getElementById('deployment_country');
    if (!selectEl || selectEl.tomselect) return;

    const ts = new TomSelect(selectEl, {
      create: false,
      allowEmptyOption: true,
      closeAfterSelect: true,
      openOnFocus: true,
      maxOptions: 10000,
      placeholder: 'Select country',
      searchField: ['text'],
      sortField: { field: 'text', direction: 'asc' },
      onDropdownOpen: function() {
        if (this.dropdown_content) {
          this.dropdown_content.scrollTop = 0;
        }
      },
      onItemAdd: function() {
        this.close();
        this.setTextboxValue('');
        this.blur();
      }
    });

    COUNTRY_OPTIONS.forEach(country => {
      ts.addOption({ value: country, text: country });
    });
    ts.refreshOptions(false);
  }

  function populateReturnYearOptions() {
    const selectEl = document.getElementById('return_year');
    if (!selectEl) return;
    const selectedYear = (selectEl.value || '').trim();
    const currentYear = new Date().getFullYear();
    const minYear = 1990;

    selectEl.innerHTML = '';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Select Year';
    selectEl.appendChild(placeholder);

    for (let year = currentYear; year >= minYear; year--) {
      const opt = document.createElement('option');
      opt.value = String(year);
      opt.textContent = String(year);
      selectEl.appendChild(opt);
    }

    if (selectedYear && Array.from(selectEl.options).some((o) => o.value === selectedYear)) {
      selectEl.value = selectedYear;
    }
  }

  function applyMobileFriendlyFieldLabels() {
    const isMobile = window.matchMedia('(max-width: 768px)').matches;

    const setInputPlaceholder = function(name, mobileText, desktopText) {
      const el = document.querySelector(`[name="${name}"]`);
      if (!el) return;
      if (isMobile) {
        el.setAttribute('placeholder', mobileText);
      } else {
        el.setAttribute('placeholder', desktopText);
      }
    };

    const ensureMobileReviewLabel = function(name, labelText) {
      const el = document.querySelector(`[name="${name}"]`);
      if (!el || !el.parentNode) return;
      const parent = el.parentNode;
      let labelEl = parent.querySelector(`.mobile-review-label[data-for="${name}"]`);
      if (!labelEl) {
        labelEl = document.createElement('div');
        labelEl.className = 'mobile-review-label';
        labelEl.setAttribute('data-for', name);
        parent.insertBefore(labelEl, el);
      }
      labelEl.textContent = labelText;
      labelEl.style.display = isMobile ? 'block' : 'none';
    };

    // Section 3.1 Technical training placeholders
    // On mobile, keep placeholders minimal because persistent labels are shown.
    setInputPlaceholder('training_course_1', '', 'Course 1');
    setInputPlaceholder('training_hours_1', '', 'Hours');
    setInputPlaceholder('training_institution_1', '', 'Institution');
    setInputPlaceholder('training_skills_1', '', 'Skills');
    setInputPlaceholder('training_cert_1', '', 'Certificate');
    setInputPlaceholder('training_course_2', '', 'Course 2');
    setInputPlaceholder('training_hours_2', '', 'Hours');
    setInputPlaceholder('training_institution_2', '', 'Institution');
    setInputPlaceholder('training_skills_2', '', 'Skills');
    setInputPlaceholder('training_cert_2', '', 'Certificate');
    setInputPlaceholder('training_course_3', '', 'Course 3');
    setInputPlaceholder('training_hours_3', '', 'Hours');
    setInputPlaceholder('training_institution_3', '', 'Institution');
    setInputPlaceholder('training_skills_3', '', 'Skills');
    setInputPlaceholder('training_cert_3', '', 'Certificate');

    // Section 3.3 Work experience placeholders
    // On mobile, keep placeholders minimal because persistent labels are shown.
    setInputPlaceholder('company_name_1', '', 'Company Name');
    setInputPlaceholder('company_address_1', '', 'Address');
    setInputPlaceholder('position_1', '', 'Position');
    setInputPlaceholder('months_1', '', 'Months');
    setInputPlaceholder('company_name_2', '', 'Company Name');
    setInputPlaceholder('company_address_2', '', 'Address');
    setInputPlaceholder('position_2', '', 'Position');
    setInputPlaceholder('months_2', '', 'Months');
    setInputPlaceholder('company_name_3', '', 'Company Name');
    setInputPlaceholder('company_address_3', '', 'Address');
    setInputPlaceholder('position_3', '', 'Position');
    setInputPlaceholder('months_3', '', 'Months');

    ['status_1', 'status_2', 'status_3'].forEach(function(name, idx) {
      const sel = document.querySelector(`select[name="${name}"]`);
      if (!sel || !sel.options || !sel.options.length) return;
      sel.options[0].text = isMobile ? 'Select status' : 'Status';
    });

    // Mobile review labels (remain visible even when value exists)
    ensureMobileReviewLabel('training_course_1', 'Course 1 - Training/Vocational Course');
    ensureMobileReviewLabel('training_hours_1', 'Course 1 - Hours of Training');
    ensureMobileReviewLabel('training_institution_1', 'Course 1 - Training Institution');
    ensureMobileReviewLabel('training_skills_1', 'Course 1 - Skills Acquired');
    ensureMobileReviewLabel('training_cert_1', 'Course 1 - Certificates Received');
    ensureMobileReviewLabel('training_course_2', 'Course 2 - Training/Vocational Course');
    ensureMobileReviewLabel('training_hours_2', 'Course 2 - Hours of Training');
    ensureMobileReviewLabel('training_institution_2', 'Course 2 - Training Institution');
    ensureMobileReviewLabel('training_skills_2', 'Course 2 - Skills Acquired');
    ensureMobileReviewLabel('training_cert_2', 'Course 2 - Certificates Received');
    ensureMobileReviewLabel('training_course_3', 'Course 3 - Training/Vocational Course');
    ensureMobileReviewLabel('training_hours_3', 'Course 3 - Hours of Training');
    ensureMobileReviewLabel('training_institution_3', 'Course 3 - Training Institution');
    ensureMobileReviewLabel('training_skills_3', 'Course 3 - Skills Acquired');
    ensureMobileReviewLabel('training_cert_3', 'Course 3 - Certificates Received');

    ensureMobileReviewLabel('company_name_1', 'Work 1 - Company Name');
    ensureMobileReviewLabel('company_address_1', 'Work 1 - Address');
    ensureMobileReviewLabel('position_1', 'Work 1 - Position');
    ensureMobileReviewLabel('months_1', 'Work 1 - Number of Months');
    ensureMobileReviewLabel('status_1', 'Work 1 - Status');
    ensureMobileReviewLabel('company_name_2', 'Work 2 - Company Name');
    ensureMobileReviewLabel('company_address_2', 'Work 2 - Address');
    ensureMobileReviewLabel('position_2', 'Work 2 - Position');
    ensureMobileReviewLabel('months_2', 'Work 2 - Number of Months');
    ensureMobileReviewLabel('status_2', 'Work 2 - Status');
    ensureMobileReviewLabel('company_name_3', 'Work 3 - Company Name');
    ensureMobileReviewLabel('company_address_3', 'Work 3 - Address');
    ensureMobileReviewLabel('position_3', 'Work 3 - Position');
    ensureMobileReviewLabel('months_3', 'Work 3 - Number of Months');
    ensureMobileReviewLabel('status_3', 'Work 3 - Status');
  }

  function setNameIntegrityLock(locked) {
    ['middlename', 'suffix'].forEach(function(id) {
      const input = document.getElementById(id);
      if (!input) return;
      input.readOnly = !!locked;
      if (locked) {
        input.style.backgroundColor = '#f5f5f5';
        input.style.cursor = 'not-allowed';
        input.title = 'Locked after submission for data integrity.';
      } else {
        input.style.backgroundColor = '';
        input.style.cursor = '';
        input.removeAttribute('title');
      }
    });
  }

  function initOfwCountryDropdown() {
    const selectEl = document.getElementById('ofw_country');
    if (!selectEl || selectEl.tomselect) return;

    const ts = new TomSelect(selectEl, {
      create: false,
      allowEmptyOption: true,
      closeAfterSelect: true,
      openOnFocus: true,
      maxOptions: 10000,
      placeholder: 'Specify Country',
      searchField: ['text'],
      sortField: { field: 'text', direction: 'asc' },
      onDropdownOpen: function() {
        if (this.dropdown_content) {
          this.dropdown_content.scrollTop = 0;
        }
      },
      onItemAdd: function() {
        this.close();
        this.setTextboxValue('');
        this.blur();
      }
    });

    COUNTRY_OPTIONS.forEach(country => {
      ts.addOption({ value: country, text: country });
    });
    ts.refreshOptions(false);
  }

  function initOtherLanguageDropdown() {
    const selectEl = document.getElementById('other_language');
    if (!selectEl || selectEl.tomselect) return;

    const ts = new TomSelect(selectEl, {
      create: function(input) {
        const formatted = formatOccupationValue(input);
        return formatted ? { value: formatted, text: formatted } : false;
      },
      createOnBlur: true,
      persist: true,
      allowEmptyOption: false,
      closeAfterSelect: true,
      openOnFocus: true,
      maxOptions: 10000,
      placeholder: 'Specify language/dialect',
      searchField: ['text'],
      sortField: { field: 'text', direction: 'asc' },
      onDropdownOpen: function() {
        if (this.dropdown_content) {
          this.dropdown_content.scrollTop = 0;
        }
      },
      onItemAdd: function(value) {
        const formatted = formatOccupationValue(value);
        if (formatted && formatted !== value) {
          this.removeItem(value, true);
          this.addOption({ value: formatted, text: formatted });
          this.setValue(formatted, true);
        }
        this.close();
        this.setTextboxValue('');
        this.blur();
      },
      onChange: function() {
        updateOtherLanguageToggleState();
      }
    });

    LANGUAGE_DIALECT_OPTIONS.forEach(item => {
      const formatted = formatOccupationValue(item);
      ts.addOption({ value: formatted, text: formatted });
    });
    ts.refreshOptions(false);
    updateOtherLanguageToggleState();
  }

  function initCourseDropdown() {
    const courseSelect = document.getElementById('course');
    if (!courseSelect || courseSelect.tomselect) return;

    function normalizeCourseEntry(value) {
      return (value || '').replace(/\s+/g, ' ').trim();
    }

    const ts = new TomSelect(courseSelect, {
      create: function(input) {
        const raw = normalizeCourseEntry(input);
        return raw ? { value: raw, text: raw } : false;
      },
      createOnBlur: true,
      persist: true,
      allowEmptyOption: false,
      closeAfterSelect: true,
      openOnFocus: true,
      maxOptions: 10000,
      placeholder: 'Select or type course/strand',
      searchField: ['text'],
      sortField: { field: 'text', direction: 'asc' },
      onDropdownOpen: function() {
        if (this.dropdown_content) {
          this.dropdown_content.scrollTop = 0;
        }
      },
      onItemAdd: function(value) {
        const raw = normalizeCourseEntry(value);
        if (raw && raw !== value) {
          this.removeItem(value, true);
          this.addOption({ value: raw, text: raw });
          this.setValue(raw, true);
        }
        this.close();
        this.setTextboxValue('');
        this.blur();
      }
    });

    const refreshCourseOptionsByLevel = () => {
      const levelSelect = document.getElementById('levelSelect');
      const currentValue = normalizeCourseEntry(ts.getValue());
      let options = [];
      const normalizedLevel = levelSelect
        ? (levelSelect.value || '').replace(/\s+/g, ' ').trim().toLowerCase()
        : '';
      if (normalizedLevel === 'secondary (k-12)' || normalizedLevel === 'secondary (k 12)') {
        options = SHS_STRAND_OPTIONS;
      } else if (normalizedLevel === 'tertiary' || normalizedLevel === 'graduate studies / post-graduate') {
        options = COURSE_ONLY_OPTIONS;
      }

      ts.clearOptions();
      options.forEach(item => ts.addOption({ value: item, text: item }));
      if (currentValue) {
        const inOptions = options.includes(currentValue);
        if (!inOptions) {
          // Keep saved/custom values even when not part of predefined options.
          ts.addOption({ value: currentValue, text: currentValue });
          ts.setValue(currentValue, true);
        } else {
          ts.setValue(currentValue, true);
        }
      } else {
        ts.clear(true);
      }
      ts.refreshOptions(false);
    };

    courseSelect.refreshCourseOptionsByLevel = refreshCourseOptionsByLevel;
    refreshCourseOptionsByLevel();
  }

  function updateOtherLanguageToggleState() {
    const otherLang = document.querySelector('[name="other_language"]');
    const selectAll = document.getElementById('other_select_all');
    const otherCheckboxes = [
      document.querySelector('input[name="other_read"]'),
      document.querySelector('input[name="other_write"]'),
      document.querySelector('input[name="other_speak"]'),
      document.querySelector('input[name="other_understand"]')
    ];
    if (!otherLang || !selectAll) return;
    const hasValue = !!(otherLang.value && otherLang.value.trim());
    if (!hasValue) {
      selectAll.checked = false;
      selectAll.indeterminate = false;
      otherCheckboxes.forEach((checkbox) => {
        if (!checkbox) return;
        checkbox.checked = false;
      });
    }
    selectAll.disabled = !hasValue;
    otherCheckboxes.forEach((checkbox) => {
      if (!checkbox) return;
      checkbox.disabled = !hasValue;
    });
  }

  async function applySavedAddressValues(province, municipality, barangay) {
    await initAddressDropdowns();
    const provinceSelect = document.getElementById('province');
    const municipalitySelect = document.getElementById('municipality');
    const barangaySelect = document.getElementById('barangay');
    if (!provinceSelect || !municipalitySelect || !barangaySelect) return;

    ensureSelectValue(provinceSelect, province);
    const provinceCode = getSelectedCode(provinceSelect);
    await loadMunicipalitiesByProvinceCode(provinceCode);

    ensureSelectValue(municipalitySelect, municipality);
    const cityCode = getSelectedCode(municipalitySelect);
    await loadBarangaysByCityCode(cityCode);

    ensureSelectValue(barangaySelect, barangay);
  }
  
  function showNextSection() {
    // Ensure location dropdown data is initialized before first validation step.
    if (typeof initAddressDropdowns === 'function') {
      initAddressDropdowns();
    }

    const currentSection = getCurrentSection();
    
    // Check for personal information section validation when moving from section1_1 (personal information section)
    if (currentSection === 'section1_1') {
      const surname = document.getElementById('surname');
      const firstname = document.getElementById('firstname');
      const dob = document.getElementById('dob');
      const sex = document.getElementById('sex');
      const civilstatus = document.getElementById('civilstatus');
      const street = document.getElementById('street');
      const barangay = document.getElementById('barangay');
      const municipality = document.getElementById('municipality');
      const province = document.getElementById('province');
      const tin = document.getElementById('tin');
      const contact = document.getElementById('contact');
      const email = document.getElementById('email');
      
      // Check required personal information fields
      if (!surname.value.trim()) {
        Swal.fire({
          title: 'Surname Required!',
          text: 'Please enter your surname.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      if (!firstname.value.trim()) {
        Swal.fire({
          title: 'First Name Required!',
          text: 'Please enter your first name.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      if (!dob.value) {
        Swal.fire({
          title: 'Date of Birth Required!',
          text: 'Please select your date of birth.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      if (!sex.value) {
        Swal.fire({
          title: 'Sex Required!',
          text: 'Please select your sex.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      if (!civilstatus.value) {
        Swal.fire({
          title: 'Civil Status Required!',
          text: 'Please select your civil status.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      // Check present address fields
      if (!street.value.trim()) {
        Swal.fire({
          title: 'Street Address Required!',
          text: 'Please enter your house no./street/village.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      if (!barangay.value.trim()) {
        Swal.fire({
          title: 'Barangay Required!',
          text: 'Please select your barangay.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      if (!municipality.value.trim()) {
        Swal.fire({
          title: 'Municipality/City Required!',
          text: 'Please select your municipality/city.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      if (!province.value.trim()) {
        Swal.fire({
          title: 'Province Required!',
          text: 'Please select your province.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      // Check contact information
      if (!contact.value.trim()) {
        Swal.fire({
          title: 'Contact Number Required!',
          text: 'Please enter your contact number.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      if (!email.value.trim()) {
        Swal.fire({
          title: 'Email Required!',
          text: 'Please enter your email address.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      // Check TIN validation - must be empty, 9 digits, or 12 digits
      const tinValue = tin.value.replace(/[^0-9]/g, ''); // Remove hyphens to count digits only
      if (tinValue.length > 0 && tinValue.length !== 9 && tinValue.length !== 12) {
        Swal.fire({
          title: 'Invalid TIN Format!',
          text: 'TIN must be empty, 9 digits (xxx-xxx-xxx), or 12 digits (xxx-xxx-xxx-xxxx).',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      // Check contact number validation - must be exactly 11 digits
      const contactValue = contact.value.replace(/[^0-9]/g, ''); // Remove hyphens to count digits only
      if (contactValue.length !== 11) {
        Swal.fire({
          title: 'Invalid Contact Number!',
          text: 'Contact number must be exactly 11 digits (xxxx-xxx-xxxx).',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      // Check email validation - must have proper email format with @ and domain
      const emailValue = email.value.trim();
      const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
      if (!emailPattern.test(emailValue)) {
        Swal.fire({
          title: 'Invalid Email Format!',
          text: 'Please enter a valid email address (e.g., sample@gmail.com, sample@yahoo.com).',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
    }
    
    // Check for employment status section validation when moving from section1_2 (employment status section)
    if (currentSection === 'section1_2') {
      const employed = document.getElementById('employed');
      const unemployed = document.getElementById('unemployed');
      const wageEmployed = document.querySelector('input[name="employment_type_wage"]');
      const selfEmployed = document.querySelector('input[name="employment_type_self"]');
      const selfEmployedSpecify = document.querySelector('input[name="self_employed_specify"]');
      const unemployedMonths = document.getElementById('unemployed_months');
      const terminatedAbroadCheckbox = document.querySelector('input[name="unemployed_type_terminated_abroad"]');
      const terminatedCountry = document.getElementById('terminated_country');
      const unemployedOtherCheckbox = document.querySelector('input[name="unemployed_type_others"]');
      const unemployedOtherSpecify = document.getElementById('unemployed_other_specify');
      const ofwYes = document.getElementById('ofwYes');
      const ofwNo = document.getElementById('ofwNo');
      const ofwCountry = document.getElementById('ofw_country');
      const returneeYes = document.getElementById('returneeYes');
      const returneeNo = document.getElementById('returneeNo');
      const deploymentCountry = document.getElementById('deployment_country');
      const returnMonth = document.getElementById('return_month');
      const returnYear = document.getElementById('return_year');
      const beneficiaryYes = document.getElementById('beneficiaryYes');
      const beneficiaryNo = document.getElementById('beneficiaryNo');
      const householdId = document.getElementById('household_id');
      
      // Check if at least one employment status is selected (employed or unemployed)
      if (!employed.checked && !unemployed.checked) {
        Swal.fire({
          title: 'Employment Status Required!',
          text: 'Please select either "Employed" or "Unemployed".',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      // If employed is selected
      if (employed.checked) {
        // Check if wage employed or self employed is selected
        if (!wageEmployed.checked && !selfEmployed.checked) {
          Swal.fire({
            title: 'Employment Type Required!',
            text: 'Please select either "Wage employed" or "Self-employed".',
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#ff9800'
          });
          return; // Prevent navigation
        }
        
        // If wage employed is selected
        if (wageEmployed.checked) {
          const wageOptions = document.querySelectorAll('input[name^="self_type_"]:not([name="self_type_others"]):not([name="other_jobs"])');
          const othersCheckbox = document.querySelector('input[name="self_type_others"]');
          const othersField = document.querySelector('input[name="other_jobs"]');
          
          let hasWageOption = false;
          wageOptions.forEach(option => {
            if (option.checked) hasWageOption = true;
          });
          
          // Check if at least one wage option is selected or if others is selected with value
          if (!hasWageOption && !othersCheckbox.checked) {
            Swal.fire({
              title: 'Wage Employment Option Required!',
              text: 'Please select at least one option under wage employed.',
              icon: 'warning',
              confirmButtonText: 'OK',
              confirmButtonColor: '#ff9800'
            });
            return; // Prevent navigation
          }
          
          // If others is selected, check if the specify field has value
          if (othersCheckbox.checked && !othersField.value.trim()) {
            Swal.fire({
              title: 'Specification Required!',
              text: 'Please specify the "Others" option under wage employed.',
              icon: 'warning',
              confirmButtonText: 'OK',
              confirmButtonColor: '#ff9800'
            });
            return; // Prevent navigation
          }
        }
        
        // If self employed is selected
        if (selfEmployed.checked) {
          if (!selfEmployedSpecify.value.trim()) {
            Swal.fire({
              title: 'Self-Employment Specification Required!',
              text: 'Please specify your self-employment type.',
              icon: 'warning',
              confirmButtonText: 'OK',
              confirmButtonColor: '#ff9800'
            });
            return; // Prevent navigation
          }
        }
      }
      
      // If unemployed is selected
      if (unemployed.checked) {
        // Check if "How long looking for work?" has value
        if (!unemployedMonths.value.trim()) {
          Swal.fire({
            title: 'Unemployment Duration Required!',
            text: 'Please specify how long you have been looking for work (in months).',
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#ff9800'
          });
          return; // Prevent navigation
        }
        
        // Check if at least one unemployed type is selected
        const unemployedTypes = document.querySelectorAll('input[name^="unemployed_type_"]');
        let hasUnemployedType = false;
        unemployedTypes.forEach(type => {
          if (type.checked) hasUnemployedType = true;
        });
        
        if (!hasUnemployedType) {
          Swal.fire({
            title: 'Unemployment Type Required!',
            text: 'Please select at least one unemployment type.',
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#ff9800'
          });
          return; // Prevent navigation
        }
        
        // If terminated/laid off (abroad) is selected, check if country field has value
        if (terminatedAbroadCheckbox.checked && !terminatedCountry.value.trim()) {
          Swal.fire({
            title: 'Termination Country Required!',
            text: 'Please specify the country where you were terminated/laid off.',
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#ff9800'
          });
          return; // Prevent navigation
        }
        if (unemployedOtherCheckbox.checked && !unemployedOtherSpecify.value.trim()) {
          Swal.fire({
            title: 'Other Details Required!',
            text: 'Please specify details for "Others".',
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#ff9800'
          });
          return; // Prevent navigation
        }
      }
      
      // Check OFW selection
      if (!ofwYes.checked && !ofwNo.checked) {
        Swal.fire({
          title: 'OFW Status Required!',
          text: 'Please select whether you are an OFW or not.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      // If OFW is Yes, check if country is specified
      if (ofwYes.checked && !ofwCountry.value.trim()) {
        Swal.fire({
          title: 'OFW Country Required!',
          text: 'Please specify the country where you are/were employed.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      // Check returnee OFW selection
      if (!returneeYes.checked && !returneeNo.checked) {
        Swal.fire({
          title: 'Returnee OFW Status Required!',
          text: 'Please select whether you are a returnee OFW or not.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      // If returnee OFW is Yes, check required fields
      if (returneeYes.checked) {
        if (!deploymentCountry.value.trim() || !returnMonth.value || !returnYear.value) {
          Swal.fire({
            title: 'Returnee OFW Information Required!',
            text: 'Please provide the deployment country, month of return, and year of return.',
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#ff9800'
          });
          return; // Prevent navigation
        }
      }
      
      // Check 4Ps beneficiary selection
      if (!beneficiaryYes.checked && !beneficiaryNo.checked) {
        Swal.fire({
          title: '4Ps Beneficiary Status Required!',
          text: 'Please select whether you are a 4Ps beneficiary or not.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      // If 4Ps beneficiary is Yes, check if household ID is provided
      if (beneficiaryYes.checked && !householdId.value.trim()) {
        Swal.fire({
          title: 'Household ID Required!',
          text: 'Please provide your Household ID number.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
    }
    
    // Check for job preference section validation when moving from section2_1 (job preference section)
    if (currentSection === 'section2_1') {
      const fulltime = document.querySelector('input[name="fulltime"]');
      const parttime = document.querySelector('input[name="parttime"]');
      const occupation1 = document.querySelector('[name="occupation1"]');
      const local1 = document.querySelector('input[name="local1"]');
      
      // Check if at least one employment type is selected (full-time or part-time)
      if (!fulltime.checked && !parttime.checked) {
        Swal.fire({
          title: 'Employment Type Required!',
          text: 'Please select at least one employment type (Full-time or Part-time).',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      // Check if at least one preferred occupation is provided
      if (!occupation1.value.trim()) {
        Swal.fire({
          title: 'Preferred Occupation Required!',
          text: 'Please provide at least one preferred occupation.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      // Check if at least one local work location is provided
      if (!local1.value.trim()) {
        Swal.fire({
          title: 'Local Work Location Required!',
          text: 'Please provide at least one local work location (city/municipality).',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }

      // If a province is selected in any local row, municipality/city becomes required in that row.
      for (let i = 1; i <= 3; i++) {
        const provinceEl = document.getElementById(`local${i}_province`);
        const cityEl = document.getElementById(`local${i}_city`);
        const provinceValue = provinceEl ? (provinceEl.value || '').trim() : '';
        const cityValue = cityEl ? (cityEl.value || '').trim() : '';
        if (provinceValue && !cityValue) {
          Swal.fire({
            title: 'Municipality/City Required!',
            text: `Please select the municipality/city for Local preference #${i}.`,
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#ff9800'
          });
          return; // Prevent navigation
        }
      }
      
    }
    
    // Check for education section validation when moving from section2_3 (education section)
    if (currentSection === 'section2_3') {
      const inSchoolYes = document.querySelector('input[name="inschool"][value="yes"]');
      const inSchoolNo = document.querySelector('input[name="inschool"][value="no"]');
      const levelSelect = document.getElementById('levelSelect');
      const courseField = document.querySelector('[name="course"]');
      const yearGraduated = document.querySelector('input[name="year_graduated"]');
      const levelReached = document.getElementById('level_reached');
      const lastAttended = document.getElementById('last_attended');
      const courseRequiredLevels = ['Secondary (K-12)', 'Tertiary', 'Graduate Studies / Post-graduate'];
      
      // Check if user has selected "Currently in School?" option
      if (!inSchoolYes.checked && !inSchoolNo.checked) {
        Swal.fire({
          title: 'Selection Required!',
          text: 'Please select whether you are currently in school or not.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      // If user selected "Yes" for currently in school
      if (inSchoolYes.checked) {
        if (!levelSelect.value) {
          Swal.fire({
            title: 'Level Required!',
            text: 'Please select your current education level.',
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#ff9800'
          });
          return; // Prevent navigation
        }
        if (courseRequiredLevels.includes(levelSelect.value) && (!courseField || !courseField.value.trim())) {
          Swal.fire({
            title: 'Course/Strand Required!',
            text: 'Please select or type your course/strand before proceeding.',
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#ff9800'
          });
          return; // Prevent navigation
        }
      }
      
      // If user selected "No" for currently in school
      if (inSchoolNo.checked) {
        // Check if they have graduated (Level and Year Graduated filled)
        const hasLevelAndYear = levelSelect.value && yearGraduated.value.trim();
        // Check if they are undergraduate (Level Reached and Year Last Attended filled)
        const hasLevelReachedAndLastAttended = levelReached.value && lastAttended.value.trim();

        if (levelSelect.value && courseRequiredLevels.includes(levelSelect.value) && (!courseField || !courseField.value.trim())) {
          Swal.fire({
            title: 'Course/Strand Required!',
            text: 'Please select or type your course/strand before proceeding.',
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#ff9800'
          });
          return; // Prevent navigation
        }
        
        if (!hasLevelAndYear && !hasLevelReachedAndLastAttended) {
          Swal.fire({
            title: 'Education Information Required!',
            text: 'Please provide either your graduation details (Level and Year Graduated) or your undergraduate information (Level Reached and Year Last Attended).',
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#ff9800'
          });
          return; // Prevent navigation
        }
      }
    }
    
    // Check for e-signature validation when moving from section3_4 (certification section)
    if (currentSection === 'section3_4') {
      const skillValidation = validateSkills();
      if (!skillValidation.valid) {
        Swal.fire({
          title: 'Validation Required!',
          text: skillValidation.message,
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
    }
    
    // Navigate forward through the 10-step sequence
    if (currentSection === 'section1_1') {
      showFormSection('section1_2');
    } else if (currentSection === 'section1_2') {
      // Moving from step1 to step2
      document.getElementById('step1Section').style.display = 'none';
      document.getElementById('step2Section').style.display = '';
      document.getElementById('step3Section').style.display = 'none';
      setRequiredForStep('step2Section');
      showFormSection('section2_1');
    } else if (currentSection === 'section2_1') {
      showFormSection('section2_2');
    } else if (currentSection === 'section2_2') {
      showFormSection('section2_3');
    } else if (currentSection === 'section2_3') {
      // Moving from step2 to step3
      document.getElementById('step1Section').style.display = 'none';
      document.getElementById('step2Section').style.display = 'none';
      document.getElementById('step3Section').style.display = '';
      setRequiredForStep('step3Section');
      showFormSection('section3_1');
    } else if (currentSection === 'section3_1') {
      showFormSection('section3_2');
    } else if (currentSection === 'section3_2') {
      showFormSection('section3_3');
    } else if (currentSection === 'section3_3') {
      showFormSection('section3_4');
    } else if (currentSection === 'section3_4') {
      // Validate e-signature before proceeding to resume section
      const skillsValidation = validateSkills();
      if (!skillsValidation.valid) {
        Swal.fire({
          title: 'E-Signature Required!',
          text: skillsValidation.message,
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      showFormSection('section3_5');
    }
    
    // Auto scroll to top after navigation
    scrollToTop();
  }
  
  function showPreviousSection() {
    const currentSection = getCurrentSection();
    
    // Navigate backward through the 10-step sequence (10-1)
    if (currentSection === 'section1_2') {
      showFormSection('section1_1');
    } else if (currentSection === 'section2_1') {
      // Moving from step2 to step1
      document.getElementById('step1Section').style.display = '';
      document.getElementById('step2Section').style.display = 'none';
      document.getElementById('step3Section').style.display = 'none';
      setRequiredForStep('step1Section');
      showFormSection('section1_2');
    } else if (currentSection === 'section2_2') {
      showFormSection('section2_1');
    } else if (currentSection === 'section2_3') {
      showFormSection('section2_2');
    } else if (currentSection === 'section3_1') {
      // Moving from step3 to step2
      document.getElementById('step1Section').style.display = 'none';
      document.getElementById('step2Section').style.display = '';
      document.getElementById('step3Section').style.display = 'none';
      setRequiredForStep('step2Section');
      showFormSection('section2_3');
    } else if (currentSection === 'section3_2') {
      showFormSection('section3_1');
    } else if (currentSection === 'section3_3') {
      showFormSection('section3_2');
    } else if (currentSection === 'section3_4') {
      showFormSection('section3_3');
    } else if (currentSection === 'section3_5') {
      showFormSection('section3_4');
    }
    
    // Auto scroll to top after navigation
    scrollToTop();
  }
  
  function getCurrentStep() {
    if (document.getElementById('step1Section').style.display !== 'none') return 1;
    if (document.getElementById('step2Section').style.display !== 'none') return 2;
    if (document.getElementById('step3Section').style.display !== 'none') return 3;
    return 1;
  }
  
  function getCurrentSection() {
    const allSections = document.querySelectorAll('.form-section');
    for (let section of allSections) {
      if (section.style.display !== 'none' && section.offsetParent !== null) {
        return section.id;
      }
    }
    return '';
  }
  
  // Function to scroll to top of the form
  function scrollToTop() {
    // Scroll the iframe content to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
    document.documentElement.scrollTop = 0;
    document.body.scrollTop = 0;
    
    // Also scroll the parent window to the top of the apply section
    if (window.parent && window.parent !== window) {
      try {
        // Send message to parent to scroll to apply section
        window.parent.postMessage({ type: 'scrollToApplySection' }, '*');
      } catch (e) {
        // Silently fail if there are any issues
      }
    }
  }
  
  // Update progress indicator based on current section
  function updateProgressIndicator() {
    const progressFill = document.getElementById('progressFill');
    const steps = document.querySelectorAll('.step');
    const currentSection = getCurrentSection();
    
    // Map sections to progress
    const sectionProgress = {
      'section1_1': 1, 'section1_2': 2, 'section2_1': 3, 'section2_2': 4, 'section2_3': 5,
      'section3_1': 6, 'section3_2': 7, 'section3_3': 8, 'section3_4': 9, 'section3_5': 10
    };
    
    const currentProgress = sectionProgress[currentSection] || 1;
    
    // Update progress bar
    progressFill.className = `progress-fill section${currentProgress}`;
    
    // Update step indicators
    steps.forEach((step, index) => {
      step.classList.remove('active', 'completed');
      if (index + 1 < currentProgress) {
        step.classList.add('completed');
      } else if (index + 1 === currentProgress) {
        step.classList.add('active');
      }
    });
  }
  
  // Map step numbers to their corresponding sections
  const stepToSectionMap = {
    1: { section: 'section1_1', stepSection: 'step1Section' },
    2: { section: 'section1_2', stepSection: 'step1Section' },
    3: { section: 'section2_1', stepSection: 'step2Section' },
    4: { section: 'section2_2', stepSection: 'step2Section' },
    5: { section: 'section2_3', stepSection: 'step2Section' },
    6: { section: 'section3_1', stepSection: 'step3Section' },
    7: { section: 'section3_2', stepSection: 'step3Section' },
    8: { section: 'section3_3', stepSection: 'step3Section' },
    9: { section: 'section3_4', stepSection: 'step3Section' },
    10: { section: 'section3_5', stepSection: 'step3Section' }
  };
  
  // Validate all required fields up to a specific step
  function validateStepsUpTo(targetStep) {
    const currentSection = getCurrentSection();
    const currentStep = getStepNumberFromSection(currentSection);
    
    // If going backwards, no validation needed
    if (targetStep <= currentStep) {
      return { valid: true, message: '' };
    }
    
    // Validate each step from current to target
    for (let step = currentStep; step < targetStep; step++) {
      const validation = validateStep(step);
      if (!validation.valid) {
        return validation;
      }
    }
    
    return { valid: true, message: '' };
  }
  
  // Get step number from section ID
  function getStepNumberFromSection(sectionId) {
    const sectionToStepMap = {
      'section1_1': 1, 'section1_2': 2, 'section2_1': 3, 'section2_2': 4, 'section2_3': 5,
      'section3_1': 6, 'section3_2': 7, 'section3_3': 8, 'section3_4': 9, 'section3_5': 10
    };
    return sectionToStepMap[sectionId] || 1;
  }
  
  // Validate a specific step
  function validateStep(stepNumber) {
    switch(stepNumber) {
      case 1: // Personal Information (section1_1)
        return validatePersonalInfo();
      case 2: // Employment Status (section1_2)
        return validateEmploymentStatus();
      case 3: // Job Preference (section2_1)
        return validateJobPreference();
      case 4: // Language (section2_2) - no required fields, always valid
        return { valid: true, message: '' };
      case 5: // Education (section2_3)
        return validateEducation();
      case 6: // Training (section3_1) - no required fields, always valid
        return { valid: true, message: '' };
      case 7: // Eligibility (section3_2) - no required fields, always valid
        return { valid: true, message: '' };
      case 8: // Experience (section3_3) - no required fields, always valid
        return { valid: true, message: '' };
      case 9: // Skills (section3_4) - e-signature required
        return validateSkills();
      case 10: // Resume (section3_5) - resume required
        return validateResume();
      default:
        return { valid: true, message: '' };
    }
  }
  
  // Validation functions for each step (reusing existing validation logic)
  function validatePersonalInfo() {
    const surname = document.getElementById('surname');
    const firstname = document.getElementById('firstname');
    const dob = document.getElementById('dob');
    const sex = document.getElementById('sex');
    const civilstatus = document.getElementById('civilstatus');
    const street = document.getElementById('street');
    const barangay = document.getElementById('barangay');
    const municipality = document.getElementById('municipality');
    const province = document.getElementById('province');
    const contact = document.getElementById('contact');
    const email = document.getElementById('email');
    const tin = document.getElementById('tin');
    
    if (!surname.value.trim()) {
      return { valid: false, message: 'Surname is required.' };
    }
    if (!firstname.value.trim()) {
      return { valid: false, message: 'First Name is required.' };
    }
    if (!dob.value) {
      return { valid: false, message: 'Date of Birth is required.' };
    }
    if (!sex.value) {
      return { valid: false, message: 'Sex is required.' };
    }
    if (!civilstatus.value) {
      return { valid: false, message: 'Civil Status is required.' };
    }
    if (!street.value.trim()) {
      return { valid: false, message: 'Street Address is required.' };
    }
    if (!barangay.value.trim()) {
      return { valid: false, message: 'Barangay is required.' };
    }
    if (!municipality.value.trim()) {
      return { valid: false, message: 'Municipality/City is required.' };
    }
    if (!province.value.trim()) {
      return { valid: false, message: 'Province is required.' };
    }
    if (!contact.value.trim()) {
      return { valid: false, message: 'Contact Number is required.' };
    }
    const contactValue = contact.value.replace(/[^0-9]/g, '');
    if (contactValue.length !== 11) {
      return { valid: false, message: 'Contact number must be exactly 11 digits (xxxx-xxx-xxxx).' };
    }
    if (!email.value.trim()) {
      return { valid: false, message: 'Email is required.' };
    }
    const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (!emailPattern.test(email.value.trim())) {
      return { valid: false, message: 'Please enter a valid email address.' };
    }
    const tinValue = tin.value.replace(/[^0-9]/g, '');
    if (tinValue.length > 0 && tinValue.length !== 9 && tinValue.length !== 12) {
      return { valid: false, message: 'TIN must be empty, 9 digits (xxx-xxx-xxx), or 12 digits (xxx-xxx-xxx-xxxx).' };
    }
    
    return { valid: true, message: '' };
  }
  
  function validateEmploymentStatus() {
    const employed = document.getElementById('employed');
    const unemployed = document.getElementById('unemployed');
    const wageEmployed = document.querySelector('input[name="employment_type_wage"]');
    const selfEmployed = document.querySelector('input[name="employment_type_self"]');
    const selfEmployedSpecify = document.querySelector('input[name="self_employed_specify"]');
    const unemployedMonths = document.getElementById('unemployed_months');
      const terminatedAbroadCheckbox = document.querySelector('input[name="unemployed_type_terminated_abroad"]');
      const terminatedCountry = document.getElementById('terminated_country');
      const unemployedOtherCheckbox = document.querySelector('input[name="unemployed_type_others"]');
      const unemployedOtherSpecify = document.getElementById('unemployed_other_specify');
    const ofwYes = document.getElementById('ofwYes');
    const ofwNo = document.getElementById('ofwNo');
    const ofwCountry = document.getElementById('ofw_country');
    const returneeYes = document.getElementById('returneeYes');
    const returneeNo = document.getElementById('returneeNo');
    const deploymentCountry = document.getElementById('deployment_country');
    const returnMonth = document.getElementById('return_month');
    const returnYear = document.getElementById('return_year');
    const beneficiaryYes = document.getElementById('beneficiaryYes');
    const beneficiaryNo = document.getElementById('beneficiaryNo');
    const householdId = document.getElementById('household_id');
    
    if (!employed.checked && !unemployed.checked) {
      return { valid: false, message: 'Please select either "Employed" or "Unemployed".' };
    }
    
    if (employed.checked) {
      if (!wageEmployed.checked && !selfEmployed.checked) {
        return { valid: false, message: 'Please select either "Wage employed" or "Self-employed".' };
      }
      if (selfEmployed.checked && !selfEmployedSpecify.value.trim()) {
        return { valid: false, message: 'Please specify your self-employment type.' };
      }
    }
    
    if (unemployed.checked) {
      if (!unemployedMonths.value.trim()) {
        return { valid: false, message: 'Please specify how long you have been looking for work (in months).' };
      }
      const unemployedTypes = document.querySelectorAll('input[name^="unemployed_type_"]');
      let hasUnemployedType = false;
      unemployedTypes.forEach(type => {
        if (type.checked) hasUnemployedType = true;
      });
      if (!hasUnemployedType) {
        return { valid: false, message: 'Please select at least one unemployment type.' };
      }
      if (terminatedAbroadCheckbox.checked && !terminatedCountry.value.trim()) {
        return { valid: false, message: 'Please specify the country where you were terminated/laid off.' };
      }
      if (unemployedOtherCheckbox.checked && !unemployedOtherSpecify.value.trim()) {
        return { valid: false, message: 'Please specify details for "Others".' };
      }
    }
    
    if (!ofwYes.checked && !ofwNo.checked) {
      return { valid: false, message: 'Please select whether you are an OFW or not.' };
    }
    if (ofwYes.checked && !ofwCountry.value.trim()) {
      return { valid: false, message: 'Please specify the country where you are/were employed.' };
    }
    
    if (!returneeYes.checked && !returneeNo.checked) {
      return { valid: false, message: 'Please select whether you are a returnee OFW or not.' };
    }
    if (returneeYes.checked) {
      if (!deploymentCountry.value.trim() || !returnMonth.value || !returnYear.value) {
        return { valid: false, message: 'Please provide the deployment country, month of return, and year of return.' };
      }
    }
    
    if (!beneficiaryYes.checked && !beneficiaryNo.checked) {
      return { valid: false, message: 'Please select whether you are a 4Ps beneficiary or not.' };
    }
    if (beneficiaryYes.checked && !householdId.value.trim()) {
      return { valid: false, message: 'Please provide your Household ID number.' };
    }
    
    return { valid: true, message: '' };
  }
  
  function validateJobPreference() {
    const fulltime = document.querySelector('input[name="fulltime"]');
    const parttime = document.querySelector('input[name="parttime"]');
    const occupation1 = document.querySelector('[name="occupation1"]');
    const local1 = document.querySelector('input[name="local1"]');
    const overseas1 = document.querySelector('[name="overseas1"]');
    
    if (!fulltime.checked && !parttime.checked) {
      return { valid: false, message: 'Please select at least one employment type (Full-time or Part-time).' };
    }
    if (!occupation1.value.trim()) {
      return { valid: false, message: 'Please provide at least one preferred occupation.' };
    }
    if (!local1.value.trim()) {
      return { valid: false, message: 'Please provide at least one local work location (city/municipality).' };
    }
    for (let i = 1; i <= 3; i++) {
      const provinceEl = document.getElementById(`local${i}_province`);
      const cityEl = document.getElementById(`local${i}_city`);
      const provinceValue = provinceEl ? (provinceEl.value || '').trim() : '';
      const cityValue = cityEl ? (cityEl.value || '').trim() : '';
      if (provinceValue && !cityValue) {
        return { valid: false, message: `Please select the municipality/city for Local preference #${i}.` };
      }
    }
    return { valid: true, message: '' };
  }
  
  function validateEducation() {
    const inSchoolYes = document.querySelector('input[name="inschool"][value="yes"]');
    const inSchoolNo = document.querySelector('input[name="inschool"][value="no"]');
    const levelSelect = document.getElementById('levelSelect');
    const courseField = document.querySelector('[name="course"]');
    const yearGraduated = document.querySelector('input[name="year_graduated"]');
    const levelReached = document.getElementById('level_reached');
    const lastAttended = document.getElementById('last_attended');
    const courseRequiredLevels = ['Secondary (K-12)', 'Tertiary', 'Graduate Studies / Post-graduate'];
    
    if (!inSchoolYes.checked && !inSchoolNo.checked) {
      return { valid: false, message: 'Please select whether you are currently in school or not.' };
    }
    
    if (inSchoolYes.checked) {
      if (!levelSelect.value) {
        return { valid: false, message: 'Please select your current education level.' };
      }
      if (courseRequiredLevels.includes(levelSelect.value) && (!courseField || !courseField.value.trim())) {
        return { valid: false, message: 'Course/Strand is required for the selected education level.' };
      }
    }
    
    if (inSchoolNo.checked) {
      const hasLevelAndYear = levelSelect.value && yearGraduated.value.trim();
      const hasLevelReachedAndLastAttended = levelReached.value && lastAttended.value.trim();

      if (levelSelect.value && courseRequiredLevels.includes(levelSelect.value) && (!courseField || !courseField.value.trim())) {
        return { valid: false, message: 'Course/Strand is required for the selected education level.' };
      }
      
      if (!hasLevelAndYear && !hasLevelReachedAndLastAttended) {
        return { valid: false, message: 'Please provide either your graduation details (Level and Year Graduated) or your undergraduate information (Level Reached and Year Last Attended).' };
      }
    }
    
    return { valid: true, message: '' };
  }
  
  function validateSkills() {
    const otherSkillsInput = document.querySelector('input[name="skill_others"]');
    if (!otherSkillsInput || !otherSkillsInput.value.trim()) {
      return { valid: false, message: 'Please enter at least one skill in the Others field.' };
    }

    const esignatureInput = document.getElementById('esignature');
    const esignaturePreview = document.getElementById('esignaturePreview');
    
    // Check for new file upload
    const hasNewFile = esignatureInput && esignatureInput.files && esignatureInput.files.length > 0;
    
    // Check for existing file displayed in preview
    const hasExistingPreview = esignaturePreview && 
                               esignaturePreview.style.display !== 'none' && 
                               esignaturePreview.offsetParent !== null;
    
    // Check for hidden input with existing filename
    const existingEsignatureInput = document.querySelector('input[name="existing_esignature_file"]');
    const hasHiddenInput = existingEsignatureInput && existingEsignatureInput.value && existingEsignatureInput.value.trim() !== '';
    
    // Check if preview has an image source (existing file loaded)
    const esignatureImage = document.getElementById('esignatureImage');
    const hasImageSource = esignatureImage && esignatureImage.src && esignatureImage.src !== window.location.href;
    
    // If any of these conditions are true, we have a valid e-signature
    if (hasNewFile || hasExistingPreview || hasHiddenInput || hasImageSource) {
      return { valid: true, message: '' };
    }
    
    return { valid: false, message: 'E-Signature is required before proceeding to the next section.' };
  }
  
  function validateResume() {
    const resumeInput = document.getElementById('resume_file');
    const resumeContainer = resumeInput ? resumeInput.closest('.form-row') : null;
    const existingResumeInput = document.querySelector('input[name="existing_resume_file"]');
    
    // Check for new file upload
    const hasNewFile = resumeInput && resumeInput.files && resumeInput.files.length > 0;
    
    // Check for existing resume display
    const hasExistingResumeDisplay = resumeContainer && resumeContainer.querySelector('.existing-resume-display');
    
    // Check for hidden input with existing filename
    const hasHiddenInput = existingResumeInput && existingResumeInput.value && existingResumeInput.value.trim() !== '';
    
    // If any of these conditions are true, we have a valid resume
    if (hasNewFile || hasExistingResumeDisplay || hasHiddenInput) {
      return { valid: true, message: '' };
    }
    
    return { valid: false, message: 'Resume upload is required.' };
  }
  
  // Navigate to a specific step with validation
  function navigateToStep(stepNumber) {
    const currentSection = getCurrentSection();
    const currentStep = getStepNumberFromSection(currentSection);
    
    // If clicking on current step, do nothing
    if (stepNumber === currentStep) {
      return;
    }
    
    // Validate if going forward
    if (stepNumber > currentStep) {
      const validation = validateStepsUpTo(stepNumber);
      if (!validation.valid) {
        Swal.fire({
          title: 'Validation Required!',
          text: validation.message + ' Please complete the required fields before proceeding.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return;
      }
    }
    
    // Navigate to the target step
    const targetStepInfo = stepToSectionMap[stepNumber];
    if (!targetStepInfo) {
      return;
    }
    
    // Show the appropriate step section container
    document.getElementById('step1Section').style.display = 'none';
    document.getElementById('step2Section').style.display = 'none';
    document.getElementById('step3Section').style.display = 'none';
    document.getElementById(targetStepInfo.stepSection).style.display = '';
    
    // Show the specific section
    showFormSection(targetStepInfo.section);
    
    // Update required fields for the new step
    setRequiredForStep(targetStepInfo.stepSection);
    
    // Scroll to top
    scrollToTop();
  }
  
  // Add click event listeners to step indicators
  function setupStepNavigation() {
    const steps = document.querySelectorAll('.step');
    steps.forEach((step, index) => {
      const stepNumber = index + 1;
      step.addEventListener('click', function() {
        navigateToStep(stepNumber);
      });
      
      // Add title attribute for better UX
      const stepLabels = ['Personal Info', 'Employment', 'Job Preference', 'Language', 'Education', 
                          'Training', 'Eligibility', 'Experience', 'Skills', 'Resume'];
      step.setAttribute('title', `Click to go to Step ${stepNumber}: ${stepLabels[index]}`);
    });
  }
  
  // Step navigation is initialized after page load (called after showStep1)


  // On page load, mark all required fields with a data attribute
  document.querySelectorAll('input[required], select[required], textarea[required]').forEach(f => {
    f.setAttribute('data-always-required', 'true');
    f.removeAttribute('required');
  });

  // --- Dynamic field logic for Disability, Employed, Unemployed ---
  function toggleDisabilityFields() {
    const hasDisability = document.getElementById('hasDisability');
    const group = document.getElementById('disabilityFields');
    const inputs = group.querySelectorAll('input');
    if (hasDisability.checked) {
      group.style.pointerEvents = '';
      group.style.opacity = '1';
      inputs.forEach(i => i.disabled = false);
    } else {
      group.style.pointerEvents = 'none';
      group.style.opacity = '0.6';
      inputs.forEach(i => { i.disabled = true; if(i.type==='checkbox') i.checked = false; else i.value = ''; });
    }
  }


  function toggleEmployedFields() {
    const employed = document.getElementById('employed');
    const employedFields = document.getElementById('employedFields');
    const selfTypeFields = document.getElementById('selfTypeFields');
    const wage = employedFields.querySelector('input[name="employment_type_wage"]');
    const self = employedFields.querySelector('input[name="employment_type_self"]');
    const selfSpecify = employedFields.querySelector('input[name="self_employed_specify"]');
    
    if (employed.checked) {
      employedFields.style.pointerEvents = '';
      employedFields.style.opacity = '1';
      wage.disabled = false;
      self.disabled = false;
      
      // Handle wage employed vs self employed logic
      if (wage.checked) {
        // If wage employed is checked, show self type fields but hide self specify
        selfTypeFields.style.pointerEvents = '';
        selfTypeFields.style.opacity = '1';
        selfTypeFields.querySelectorAll('input').forEach(i => i.disabled = false);
        selfSpecify.style.display = 'none';
        selfSpecify.disabled = true; selfSpecify.value = '';
      } else if (self.checked) {
        // If self employed is checked, hide self type fields but show self specify
        selfTypeFields.style.pointerEvents = 'none';
        selfTypeFields.style.opacity = '0.6';
        selfTypeFields.querySelectorAll('input').forEach(i => { i.disabled = true; if(i.type==='checkbox') i.checked = false; else i.value = ''; });
        selfSpecify.style.display = '';
        selfSpecify.disabled = false;
      } else {
        // Neither checked, hide both self type fields and self specify
        selfTypeFields.style.pointerEvents = 'none';
        selfTypeFields.style.opacity = '0.6';
        selfTypeFields.querySelectorAll('input').forEach(i => { i.disabled = true; if(i.type==='checkbox') i.checked = false; else i.value = ''; });
        selfSpecify.style.display = 'none';
        selfSpecify.disabled = true; selfSpecify.value = '';
      }
    } else {
      employedFields.style.pointerEvents = 'none';
      employedFields.style.opacity = '0.6';
      wage.disabled = true; wage.checked = false;
      self.disabled = true; self.checked = false;
      selfSpecify.style.display = 'none';
      selfSpecify.disabled = true; selfSpecify.value = '';
      selfTypeFields.style.pointerEvents = 'none';
      selfTypeFields.style.opacity = '0.6';
      selfTypeFields.querySelectorAll('input').forEach(i => { i.disabled = true; if(i.type==='checkbox') i.checked = false; else i.value = ''; });
    }
  }

  function toggleSelfTypeFields() {
    const employed = document.getElementById('employed');
    const employedFields = document.getElementById('employedFields');
    const self = employedFields.querySelector('input[name="employment_type_self"]');
    const selfTypeFields = document.getElementById('selfTypeFields');
    if (self.checked && employed.checked) {
      selfTypeFields.style.pointerEvents = '';
      selfTypeFields.style.opacity = '1';
      selfTypeFields.querySelectorAll('input').forEach(i => i.disabled = false);
    } else {
      selfTypeFields.style.pointerEvents = 'none';
      selfTypeFields.style.opacity = '0.6';
      selfTypeFields.querySelectorAll('input').forEach(i => { i.disabled = true; if(i.type==='checkbox') i.checked = false; else i.value = ''; });
    }
  }

  // --- Others checkboxes logic ---
  function setupOthersCheckbox(checkboxName, textboxName) {
    const checkbox = document.querySelector(`input[name="${checkboxName}"]`);
    const textbox = document.querySelector(`input[name="${textboxName}"]`);
    if (!checkbox || !textbox) return;
    function toggleTextbox() {
      if (checkbox.checked) {
        textbox.disabled = false;
        textbox.style.display = '';
      } else {
        textbox.disabled = true;
        textbox.value = '';
        textbox.style.display = 'none';
      }
    }
    checkbox.addEventListener('change', toggleTextbox);
    // Initial state
    toggleTextbox();
  }

  function toggleUnemployedFields() {
    const unemployed = document.getElementById('unemployed');
    const unemployedFields = document.getElementById('unemployedFields');
    const unemployedTypeFields = document.getElementById('unemployedTypeFields');
    const terminatedCountryLabel = document.querySelector('label[for="terminated_country"]');
    const terminatedCountryInput = document.getElementById('terminated_country');
    const terminatedAbroadCheckbox = document.querySelector('input[name="unemployed_type_terminated_abroad"]');
    const unemployedOtherCheckbox = document.querySelector('input[name="unemployed_type_others"]');
    const unemployedOtherLabel = document.querySelector('label[for="unemployed_other_specify"]');
    const unemployedOtherInput = document.getElementById('unemployed_other_specify');
    
    if (unemployed.checked) {
      unemployedFields.style.pointerEvents = '';
      unemployedFields.style.opacity = '1';
      unemployedFields.querySelectorAll('input').forEach(i => i.disabled = false);
      unemployedTypeFields.style.pointerEvents = '';
      unemployedTypeFields.style.opacity = '1';
      unemployedTypeFields.querySelectorAll('input').forEach(i => i.disabled = false);
      
      // Show/hide terminated country field based on terminated checkbox
      if (terminatedAbroadCheckbox && terminatedAbroadCheckbox.checked) {
        terminatedCountryLabel.style.display = '';
        terminatedCountryInput.style.display = '';
        terminatedCountryInput.disabled = false;
      } else {
        terminatedCountryLabel.style.display = 'none';
        terminatedCountryInput.style.display = 'none';
        terminatedCountryInput.disabled = true;
        terminatedCountryInput.value = '';
      }
      if (unemployedOtherCheckbox && unemployedOtherCheckbox.checked) {
        unemployedOtherLabel.style.display = '';
        unemployedOtherInput.style.display = '';
        unemployedOtherInput.disabled = false;
      } else {
        unemployedOtherLabel.style.display = 'none';
        unemployedOtherInput.style.display = 'none';
        unemployedOtherInput.disabled = true;
        unemployedOtherInput.value = '';
      }
    } else {
      unemployedFields.style.pointerEvents = 'none';
      unemployedFields.style.opacity = '0.6';
      unemployedFields.querySelectorAll('input').forEach(i => { i.disabled = true; i.value = ''; });
      unemployedTypeFields.style.pointerEvents = 'none';
      unemployedTypeFields.style.opacity = '0.6';
      unemployedTypeFields.querySelectorAll('input').forEach(i => { i.disabled = true; if(i.type==='checkbox') i.checked = false; else i.value = ''; });
      terminatedCountryLabel.style.display = 'none';
      terminatedCountryInput.style.display = 'none';
      terminatedCountryInput.disabled = true;
      terminatedCountryInput.value = '';
      unemployedOtherLabel.style.display = 'none';
      unemployedOtherInput.style.display = 'none';
      unemployedOtherInput.disabled = true;
      unemployedOtherInput.value = '';
    }
  }

  // Event listeners

  document.getElementById('hasDisability').addEventListener('change', toggleDisabilityFields);
  document.getElementById('employed').addEventListener('change', function() {
    toggleEmployedFields();
    toggleSelfTypeFields();
    
    // If employed is checked, uncheck and disable unemployed
    if (this.checked) {
      const unemployedCheckbox = document.getElementById('unemployed');
      unemployedCheckbox.checked = false;
      unemployedCheckbox.disabled = true;
      toggleUnemployedFields(); // Hide unemployed fields
    } else {
      // If employed is unchecked, enable unemployed checkbox
      const unemployedCheckbox = document.getElementById('unemployed');
      unemployedCheckbox.disabled = false;
    }
  });
  
  document.getElementById('unemployed').addEventListener('change', function() {
    toggleUnemployedFields();
    
    // If unemployed is checked, uncheck and disable employed
    if (this.checked) {
      const employedCheckbox = document.getElementById('employed');
      employedCheckbox.checked = false;
      employedCheckbox.disabled = true;
      toggleEmployedFields(); // Hide employed fields
      toggleSelfTypeFields(); // Hide self-employed fields
    } else {
      // If unemployed is unchecked, enable employed checkbox
      const employedCheckbox = document.getElementById('employed');
      employedCheckbox.disabled = false;
    }
  });
  // Terminated abroad checkbox triggers show/hide of country field
  document.querySelector('input[name="unemployed_type_terminated_abroad"]').addEventListener('change', function() {
    const terminatedCountryLabel = document.querySelector('label[for="terminated_country"]');
    const terminatedCountryInput = document.getElementById('terminated_country');
    
    if (this.checked) {
      terminatedCountryLabel.style.display = '';
      terminatedCountryInput.style.display = '';
      terminatedCountryInput.disabled = false;
    } else {
      terminatedCountryLabel.style.display = 'none';
      terminatedCountryInput.style.display = 'none';
      terminatedCountryInput.disabled = true;
      terminatedCountryInput.value = '';
    }
  });
  // Unemployed others checkbox triggers show/hide of specify field
  document.querySelector('input[name="unemployed_type_others"]').addEventListener('change', function() {
    const othersLabel = document.querySelector('label[for="unemployed_other_specify"]');
    const othersInput = document.getElementById('unemployed_other_specify');
    if (this.checked) {
      othersLabel.style.display = '';
      othersInput.style.display = '';
      othersInput.disabled = false;
    } else {
      othersLabel.style.display = 'none';
      othersInput.style.display = 'none';
      othersInput.disabled = true;
      othersInput.value = '';
    }
  });
  // Self-employed checkbox triggers selfTypeFields and selfSpecify
  document.querySelector('input[name="employment_type_self"]').addEventListener('change', function() {
    toggleSelfTypeFields();
    toggleEmployedFields();
    
    // If self-employed is checked, uncheck and disable wage employed
    if (this.checked) {
      const wageEmployedCheckbox = document.querySelector('input[name="employment_type_wage"]');
      wageEmployedCheckbox.checked = false;
      wageEmployedCheckbox.disabled = true;
    } else {
      // If self-employed is unchecked, enable wage employed checkbox
      const wageEmployedCheckbox = document.querySelector('input[name="employment_type_wage"]');
      wageEmployedCheckbox.disabled = false;
    }
  });
  
  // Wage employed checkbox triggers toggleEmployedFields
  document.querySelector('input[name="employment_type_wage"]').addEventListener('change', function() {
    toggleEmployedFields();
    
    // If wage employed is checked, uncheck and disable self-employed and its components
    if (this.checked) {
      const selfEmployedCheckbox = document.querySelector('input[name="employment_type_self"]');
      selfEmployedCheckbox.checked = false;
      selfEmployedCheckbox.disabled = true;
      
      // Hide and disable self-employed specify field
      const selfSpecifyField = document.querySelector('input[name="self_employed_specify"]');
      selfSpecifyField.style.display = 'none';
      selfSpecifyField.disabled = true;
      selfSpecifyField.value = '';
      
      // Note: selfTypeFields (Voluntary, Vendor, etc.) are under wage employed, so they should remain enabled
    } else {
      // If wage employed is unchecked, enable self-employed checkbox
      const selfEmployedCheckbox = document.querySelector('input[name="employment_type_self"]');
      selfEmployedCheckbox.disabled = false;
    }
  });


  // Others checkboxes and textboxes
  setupOthersCheckbox('disability_others', 'disability_other');
  setupOthersCheckbox('self_type_others', 'other_jobs');
  setupOthersCheckbox('unemployed_type_others', 'unemployed_other_specify');

  // OFW radio logic
  document.getElementById('ofwYes').addEventListener('change', toggleOfwCountry);
  document.getElementById('ofwNo').addEventListener('change', toggleOfwCountry);
  toggleOfwCountry();

  // Returnee radio logic
  document.getElementById('returneeYes').addEventListener('change', toggleReturneeFields);
  document.getElementById('returneeNo').addEventListener('change', toggleReturneeFields);
  toggleReturneeFields();

  // Job beneficiary radio logic
  document.getElementById('beneficiaryYes').addEventListener('change', toggleHouseholdId);
  document.getElementById('beneficiaryNo').addEventListener('change', toggleHouseholdId);
  toggleHouseholdId();

  // On page load, initialize all
  toggleDisabilityFields();
  toggleEmployedFields();
  toggleSelfTypeFields();
  toggleUnemployedFields();

  // Set maximum date for date of birth (18+ years old)
  function setMaxDateOfBirth() {
    const currentYear = new Date().getFullYear();
    const maxYear = currentYear - 18;
    const maxDate = `${maxYear}-12-31`;
    document.getElementById('dob').setAttribute('max', maxDate);
  }
  
  // Call the function to set the max date
  setMaxDateOfBirth();

  // Name field validation - prevent numerical and unwanted characters
  function validateNameField(input) {
    // Remove any characters that are not letters, spaces, hyphens, periods, or Filipino special characters
    input.value = input.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    
    // Limit to maximum 40 characters
    if (input.value.length > 40) {
      input.value = input.value.substring(0, 40);
    }
  }

  // Add event listeners for name fields
  document.getElementById('surname').addEventListener('input', function() {
    validateNameField(this);
  });

  document.getElementById('firstname').addEventListener('input', function() {
    validateNameField(this);
  });

  document.getElementById('middlename').addEventListener('input', function() {
    validateNameField(this);
  });

  document.getElementById('suffix').addEventListener('input', function() {
    // For suffix, we don't allow spaces, only letters, periods, and Filipino characters
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\.]/g, '');
    
    // Limit to maximum 40 characters
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  document.getElementById('religion').addEventListener('input', function() {
    validateNameField(this);
  });

  // Address fields validation - street field limit to 50 characters, others to 40
  document.getElementById('street').addEventListener('input', function() {
    if (this.value.length > 0) {
      this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);
    }
    if (this.value.length > 50) {
      this.value = this.value.substring(0, 50);
    }
  });

  document.getElementById('barangay').addEventListener('input', function() {
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  document.getElementById('municipality').addEventListener('input', function() {
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  document.getElementById('province').addEventListener('input', function() {
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  // Email field validation - limit to 40 characters
  document.getElementById('email').addEventListener('input', function() {
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  // Self-employed specify field validation - only allow letters and Filipino characters
  document.querySelector('input[name="self_employed_specify"]').addEventListener('input', function() {
    // Remove any characters that are not letters, spaces, hyphens, periods, or Filipino special characters
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    
    // Limit to maximum 50 characters
    if (this.value.length > 50) {
      this.value = this.value.substring(0, 50);
    }
  });

  // Other jobs field validation - only allow letters and Filipino characters
  document.querySelector('input[name="other_jobs"]').addEventListener('input', function() {
    // Remove any characters that are not letters, spaces, hyphens, periods, or Filipino special characters
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    
    // Limit to maximum 50 characters
    if (this.value.length > 50) {
      this.value = this.value.substring(0, 50);
    }
  });

  // Unemployed months field validation - only allow numbers
  document.getElementById('unemployed_months').addEventListener('input', function() {
    // Remove any characters that are not numbers
    this.value = this.value.replace(/[^0-9]/g, '');
    
    // Limit to maximum 30 characters
    if (this.value.length > 30) {
      this.value = this.value.substring(0, 30);
    }
  });

  // Terminated country field validation - only allow letters and Filipino characters
  document.getElementById('terminated_country').addEventListener('input', function() {
    // Remove any characters that are not letters, spaces, hyphens, periods, or Filipino special characters
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    
    // Limit to maximum 50 characters
    if (this.value.length > 50) {
      this.value = this.value.substring(0, 50);
    }
  });
  document.getElementById('unemployed_other_specify').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ0-9\s\-\.]/g, '');
    if (this.value.length > 50) {
      this.value = this.value.substring(0, 50);
    }
  });

  // Job preference fields validation - occupation values are normalized via TomSelect create/add handlers

  // Local work location fields now use Province + Municipality/City dropdown pairs and hidden combined values.

  // Overseas fields now use searchable country dropdowns.

  // Other language field now uses TomSelect dropdown with optional custom entries.

  // Year fields validation - only allow numbers
  document.querySelector('input[name="year_graduated"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
    if (this.value.length > 10) {
      this.value = this.value.substring(0, 10);
    }
  });

  document.getElementById('last_attended').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
    if (this.value.length > 10) {
      this.value = this.value.substring(0, 10);
    }
  });

  // Technical training fields validation
  // Text fields (letters, numbers, and special characters allowed; max 40 characters)
  [
    'training_course_1','training_institution_1','training_skills_1','training_cert_1',
    'training_course_2','training_institution_2','training_skills_2','training_cert_2',
    'training_course_3','training_institution_3','training_skills_3','training_cert_3'
  ].forEach(function(fieldName) {
    const field = document.querySelector(`input[name="${fieldName}"]`);
    if (!field) return;
    field.addEventListener('input', function() {
      if (this.value.length > 40) {
        this.value = this.value.substring(0, 40);
      }
    });
  });

  // Hours fields (numeric only, max 10 characters)
  document.querySelector('input[name="training_hours_1"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
    if (this.value.length > 10) {
      this.value = this.value.substring(0, 10);
    }
  });

  document.querySelector('input[name="training_hours_2"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
    if (this.value.length > 10) {
      this.value = this.value.substring(0, 10);
    }
  });

  document.querySelector('input[name="training_hours_3"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
    if (this.value.length > 10) {
      this.value = this.value.substring(0, 10);
    }
  });

  // Eligibility and PRC fields validation (allow special characters; max 40 chars)
  ['eligibility_1', 'eligibility_2', 'prc_1', 'prc_2'].forEach(function(fieldName) {
    const field = document.querySelector(`input[name="${fieldName}"]`);
    if (!field) return;
    field.addEventListener('input', function() {
      if (this.value.length > 40) {
        this.value = this.value.substring(0, 40);
      }
    });
  });

  // Work experience fields validation
  // Text fields (alphabetic + numeric + parentheses, max 50 characters)
  document.querySelector('input[name="company_name_1"]').addEventListener('input', function() {
    if (this.value.length > 50) {
      this.value = this.value.substring(0, 50);
    }
  });

  document.querySelector('input[name="company_address_1"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-z0-9()\s\-\.]/g, '');
    if (this.value.length > 50) {
      this.value = this.value.substring(0, 50);
    }
  });

  document.querySelector('input[name="position_1"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-z0-9()\s\-\.]/g, '');
    if (this.value.length > 50) {
      this.value = this.value.substring(0, 50);
    }
  });


  document.querySelector('input[name="company_name_2"]').addEventListener('input', function() {
    if (this.value.length > 50) {
      this.value = this.value.substring(0, 50);
    }
  });

  document.querySelector('input[name="company_address_2"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-z0-9()\s\-\.]/g, '');
    if (this.value.length > 50) {
      this.value = this.value.substring(0, 50);
    }
  });

  document.querySelector('input[name="position_2"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-z0-9()\s\-\.]/g, '');
    if (this.value.length > 50) {
      this.value = this.value.substring(0, 50);
    }
  });


  document.querySelector('input[name="company_name_3"]').addEventListener('input', function() {
    if (this.value.length > 50) {
      this.value = this.value.substring(0, 50);
    }
  });

  document.querySelector('input[name="company_address_3"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-z0-9()\s\-\.]/g, '');
    if (this.value.length > 50) {
      this.value = this.value.substring(0, 50);
    }
  });

  document.querySelector('input[name="position_3"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-z0-9()\s\-\.]/g, '');
    if (this.value.length > 50) {
      this.value = this.value.substring(0, 50);
    }
  });


  // Months fields (numeric only, max 10 characters)
  document.querySelector('input[name="months_1"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
    if (this.value.length > 10) {
      this.value = this.value.substring(0, 10);
    }
  });

  document.querySelector('input[name="months_2"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
    if (this.value.length > 10) {
      this.value = this.value.substring(0, 10);
    }
  });

  document.querySelector('input[name="months_3"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
    if (this.value.length > 10) {
      this.value = this.value.substring(0, 10);
    }
  });

  // Skills others field validation - only allow letters and commas, and format as Title case per segment
  document.querySelector('input[name="skill_others"]').addEventListener('input', function() {
    // Save cursor position
    const start = this.selectionStart;
    let originalValue = this.value;
    
    // Only allow valid characters
    let value = originalValue.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ,\s]/g, '');
    
    // Split by comma
    let parts = value.split(',');
    
    // Format each segment
    let formattedParts = parts.map((part, index) => {
      // Trim only the leading space, keep trailing space if user just typed it
      let trimmed = part.trimStart();
      if (trimmed.length > 0) {
        // Capitalize the first letter of each word within the segment
        let formatted = trimmed.split(' ').map(word => {
          if (word.length > 0) {
            return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
          }
          return word;
        }).join(' ');
        
        // Add a single space before the segment if it's not the first segment
        return (index > 0 ? ' ' : '') + formatted;
      }
      return part; // Return original if it's just spaces
    });
    
    // Join back and update input value
    let newValue = formattedParts.join(',');
    
    // Ensure only one space after each comma
    newValue = newValue.replace(/,\s*/g, ', ');
    
    // Remove any trailing space if the original didn't have one (to allow typing a comma)
    if (!originalValue.endsWith(' ') && newValue.endsWith(' ')) {
      newValue = newValue.trimEnd();
    }
    
    this.value = newValue;
    
    // Restore cursor position if it was at the end
    if (start >= originalValue.length) {
      this.setSelectionRange(newValue.length, newValue.length);
    } else {
      // If cursor was in the middle, try to keep it in the same relative position
      // This is simpler than calculating the exact shift
      this.setSelectionRange(start, start);
    }
  });

  // TIN field validation - only allow numbers and automatically format with hyphens
  document.getElementById('tin').addEventListener('input', function() {
    // Remove any characters that are not numbers
    let value = this.value.replace(/[^0-9]/g, '');
    
    // Limit to maximum 12 digits
    if (value.length > 12) {
      value = value.substring(0, 12);
    }
    
    // Auto-format with hyphens based on length
    if (value.length <= 3) {
      this.value = value;
    } else if (value.length <= 6) {
      this.value = value.substring(0, 3) + '-' + value.substring(3);
    } else if (value.length <= 9) {
      this.value = value.substring(0, 3) + '-' + value.substring(3, 6) + '-' + value.substring(6);
    } else {
      this.value = value.substring(0, 3) + '-' + value.substring(3, 6) + '-' + value.substring(6, 9) + '-' + value.substring(9);
    }
  });

  // Height field validation - only allow numbers and apostrophe
  document.getElementById('height').addEventListener('input', function() {
    // Remove any characters that are not numbers or apostrophe
    this.value = this.value.replace(/[^0-9']/g, '');
    
    // Limit to maximum 5 characters
    if (this.value.length > 5) {
      this.value = this.value.substring(0, 5);
    }
  });

  // Contact number field validation - only allow numbers and automatically format with hyphens
  document.getElementById('contact').addEventListener('input', function() {
    // Remove any characters that are not numbers
    let value = this.value.replace(/[^0-9]/g, '');
    
    // Limit to maximum 11 digits
    if (value.length > 11) {
      value = value.substring(0, 11);
    }
    
    // Auto-format with hyphens based on length (xxxx-xxx-xxxx pattern)
    if (value.length <= 4) {
      this.value = value;
    } else if (value.length <= 7) {
      this.value = value.substring(0, 4) + '-' + value.substring(4);
    } else {
      this.value = value.substring(0, 4) + '-' + value.substring(4, 7) + '-' + value.substring(7);
    }
  });

  // Prevent pasting of invalid characters
  function preventInvalidPaste(event) {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    const validPattern = /^[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]*$/;
    
    if (!validPattern.test(pastedText)) {
      event.preventDefault();
      // Show a brief warning
      const input = event.target;
      const originalBorder = input.style.borderColor;
      input.style.borderColor = '#ff9800';
      setTimeout(() => {
        input.style.borderColor = originalBorder;
      }, 1000);
    }
  }

  // Add paste event listeners
  document.getElementById('surname').addEventListener('paste', preventInvalidPaste);
  document.getElementById('firstname').addEventListener('paste', preventInvalidPaste);
  document.getElementById('middlename').addEventListener('paste', preventInvalidPaste);
  document.getElementById('religion').addEventListener('paste', preventInvalidPaste);
  document.querySelector('input[name="self_employed_specify"]').addEventListener('paste', preventInvalidPaste);
  document.querySelector('input[name="other_jobs"]').addEventListener('paste', preventInvalidPaste);
  document.getElementById('unemployed_months').addEventListener('paste', function(event) {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    const validPattern = /^[0-9]*$/;
    
    if (!validPattern.test(pastedText)) {
      event.preventDefault();
      const input = event.target;
      const originalBorder = input.style.borderColor;
      input.style.borderColor = '#ff9800';
      setTimeout(() => {
        input.style.borderColor = originalBorder;
      }, 1000);
    } else if (pastedText.length > 30) {
      // If valid but too long, truncate it
      event.preventDefault();
      const input = event.target;
      input.value = pastedText.substring(0, 30);
    }
  });
  document.getElementById('terminated_country').addEventListener('paste', preventInvalidPaste);
  document.getElementById('unemployed_other_specify').addEventListener('paste', preventInvalidPaste);
  // Job preference fields paste event listeners
  // occupation fields are select+search (TomSelect), no direct paste handler needed
  // local1/local2/local3 are hidden combined fields generated from dropdown selections.
  // overseas fields are select+search (TomSelect), no direct paste handler needed
  
  // Other language field now uses TomSelect dropdown with optional custom entries.
  
  // Year fields paste event listeners - only allow numbers
  document.querySelector('input[name="year_graduated"]').addEventListener('paste', function(event) {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    const validPattern = /^[0-9]*$/;
    
    if (!validPattern.test(pastedText)) {
      event.preventDefault();
      Swal.fire({
        title: 'Invalid Characters!',
        text: 'Only numbers (0-9) are allowed in year fields.',
        icon: 'warning',
        confirmButtonText: 'OK',
        confirmButtonColor: '#ff9800'
      });
    } else if (pastedText.length > 10) {
      event.preventDefault();
      const input = event.target;
      input.value = pastedText.substring(0, 10);
    }
  });

  document.getElementById('last_attended').addEventListener('paste', function(event) {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    const validPattern = /^[0-9]*$/;
    
    if (!validPattern.test(pastedText)) {
      event.preventDefault();
      Swal.fire({
        title: 'Invalid Characters!',
        text: 'Only numbers (0-9) are allowed in year fields.',
        icon: 'warning',
        confirmButtonText: 'OK',
        confirmButtonColor: '#ff9800'
      });
    } else if (pastedText.length > 10) {
      event.preventDefault();
      const input = event.target;
      input.value = pastedText.substring(0, 10);
    }
  });
  
  // Technical training fields paste event listeners
  // Text fields paste event listeners (letters, numbers, and special characters allowed)
  [
    'training_course_1','training_institution_1','training_skills_1','training_cert_1',
    'training_course_2','training_institution_2','training_skills_2','training_cert_2',
    'training_course_3','training_institution_3','training_skills_3','training_cert_3'
  ].forEach(function(fieldName) {
    const field = document.querySelector(`input[name="${fieldName}"]`);
    if (!field) return;
    field.addEventListener('paste', function(event) {
      const pastedText = (event.clipboardData || window.clipboardData).getData('text');
      const currentLen = (this.value || '').length;
      if (currentLen + pastedText.length > 40) {
        event.preventDefault();
        const allowed = Math.max(0, 40 - currentLen);
        this.value = (this.value || '') + pastedText.substring(0, allowed);
      }
    });
  });
  
  // Hours fields paste event listeners (numeric only)
  document.querySelector('input[name="training_hours_1"]').addEventListener('paste', function(event) {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    const validPattern = /^[0-9]*$/;
    
    if (!validPattern.test(pastedText)) {
      event.preventDefault();
      Swal.fire({
        title: 'Invalid Characters!',
        text: 'Only numbers (0-9) are allowed in hours fields.',
        icon: 'warning',
        confirmButtonText: 'OK',
        confirmButtonColor: '#ff9800'
      });
    } else if (pastedText.length > 10) {
      event.preventDefault();
      const input = event.target;
      input.value = pastedText.substring(0, 10);
    }
  });

  document.querySelector('input[name="training_hours_2"]').addEventListener('paste', function(event) {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    const validPattern = /^[0-9]*$/;
    
    if (!validPattern.test(pastedText)) {
      event.preventDefault();
      Swal.fire({
        title: 'Invalid Characters!',
        text: 'Only numbers (0-9) are allowed in hours fields.',
        icon: 'warning',
        confirmButtonText: 'OK',
        confirmButtonColor: '#ff9800'
      });
    } else if (pastedText.length > 10) {
      event.preventDefault();
      const input = event.target;
      input.value = pastedText.substring(0, 10);
    }
  });

  document.querySelector('input[name="training_hours_3"]').addEventListener('paste', function(event) {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    const validPattern = /^[0-9]*$/;
    
    if (!validPattern.test(pastedText)) {
      event.preventDefault();
      Swal.fire({
        title: 'Invalid Characters!',
        text: 'Only numbers (0-9) are allowed in hours fields.',
        icon: 'warning',
        confirmButtonText: 'OK',
        confirmButtonColor: '#ff9800'
      });
    } else if (pastedText.length > 10) {
      event.preventDefault();
      const input = event.target;
      input.value = pastedText.substring(0, 10);
    }
  });
  
  // Eligibility and PRC fields paste event listeners (allow special characters; max 40 chars)
  ['eligibility_1', 'eligibility_2', 'prc_1', 'prc_2'].forEach(function(fieldName) {
    const field = document.querySelector(`input[name="${fieldName}"]`);
    if (!field) return;
    field.addEventListener('paste', function(event) {
      const pastedText = (event.clipboardData || window.clipboardData).getData('text');
      const currentLen = (this.value || '').length;
      if (currentLen + pastedText.length > 40) {
        event.preventDefault();
        const allowed = Math.max(0, 40 - currentLen);
        this.value = (this.value || '') + pastedText.substring(0, allowed);
      }
    });
  });
  
  // Work experience fields paste event listeners
  // Text fields paste event listeners (alphabetic + numeric + parentheses)
  document.querySelector('input[name="company_name_1"]').addEventListener('paste', function(event) {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    if (pastedText.length > 50) {
      event.preventDefault();
      const input = event.target;
      input.value = pastedText.substring(0, 50);
    }
  });

  document.querySelector('input[name="company_address_1"]').addEventListener('paste', function(event) {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    const validPattern = /^[A-Za-z0-9()\s\-\.]*$/;
    
    if (!validPattern.test(pastedText)) {
      event.preventDefault();
      Swal.fire({
        title: 'Invalid Characters!',
        text: 'Only letters, numbers, parentheses (), spaces, hyphens, and periods are allowed.',
        icon: 'warning',
        confirmButtonText: 'OK',
        confirmButtonColor: '#ff9800'
      });
    } else if (pastedText.length > 50) {
      event.preventDefault();
      const input = event.target;
      input.value = pastedText.substring(0, 50);
    }
  });

  document.querySelector('input[name="position_1"]').addEventListener('paste', function(event) {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    const validPattern = /^[A-Za-z0-9()\s\-\.]*$/;
    
    if (!validPattern.test(pastedText)) {
      event.preventDefault();
      Swal.fire({
        title: 'Invalid Characters!',
        text: 'Only letters, numbers, parentheses (), spaces, hyphens, and periods are allowed.',
        icon: 'warning',
        confirmButtonText: 'OK',
        confirmButtonColor: '#ff9800'
      });
    } else if (pastedText.length > 50) {
      event.preventDefault();
      const input = event.target;
      input.value = pastedText.substring(0, 50);
    }
  });


  document.querySelector('input[name="company_name_2"]').addEventListener('paste', function(event) {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    if (pastedText.length > 50) {
      event.preventDefault();
      const input = event.target;
      input.value = pastedText.substring(0, 50);
    }
  });

  document.querySelector('input[name="company_address_2"]').addEventListener('paste', function(event) {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    const validPattern = /^[A-Za-z0-9()\s\-\.]*$/;
    
    if (!validPattern.test(pastedText)) {
      event.preventDefault();
      Swal.fire({
        title: 'Invalid Characters!',
        text: 'Only letters, numbers, parentheses (), spaces, hyphens, and periods are allowed.',
        icon: 'warning',
        confirmButtonText: 'OK',
        confirmButtonColor: '#ff9800'
      });
    } else if (pastedText.length > 50) {
      event.preventDefault();
      const input = event.target;
      input.value = pastedText.substring(0, 50);
    }
  });

  document.querySelector('input[name="position_2"]').addEventListener('paste', function(event) {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    const validPattern = /^[A-Za-z0-9()\s\-\.]*$/;
    
    if (!validPattern.test(pastedText)) {
      event.preventDefault();
      Swal.fire({
        title: 'Invalid Characters!',
        text: 'Only letters, numbers, parentheses (), spaces, hyphens, and periods are allowed.',
        icon: 'warning',
        confirmButtonText: 'OK',
        confirmButtonColor: '#ff9800'
      });
    } else if (pastedText.length > 50) {
      event.preventDefault();
      const input = event.target;
      input.value = pastedText.substring(0, 50);
    }
  });


  document.querySelector('input[name="company_name_3"]').addEventListener('paste', function(event) {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    if (pastedText.length > 50) {
      event.preventDefault();
      const input = event.target;
      input.value = pastedText.substring(0, 50);
    }
  });

  document.querySelector('input[name="company_address_3"]').addEventListener('paste', function(event) {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    const validPattern = /^[A-Za-z0-9()\s\-\.]*$/;
    
    if (!validPattern.test(pastedText)) {
      event.preventDefault();
      Swal.fire({
        title: 'Invalid Characters!',
        text: 'Only letters, numbers, parentheses (), spaces, hyphens, and periods are allowed.',
        icon: 'warning',
        confirmButtonText: 'OK',
        confirmButtonColor: '#ff9800'
      });
    } else if (pastedText.length > 50) {
      event.preventDefault();
      const input = event.target;
      input.value = pastedText.substring(0, 50);
    }
  });

  document.querySelector('input[name="position_3"]').addEventListener('paste', function(event) {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    const validPattern = /^[A-Za-z0-9()\s\-\.]*$/;
    
    if (!validPattern.test(pastedText)) {
      event.preventDefault();
      Swal.fire({
        title: 'Invalid Characters!',
        text: 'Only letters, numbers, parentheses (), spaces, hyphens, and periods are allowed.',
        icon: 'warning',
        confirmButtonText: 'OK',
        confirmButtonColor: '#ff9800'
      });
    } else if (pastedText.length > 50) {
      event.preventDefault();
      const input = event.target;
      input.value = pastedText.substring(0, 50);
    }
  });


  // Months fields paste event listeners (numeric only)
  document.querySelector('input[name="months_1"]').addEventListener('paste', function(event) {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    const validPattern = /^[0-9]*$/;
    
    if (!validPattern.test(pastedText)) {
      event.preventDefault();
      Swal.fire({
        title: 'Invalid Characters!',
        text: 'Only numbers (0-9) are allowed in months fields.',
        icon: 'warning',
        confirmButtonText: 'OK',
        confirmButtonColor: '#ff9800'
      });
    } else if (pastedText.length > 10) {
      event.preventDefault();
      const input = event.target;
      input.value = pastedText.substring(0, 10);
    }
  });

  document.querySelector('input[name="months_2"]').addEventListener('paste', function(event) {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    const validPattern = /^[0-9]*$/;
    
    if (!validPattern.test(pastedText)) {
      event.preventDefault();
      Swal.fire({
        title: 'Invalid Characters!',
        text: 'Only numbers (0-9) are allowed in months fields.',
        icon: 'warning',
        confirmButtonText: 'OK',
        confirmButtonColor: '#ff9800'
      });
    } else if (pastedText.length > 10) {
      event.preventDefault();
      const input = event.target;
      input.value = pastedText.substring(0, 10);
    }
  });

  document.querySelector('input[name="months_3"]').addEventListener('paste', function(event) {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    const validPattern = /^[0-9]*$/;
    
    if (!validPattern.test(pastedText)) {
      event.preventDefault();
      Swal.fire({
        title: 'Invalid Characters!',
        text: 'Only numbers (0-9) are allowed in months fields.',
        icon: 'warning',
        confirmButtonText: 'OK',
        confirmButtonColor: '#ff9800'
      });
    } else if (pastedText.length > 10) {
      event.preventDefault();
      const input = event.target;
      input.value = pastedText.substring(0, 10);
    }
  });
  
  // Skills others field paste event listener - only allow letters and commas
  document.querySelector('input[name="skill_others"]').addEventListener('paste', function(event) {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    const validPattern = /^[A-Za-zñÑáÁéÉíÍóÓúÚüÜ,\s]*$/;
    
    if (!validPattern.test(pastedText)) {
      event.preventDefault();
      Swal.fire({
        title: 'Invalid Characters!',
        text: 'Only letters (including Filipino alphabet) and commas (,) are allowed in the Others field.',
        icon: 'warning',
        confirmButtonText: 'OK',
        confirmButtonColor: '#ff9800'
      });
    } else {
      // Allow paste and then trigger input event for formatting
      setTimeout(() => {
        this.dispatchEvent(new Event('input'));
      }, 0);
    }
  });
  document.getElementById('tin').addEventListener('paste', function(event) {
    event.preventDefault();
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    
    // Extract only numbers from pasted text
    let numbersOnly = pastedText.replace(/[^0-9]/g, '');
    
    // Limit to maximum 12 digits
    if (numbersOnly.length > 12) {
      numbersOnly = numbersOnly.substring(0, 12);
    }
    
    // Auto-format with hyphens based on length
    if (numbersOnly.length <= 3) {
      this.value = numbersOnly;
    } else if (numbersOnly.length <= 6) {
      this.value = numbersOnly.substring(0, 3) + '-' + numbersOnly.substring(3);
    } else if (numbersOnly.length <= 9) {
      this.value = numbersOnly.substring(0, 3) + '-' + numbersOnly.substring(3, 6) + '-' + numbersOnly.substring(6);
    } else {
      this.value = numbersOnly.substring(0, 3) + '-' + numbersOnly.substring(3, 6) + '-' + numbersOnly.substring(6, 9) + '-' + numbersOnly.substring(9);
    }
    
    // Trigger input event to ensure formatting is applied
    this.dispatchEvent(new Event('input'));
  });
  document.getElementById('height').addEventListener('paste', function(event) {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    const validPattern = /^[0-9']*$/;
    
    if (!validPattern.test(pastedText)) {
      event.preventDefault();
      const input = event.target;
      const originalBorder = input.style.borderColor;
      input.style.borderColor = '#ff9800';
      setTimeout(() => {
        input.style.borderColor = originalBorder;
      }, 1000);
    } else if (pastedText.length > 5) {
      // If valid but too long, truncate it
      event.preventDefault();
      const input = event.target;
      input.value = pastedText.substring(0, 5);
    }
  });
  document.getElementById('contact').addEventListener('paste', function(event) {
    event.preventDefault();
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    
    // Extract only numbers from pasted text
    let numbersOnly = pastedText.replace(/[^0-9]/g, '');
    
    // Limit to maximum 11 digits
    if (numbersOnly.length > 11) {
      numbersOnly = numbersOnly.substring(0, 11);
    }
    
    // Auto-format with hyphens based on length (xxxx-xxx-xxxx pattern)
    if (numbersOnly.length <= 4) {
      this.value = numbersOnly;
    } else if (numbersOnly.length <= 7) {
      this.value = numbersOnly.substring(0, 4) + '-' + numbersOnly.substring(4);
    } else {
      this.value = numbersOnly.substring(0, 4) + '-' + numbersOnly.substring(4, 7) + '-' + numbersOnly.substring(7);
    }
    
    // Trigger input event to ensure formatting is applied
    this.dispatchEvent(new Event('input'));
  });
  document.getElementById('suffix').addEventListener('paste', function(event) {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    const validPattern = /^[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\.]*$/;
    
    if (!validPattern.test(pastedText)) {
      event.preventDefault();
      const input = event.target;
      const originalBorder = input.style.borderColor;
      input.style.borderColor = '#ff9800';
      setTimeout(() => {
        input.style.borderColor = originalBorder;
      }, 1000);
    }
  });

  // Language proficiency "Select All" functionality
  function toggleLanguageGroup(language, checked) {
    if (language === 'other') {
      const otherLanguage = document.querySelector('[name="other_language"]');
      if (!otherLanguage || !otherLanguage.value.trim()) {
        const otherSelectAll = document.getElementById('other_select_all');
        if (otherSelectAll) {
          otherSelectAll.checked = false;
          otherSelectAll.indeterminate = false;
        }
        return;
      }
    }
    const checkboxes = [
      document.querySelector(`input[name="${language}_read"]`),
      document.querySelector(`input[name="${language}_write"]`),
      document.querySelector(`input[name="${language}_speak"]`),
      document.querySelector(`input[name="${language}_understand"]`)
    ];
    
    checkboxes.forEach(checkbox => {
      if (checkbox) {
        checkbox.checked = checked;
      }
    });
  }
  
  // Update "Select All" checkbox state when individual checkboxes change
  function updateSelectAllCheckbox(language) {
    const selectAllCheckbox = document.getElementById(`${language}_select_all`);
    if (!selectAllCheckbox) return;
    if (language === 'other') {
      const otherLanguage = document.querySelector('[name="other_language"]');
      const hasOtherLanguage = !!(otherLanguage && otherLanguage.value && otherLanguage.value.trim());
      if (!hasOtherLanguage) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.disabled = true;
        return;
      }
      selectAllCheckbox.disabled = false;
    }
    
    const checkboxes = [
      document.querySelector(`input[name="${language}_read"]`),
      document.querySelector(`input[name="${language}_write"]`),
      document.querySelector(`input[name="${language}_speak"]`),
      document.querySelector(`input[name="${language}_understand"]`)
    ];
    
    const allChecked = checkboxes.every(checkbox => checkbox && checkbox.checked);
    const someChecked = checkboxes.some(checkbox => checkbox && checkbox.checked);
    
    selectAllCheckbox.checked = allChecked;
    selectAllCheckbox.indeterminate = someChecked && !allChecked;
  }
  
  // Add event listeners to individual language checkboxes
  ['english', 'filipino', 'mandarin', 'other'].forEach(language => {
    ['read', 'write', 'speak', 'understand'].forEach(proficiency => {
      const checkbox = document.querySelector(`input[name="${language}_${proficiency}"]`);
      if (checkbox) {
        checkbox.addEventListener('change', () => {
          updateSelectAllCheckbox(language);
        });
      }
    });
  });

  const otherLanguageField = document.querySelector('[name="other_language"]');
  if (otherLanguageField) {
    otherLanguageField.addEventListener('change', function() {
      updateOtherLanguageToggleState();
      updateSelectAllCheckbox('other');
    });
  }

  // Initial step
  showStep1();
  updateProgressIndicator(1);
  
  // Setup clickable step navigation
  setupStepNavigation();
  
  // Auto-load form data if needed
  if (typeof AUTO_LOAD_FORM !== 'undefined' && AUTO_LOAD_FORM) {
    console.log('Auto-loading form data...');
    // Wait a bit for DOM to be fully ready
    setTimeout(() => {
      console.log('Calling loadExistingNRSPForm...');
      loadExistingNRSPForm(true); // Pass true to indicate auto-load (no alert)
    }, 500);
  }
  
  // Update submit button text and state based on status
  const submitBtn = document.getElementById('submitNRSPBtn');
  if (submitBtn) {
    if (typeof IS_PENDING !== 'undefined' && IS_PENDING) {
      submitBtn.textContent = 'Save';
      submitBtn.title = 'Save your changes. The form will remain in pending status.';
    } else if (typeof IS_REJECTED !== 'undefined' && IS_REJECTED) {
      submitBtn.textContent = 'Re-submit';
      if (typeof COOLDOWN_REMAINING !== 'undefined' && COOLDOWN_REMAINING !== null && COOLDOWN_REMAINING > 0) {
        submitBtn.disabled = true;
        submitBtn.title = 'You cannot resubmit your form until the cooldown period expires.';
      } else {
        submitBtn.title = 'Resubmit your form. Status will change to Pending.';
      }
    } else {
      submitBtn.textContent = 'Submit';
      submitBtn.title = 'Submit your NRSP form.';
    }
    
    // Disable if not allowed
    if (typeof CAN_SUBMIT_NRSP !== 'undefined' && !CAN_SUBMIT_NRSP) {
      submitBtn.disabled = true;
      if (typeof IS_REJECTED !== 'undefined' && IS_REJECTED) {
        submitBtn.title = 'You cannot resubmit your form until the cooldown period expires.';
      }
    }
  }
  
  // Duplicate entry validation function
  async function checkDuplicateEntry() {
    const surname = document.getElementById('surname').value.trim();
    const firstname = document.getElementById('firstname').value.trim();
    const middlename = document.getElementById('middlename').value.trim();
    const suffix = document.getElementById('suffix').value.trim();
    
    // Normalize data for case-insensitive comparison
    // Treat empty strings, "n/a", and null as empty for proper comparison
    const normalizedSurname = surname.toLowerCase().trim();
    const normalizedFirstname = firstname.toLowerCase().trim();
    const normalizedMiddlename = (middlename.toLowerCase().trim() === 'n/a' || middlename.toLowerCase().trim() === 'null' || middlename.toLowerCase().trim() === '') ? '' : middlename.toLowerCase().trim();
    const normalizedSuffix = (suffix.toLowerCase().trim() === 'n/a' || suffix.toLowerCase().trim() === 'null' || suffix.toLowerCase().trim() === '') ? '' : suffix.toLowerCase().trim();
    
    try {
      const response = await fetch('check_duplicate.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `surname=${encodeURIComponent(normalizedSurname)}&firstname=${encodeURIComponent(normalizedFirstname)}&middlename=${encodeURIComponent(normalizedMiddlename)}&suffix=${encodeURIComponent(normalizedSuffix)}`
      });
      
      const result = await response.json();
      
        if (result.duplicate) {
          Swal.fire({
            title: 'Duplicate Entry Detected!',
            html: `A record with the same name combination already exists:<br><br>
                   <strong>Name:</strong> ${result.existingName}<br><br>
                   Please refrain from submitting duplicate forms.`,
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#ff9800',
            allowOutsideClick: false
          });
          return false;
        }
      return true;
    } catch (error) {
      console.error('Error checking duplicate:', error);
      // If there's an error checking duplicates, allow submission to proceed
      return true;
    }
  }

  // Validation and AJAX submit
  document.getElementById('jobseekerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    // Remove required attributes from hidden fields to prevent validation errors
    const allSections = document.querySelectorAll('.form-section');
    allSections.forEach(section => {
      if (section.style.display === 'none' || section.offsetParent === null) {
        const requiredFields = section.querySelectorAll('[required]');
        requiredFields.forEach(field => {
          field.removeAttribute('required');
          // Also remove pattern to prevent regex errors
          if (field.hasAttribute('pattern')) {
            const pattern = field.getAttribute('pattern');
            // Fix invalid patterns by removing problematic parts
            if (pattern && pattern.includes('{0,')) {
              field.removeAttribute('pattern');
            }
          }
        });
      }
    });
    
    // Remove pattern from all hidden inputs to prevent regex validation errors
    const allHiddenInputs = document.querySelectorAll('input[style*="display: none"], input[style*="display:none"], .form-section[style*="display: none"] input, .form-section[style*="display:none"] input');
    allHiddenInputs.forEach(input => {
      input.removeAttribute('required');
      input.removeAttribute('pattern');
    });
    
    // Check for duplicate entry ONLY for NEW submissions, NOT for updates
    // Skip duplicate check if this is an update (pending or rejected form being edited)
    const isUpdate = (typeof IS_PENDING !== 'undefined' && IS_PENDING) || 
                     (typeof IS_REJECTED !== 'undefined' && IS_REJECTED && typeof COOLDOWN_REMAINING !== 'undefined' && (COOLDOWN_REMAINING === null || COOLDOWN_REMAINING === 0));
    
    if (!isUpdate) {
      // Only check for duplicates when creating a NEW form
      const isNotDuplicate = await checkDuplicateEntry();
      if (!isNotDuplicate) {
        return; // Stop submission if duplicate is found
      }
    }
    
    // Check for e-signature validation - only if no existing file
    const esignatureInput = document.getElementById('esignature');
    const esignaturePreview = document.getElementById('esignaturePreview');
    const esignatureImage = document.getElementById('esignatureImage');
    const existingEsignatureInput = document.querySelector('input[name="existing_esignature_file"]');
    
    // Check for new file upload
    const hasNewFile = esignatureInput && esignatureInput.files && esignatureInput.files.length > 0;
    
    // Check for existing file displayed in preview
    const hasExistingPreview = esignaturePreview && 
                               esignaturePreview.style.display !== 'none' && 
                               esignaturePreview.offsetParent !== null;
    
    // Check for hidden input with existing filename
    const hasHiddenInput = existingEsignatureInput && existingEsignatureInput.value && existingEsignatureInput.value.trim() !== '';
    
    // Check if preview has an image source (existing file loaded)
    const hasImageSource = esignatureImage && esignatureImage.src && esignatureImage.src !== window.location.href;
    
    // If none of these conditions are true, we need an e-signature
    if (!hasNewFile && !hasExistingPreview && !hasHiddenInput && !hasImageSource) {
      Swal.fire({
        title: 'E-Signature Required!',
        text: 'Please upload your e-signature before submitting the form.',
        icon: 'warning',
        confirmButtonText: 'OK',
        confirmButtonColor: '#ff9800'
      });
      return;
    }
    
    // Check for resume validation - only if no existing file
    const resumeInput = document.getElementById('resume_file');
    const resumeContainer = resumeInput ? resumeInput.closest('.form-row') : null;
    const existingResumeInput = document.querySelector('input[name="existing_resume_file"]');
    
    // Check for new file upload
    const hasNewResumeFile = resumeInput && resumeInput.files && resumeInput.files.length > 0;
    
    // Check for existing resume display
    const hasExistingResumeDisplay = resumeContainer && resumeContainer.querySelector('.existing-resume-display');
    
    // Check for hidden input with existing filename
    const hasResumeHiddenInput = existingResumeInput && existingResumeInput.value && existingResumeInput.value.trim() !== '';
    
    // If none of these conditions are true, we need a resume
    if (!hasNewResumeFile && !hasExistingResumeDisplay && !hasResumeHiddenInput) {
      Swal.fire({
        title: 'Resume Required!',
        text: 'Please upload your resume before submitting the form.',
        icon: 'warning',
        confirmButtonText: 'OK',
        confirmButtonColor: '#ff9800'
      });
      return;
    }
    
    // Define variables to check if existing files are present (used in validation loop)
    const hasExistingEsignature = hasNewFile || hasExistingPreview || hasHiddenInput || hasImageSource;
    const hasExistingResume = hasNewResumeFile || hasExistingResumeDisplay || hasResumeHiddenInput;
    
    // Only validate required fields in visible sections
    let valid = true;
    const visibleSections = document.querySelectorAll('.form-section[style*="block"], .form-section:not([style*="none"])');
    visibleSections.forEach(section => {
      if (section.offsetParent !== null) {
        const requiredFields = section.querySelectorAll('[required]');
        requiredFields.forEach(field => {
          // Skip file inputs if they have existing files
          if (field.type === 'file') {
            if (field.id === 'esignature' && hasExistingEsignature) return;
            if (field.id === 'resume_file' && hasExistingResume) return;
          }
          
          if (field.type === 'checkbox' || field.type === 'radio') {
            const checked = section.querySelector(`input[name="${field.name}"]:checked`);
            if (!checked) {
              valid = false;
            }
          } else if (!field.value || !field.value.trim()) {
            field.style.borderColor = 'red';
            valid = false;
          } else {
            field.style.borderColor = '';
          }
        });
      }
    });
    
    if (!valid) {
      // Show SweetAlert validation error
      Swal.fire({
        title: 'Validation Error!',
        text: 'Please fill out all required fields before submitting.',
        icon: 'warning',
        confirmButtonText: 'OK',
        confirmButtonColor: '#ff9800'
      });
      return;
    }
     // AJAX submit
     const form = this;
     const formData = new FormData(form);
     
     // Explicitly add hidden inputs for existing files if they exist (reuse variables declared above)
     if (existingEsignatureInput && existingEsignatureInput.value) {
       formData.append('existing_esignature_file', existingEsignatureInput.value);
     }
     
     if (existingResumeInput && existingResumeInput.value) {
       formData.append('existing_resume_file', existingResumeInput.value);
     }
     
     // Remove required from all hidden fields one more time before submission
     const allHiddenFields = form.querySelectorAll('input[type="text"][style*="display: none"], input[type="text"][style*="display:none"], .form-section[style*="display: none"] input, .form-section[style*="display:none"] input');
     allHiddenFields.forEach(field => {
       field.removeAttribute('required');
     });
     
     // Show loading state
     const submitBtn = form.querySelector('button[type="submit"]');
     const originalText = submitBtn.textContent;
     submitBtn.textContent = 'Submitting...';
     submitBtn.disabled = true;
     
     fetch(form.action, {
       method: 'POST',
       body: formData
     })
     .then(async response => {
       console.log('Response status:', response.status);
       const contentType = response.headers.get('content-type');
       if (contentType && contentType.includes('application/json')) {
         return response.json();
       } else {
         const text = await response.text();
         console.error('Non-JSON response:', text);
         throw new Error('Server returned non-JSON response');
       }
     })
     .then(data => {
       console.log('Response data:', data);
       
       if (data.success) {
         // Show SweetAlert success message
         Swal.fire({
           title: 'Success!',
           text: data.message,
           icon: 'success',
           confirmButtonText: 'OK',
           confirmButtonColor: '#4caf50',
           allowOutsideClick: false,
           allowEscapeKey: false
        }).then(() => {
          // Notify parent dashboard to reload so NRSP status / recommended jobs reflect the submission
           if (window.parent && window.parent !== window) {
             try {
               window.parent.postMessage({ type: 'nrsp_submitted' }, '*');
             } catch (err) {}
           }
           // Check if this was an update (existing form) or new submission
           const isUpdate = typeof IS_PENDING !== 'undefined' && IS_PENDING;
           if (isUpdate) {
             window.location.reload();
           } else {
             form.reset();
             showStep1();
           }
         });
       } else {
         // Check if it's a duplicate error
         if (data.duplicate_info) {
           // Show SweetAlert duplicate error with detailed info
           Swal.fire({
             title: 'Duplicate Entry Detected!',
             html: `A record with the same name combination already exists:<br><br>
                    <strong>Name:</strong> ${data.duplicate_info.existing_name}<br><br>
                    Please verify your information or contact support if this is an error.`,
             icon: 'warning',
             confirmButtonText: 'OK',
             confirmButtonColor: '#ff9800',
             allowOutsideClick: false
           });
         } else {
           // Show SweetAlert error message
           Swal.fire({
             title: 'Error!',
             text: data.message,
             icon: 'error',
             confirmButtonText: 'OK',
             confirmButtonColor: '#f44336'
           });
         }
       }
     })
     .catch(error => {
       console.error('Submission error:', error);
       // Show SweetAlert for network errors
       Swal.fire({
         title: 'Network Error!',
         text: 'Please check your connection and try again.',
         icon: 'error',
         confirmButtonText: 'OK',
         confirmButtonColor: '#f44336'
       });
     })
     .finally(() => {
       // Reset button state
       submitBtn.textContent = originalText;
       submitBtn.disabled = false;
     });
  });
 // Show/hide OFW country textbox
  function toggleOfwCountry() {
    const ofwYes = document.getElementById('ofwYes');
    const ofwCountryGroup = document.getElementById('ofwCountryGroup');
    const ofwCountry = document.getElementById('ofw_country');
    if (ofwYes && ofwYes.checked) {
      ofwCountryGroup.style.display = '';
      ofwCountry.disabled = false;
      if (ofwCountry.tomselect) {
        ofwCountry.tomselect.enable();
      }
    } else {
      ofwCountryGroup.style.display = 'none';
      ofwCountry.disabled = true;
      if (ofwCountry.tomselect) {
        ofwCountry.tomselect.disable();
        ofwCountry.tomselect.clear(true);
        ofwCountry.tomselect.setTextboxValue('');
      } else {
        ofwCountry.value = '';
      }
    }
  }

  // Show/hide returnee fields
  function toggleReturneeFields() {
    const returneeYes = document.getElementById('returneeYes');
    const returneeFields = document.getElementById('returneeFields');
    const returneeReturnFields = document.getElementById('returneeReturnFields');
    const deploymentCountry = document.getElementById('deployment_country');
    const returnMonth = document.getElementById('return_month');
    const returnYear = document.getElementById('return_year');
    
    if (returneeYes && returneeYes.checked) {
      returneeFields.style.display = '';
      returneeReturnFields.style.display = '';
      deploymentCountry.disabled = false;
      if (deploymentCountry.tomselect) {
        deploymentCountry.tomselect.enable();
      }
      returnMonth.disabled = false;
      returnYear.disabled = false;
    } else {
      returneeFields.style.display = 'none';
      returneeReturnFields.style.display = 'none';
      deploymentCountry.disabled = true;
      if (deploymentCountry.tomselect) {
        deploymentCountry.tomselect.disable();
      }
      if (deploymentCountry.tomselect) {
        deploymentCountry.tomselect.clear(true);
        deploymentCountry.tomselect.setTextboxValue('');
      } else {
        deploymentCountry.value = '';
      }
      returnMonth.disabled = true;
      returnMonth.value = '';
      returnYear.disabled = true;
      returnYear.value = '';
    }
  }

  // Show/hide job beneficiary household ID textbox   
  function toggleHouseholdId() {
    const beneficiaryYes = document.getElementById('beneficiaryYes');
    const householdIdGroup = document.getElementById('householdIdGroup');
    const householdId = document.getElementById('household_id');
    if (beneficiaryYes && beneficiaryYes.checked) {
      householdIdGroup.style.display = '';
      householdId.disabled = false;
    } else {
      householdIdGroup.style.display = 'none';
      householdId.disabled = true;
      householdId.value = '';
    }
  }

  // E-Signature Upload Functionality with AI-like Verification
  document.getElementById('esignature').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('esignaturePreview');
    const image = document.getElementById('esignatureImage');
    const filename = document.getElementById('esignatureFilename');
    const input = this;
    
    if (file) {
      // Validate file type
      const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'];
      if (!allowedTypes.includes(file.type)) {
        Swal.fire({
          icon: 'error',
          title: 'Invalid File Type',
          text: 'Please upload a valid image file (JPG, PNG, GIF, BMP, WEBP).'
        });
        this.value = '';
        return;
      }
      
      // Validate file size (2MB)
      if (file.size > 2 * 1024 * 1024) {
        Swal.fire({
          icon: 'error',
          title: 'File Too Large',
          text: 'Please upload a file smaller than 2MB.'
        });
        this.value = '';
        return;
      }
      
      // AI-like Verification using Canvas Analysis
      const reader = new FileReader();
      reader.onload = function(event) {
        const img = new Image();
        img.onload = function() {
          // Create a temporary canvas for analysis
          const canvas = document.createElement('canvas');
          const ctx = canvas.getContext('2d');
          
          // Downscale for faster processing
          const MAX_WIDTH = 300;
          const scale = Math.min(1, MAX_WIDTH / img.width);
          canvas.width = img.width * scale;
          canvas.height = img.height * scale;
          
          ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
          
          const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
          const data = imageData.data;
          
          let darkPixels = 0;
          let lightPixels = 0;
          let colorfulPixels = 0;
          let edgeDarkPixels = 0;
          const totalPixels = data.length / 4;
          const w = canvas.width;
          const h = canvas.height;
          const edgeMargin = Math.max(8, Math.floor(Math.min(w, h) * 0.04));
          let minX = w, minY = h, maxX = -1, maxY = -1;
          
          // Analyze pixel intensities
          for (let i = 0, p = 0; i < data.length; i += 4, p++) {
            const r = data[i];
            const g = data[i + 1];
            const b = data[i + 2];
            const x = p % w;
            const y = Math.floor(p / w);
            
            // Grayscale intensity (0-255)
            const intensity = (r + g + b) / 3;
            const chroma = Math.max(r, g, b) - Math.min(r, g, b);
            
            if (chroma > 40 && intensity > 40 && intensity < 230) {
              colorfulPixels++;
            }

            if (intensity < 140) { // Dark pixel (possible ink)
              darkPixels++;
              if (x < minX) minX = x;
              if (x > maxX) maxX = x;
              if (y < minY) minY = y;
              if (y > maxY) maxY = y;
              if (x < edgeMargin || x >= w - edgeMargin || y < edgeMargin || y >= h - edgeMargin) {
                edgeDarkPixels++;
              }
            } else if (intensity > 160) { // Light pixel (Paper/Background - lowered for greyish photos)
              lightPixels++;
            }
          }
          
          const inkRatio = (darkPixels / totalPixels) * 100;
          const backgroundRatio = (lightPixels / totalPixels) * 100;
          const colorfulRatio = (colorfulPixels / totalPixels) * 100;
          const bboxWidth = maxX >= minX ? (maxX - minX + 1) : 0;
          const bboxHeight = maxY >= minY ? (maxY - minY + 1) : 0;
          const bboxWidthRatio = bboxWidth > 0 ? (bboxWidth / w) * 100 : 0;
          const bboxHeightRatio = bboxHeight > 0 ? (bboxHeight / h) * 100 : 0;
          const edgeInkRatio = darkPixels > 0 ? (edgeDarkPixels / darkPixels) * 100 : 0;
          
          console.log(`Signature Analysis: Ink=${inkRatio.toFixed(2)}%, BG=${backgroundRatio.toFixed(2)}%, Color=${colorfulRatio.toFixed(2)}%, BBoxW=${bboxWidthRatio.toFixed(2)}%, BBoxH=${bboxHeightRatio.toFixed(2)}%, EdgeInk=${edgeInkRatio.toFixed(2)}%`);
          
          // Balanced heuristics:
          // - allow camera photos and different signature styles
          // - strongly reject obvious non-signatures (posters/selfies/very dense scenes)
          let isVerified = true;
          let errorMessage = '';

          // Hard fails for clearly invalid uploads.
          if (inkRatio > 55 || colorfulRatio > 65 || backgroundRatio < 18) {
            isVerified = false;
            errorMessage = 'This file looks like a regular photo/poster, not a signature on paper. Please upload a clearer signature image.';
          } else {
            // Soft scoring: tolerate variation, fail only when multiple red flags combine.
            let riskScore = 0;

            if (inkRatio > 42) riskScore += 2;
            else if (inkRatio > 34) riskScore += 1;

            if (inkRatio < 0.06) riskScore += 2;
            else if (inkRatio < 0.12) riskScore += 1;

            if (backgroundRatio < 24) riskScore += 2;
            else if (backgroundRatio < 30) riskScore += 1;

            if (colorfulRatio > 50) riskScore += 2;
            else if (colorfulRatio > 38) riskScore += 1;

            if (edgeInkRatio > 60) riskScore += 2;
            else if (edgeInkRatio > 48) riskScore += 1;

            if (bboxHeightRatio > 85) riskScore += 2;
            else if (bboxHeightRatio > 75) riskScore += 1;

            if (bboxWidthRatio < 6 || bboxHeightRatio < 3) riskScore += 2; // too tiny/empty crop

            if (riskScore >= 4) {
              isVerified = false;
              errorMessage = 'The uploaded image does not look like a clean signature sample. Please use a plain background and make the signature clearly visible.';
            }
          }
          
          if (!isVerified) {
            Swal.fire({
              icon: 'warning',
              title: 'Signature Verification Failed',
              text: errorMessage,
              showCancelButton: true,
              confirmButtonText: 'Try Anyway',
              cancelButtonText: 'Reselect Image',
              confirmButtonColor: '#ff9800',
              cancelButtonColor: '#d33'
            }).then((result) => {
              if (result.isConfirmed) {
                // User wants to proceed anyway
                image.src = event.target.result;
                filename.textContent = file.name;
                preview.style.display = 'flex';
              } else {
                // User wants to reselect
                input.value = '';
                preview.style.display = 'none';
              }
            });
          } else {
            // Success - proceed with preview
            image.src = event.target.result;
            filename.textContent = file.name;
            preview.style.display = 'flex';
          }
        };
        img.src = event.target.result;
      };
      reader.readAsDataURL(file);
    } else {
      preview.style.display = 'none';
    }
  });

  // Remove e-signature
  document.getElementById('esignatureRemove').addEventListener('click', function() {
    document.getElementById('esignature').value = '';
    document.getElementById('esignaturePreview').style.display = 'none';
  });

  // Toggle Course/Strand field based on Level selection
  function toggleCourseField() {
    const levelSelect = document.getElementById('levelSelect');
    const courseField = document.getElementById('courseField');
    const courseInput = courseField.querySelector('[name="course"]');
    
    const showCourseOptions = [
      'Secondary (K-12)',
      'Tertiary', 
      'Graduate Studies / Post-graduate'
    ];
    
    if (showCourseOptions.includes(levelSelect.value)) {
      courseField.style.display = '';
      courseInput.disabled = false;
      if (typeof courseInput.refreshCourseOptionsByLevel === 'function') {
        courseInput.refreshCourseOptionsByLevel();
      }
    } else {
      courseField.style.display = 'none';
      courseInput.disabled = true;
      if (courseInput.tomselect) {
        courseInput.tomselect.clear(true);
        courseInput.tomselect.setTextboxValue('');
      } else {
        courseInput.value = '';
      }
    }
    syncEducationFieldsLocking();
  }

  function syncEducationFieldsLocking() {
    const levelSelect = document.getElementById('levelSelect');
    const yearGraduated = document.querySelector('input[name="year_graduated"]');
    const levelReached = document.getElementById('level_reached');
    const lastAttended = document.getElementById('last_attended');
    if (!levelSelect || !yearGraduated || !levelReached || !lastAttended) return;

    const hasPrimaryPath = !!(levelSelect.value && levelSelect.value.trim());
    const hasUndergradPath = !!(levelReached.value && levelReached.value.trim());

    if (hasPrimaryPath) {
      levelReached.value = '';
      lastAttended.value = '';
      levelReached.disabled = true;
      lastAttended.disabled = true;
      levelSelect.disabled = false;
      yearGraduated.disabled = false;
      return;
    }

    if (hasUndergradPath) {
      levelSelect.value = '';
      yearGraduated.value = '';
      levelSelect.disabled = true;
      yearGraduated.disabled = true;
      levelReached.disabled = false;
      lastAttended.disabled = false;
      const courseField = document.getElementById('courseField');
      const courseInput = document.querySelector('[name="course"]');
      if (courseField) courseField.style.display = 'none';
      if (courseInput) {
        courseInput.disabled = true;
        if (courseInput.tomselect) {
          courseInput.tomselect.clear(true);
          courseInput.tomselect.setTextboxValue('');
        } else {
          courseInput.value = '';
        }
      }
      return;
    }

    levelSelect.disabled = false;
    yearGraduated.disabled = false;
    levelReached.disabled = false;
    lastAttended.disabled = false;
  }
</script>

<!-- Session Security Script -->
<script>
  // Validate session parameters on page load
  document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const sessionId = urlParams.get('session_id');
    const userId = urlParams.get('user_id');
    const token = urlParams.get('token');
    
    // If missing required parameters, redirect to dashboard
    if (!sessionId || !userId || !token) {
      alert('Invalid access. Redirecting to dashboard...');
      window.location.href = 'dashboard.php';
      return;
    }
    
    // Show session info in console for debugging
    console.log('Secure session loaded:', {
      sessionId: sessionId,
      userId: userId,
      token: token.substring(0, 10) + '...'
    });

    initAddressDropdowns();
    initOccupationDropdowns();
    initLocalWorkLocationDropdowns();
    initOverseasDropdowns();
    initOfwCountryDropdown();
    initDeploymentCountryDropdown();
    populateReturnYearOptions();
    initOtherLanguageDropdown();
    initCourseDropdown();
    applyMobileFriendlyFieldLabels();
    setNameIntegrityLock(false);
    window.addEventListener('resize', applyMobileFriendlyFieldLabels);
    const levelSelectEl = document.getElementById('levelSelect');
    const levelReachedEl = document.getElementById('level_reached');
    if (levelSelectEl) {
      levelSelectEl.addEventListener('change', function() {
        toggleCourseField();
        syncEducationFieldsLocking();
      });
    }
    if (levelReachedEl) {
      levelReachedEl.addEventListener('change', syncEducationFieldsLocking);
    }
    syncEducationFieldsLocking();
    
  });
  
  // Prevent direct access by checking referrer
  window.addEventListener('beforeunload', function() {
    // This helps ensure the form is accessed through dashboard
    if (!document.referrer.includes('dashboard.php')) {
      console.warn('Form accessed without proper dashboard referrer');
    }
  });
  
  // Comprehensive function to populate all form fields
  function populateFormFields(nrsp) {
    const asDisplayText = (value, fallback = '') => {
      const normalized = (value === null || value === undefined) ? '' : String(value).trim();
      if (!normalized || normalized.toLowerCase() === 'null') return fallback;
      return normalized;
    };

    // Personal Information
    const fields = {
      'surname': nrsp.surname,
      'firstname': nrsp.firstname,
      'middlename': nrsp.middlename || '',
      'suffix': nrsp.suffix || '',
      'dob': nrsp.dob,
      'sex': nrsp.sex,
      'religion': nrsp.religion || '',
      'civilstatus': nrsp.civilstatus,
      'street': nrsp.street || '',
      'barangay': nrsp.barangay,
      'municipality': nrsp.municipality,
      'province': nrsp.province,
      'tin': nrsp.tin || '',
      'height': nrsp.height || '',
      'contact': nrsp.contact,
      'email': nrsp.email,
      'disability_other': nrsp.disability_other || '',
      'self_employed_specify': nrsp.self_employed_specify || '',
      'other_jobs': nrsp.other_jobs || '',
      'unemployed_months': nrsp.unemployed_months || '',
      'terminated_country': nrsp.terminated_country || '',
      'unemployed_other_specify': nrsp.unemployed_other_specify || '',
      'ofw_country': nrsp.ofw_country || '',
      'returnee': nrsp.returnee,
      'deployment_country': nrsp.deployment_country || '',
      'return_month': nrsp.return_month || '',
      'return_year': nrsp.return_year || '',
      'abroad': nrsp.abroad,
      'beneficiary': nrsp.beneficiary,
      'household_id': nrsp.household_id || '',
      'occupation1': nrsp.occupation1,
      'occupation2': nrsp.occupation2 || '',
      'occupation3': nrsp.occupation3 || '',
      'local1': nrsp.local1,
      'local2': nrsp.local2 || '',
      'local3': nrsp.local3 || '',
      'overseas1': nrsp.overseas1,
      'overseas2': nrsp.overseas2 || '',
      'overseas3': nrsp.overseas3 || '',
      'other_language': nrsp.other_language || '',
      'inschool': nrsp.inschool,
      'level': nrsp.level,
      'course': nrsp.course || '',
      'year_graduated': nrsp.year_graduated || '',
      'level_reached': nrsp.level_reached || '',
      'last_attended': nrsp.last_attended || '',
      'training_course_1': nrsp.training_course_1 || '',
      'training_hours_1': nrsp.training_hours_1 || '',
      'training_institution_1': nrsp.training_institution_1 || '',
      'training_skills_1': nrsp.training_skills_1 || '',
      'training_cert_1': nrsp.training_cert_1 || '',
      'training_course_2': nrsp.training_course_2 || '',
      'training_hours_2': nrsp.training_hours_2 || '',
      'training_institution_2': nrsp.training_institution_2 || '',
      'training_skills_2': nrsp.training_skills_2 || '',
      'training_cert_2': nrsp.training_cert_2 || '',
      'training_course_3': nrsp.training_course_3 || '',
      'training_hours_3': nrsp.training_hours_3 || '',
      'training_institution_3': nrsp.training_institution_3 || '',
      'training_skills_3': nrsp.training_skills_3 || '',
      'training_cert_3': nrsp.training_cert_3 || '',
      'eligibility_1': nrsp.eligibility_1 || '',
      'eligibility_date_1': nrsp.eligibility_date_1 || '',
      'eligibility_2': nrsp.eligibility_2 || '',
      'eligibility_date_2': nrsp.eligibility_date_2 || '',
      'prc_1': nrsp.prc_1 || '',
      'prc_valid_1': nrsp.prc_valid_1 || '',
      'prc_2': nrsp.prc_2 || '',
      'prc_valid_2': nrsp.prc_valid_2 || '',
      'company_name_1': asDisplayText(nrsp.company_name_1, 'n/a'),
      'company_address_1': asDisplayText(nrsp.company_address_1, 'n/a'),
      'position_1': asDisplayText(nrsp.position_1, 'n/a'),
      'months_1': asDisplayText(nrsp.months_1, 'n/a'),
      'status_1': asDisplayText(nrsp.status_1, 'n/a'),
      'company_name_2': asDisplayText(nrsp.company_name_2, 'n/a'),
      'company_address_2': asDisplayText(nrsp.company_address_2, 'n/a'),
      'position_2': asDisplayText(nrsp.position_2, 'n/a'),
      'months_2': asDisplayText(nrsp.months_2, 'n/a'),
      'status_2': asDisplayText(nrsp.status_2, 'n/a'),
      'company_name_3': asDisplayText(nrsp.company_name_3, 'n/a'),
      'company_address_3': asDisplayText(nrsp.company_address_3, 'n/a'),
      'position_3': asDisplayText(nrsp.position_3, 'n/a'),
      'months_3': asDisplayText(nrsp.months_3, 'n/a'),
      'status_3': asDisplayText(nrsp.status_3, 'n/a'),
      'skill_others': nrsp.skill_others || ''
    };
    
    // Populate text/select inputs
    Object.keys(fields).forEach(fieldName => {
      const field = document.querySelector(`input[name="${fieldName}"], select[name="${fieldName}"]`);
      if (field && fields[fieldName] !== null && fields[fieldName] !== undefined) {
        if (field.type === 'checkbox' || field.type === 'radio') {
          // Handle separately
        } else {
          field.value = fields[fieldName];
        }
      }
    });

    ['occupation1', 'occupation2', 'occupation3'].forEach((fieldName) => {
      const selectEl = document.getElementById(fieldName);
      const rawValue = fields[fieldName] || '';
      const formatted = formatOccupationValue(rawValue);
      if (!selectEl || !formatted) return;
      if (selectEl.tomselect) {
        selectEl.tomselect.addOption({ value: formatted, text: formatted });
        selectEl.tomselect.setValue(formatted, true);
      } else {
        selectEl.value = formatted;
      }
    });

    ['overseas1', 'overseas2', 'overseas3'].forEach((fieldName) => {
      const selectEl = document.getElementById(fieldName);
      const rawValue = asDisplayText(fields[fieldName], '');
      if (!selectEl || !rawValue) return;
      if (selectEl.tomselect) {
        selectEl.tomselect.addOption({ value: rawValue, text: rawValue });
        selectEl.tomselect.setValue(rawValue, true);
      } else {
        ensureSelectValue(selectEl, rawValue);
      }
    });
    const deploymentCountryEl = document.getElementById('deployment_country');
    const savedDeploymentCountry = asDisplayText(fields.deployment_country, '');
    if (deploymentCountryEl && savedDeploymentCountry) {
      if (deploymentCountryEl.tomselect) {
        deploymentCountryEl.tomselect.addOption({ value: savedDeploymentCountry, text: savedDeploymentCountry });
        deploymentCountryEl.tomselect.setValue(savedDeploymentCountry, true);
      } else {
        ensureSelectValue(deploymentCountryEl, savedDeploymentCountry);
      }
    }
    const ofwCountryEl = document.getElementById('ofw_country');
    const savedOfwCountry = asDisplayText(fields.ofw_country, '');
    if (ofwCountryEl && savedOfwCountry) {
      if (ofwCountryEl.tomselect) {
        ofwCountryEl.tomselect.addOption({ value: savedOfwCountry, text: savedOfwCountry });
        ofwCountryEl.tomselect.setValue(savedOfwCountry, true);
      } else {
        ensureSelectValue(ofwCountryEl, savedOfwCountry);
      }
    }

    const courseSelectEl = document.getElementById('course');
    const savedCourse = asDisplayText(fields.course, '');
    if (courseSelectEl && courseSelectEl.tomselect) {
      if (typeof courseSelectEl.refreshCourseOptionsByLevel === 'function') {
        courseSelectEl.refreshCourseOptionsByLevel();
      }
      if (savedCourse) {
        courseSelectEl.tomselect.addOption({ value: savedCourse, text: savedCourse });
        courseSelectEl.tomselect.setValue(savedCourse, true);
      } else {
        courseSelectEl.tomselect.clear(true);
      }
    } else if (courseSelectEl && savedCourse) {
      ensureSelectValue(courseSelectEl, savedCourse);
    }

    const otherLanguageSelect = document.getElementById('other_language');
    const formattedOtherLanguage = formatOccupationValue(fields.other_language || '');
    if (otherLanguageSelect && formattedOtherLanguage) {
      if (otherLanguageSelect.tomselect) {
        otherLanguageSelect.tomselect.addOption({ value: formattedOtherLanguage, text: formattedOtherLanguage });
        otherLanguageSelect.tomselect.setValue(formattedOtherLanguage, true);
      } else {
        ensureSelectValue(otherLanguageSelect, formattedOtherLanguage);
      }
    }
    updateOtherLanguageToggleState();

    applySavedAddressValues(nrsp.province, nrsp.municipality, nrsp.barangay);
    applySavedLocalValues(nrsp.local1, nrsp.local2, nrsp.local3);
    syncOccupationDuplicateOptions();
    syncOverseasDuplicateOptions();
    syncLocalLocationDuplicateOptions();
    
    // Populate checkboxes
    const checkboxes = {
      'hasDisability': nrsp.hasDisability,
      'disability_speech': nrsp.disability_speech,
      'disability_hearing': nrsp.disability_hearing,
      'disability_visual': nrsp.disability_visual,
      'disability_mental': nrsp.disability_mental,
      'disability_others': nrsp.disability_others,
      'employed': nrsp.employed,
      'employment_type_wage': nrsp.employment_type_wage,
      'employment_type_self': nrsp.employment_type_self,
      'self_type_voluntary': nrsp.self_type_voluntary,
      'self_type_vendor': nrsp.self_type_vendor,
      'self_type_homebased': nrsp.self_type_homebased,
      'self_type_transport': nrsp.self_type_transport,
      'self_type_domestic': nrsp.self_type_domestic,
      'self_type_fisherfolk': nrsp.self_type_fisherfolk,
      'self_type_freelancer': nrsp.self_type_freelancer,
      'self_type_artisan': nrsp.self_type_artisan,
      'self_type_others': nrsp.self_type_others,
      'unemployed': nrsp.unemployed,
      'unemployed_type_first': nrsp.unemployed_type_first,
      'unemployed_type_local': nrsp.unemployed_type_local,
      'unemployed_type_resigned': nrsp.unemployed_type_resigned,
      'unemployed_type_finished': nrsp.unemployed_type_finished,
      'unemployed_type_public': nrsp.unemployed_type_public,
      'unemployed_type_retired': nrsp.unemployed_type_retired,
      'unemployed_type_terminated': nrsp.unemployed_type_terminated,
      'unemployed_type_terminated_abroad': nrsp.unemployed_type_terminated_abroad,
      'unemployed_type_others': nrsp.unemployed_type_others,
      'fulltime': nrsp.fulltime,
      'parttime': nrsp.parttime,
      'english_read': nrsp.english_read,
      'english_write': nrsp.english_write,
      'english_speak': nrsp.english_speak,
      'english_understand': nrsp.english_understand,
      'filipino_read': nrsp.filipino_read,
      'filipino_write': nrsp.filipino_write,
      'filipino_speak': nrsp.filipino_speak,
      'filipino_understand': nrsp.filipino_understand,
      'mandarin_read': nrsp.mandarin_read,
      'mandarin_write': nrsp.mandarin_write,
      'mandarin_speak': nrsp.mandarin_speak,
      'mandarin_understand': nrsp.mandarin_understand,
      'other_read': nrsp.other_read,
      'other_write': nrsp.other_write,
      'other_speak': nrsp.other_speak,
      'other_understand': nrsp.other_understand,
      'skill_auto_mechanic': nrsp.skill_auto_mechanic,
      'skill_electrician': nrsp.skill_electrician,
      'skill_photography': nrsp.skill_photography,
      'skill_beautician': nrsp.skill_beautician,
      'skill_embroidery': nrsp.skill_embroidery,
      'skill_plumbing': nrsp.skill_plumbing,
      'skill_carpentry': nrsp.skill_carpentry,
      'skill_gardening': nrsp.skill_gardening,
      'skill_sewing': nrsp.skill_sewing,
      'skill_computer': nrsp.skill_computer,
      'skill_masonry': nrsp.skill_masonry,
      'skill_stenography': nrsp.skill_stenography,
      'skill_domestic': nrsp.skill_domestic,
      'skill_painter': nrsp.skill_painter,
      'skill_tailoring': nrsp.skill_tailoring,
      'skill_driver': nrsp.skill_driver,
      'skill_painting': nrsp.skill_painting
    };
    
    Object.keys(checkboxes).forEach(checkboxName => {
      const checkbox = document.querySelector(`input[name="${checkboxName}"]`);
      if (checkbox && checkboxes[checkboxName] == 1) {
        checkbox.checked = true;
      }
    });
    
    // Update "Select All" checkboxes for language proficiency after populating
    ['english', 'filipino', 'mandarin', 'other'].forEach(language => {
      updateSelectAllCheckbox(language);
    });
    
    // Handle radio buttons
    if (nrsp.inschool) {
      const inschoolRadio = document.querySelector(`input[name="inschool"][value="${nrsp.inschool}"]`);
      if (inschoolRadio) inschoolRadio.checked = true;
    }
    if (nrsp.ofw) {
      const ofwRadio = document.querySelector(`input[name="ofw"][value="${nrsp.ofw}"]`);
      if (ofwRadio) ofwRadio.checked = true;
    }
    if (nrsp.returnee) {
      const returneeRadio = document.querySelector(`input[name="returnee"][value="${nrsp.returnee}"]`);
      if (returneeRadio) returneeRadio.checked = true;
    }
    if (nrsp.abroad) {
      const abroadRadio = document.querySelector(`input[name="abroad"][value="${nrsp.abroad}"]`);
      if (abroadRadio) abroadRadio.checked = true;
    }
    if (nrsp.beneficiary) {
      const beneficiaryRadio = document.querySelector(`input[name="beneficiary"][value="${nrsp.beneficiary}"]`);
      if (beneficiaryRadio) beneficiaryRadio.checked = true;
    }
    
  }
  
  // Function to load existing NRSP form data for editing
  function loadExistingNRSPForm(autoLoad = false) {
    if (!autoLoad) {
      Swal.fire({
        title: 'Loading Form...',
        text: 'Please wait while we load your existing NRSP form data.',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });
    }
    
    // Fetch existing NRSP form data
    fetch('get_nrsp_form_data.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        action: 'get_nrsp_data'
      })
    })
    .then(response => response.json())
    .then(data => {
      if (!autoLoad) {
        Swal.close();
      }
      
      if (data.success && data.nrsp_data) {
        const nrsp = data.nrsp_data;
        setNameIntegrityLock(true);
        
        // Populate ALL form fields comprehensively
        populateFormFields(nrsp);
        
        // Trigger field toggles to show/hide conditional fields
        if (nrsp.hasDisability == 1) {
          const hasDisabilityCheckbox = document.getElementById('hasDisability');
          if (hasDisabilityCheckbox) {
            hasDisabilityCheckbox.checked = true;
            toggleDisabilityFields();
          }
        }
        if (nrsp.employed == 1) {
          const employedCheckbox = document.getElementById('employed');
          if (employedCheckbox) {
            employedCheckbox.checked = true;
            toggleEmployedFields();
          }
        }
        if (nrsp.unemployed == 1) {
          const unemployedCheckbox = document.getElementById('unemployed');
          if (unemployedCheckbox) {
            unemployedCheckbox.checked = true;
            toggleUnemployedFields();
          }
        }
        
        // Trigger toggles for radio buttons
        if (nrsp.inschool) {
          const inschoolRadio = document.querySelector(`input[name="inschool"][value="${nrsp.inschool}"]`);
          if (inschoolRadio) {
            inschoolRadio.checked = true;
            toggleCourseField();
          }
        }
        if (nrsp.ofw) {
          toggleOfwCountry();
        }
        if (nrsp.returnee) {
          toggleReturneeFields();
        }
        if (nrsp.beneficiary) {
          toggleHouseholdId();
        }
        
        // Handle wage/self-employed toggles
        if (nrsp.employment_type_wage == 1) {
          const wageCheckbox = document.querySelector('input[name="employment_type_wage"]');
          if (wageCheckbox) {
            wageCheckbox.checked = true;
            toggleEmployedFields();
          }
        }
        if (nrsp.employment_type_self == 1) {
          const selfCheckbox = document.querySelector('input[name="employment_type_self"]');
          if (selfCheckbox) {
            selfCheckbox.checked = true;
            toggleEmployedFields();
          }
        }
        
        // Handle terminated abroad and unemployed others checkbox toggles
        if (nrsp.unemployed_type_terminated_abroad == 1) {
          const terminatedAbroadCheckbox = document.querySelector('input[name="unemployed_type_terminated_abroad"]');
          if (terminatedAbroadCheckbox) {
            terminatedAbroadCheckbox.checked = true;
            const terminatedCountryLabel = document.querySelector('label[for="terminated_country"]');
            const terminatedCountryInput = document.getElementById('terminated_country');
            if (terminatedCountryLabel && terminatedCountryInput) {
              terminatedCountryLabel.style.display = '';
              terminatedCountryInput.style.display = '';
              terminatedCountryInput.disabled = false;
            }
          }
        }
        if (nrsp.unemployed_type_others == 1) {
          const unemployedOthersCheckbox = document.querySelector('input[name="unemployed_type_others"]');
          if (unemployedOthersCheckbox) {
            unemployedOthersCheckbox.checked = true;
            const unemployedOtherLabel = document.querySelector('label[for="unemployed_other_specify"]');
            const unemployedOtherInput = document.getElementById('unemployed_other_specify');
            if (unemployedOtherLabel && unemployedOtherInput) {
              unemployedOtherLabel.style.display = '';
              unemployedOtherInput.style.display = '';
              unemployedOtherInput.disabled = false;
            }
          }
        }
        
        // Display existing esignature file if available
        if (nrsp.esignature_file) {
          const esignaturePreview = document.getElementById('esignaturePreview');
          const esignatureImage = document.getElementById('esignatureImage');
          const esignatureFilename = document.getElementById('esignatureFilename');
          if (esignaturePreview && esignatureImage && esignatureFilename) {
            // Try different possible paths
            const possiblePaths = [
              '../uploads/esignatures/' + nrsp.esignature_file,
              'uploads/esignatures/' + nrsp.esignature_file,
              '/WorkConnect/uploads/esignatures/' + nrsp.esignature_file
            ];
            
            // Set image source and handle error
            esignatureImage.onerror = function() {
              // If image fails to load, try next path or show filename only
              this.style.display = 'none';
            };
            esignatureImage.src = possiblePaths[0];
            esignatureFilename.textContent = nrsp.esignature_file;
            esignaturePreview.style.display = 'flex';
            // Remove required attribute since file exists
            const esignatureInput = document.getElementById('esignature');
            if (esignatureInput) {
              esignatureInput.removeAttribute('required');
              // Store existing filename in a hidden input for form submission
              let hiddenInput = document.querySelector('input[name="existing_esignature_file"]');
              if (!hiddenInput) {
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'existing_esignature_file';
                esignatureInput.parentNode.appendChild(hiddenInput);
              }
              hiddenInput.value = nrsp.esignature_file;
            }
          }
        }
        
        // Display existing resume file if available
        if (nrsp.resume_file) {
          const resumeInput = document.getElementById('resume_file');
          if (resumeInput) {
            // Create a display element for existing resume
            const resumeContainer = resumeInput.closest('.form-row');
            if (resumeContainer) {
              // Check if display already exists
              let existingResumeDiv = resumeContainer.querySelector('.existing-resume-display');
              if (!existingResumeDiv) {
                existingResumeDiv = document.createElement('div');
                existingResumeDiv.className = 'existing-resume-display';
                existingResumeDiv.style.cssText = 'margin-top: 10px; padding: 10px; background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 5px;';
                existingResumeDiv.innerHTML = '<strong>Current Resume:</strong> ' + nrsp.resume_file + ' <span style="color: #666; font-size: 0.9rem;">(Upload a new file to replace)</span>';
                resumeContainer.appendChild(existingResumeDiv);
              }
              // Remove required attribute since file exists
              resumeInput.removeAttribute('required');
              // Store existing filename in a hidden input for form submission
              let hiddenInput = document.querySelector('input[name="existing_resume_file"]');
              if (!hiddenInput) {
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'existing_resume_file';
                resumeInput.parentNode.appendChild(hiddenInput);
              }
              hiddenInput.value = nrsp.resume_file;
            }
          }
        }
        
        // Remove required attributes from hidden fields to prevent validation errors
        const allSections = document.querySelectorAll('.form-section');
        allSections.forEach(section => {
          if (section.style.display === 'none' || section.offsetParent === null) {
            const requiredFields = section.querySelectorAll('[required]');
            requiredFields.forEach(field => {
              field.removeAttribute('required');
            });
          }
        });
        
        // If form is locked (Accepted/Referred), disable all inputs
        if (typeof CAN_EDIT_NRSP !== 'undefined' && !CAN_EDIT_NRSP) {
          const form = document.getElementById('jobseekerForm');
          if (form) {
            form.querySelectorAll('input, select, textarea').forEach(el => {
              el.disabled = true;
            });
            form.querySelectorAll('.back-btn, .next-btn').forEach(btn => {
              if (btn.type !== 'submit') btn.disabled = true;
            });
          }
        }
        
        // Show success message only if not auto-loading
        if (!autoLoad) {
          Swal.fire({
            title: 'Form Loaded!',
            text: 'Your existing NRSP form data has been loaded. Please review and update as needed.',
            icon: 'success',
            confirmButtonText: 'OK',
            confirmButtonColor: '#233a8b'
          }).then(() => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            if (typeof showStep1 === 'function') {
              showStep1();
            }
          });
        } else {
          // Auto-load: just scroll to top and show first step
          console.log('Auto-load complete, showing form...');
          // Make sure form is visible
          const form = document.getElementById('jobseekerForm');
          if (form) {
            form.style.display = '';
          }
          window.scrollTo({ top: 0, behavior: 'smooth' });
          if (typeof showStep1 === 'function') {
            showStep1();
          } else {
            console.error('showStep1 function not found');
          }
        }
      } else {
        setNameIntegrityLock(false);
        Swal.fire({
          title: 'Error',
          text: data.message || 'Failed to load NRSP form data.',
          icon: 'error',
          confirmButtonText: 'OK'
        });
      }
    })
    .catch(error => {
      Swal.close();
      console.error('Error loading NRSP form:', error);
      Swal.fire({
        title: 'Error',
        text: 'Failed to load NRSP form data. Please try again.',
        icon: 'error',
        confirmButtonText: 'OK'
      });
    });
  }
</script>
<script>
(function () {
  function workconnectNotifyParentApplyResize() {
    try {
      if (window.parent && window.parent !== window) {
        window.parent.postMessage({ type: 'workconnect-resize-apply', source: 'apply' }, '*');
      }
    } catch (e) {}
  }
  window.addEventListener('load', function () {
    workconnectNotifyParentApplyResize();
    [200, 600, 1400].forEach(function (ms) { setTimeout(workconnectNotifyParentApplyResize, ms); });
  });
})();
</script>
</body>
</html>