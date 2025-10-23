<?php
header('Content-Type: application/json');

// Check if PHPMailer is available
$phpmailer_available = false;
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
    require_once 'email_config.php';
    $phpmailer_available = true;
    
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;
}

$host = "workconnect.cz2woayyket3.ap-southeast-2.rds.amazonaws.com";
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
    $employer_email = isset($input['employer_email']) ? $conn->real_escape_string($input['employer_email']) : 'employer@workconnect.com';
    
    // Get jobseeker data
    $sql = "SELECT * FROM jobseeker WHERE id = $jobseeker_id";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $jobseeker = $result->fetch_assoc();
        
        // Create professional email content
        $subject = "New Jobseeker Application - " . $jobseeker['firstname'] . " " . $jobseeker['surname'];
        
        // Helper function to format boolean values
        function formatBoolean($value) {
            return ($value == 1 || $value === '1' || $value === true) ? 'Yes' : 'No';
        }
        
        // Helper function to format skills
        function formatSkills($jobseeker) {
            $skills = [];
            if ($jobseeker['skill_auto_mechanic']) $skills[] = 'Auto Mechanic';
            if ($jobseeker['skill_electrician']) $skills[] = 'Electrician';
            if ($jobseeker['skill_photography']) $skills[] = 'Photography';
            if ($jobseeker['skill_beautician']) $skills[] = 'Beautician';
            if ($jobseeker['skill_embroidery']) $skills[] = 'Embroidery';
            if ($jobseeker['skill_plumbing']) $skills[] = 'Plumbing';
            if ($jobseeker['skill_carpentry']) $skills[] = 'Carpentry';
            if ($jobseeker['skill_gardening']) $skills[] = 'Gardening';
            if ($jobseeker['skill_sewing']) $skills[] = 'Sewing';
            if ($jobseeker['skill_computer']) $skills[] = 'Computer Literacy';
            if ($jobseeker['skill_masonry']) $skills[] = 'Masonry';
            if ($jobseeker['skill_stenography']) $skills[] = 'Stenography';
            if ($jobseeker['skill_domestic']) $skills[] = 'Domestic Work';
            if ($jobseeker['skill_painter']) $skills[] = 'Painting/Art';
            if ($jobseeker['skill_tailoring']) $skills[] = 'Tailoring';
            if ($jobseeker['skill_driver']) $skills[] = 'Driving';
            if ($jobseeker['skill_painting']) $skills[] = 'Painting';
            if ($jobseeker['skill_others'] && $jobseeker['skill_others'] !== 'n/a') {
                $skills[] = 'Others: ' . $jobseeker['skill_others'];
            }
            return empty($skills) ? 'None specified' : implode(', ', $skills);
        }
        
        // Helper function to format disability
        function formatDisability($jobseeker) {
            if (!$jobseeker['hasDisability'] || $jobseeker['hasDisability'] == 0) return 'No';
            
            $disabilities = [];
            if ($jobseeker['disability_speech']) $disabilities[] = 'Speech';
            if ($jobseeker['disability_hearing']) $disabilities[] = 'Hearing';
            if ($jobseeker['disability_visual']) $disabilities[] = 'Visual';
            if ($jobseeker['disability_mental']) $disabilities[] = 'Mental';
            if ($jobseeker['disability_others']) {
                if ($jobseeker['disability_other'] && $jobseeker['disability_other'] !== 'n/a') {
                    $disabilities[] = 'Others: ' . $jobseeker['disability_other'];
                } else {
                    $disabilities[] = 'Others';
                }
            }
            return empty($disabilities) ? 'Yes (not specified)' : implode(', ', $disabilities);
        }
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Jobseeker Application Details</title>
            <style>
                body { 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    margin: 0; 
                    padding: 0; 
                    background-color: #f5f5f5;
                }
                .email-container { 
                    max-width: 800px; 
                    margin: 0 auto; 
                    background: white; 
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                }
                .header { 
                    background: linear-gradient(135deg, #233a8b 0%, #1976d2 100%); 
                    color: white; 
                    padding: 30px; 
                    text-align: center; 
                }
                .header h1 { 
                    margin: 0; 
                    font-size: 28px; 
                    font-weight: 300;
                }
                .header p { 
                    margin: 10px 0 0 0; 
                    font-size: 16px; 
                    opacity: 0.9;
                }
                .content { 
                    padding: 30px; 
                }
                .section { 
                    margin-bottom: 25px; 
                    padding: 20px; 
                    background: #f8f9fa; 
                    border-radius: 8px; 
                    border-left: 4px solid #1976d2;
                }
                .section h3 { 
                    color: #233a8b; 
                    margin: 0 0 15px 0; 
                    font-size: 18px; 
                    font-weight: 600;
                }
                .field { 
                    margin-bottom: 10px; 
                    display: flex;
                }
                .field strong { 
                    color: #233a8b; 
                    min-width: 150px; 
                    font-weight: 600;
                }
                .field span { 
                    color: #555;
                }
                .resume-section { 
                    text-align: center; 
                    padding: 20px; 
                    background: #e3f2fd; 
                    border-radius: 8px; 
                    margin-top: 20px;
                }
                .resume-link { 
                    background: #1976d2; 
                    color: white; 
                    padding: 12px 24px; 
                    text-decoration: none; 
                    border-radius: 6px; 
                    display: inline-block; 
                    font-weight: 600;
                    transition: background-color 0.3s;
                }
                .resume-link:hover { 
                    background: #1565c0; 
                }
                .footer { 
                    background: #f8f9fa; 
                    padding: 20px; 
                    text-align: center; 
                    color: #666; 
                    font-size: 14px;
                }
                .highlight { 
                    background: #fff3cd; 
                    padding: 15px; 
                    border-radius: 6px; 
                    border-left: 4px solid #ffc107; 
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='header'>
                    <h1>🎯 New Jobseeker Application</h1>
                    <p>WorkConnect - Jobseeker Profile Details</p>
                </div>
                
                <div class='content'>
                    <div class='highlight'>
                        <strong>📋 Application Summary:</strong> " . $jobseeker['firstname'] . " " . $jobseeker['surname'] . " has been accepted for employment consideration. Please review their complete profile below.
                    </div>
                    
                    <div class='section'>
                        <h3>👤 Personal Information</h3>
                        <div class='field'><strong>Full Name:</strong> <span>" . $jobseeker['firstname'] . " " . $jobseeker['middlename'] . " " . $jobseeker['surname'] . "</span></div>
                        <div class='field'><strong>Age:</strong> <span>" . $jobseeker['age'] . " years old</span></div>
                        <div class='field'><strong>Gender:</strong> <span>" . ucfirst($jobseeker['sex']) . "</span></div>
                        <div class='field'><strong>Date of Birth:</strong> <span>" . $jobseeker['dob'] . "</span></div>
                        <div class='field'><strong>Civil Status:</strong> <span>" . $jobseeker['civilstatus'] . "</span></div>
                        <div class='field'><strong>Religion:</strong> <span>" . $jobseeker['religion'] . "</span></div>
                        <div class='field'><strong>Address:</strong> <span>" . $jobseeker['street'] . ", " . $jobseeker['barangay'] . ", " . $jobseeker['municipality'] . ", " . $jobseeker['province'] . "</span></div>
                        <div class='field'><strong>Contact Number:</strong> <span>" . $jobseeker['contact'] . "</span></div>
                        <div class='field'><strong>Email Address:</strong> <span>" . $jobseeker['email'] . "</span></div>
                        <div class='field'><strong>TIN Number:</strong> <span>" . $jobseeker['tin'] . "</span></div>
                        <div class='field'><strong>Height:</strong> <span>" . $jobseeker['height'] . " ft.</span></div>
                        <div class='field'><strong>Disability Status:</strong> <span>" . formatDisability($jobseeker) . "</span></div>
                    </div>
                    
                    <div class='section'>
                        <h3>💼 Job Preferences</h3>
                        <div class='field'><strong>Preferred Occupations:</strong> <span>" . $jobseeker['occupation1'] . ", " . $jobseeker['occupation2'] . ", " . $jobseeker['occupation3'] . "</span></div>
                        <div class='field'><strong>Local Work Locations:</strong> <span>" . $jobseeker['local1'] . ", " . $jobseeker['local2'] . ", " . $jobseeker['local3'] . "</span></div>
                        <div class='field'><strong>Overseas Work Locations:</strong> <span>" . $jobseeker['overseas1'] . ", " . $jobseeker['overseas2'] . ", " . $jobseeker['overseas3'] . "</span></div>
                        <div class='field'><strong>Employment Type:</strong> <span>" . (formatBoolean($jobseeker['fulltime']) == 'Yes' ? 'Full-time' : '') . (formatBoolean($jobseeker['parttime']) == 'Yes' ? (formatBoolean($jobseeker['fulltime']) == 'Yes' ? ', ' : '') . 'Part-time' : '') . "</span></div>
                    </div>
                    
                    <div class='section'>
                        <h3>🎓 Educational Background</h3>
                        <div class='field'><strong>Education Level:</strong> <span>" . $jobseeker['level'] . "</span></div>
                        <div class='field'><strong>Course/Program:</strong> <span>" . $jobseeker['course'] . "</span></div>
                        <div class='field'><strong>Year Graduated:</strong> <span>" . $jobseeker['year_graduated'] . "</span></div>
                    </div>
                    
                    <div class='section'>
                        <h3>💼 Work Experience</h3>";
        
        if ($jobseeker['company_name_1']) {
            $message .= "<div class='field'><strong>Experience 1:</strong> <span>" . $jobseeker['company_name_1'] . " - " . $jobseeker['position_1'] . " (" . $jobseeker['months_1'] . " months)</span></div>";
        }
        if ($jobseeker['company_name_2']) {
            $message .= "<div class='field'><strong>Experience 2:</strong> <span>" . $jobseeker['company_name_2'] . " - " . $jobseeker['position_2'] . " (" . $jobseeker['months_2'] . " months)</span></div>";
        }
        if ($jobseeker['company_name_3']) {
            $message .= "<div class='field'><strong>Experience 3:</strong> <span>" . $jobseeker['company_name_3'] . " - " . $jobseeker['position_3'] . " (" . $jobseeker['months_3'] . " months)</span></div>";
        }
        
        $message .= "
                    </div>
                    
                    <div class='section'>
                        <h3>🛠️ Skills & Competencies</h3>
                        <div class='field'><strong>Technical Skills:</strong> <span>" . formatSkills($jobseeker) . "</span></div>
                    </div>";
        
        if ($jobseeker['resume_file']) {
            $resume_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/../uploads/resumes/" . $jobseeker['resume_file'];
            $message .= "
                    <div class='resume-section'>
                        <h3>📄 Resume Document</h3>
                        <p>Download the complete resume and supporting documents:</p>
                        <a href='" . $resume_url . "' class='resume-link'>📥 Download Resume</a>
                    </div>";
        } else {
            $message .= "
                    <div class='resume-section'>
                        <h3>📄 Resume Document</h3>
                        <p>No resume file uploaded by the jobseeker.</p>
                    </div>";
        }
        
        $message .= "
                </div>
                
                <div class='footer'>
                    <p><strong>WorkConnect</strong> - Connecting Talent with Opportunity</p>
                    <p>This email was generated automatically. Please contact the system administrator if you have any questions.</p>
                </div>
            </div>
        </body>
        </html>";
        
        // Send email using available method
        if ($phpmailer_available) {
            // Use PHPMailer to send email
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
                $mail->addAddress($employer_email);
                
                // Content
                $mail->isHTML(true);
                $mail->CharSet = 'UTF-8';
                $mail->Subject = $subject;
                $mail->Body    = $message;
                
                $mail->send();
                echo json_encode(['success' => true, 'message' => 'Email sent successfully to ' . $employer_email]);
                
            } catch (Exception $e) {
                // Log the error for debugging
                error_log("Email sending failed for jobseeker ID: $jobseeker_id, Email: $employer_email, Error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again later.']);
            }
        } else {
            // Fallback to basic PHP mail() function
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: WorkConnect <noreply@workconnect.com>" . "\r\n";
            $headers .= "Reply-To: noreply@workconnect.com" . "\r\n";
            
            if (mail($employer_email, $subject, $message, $headers)) {
                echo json_encode(['success' => true, 'message' => 'Email sent successfully to ' . $employer_email]);
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
