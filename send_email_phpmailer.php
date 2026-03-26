<?php
// Alternative email sending using PHPMailer (if available)
// This is a more reliable method for sending emails

header('Content-Type: application/json');

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
    $employer_email = isset($input['employer_email']) ? $conn->real_escape_string($input['employer_email']) : 'employer@workconnect.com';
    
    // Get jobseeker data
    $sql = "SELECT * FROM jobseeker WHERE id = $jobseeker_id";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $jobseeker = $result->fetch_assoc();
        
        // Simple email content (text version for better compatibility)
        $subject = "New Jobseeker Referral - " . $jobseeker['firstname'] . " " . $jobseeker['surname'];
        
        $message = "NEW JOBSEEKER REFERRAL\n";
        $message .= "========================\n\n";
        $message .= "Jobseeker: " . $jobseeker['firstname'] . " " . $jobseeker['surname'] . "\n";
        $message .= "Age: " . $jobseeker['age'] . "\n";
        $message .= "Contact: " . $jobseeker['contact'] . "\n";
        $message .= "Email: " . $jobseeker['email'] . "\n";
        $message .= "Address: " . $jobseeker['street'] . ", " . $jobseeker['barangay'] . ", " . $jobseeker['municipality'] . ", " . $jobseeker['province'] . "\n\n";
        
        $message .= "JOB PREFERENCES\n";
        $message .= "===============\n";
        $message .= "Preferred Occupations: " . $jobseeker['occupation1'] . ", " . $jobseeker['occupation2'] . ", " . $jobseeker['occupation3'] . "\n";
        $message .= "Local Work: " . $jobseeker['local1'] . ", " . $jobseeker['local2'] . ", " . $jobseeker['local3'] . "\n";
        $message .= "Overseas Work: " . $jobseeker['overseas1'] . ", " . $jobseeker['overseas2'] . ", " . $jobseeker['overseas3'] . "\n\n";
        
        $message .= "EDUCATION\n";
        $message .= "=========\n";
        $message .= "Level: " . $jobseeker['level'] . "\n";
        $message .= "Course: " . $jobseeker['course'] . "\n";
        $message .= "Year Graduated: " . $jobseeker['year_graduated'] . "\n\n";
        
        $message .= "WORK EXPERIENCE\n";
        $message .= "===============\n";
        if ($jobseeker['company_name_1']) {
            $message .= "1. " . $jobseeker['company_name_1'] . " - " . $jobseeker['position_1'] . " (" . $jobseeker['months_1'] . " months)\n";
        }
        if ($jobseeker['company_name_2']) {
            $message .= "2. " . $jobseeker['company_name_2'] . " - " . $jobseeker['position_2'] . " (" . $jobseeker['months_2'] . " months)\n";
        }
        if ($jobseeker['company_name_3']) {
            $message .= "3. " . $jobseeker['company_name_3'] . " - " . $jobseeker['position_3'] . " (" . $jobseeker['months_3'] . " months)\n";
        }
        
        if ($jobseeker['resume_file']) {
            $resume_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/../uploads/resumes/" . $jobseeker['resume_file'];
            $message .= "\nRESUME: " . $resume_url . "\n";
        }
        
        $message .= "\n\nWorkConnect - Connecting Talent with Opportunity";
        
        // Simple headers
        $headers = "From: WorkConnect <noreply@workconnect.com>\r\n";
        $headers .= "Reply-To: noreply@workconnect.com\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        
        // Try to send email
        if (mail($employer_email, $subject, $message, $headers)) {
            echo json_encode(['success' => true, 'message' => 'Email sent successfully to ' . $employer_email]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send email. Please check server configuration.']);
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
