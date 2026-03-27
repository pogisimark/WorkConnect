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
    $employer_email = isset($input['employer_email']) ? $conn->real_escape_string($input['employer_email']) : 'employer@workconnect.com';
    
    // Get jobseeker data with calculated age
    $sql = "SELECT *, YEAR(CURDATE())-YEAR(dob) - (DATE_FORMAT(CURDATE(),'%m%d') < DATE_FORMAT(dob,'%m%d')) AS age FROM jobseeker WHERE id = $jobseeker_id";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $jobseeker = $result->fetch_assoc();
        
        // Create professional email content
        $subject = "New Jobseeker Referral - " . $jobseeker['firstname'] . " " . $jobseeker['surname'];
        
        // Helper function to format boolean values
        function formatBoolean($value) {
            return ($value == 1 || $value === '1' || $value === true) ? 'Yes' : 'No';
        }
        
        // Helper function to format skills - Enhanced to match job applicant card display
        function formatSkills($jobseeker) {
            $predefinedSkills = [];
            $otherSkills = [];
            
            // Collect predefined skills
            if ($jobseeker['skill_auto_mechanic'] && ($jobseeker['skill_auto_mechanic'] === 1 || $jobseeker['skill_auto_mechanic'] === '1')) $predefinedSkills[] = 'Auto mechanic';
            if ($jobseeker['skill_electrician'] && ($jobseeker['skill_electrician'] === 1 || $jobseeker['skill_electrician'] === '1')) $predefinedSkills[] = 'Electrician';
            if ($jobseeker['skill_photography'] && ($jobseeker['skill_photography'] === 1 || $jobseeker['skill_photography'] === '1')) $predefinedSkills[] = 'Photography';
            if ($jobseeker['skill_beautician'] && ($jobseeker['skill_beautician'] === 1 || $jobseeker['skill_beautician'] === '1')) $predefinedSkills[] = 'Beautician';
            if ($jobseeker['skill_embroidery'] && ($jobseeker['skill_embroidery'] === 1 || $jobseeker['skill_embroidery'] === '1')) $predefinedSkills[] = 'Embroidery';
            if ($jobseeker['skill_plumbing'] && ($jobseeker['skill_plumbing'] === 1 || $jobseeker['skill_plumbing'] === '1')) $predefinedSkills[] = 'Plumbing';
            if ($jobseeker['skill_carpentry'] && ($jobseeker['skill_carpentry'] === 1 || $jobseeker['skill_carpentry'] === '1')) $predefinedSkills[] = 'Carpentry work';
            if ($jobseeker['skill_gardening'] && ($jobseeker['skill_gardening'] === 1 || $jobseeker['skill_gardening'] === '1')) $predefinedSkills[] = 'Gardening';
            if ($jobseeker['skill_sewing'] && ($jobseeker['skill_sewing'] === 1 || $jobseeker['skill_sewing'] === '1')) $predefinedSkills[] = 'Sewing dresses';
            if ($jobseeker['skill_computer'] && ($jobseeker['skill_computer'] === 1 || $jobseeker['skill_computer'] === '1')) $predefinedSkills[] = 'Computer literature';
            if ($jobseeker['skill_masonry'] && ($jobseeker['skill_masonry'] === 1 || $jobseeker['skill_masonry'] === '1')) $predefinedSkills[] = 'Masonry';
            if ($jobseeker['skill_stenography'] && ($jobseeker['skill_stenography'] === 1 || $jobseeker['skill_stenography'] === '1')) $predefinedSkills[] = 'Stenography';
            if ($jobseeker['skill_domestic'] && ($jobseeker['skill_domestic'] === 1 || $jobseeker['skill_domestic'] === '1')) $predefinedSkills[] = 'Domestic chores';
            if ($jobseeker['skill_painter'] && ($jobseeker['skill_painter'] === 1 || $jobseeker['skill_painter'] === '1')) $predefinedSkills[] = 'Painter/Artist';
            if ($jobseeker['skill_tailoring'] && ($jobseeker['skill_tailoring'] === 1 || $jobseeker['skill_tailoring'] === '1')) $predefinedSkills[] = 'Tailoring';
            if ($jobseeker['skill_driver'] && ($jobseeker['skill_driver'] === 1 || $jobseeker['skill_driver'] === '1')) $predefinedSkills[] = 'Driver';
            if ($jobseeker['skill_painting'] && ($jobseeker['skill_painting'] === 1 || $jobseeker['skill_painting'] === '1')) $predefinedSkills[] = 'Painting job';
            
            // Parse and collect other skills
            if ($jobseeker['skill_others'] && $jobseeker['skill_others'] !== 'n/a' && $jobseeker['skill_others'] !== '') {
                $othersText = trim($jobseeker['skill_others']);
                // Split by common separators: comma, semicolon, "and", "or", newline
                $separators = [',', ';', ' and ', ' or ', '\n', '\r\n'];
                $skills = [$othersText];
                
                // Split by each separator
                foreach ($separators as $separator) {
                    $newSkills = [];
                    foreach ($skills as $skill) {
                        if (strpos($skill, $separator) !== false) {
                            $newSkills = array_merge($newSkills, array_filter(array_map('trim', explode($separator, $skill))));
                        } else {
                            $newSkills[] = $skill;
                        }
                    }
                    $skills = $newSkills;
                }
                
                // Clean up and filter out empty strings
                $otherSkills = array_filter(
                    array_map('trim', $skills),
                    function($skill) {
                        return $skill !== '' && $skill !== 'n/a' && strlen($skill) > 1;
                    }
                );
            }
            
            $result = '';
            
            // Display predefined skills
            if (!empty($predefinedSkills)) {
                $result .= '<strong>Predefined Skills:</strong> ' . implode(', ', $predefinedSkills);
            }
            
            // Display other skills
            if (!empty($otherSkills)) {
                if (!empty($predefinedSkills)) $result .= '<br><br>';
                $result .= '<strong>Other Skills:</strong> ' . implode(', ', $otherSkills);
            }
            
            return empty($predefinedSkills) && empty($otherSkills) ? 'None specified' : $result;
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
    <link rel='icon' type='image/png' href='/assets/image/PESO Logo circle.png'>
            <meta charset='UTF-8'>
            <title>Jobseeker Referral Details</title>
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
                    <h1>New Jobseeker Referral</h1>
                    <p>WorkConnect - Jobseeker Profile Details</p>
                </div>
                
                <div class='content'>
                    <div class='highlight'>
                        <strong>Referral Summary:</strong> " . $jobseeker['firstname'] . " " . $jobseeker['surname'] . " has been referred for employment consideration. Please review their complete profile below.
                    </div>
                    
                    <div class='section'>
                        <h3>I. PERSONAL INFORMATION</h3>
                        <div class='field'><strong>Full Name:</strong> <span>" . $jobseeker['firstname'] . " " . $jobseeker['middlename'] . " " . $jobseeker['surname'] . "</span></div>
                        <div class='field'><strong>Age:</strong> <span>" . (!empty($jobseeker['age']) ? $jobseeker['age'] . " years old" : "Not specified") . "</span></div>
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
                        <h3>II. EMPLOYMENT STATUS</h3>";
        
        // Employment Status Section - Updated to match job applicant card display
        if ($jobseeker['employed'] && ($jobseeker['employed'] === 1 || $jobseeker['employed'] === '1')) {
            $message .= "<div class='field'><strong>Employment Status:</strong> <span>Employed</span></div>";
            if ($jobseeker['employment_type_wage'] && ($jobseeker['employment_type_wage'] === 1 || $jobseeker['employment_type_wage'] === '1')) {
                $message .= "<div class='field'><strong>Wage Employed:</strong> <span>" . formatBoolean($jobseeker['employment_type_wage']) . "</span></div>";
                // selfTypeFields (Voluntary, Vendor, etc.) are under wage employed
                if ($jobseeker['self_type_voluntary'] && ($jobseeker['self_type_voluntary'] === 1 || $jobseeker['self_type_voluntary'] === '1')) $message .= "<div class='field'><strong>Voluntary/PhilHealth:</strong> <span>" . formatBoolean($jobseeker['self_type_voluntary']) . "</span></div>";
                if ($jobseeker['self_type_vendor'] && ($jobseeker['self_type_vendor'] === 1 || $jobseeker['self_type_vendor'] === '1')) $message .= "<div class='field'><strong>Vendor / Retailer:</strong> <span>" . formatBoolean($jobseeker['self_type_vendor']) . "</span></div>";
                if ($jobseeker['self_type_homebased'] && ($jobseeker['self_type_homebased'] === 1 || $jobseeker['self_type_homebased'] === '1')) $message .= "<div class='field'><strong>Home-based worker:</strong> <span>" . formatBoolean($jobseeker['self_type_homebased']) . "</span></div>";
                if ($jobseeker['self_type_transport'] && ($jobseeker['self_type_transport'] === 1 || $jobseeker['self_type_transport'] === '1')) $message .= "<div class='field'><strong>Transport:</strong> <span>" . formatBoolean($jobseeker['self_type_transport']) . "</span></div>";
                if ($jobseeker['self_type_domestic'] && ($jobseeker['self_type_domestic'] === 1 || $jobseeker['self_type_domestic'] === '1')) $message .= "<div class='field'><strong>Domestic Worker:</strong> <span>" . formatBoolean($jobseeker['self_type_domestic']) . "</span></div>";
                if ($jobseeker['self_type_fisherfolk'] && ($jobseeker['self_type_fisherfolk'] === 1 || $jobseeker['self_type_fisherfolk'] === '1')) $message .= "<div class='field'><strong>Fisherfolk:</strong> <span>" . formatBoolean($jobseeker['self_type_fisherfolk']) . "</span></div>";
                if ($jobseeker['self_type_freelancer'] && ($jobseeker['self_type_freelancer'] === 1 || $jobseeker['self_type_freelancer'] === '1')) $message .= "<div class='field'><strong>Freelancer:</strong> <span>" . formatBoolean($jobseeker['self_type_freelancer']) . "</span></div>";
                if ($jobseeker['self_type_artisan'] && ($jobseeker['self_type_artisan'] === 1 || $jobseeker['self_type_artisan'] === '1')) $message .= "<div class='field'><strong>Artisan/Craft Worker:</strong> <span>" . formatBoolean($jobseeker['self_type_artisan']) . "</span></div>";
                if ($jobseeker['self_type_others'] && ($jobseeker['self_type_others'] === 1 || $jobseeker['self_type_others'] === '1') && $jobseeker['other_jobs']) $message .= "<div class='field'><strong>Other Job/s:</strong> <span>" . $jobseeker['other_jobs'] . "</span></div>";
            }
            if ($jobseeker['employment_type_self'] && ($jobseeker['employment_type_self'] === 1 || $jobseeker['employment_type_self'] === '1')) {
                $message .= "<div class='field'><strong>Self-Employed:</strong> <span>" . formatBoolean($jobseeker['employment_type_self']) . "</span></div>";
                if ($jobseeker['self_employed_specify'] && $jobseeker['self_employed_specify'] !== 'n/a' && $jobseeker['self_employed_specify'] !== '') {
                    $message .= "<div class='field'><strong>Self-Employed Specify:</strong> <span>" . $jobseeker['self_employed_specify'] . "</span></div>";
                }
            }
        }
        
        if ($jobseeker['unemployed'] && ($jobseeker['unemployed'] === 1 || $jobseeker['unemployed'] === '1')) {
            $message .= "<div class='field'><strong>Employment Status:</strong> <span>Unemployed</span></div>";
            if ($jobseeker['unemployed_months']) $message .= "<div class='field'><strong>Duration Looking for Work:</strong> <span>" . $jobseeker['unemployed_months'] . " months</span></div>";
            if ($jobseeker['unemployed_type_first'] && ($jobseeker['unemployed_type_first'] === 1 || $jobseeker['unemployed_type_first'] === '1')) $message .= "<div class='field'><strong>First-time Jobseeker/Graduate:</strong> <span>" . formatBoolean($jobseeker['unemployed_type_first']) . "</span></div>";
            if ($jobseeker['unemployed_type_local'] && ($jobseeker['unemployed_type_local'] === 1 || $jobseeker['unemployed_type_local'] === '1')) $message .= "<div class='field'><strong>Terminated/Laid off due to calamity:</strong> <span>" . formatBoolean($jobseeker['unemployed_type_local']) . "</span></div>";
            if ($jobseeker['unemployed_type_resigned'] && ($jobseeker['unemployed_type_resigned'] === 1 || $jobseeker['unemployed_type_resigned'] === '1')) $message .= "<div class='field'><strong>Resigned:</strong> <span>" . formatBoolean($jobseeker['unemployed_type_resigned']) . "</span></div>";
            if ($jobseeker['unemployed_type_finished'] && ($jobseeker['unemployed_type_finished'] === 1 || $jobseeker['unemployed_type_finished'] === '1')) $message .= "<div class='field'><strong>Finished Contract (OFW):</strong> <span>" . formatBoolean($jobseeker['unemployed_type_finished']) . "</span></div>";
            if ($jobseeker['unemployed_type_public'] && ($jobseeker['unemployed_type_public'] === 1 || $jobseeker['unemployed_type_public'] === '1')) $message .= "<div class='field'><strong>Public Contract:</strong> <span>" . formatBoolean($jobseeker['unemployed_type_public']) . "</span></div>";
            if ($jobseeker['unemployed_type_retired'] && ($jobseeker['unemployed_type_retired'] === 1 || $jobseeker['unemployed_type_retired'] === '1')) $message .= "<div class='field'><strong>Retired:</strong> <span>" . formatBoolean($jobseeker['unemployed_type_retired']) . "</span></div>";
            if ($jobseeker['unemployed_type_terminated'] && ($jobseeker['unemployed_type_terminated'] === 1 || $jobseeker['unemployed_type_terminated'] === '1')) $message .= "<div class='field'><strong>Terminated/Laid off (Local):</strong> <span>" . formatBoolean($jobseeker['unemployed_type_terminated']) . "</span></div>";
            if ($jobseeker['unemployed_type_terminated_abroad'] && ($jobseeker['unemployed_type_terminated_abroad'] === 1 || $jobseeker['unemployed_type_terminated_abroad'] === '1')) {
                $message .= "<div class='field'><strong>Terminated/Laid off (Abroad):</strong> <span>" . formatBoolean($jobseeker['unemployed_type_terminated_abroad']) . "</span></div>";
                if (!empty($jobseeker['terminated_country']) && strtolower($jobseeker['terminated_country']) !== 'n/a') {
                    $message .= "<div class='field'><strong>Specify Country:</strong> <span>" . $jobseeker['terminated_country'] . "</span></div>";
                }
            }
            if ($jobseeker['unemployed_type_others'] && ($jobseeker['unemployed_type_others'] === 1 || $jobseeker['unemployed_type_others'] === '1')) {
                $message .= "<div class='field'><strong>Others:</strong> <span>" . formatBoolean($jobseeker['unemployed_type_others']) . "</span></div>";
                if (!empty($jobseeker['unemployed_other_specify']) && strtolower($jobseeker['unemployed_other_specify']) !== 'n/a') {
                    $message .= "<div class='field'><strong>Please Specify:</strong> <span>" . $jobseeker['unemployed_other_specify'] . "</span></div>";
                }
            }
        }
        
        if ($jobseeker['ofw']) $message .= "<div class='field'><strong>OFW:</strong> <span>" . $jobseeker['ofw'] . "</span></div>";
        if ($jobseeker['ofw_country']) $message .= "<div class='field'><strong>OFW Country:</strong> <span>" . $jobseeker['ofw_country'] . "</span></div>";
        if ($jobseeker['returnee']) $message .= "<div class='field'><strong>OFW Returnee:</strong> <span>" . $jobseeker['returnee'] . "</span></div>";
        if ($jobseeker['deployment_country']) $message .= "<div class='field'><strong>Deployment Country:</strong> <span>" . $jobseeker['deployment_country'] . "</span></div>";
        if ($jobseeker['return_month']) $message .= "<div class='field'><strong>Month of Return:</strong> <span>" . $jobseeker['return_month'] . "</span></div>";
        if ($jobseeker['return_year']) $message .= "<div class='field'><strong>Year of Return:</strong> <span>" . $jobseeker['return_year'] . "</span></div>";
        if ($jobseeker['abroad']) $message .= "<div class='field'><strong>Employed Abroad in Philippines:</strong> <span>" . $jobseeker['abroad'] . "</span></div>";
        if ($jobseeker['beneficiary']) $message .= "<div class='field'><strong>Job Beneficiary:</strong> <span>" . $jobseeker['beneficiary'] . "</span></div>";
        if ($jobseeker['household_id']) $message .= "<div class='field'><strong>Household ID:</strong> <span>" . $jobseeker['household_id'] . "</span></div>";
        
        $message .= "
                    </div>
                    
                    <div class='section'>
                        <h3>III. JOB PREFERENCE</h3>
                        <div class='field'><strong>Preferred Occupations:</strong> <span>" . $jobseeker['occupation1'] . ", " . $jobseeker['occupation2'] . ", " . $jobseeker['occupation3'] . "</span></div>
                        <div class='field'><strong>Local Work Locations:</strong> <span>" . $jobseeker['local1'] . ", " . $jobseeker['local2'] . ", " . $jobseeker['local3'] . "</span></div>
                        <div class='field'><strong>Overseas Work Locations:</strong> <span>" . $jobseeker['overseas1'] . ", " . $jobseeker['overseas2'] . ", " . $jobseeker['overseas3'] . "</span></div>
                        <div class='field'><strong>Employment Type:</strong> <span>" . (formatBoolean($jobseeker['fulltime']) == 'Yes' ? 'Full-time' : '') . (formatBoolean($jobseeker['parttime']) == 'Yes' ? (formatBoolean($jobseeker['fulltime']) == 'Yes' ? ', ' : '') . 'Part-time' : '') . "</span></div>
                    </div>
                    
                    <div class='section'>
                        <h3>IV. LANGUAGE / DIALECT PROFICIENCY</h3>";
        
        // Language Proficiency Section
        if ($jobseeker['english_read'] || $jobseeker['english_write'] || $jobseeker['english_speak'] || $jobseeker['english_understand']) {
            $english_skills = [];
            if ($jobseeker['english_read']) $english_skills[] = 'Read';
            if ($jobseeker['english_write']) $english_skills[] = 'Write';
            if ($jobseeker['english_speak']) $english_skills[] = 'Speak';
            if ($jobseeker['english_understand']) $english_skills[] = 'Understand';
            $message .= "<div class='field'><strong>English:</strong> <span>" . implode(', ', $english_skills) . "</span></div>";
        }
        
        if ($jobseeker['filipino_read'] || $jobseeker['filipino_write'] || $jobseeker['filipino_speak'] || $jobseeker['filipino_understand']) {
            $filipino_skills = [];
            if ($jobseeker['filipino_read']) $filipino_skills[] = 'Read';
            if ($jobseeker['filipino_write']) $filipino_skills[] = 'Write';
            if ($jobseeker['filipino_speak']) $filipino_skills[] = 'Speak';
            if ($jobseeker['filipino_understand']) $filipino_skills[] = 'Understand';
            $message .= "<div class='field'><strong>Filipino:</strong> <span>" . implode(', ', $filipino_skills) . "</span></div>";
        }
        
        if ($jobseeker['mandarin_read'] || $jobseeker['mandarin_write'] || $jobseeker['mandarin_speak'] || $jobseeker['mandarin_understand']) {
            $mandarin_skills = [];
            if ($jobseeker['mandarin_read']) $mandarin_skills[] = 'Read';
            if ($jobseeker['mandarin_write']) $mandarin_skills[] = 'Write';
            if ($jobseeker['mandarin_speak']) $mandarin_skills[] = 'Speak';
            if ($jobseeker['mandarin_understand']) $mandarin_skills[] = 'Understand';
            $message .= "<div class='field'><strong>Mandarin:</strong> <span>" . implode(', ', $mandarin_skills) . "</span></div>";
        }
        
        if ($jobseeker['other_language'] && ($jobseeker['other_read'] || $jobseeker['other_write'] || $jobseeker['other_speak'] || $jobseeker['other_understand'])) {
            $other_skills = [];
            if ($jobseeker['other_read']) $other_skills[] = 'Read';
            if ($jobseeker['other_write']) $other_skills[] = 'Write';
            if ($jobseeker['other_speak']) $other_skills[] = 'Speak';
            if ($jobseeker['other_understand']) $other_skills[] = 'Understand';
            $message .= "<div class='field'><strong>" . $jobseeker['other_language'] . ":</strong> <span>" . implode(', ', $other_skills) . "</span></div>";
        }
        
        $message .= "
                    </div>
                    
                    <div class='section'>
                        <h3>V. EDUCATIONAL BACKGROUND</h3>
                        <div class='field'><strong>Currently in School:</strong> <span>" . $jobseeker['inschool'] . "</span></div>
                        <div class='field'><strong>Education Level:</strong> <span>" . $jobseeker['level'] . "</span></div>
                        <div class='field'><strong>Course/Program:</strong> <span>" . $jobseeker['course'] . "</span></div>
                        <div class='field'><strong>Year Graduated:</strong> <span>" . $jobseeker['year_graduated'] . "</span></div>
                        <div class='field'><strong>Level Reached:</strong> <span>" . $jobseeker['level_reached'] . "</span></div>
                        <div class='field'><strong>Last Attended:</strong> <span>" . $jobseeker['last_attended'] . "</span></div>
                    </div>
                    
                    <div class='section'>
                        <h3>VI. TECHNICAL/VOCATIONAL AND OTHER TRAINING</h3>";
        
        // Technical/Vocational Training Section
        if ($jobseeker['training_course_1']) {
            $message .= "<div class='field'><strong>Training Course 1:</strong> <span>" . $jobseeker['training_course_1'] . "</span></div>";
            if ($jobseeker['training_hours_1']) $message .= "<div class='field'><strong>Training Hours 1:</strong> <span>" . $jobseeker['training_hours_1'] . "</span></div>";
            if ($jobseeker['training_institution_1']) $message .= "<div class='field'><strong>Training Institution 1:</strong> <span>" . $jobseeker['training_institution_1'] . "</span></div>";
            if ($jobseeker['training_skills_1']) $message .= "<div class='field'><strong>Training Skills 1:</strong> <span>" . $jobseeker['training_skills_1'] . "</span></div>";
            if ($jobseeker['training_cert_1']) $message .= "<div class='field'><strong>Training Certificate 1:</strong> <span>" . $jobseeker['training_cert_1'] . "</span></div>";
        }
        
        if ($jobseeker['training_course_2']) {
            $message .= "<div class='field'><strong>Training Course 2:</strong> <span>" . $jobseeker['training_course_2'] . "</span></div>";
            if ($jobseeker['training_hours_2']) $message .= "<div class='field'><strong>Training Hours 2:</strong> <span>" . $jobseeker['training_hours_2'] . "</span></div>";
            if ($jobseeker['training_institution_2']) $message .= "<div class='field'><strong>Training Institution 2:</strong> <span>" . $jobseeker['training_institution_2'] . "</span></div>";
            if ($jobseeker['training_skills_2']) $message .= "<div class='field'><strong>Training Skills 2:</strong> <span>" . $jobseeker['training_skills_2'] . "</span></div>";
            if ($jobseeker['training_cert_2']) $message .= "<div class='field'><strong>Training Certificate 2:</strong> <span>" . $jobseeker['training_cert_2'] . "</span></div>";
        }
        
        if ($jobseeker['training_course_3']) {
            $message .= "<div class='field'><strong>Training Course 3:</strong> <span>" . $jobseeker['training_course_3'] . "</span></div>";
            if ($jobseeker['training_hours_3']) $message .= "<div class='field'><strong>Training Hours 3:</strong> <span>" . $jobseeker['training_hours_3'] . "</span></div>";
            if ($jobseeker['training_institution_3']) $message .= "<div class='field'><strong>Training Institution 3:</strong> <span>" . $jobseeker['training_institution_3'] . "</span></div>";
            if ($jobseeker['training_skills_3']) $message .= "<div class='field'><strong>Training Skills 3:</strong> <span>" . $jobseeker['training_skills_3'] . "</span></div>";
            if ($jobseeker['training_cert_3']) $message .= "<div class='field'><strong>Training Certificate 3:</strong> <span>" . $jobseeker['training_cert_3'] . "</span></div>";
        }
        
        $message .= "
                    </div>
                    
                    <div class='section'>
                        <h3>VII. ELIGIBILITY/PROFESSIONAL LICENSE</h3>";
        
        // Eligibility/Professional License Section
        if ($jobseeker['eligibility_1']) $message .= "<div class='field'><strong>Eligibility 1:</strong> <span>" . $jobseeker['eligibility_1'] . "</span></div>";
        if ($jobseeker['eligibility_date_1']) $message .= "<div class='field'><strong>Eligibility Date 1:</strong> <span>" . $jobseeker['eligibility_date_1'] . "</span></div>";
        if ($jobseeker['eligibility_2']) $message .= "<div class='field'><strong>Eligibility 2:</strong> <span>" . $jobseeker['eligibility_2'] . "</span></div>";
        if ($jobseeker['eligibility_date_2']) $message .= "<div class='field'><strong>Eligibility Date 2:</strong> <span>" . $jobseeker['eligibility_date_2'] . "</span></div>";
        if ($jobseeker['prc_1']) $message .= "<div class='field'><strong>PRC License 1:</strong> <span>" . $jobseeker['prc_1'] . "</span></div>";
        if ($jobseeker['prc_valid_1']) $message .= "<div class='field'><strong>PRC Valid Until 1:</strong> <span>" . $jobseeker['prc_valid_1'] . "</span></div>";
        if ($jobseeker['prc_2']) $message .= "<div class='field'><strong>PRC License 2:</strong> <span>" . $jobseeker['prc_2'] . "</span></div>";
        if ($jobseeker['prc_valid_2']) $message .= "<div class='field'><strong>PRC Valid Until 2:</strong> <span>" . $jobseeker['prc_valid_2'] . "</span></div>";
        
        $message .= "
                    </div>
                    
                    <div class='section'>
                        <h3>VIII. WORK EXPERIENCE</h3>";
        
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
                        <h3>IX. OTHER SKILLS ACQUIRED</h3>
                        <div class='field'>" . formatSkills($jobseeker) . "</div>
                    </div>";
        
        // Build absolute download URLs with HTTPS-aware scheme (supports reverse proxies).
        $scheme = 'http';
        if (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
        ) {
            $scheme = 'https';
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $scheme = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
        }
        $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['REQUEST_URI'])), '/');

        if ($jobseeker['resume_file']) {
            $resume_url = $scheme . "://" . $_SERVER['HTTP_HOST'] . $basePath . "/../uploads/resumes/" . $jobseeker['resume_file'];
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
        
        if ($jobseeker['esignature_file']) {
            $esignature_url = $scheme . "://" . $_SERVER['HTTP_HOST'] . $basePath . "/../uploads/esignatures/" . $jobseeker['esignature_file'];
            $message .= "
                    <div class='resume-section'>
                        <h3>✍️ E-Signature</h3>
                        <p>Download the jobseeker's electronic signature:</p>
                        <a href='" . $esignature_url . "' class='resume-link'>📥 Download E-Signature</a>
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
                $mail->addAddress($employer_email);
                
                // Content
                $mail->isHTML(true);
                $mail->CharSet = 'UTF-8';
                $mail->Subject = $subject;
                $mail->Body    = $message;
                
                $mail->send();
                echo json_encode(['success' => true, 'message' => 'Email sent successfully to ' . $employer_email]);
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Email could not be sent. Error: ' . $mail->ErrorInfo]);
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
