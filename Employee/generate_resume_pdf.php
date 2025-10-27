<?php
// Generate Resume PDF - NEW VERSION WITH SPECIFIC COLUMNS
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to client
ini_set('log_errors', 1);     // Log errors to error log

// Log the start of PDF generation
error_log("=== PDF GENERATION START ===");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'));

try {
    require_once 'session_check.php';
    error_log("Session check loaded successfully");
} catch (Exception $e) {
    error_log("Session check failed: " . $e->getMessage());
    http_response_code(500);
    exit('Session check failed');
}

try {
    require_once 'db.php';
    error_log("Database connection loaded successfully");
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    exit('Database connection failed');
}

try {
    require_once '../vendor/autoload.php';
    error_log("Vendor autoload loaded successfully");
} catch (Exception $e) {
    error_log("Vendor autoload failed: " . $e->getMessage());
    http_response_code(500);
    exit('Vendor autoload failed');
}

// Define TCPDF constants if not already defined
if (!defined('PDF_PAGE_ORIENTATION')) {
    define('PDF_PAGE_ORIENTATION', 'P');
}
if (!defined('PDF_UNIT')) {
    define('PDF_UNIT', 'mm');
}
if (!defined('PDF_PAGE_FORMAT')) {
    define('PDF_PAGE_FORMAT', 'A4');
}

error_log("TCPDF constants defined successfully");

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Not authenticated');
}

$userId = $_SESSION['user_id'];

// Handle both GET and POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Processing POST request");
    
    // Get JSON input with error handling
    $rawInput = file_get_contents('php://input');
    error_log("Raw input received: " . $rawInput);
    
    $input = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        http_response_code(400);
        exit('Invalid JSON input: ' . json_last_error_msg());
    }
    
    $resumeId = (int)($input['resume_id'] ?? 0);
    error_log("Resume ID extracted from POST: " . $resumeId);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    error_log("Processing GET request");
    
    $resumeId = (int)($_GET['resume_id'] ?? 0);
    error_log("Resume ID extracted from GET: " . $resumeId);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resume_id'])) {
    error_log("Processing POST form request");
    
    $resumeId = (int)($_POST['resume_id'] ?? 0);
    error_log("Resume ID extracted from POST form: " . $resumeId);
    
} else {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    exit('Method not allowed');
}

if ($resumeId <= 0) {
    error_log("Invalid resume ID: " . $resumeId);
    http_response_code(400);
    exit('Invalid resume ID');
}

try {
        // Get main resume data
        $stmt = $conn->prepare("SELECT * FROM resumes_new WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $resumeId, $userId);
    $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(404);
            exit('Resume not found');
        }
        
        $resume = $result->fetch_assoc();
    $stmt->close();
    
        // Debug: Log the resume data being processed
        error_log("PDF Generation Debug - Resume ID: " . $resumeId);
        error_log("PDF Generation Debug - Resume Name from DB: " . ($resume['resume_name'] ?? 'NULL'));
        error_log("PDF Generation Debug - Full Resume Data: " . print_r($resume, true));
        
        // Get work experience
        $workExpStmt = $conn->prepare("SELECT * FROM resume_work_experience WHERE resume_id = ? ORDER BY sort_order");
        $workExpStmt->bind_param("i", $resumeId);
        $workExpStmt->execute();
        $workExpResult = $workExpStmt->get_result();
        $workExperience = [];
        while ($exp = $workExpResult->fetch_assoc()) {
            $workExperience[] = $exp;
        }
        $workExpStmt->close();
        
        // Get education
        $eduStmt = $conn->prepare("SELECT * FROM resume_education WHERE resume_id = ? ORDER BY sort_order");
        $eduStmt->bind_param("i", $resumeId);
        $eduStmt->execute();
        $eduResult = $eduStmt->get_result();
        $education = [];
        while ($edu = $eduResult->fetch_assoc()) {
            $education[] = $edu;
        }
        $eduStmt->close();
        
        // Get certifications
        $certStmt = $conn->prepare("SELECT * FROM resume_certifications WHERE resume_id = ? ORDER BY sort_order");
        $certStmt->bind_param("i", $resumeId);
        $certStmt->execute();
        $certResult = $certStmt->get_result();
        $certifications = [];
        while ($cert = $certResult->fetch_assoc()) {
            $certifications[] = $cert;
        }
        $certStmt->close();
        
        // Combine all data
        $resume['work_experience'] = $workExperience;
        $resume['education'] = $education;
        $resume['certifications'] = $certifications;
        
        // Generate HTML
        $html = generateResumeHTML($resume);
        
        // Debug: Log HTML generation
        error_log("PDF Generation Debug - HTML Length: " . strlen($html));
        error_log("PDF Generation Debug - HTML Preview: " . substr($html, 0, 200) . "...");

        // Create PDF with error handling
        try {
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            error_log("PDF Generation Debug - TCPDF object created successfully");
        } catch (Exception $e) {
            error_log("PDF Generation Error - TCPDF creation failed: " . $e->getMessage());
            throw new Exception("Failed to create PDF object: " . $e->getMessage());
        }

// Set document information
$pdf->SetCreator('WorkConnect Resume Builder');
        $pdf->SetAuthor($resume['firstname'] . ' ' . $resume['lastname']);
$pdf->SetTitle($resume['resume_name']);
$pdf->SetSubject('Resume');

// Set margins
$pdf->SetMargins(15, 15, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);

// Add a page
$pdf->AddPage();

// Write HTML content
$pdf->writeHTML($html, true, false, true, false, '');

        // Generate filename - sanitize and ensure it's not empty
        $resumeName = trim($resume['resume_name']);
        if (empty($resumeName)) {
            $resumeName = $resume['firstname'] . '_' . $resume['lastname'] . '_Resume';
        }
        
        // Debug: Log the filename generation
        error_log("PDF Filename Debug - Resume Name: " . $resumeName);
        error_log("PDF Filename Debug - Resume Data: " . print_r($resume, true));
        
        // Sanitize filename - replace spaces and special characters
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $resumeName);
        $filename = preg_replace('/_+/', '_', $filename); // Replace multiple underscores with single
        $filename = trim($filename, '_'); // Remove leading/trailing underscores
        $filename .= '_' . time() . '.pdf'; // Add timestamp to force new download
        
        error_log("PDF Filename Debug - Final filename: " . $filename);
        
        // Generate PDF content first with error handling
        try {
            error_log("PDF Generation Debug - Starting PDF output generation");
            $pdfContent = $pdf->Output($filename, 'S'); // 'S' for string output
            error_log("PDF Generation Debug - PDF content generated successfully, size: " . strlen($pdfContent));
        } catch (Exception $e) {
            error_log("PDF Generation Error - PDF output failed: " . $e->getMessage());
            throw new Exception("Failed to generate PDF content: " . $e->getMessage());
        }
        
        // Set proper headers for download
        // Set headers to prevent caching
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output PDF content
        echo $pdfContent;
        
    } catch (Exception $e) {
        http_response_code(500);
        exit('Error generating PDF: ' . $e->getMessage());
    }

function generateResumeHTML($resume) {
    $profileImage = $resume['profile_image'];
    
    // Convert relative path to absolute path for TCPDF
    if (!empty($profileImage) && !file_exists($profileImage)) {
        $profileImage = __DIR__ . '/' . $profileImage;
    }
    
    // Debug: Log template ID and profile image
    error_log("PDF Generation Debug - Template ID: " . $resume['template_id']);
    error_log("PDF Generation Debug - Profile Image: " . $profileImage);
    error_log("PDF Generation Debug - Profile Image Exists: " . (file_exists($profileImage) ? 'YES' : 'NO'));
    error_log("PDF Generation Debug - TIMESTAMP: " . date('Y-m-d H:i:s'));
    
    // Generate HTML based on template
    switch ($resume['template_id']) {
        case 1:
            error_log("PDF Generation Debug - Using Classic Template");
            return generateClassicPDF($resume, $profileImage);
        case 2:
            error_log("PDF Generation Debug - Using Modern Template");
            return generateModernPDF($resume, $profileImage);
        case 3:
            error_log("PDF Generation Debug - Using Creative Template");
            return generateCreativePDF($resume, $profileImage);
        case 4:
            error_log("PDF Generation Debug - Using Two Column Template");
            return generateTwoColumnPDF($resume, $profileImage);
        default:
            error_log("PDF Generation Debug - Using Classic Template (Default)");
            return generateClassicPDF($resume, $profileImage);
    }
}

function generateTwoColumnPDF($resume, $profileImage) {
    $html = '
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: white;
            color: #333;
        }
        .two-column-container {
            width: 100%;
            min-height: auto;
        }
        .two-column-left {
            width: 35%;
            background: #ffd700;
            float: left;
            padding: 0;
            min-height: 100%;
        }
        .two-column-left-top {
            background: #ffd700;
            padding: 20px;
            text-align: center;
            height: 200px;
        }
        .two-column-left-bottom {
            background: white;
            padding: 15px;
        }
        .two-column-right {
            width: 65%;
            padding: 25px 30px;
            background: white;
            float: right;
            min-height: 100%;
        }
        .clearfix {
            clear: both;
        }
        .two-column-profile-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 15px;
            border: 3px solid white;
            display: block;
        }
        .two-column-contact {
            color: #333;
            font-size: 11px;
            line-height: 1.3;
        }
        .two-column-contact h3 {
            font-size: 12px;
            font-weight: bold;
            margin: 0 0 8px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .two-column-contact p {
            margin: 2px 0;
        }
        .two-column-section {
            margin-bottom: 20px;
        }
        .two-column-section h3 {
            font-size: 12px;
            font-weight: bold;
            margin: 0 0 8px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #333;
        }
        .two-column-section ul {
            margin: 0;
            padding-left: 12px;
        }
        .two-column-section li {
            font-size: 10px;
            margin-bottom: 2px;
            line-height: 1.2;
        }
        .two-column-name-header {
            width: 100%;
            background: white;
            padding: 20px 0;
            text-align: center;
            margin-bottom: 0;
        }
        .two-column-name {
            font-size: 28px;
            font-weight: bold;
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #333;
        }
        .two-column-title-line {
            width: 60px;
            height: 3px;
            background: #ffd700;
            margin: 0 auto;
        }
        .two-column-main-section {
            margin-bottom: 25px;
        }
        .two-column-main-section h2 {
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #333;
        }
        .two-column-main-section p {
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            color: #555;
        }
        .two-column-work-item {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .two-column-work-item:last-child {
            border-bottom: none;
        }
        .two-column-work-header {
            margin-bottom: 5px;
            overflow: hidden;
        }
        .two-column-work-title {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            margin: 0;
            float: left;
            width: 60%;
        }
        .two-column-work-company {
            font-size: 11px;
            font-weight: bold;
            color: #333;
            margin: 0 0 2px 0;
            float: left;
            width: 60%;
        }
        .two-column-work-dates {
            font-size: 9px;
            color: #666;
            font-weight: normal;
            float: right;
            width: 35%;
            text-align: right;
        }
        .two-column-work-description {
            font-size: 10px;
            line-height: 1.3;
            color: #555;
            margin: 5px 0 0 0;
            clear: both;
        }
        .clear {
            clear: both;
        }
    </style>
    
    <!-- Full Width Name Header -->
    <div class="two-column-name-header">
        <h1 class="two-column-name">' . htmlspecialchars($resume['firstname'] . ' ' . $resume['lastname']) . '</h1>
        <div class="two-column-title-line"></div>
    </div>
    
    <div class="two-column-container">
        <!-- Left Column -->
        <div class="two-column-left">
            <!-- Top Section with Photo -->
            <div class="two-column-left-top">
                ' . (!empty($profileImage) && file_exists($profileImage) ? '<img src="' . htmlspecialchars($profileImage) . '" alt="Profile Photo" class="two-column-profile-photo">' : '') . '
            </div>
            
            <!-- Bottom Section with Contact, Skills, Education -->
            <div class="two-column-left-bottom">
                <!-- Contact -->
                <div class="two-column-section">
                    <h3>CONTACT</h3>
                    <div class="two-column-contact">
                        ' . ($resume['location'] ? '<p>' . htmlspecialchars($resume['location']) . '</p>' : '') . '
                        ' . ($resume['phone'] ? '<p>Mobile: ' . htmlspecialchars($resume['phone']) . '</p>' : '') . '
                        ' . ($resume['email'] ? '<p>' . htmlspecialchars($resume['email']) . '</p>' : '') . '
                    </div>
                </div>
                
                <!-- Skills -->
                ' . ($resume['skills'] ? '
                    <div class="two-column-section">
                        <h3>SKILLS</h3>
                        <ul>
                            ' . implode('', array_map(function($skill) {
                                return '<li>' . htmlspecialchars(trim($skill)) . '</li>';
                            }, explode(',', $resume['skills']))) . '
                        </ul>
                    </div>
                ' : '') . '
                
                <!-- Education -->
                ' . (!empty($resume['education']) ? '
                    <div class="two-column-section">
                        <h3>EDUCATION</h3>
                        ' . implode('', array_map(function($edu) {
                            return '
                                <div style="margin-bottom: 12px;">
                                    ' . ($edu['graduation_year'] ? '<div style="font-size: 9px; color: #666; margin-bottom: 2px;">' . htmlspecialchars($edu['graduation_year']) . '</div>' : '') . '
                                    ' . ($edu['degree'] && $edu['field'] ? '<div style="font-size: 10px; font-weight: bold; margin-bottom: 1px;">' . htmlspecialchars($edu['degree']) . ': ' . htmlspecialchars($edu['field']) . '</div>' : '') . '
                                    ' . ($edu['school'] ? '<div style="font-size: 9px; color: #555;">' . htmlspecialchars($edu['school']) . '</div>' : '') . '
                                </div>
                            ';
                        }, $resume['education'])) . '
                    </div>
                ' : '') . '
                
                <!-- Languages -->
                ' . ($resume['languages'] ? '
                    <div class="two-column-section">
                        <h3>LANGUAGES</h3>
                        <ul>
                            ' . implode('', array_map(function($lang) {
                                return '<li>' . htmlspecialchars(trim($lang)) . '</li>';
                            }, explode(',', $resume['languages']))) . '
                        </ul>
                    </div>
                ' : '') . '
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="two-column-right">
            <!-- Professional Summary -->
            ' . ($resume['summary'] ? '
                <div class="two-column-main-section">
                    <h2>PROFESSIONAL SUMMARY</h2>
                    <p>' . htmlspecialchars($resume['summary']) . '</p>
                </div>
            ' : '') . '
            
            <!-- Work History -->
            ' . (!empty($resume['work_experience']) ? '
                <div class="two-column-main-section">
                    <h2>WORK HISTORY</h2>
                    ' . implode('', array_map(function($exp) {
                        return '
                            <div class="two-column-work-item">
                                <div class="two-column-work-header">
                                    <div>
                                        <div class="two-column-work-title">' . htmlspecialchars($exp['job_title']) . '</div>
                                        <div class="two-column-work-company">' . htmlspecialchars($exp['company']) . ($exp['location'] ? ', ' . htmlspecialchars($exp['location']) : '') . '</div>
                                    </div>
                                    <div class="two-column-work-dates">
                                        ' . ($exp['start_date'] ? htmlspecialchars($exp['start_date']) : '') . 
                                        ($exp['end_date'] ? ' - ' . htmlspecialchars($exp['end_date']) : ($exp['start_date'] ? ' - Current' : '')) . '
                                    </div>
                                </div>
                                ' . ($exp['description'] ? '<div class="two-column-work-description">' . htmlspecialchars($exp['description']) . '</div>' : '') . '
                                <div class="clear"></div>
                            </div>
                        ';
                    }, $resume['work_experience'])) . '
                </div>
            ' : '') . '
        </div>
    </div>
    <div class="clearfix"></div>';
    
    error_log("PDF Generation Debug - Two Column HTML Structure: " . substr($html, 0, 500) . "...");
    
    return $html;
}

function generateClassicPDF($resume, $profileImage) {
    
    $html = '
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: white;
            color: #333;
            font-size: 12px;
            line-height: 1.4;
        }
        .resume-container {
            width: 100%;
            background: white;
            padding: 20px;
        }
        .resume-header {
            margin-bottom: 20px;
            overflow: hidden;
        }
        .profile-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 2px solid #233a8b;
            float: left;
            margin-right: 15px;
            margin-top: 5px;
        }
        .header-content {
            float: left;
            width: 70%;
        }
        .resume-name {
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 3px 0;
            color: #233a8b;
        }
        .resume-title {
            font-size: 12px;
            font-weight: normal;
            margin: 0 0 8px 0;
            color: #666;
        }
        .contact-info {
            font-size: 11px;
            line-height: 1.3;
        }
        .contact-item {
            margin-bottom: 2px;
            color: #333;
        }
        .resume-section {
            margin-bottom: 15px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #233a8b;
            margin: 0 0 8px 0;
            padding-bottom: 2px;
            border-bottom: 1px solid #233a8b;
        }
        .summary-text {
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            margin: 0;
        }
        .experience-item, .education-item, .certification-item {
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }
        .experience-item:last-child, .education-item:last-child, .certification-item:last-child {
            border-bottom: none;
        }
        .item-header {
            margin-bottom: 3px;
            overflow: hidden;
        }
        .item-title {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            margin: 0;
            float: left;
            width: 60%;
        }
        .item-company, .item-school, .item-organization {
            font-size: 11px;
            font-weight: bold;
            color: #233a8b;
            margin: 0 0 2px 0;
            float: left;
            width: 60%;
        }
        .item-dates {
            font-size: 10px;
            color: #666;
            font-weight: normal;
            float: right;
            width: 35%;
            text-align: right;
        }
        .item-description {
            font-size: 10px;
            line-height: 1.3;
            color: #555;
            margin: 3px 0 0 0;
            clear: both;
        }
        .skills-list {
            font-size: 11px;
            line-height: 1.4;
        }
        .skill-item {
            display: inline-block;
            margin-right: 8px;
            margin-bottom: 3px;
            color: #333;
        }
        .certification-dates {
            font-size: 10px;
            color: #666;
            margin-top: 2px;
        }
        .clear {
            clear: both;
        }
    </style>
    
    <div class="resume-container">
        <!-- Header Section -->
        <div class="resume-header">
            ' . (!empty($profileImage) && file_exists($profileImage) ? '<img src="' . htmlspecialchars($profileImage) . '" alt="Profile Photo" class="profile-photo">' : '') . '
            <div class="header-content">
                <h1 class="resume-name">' . htmlspecialchars($resume['firstname'] . ' ' . $resume['lastname']) . '</h1>
                <div class="resume-title">Professional Resume</div>
                <div class="contact-info">
                    ' . ($resume['email'] ? '<div class="contact-item">' . htmlspecialchars($resume['email']) . '</div>' : '') . '
                    ' . ($resume['phone'] ? '<div class="contact-item">' . htmlspecialchars($resume['phone']) . '</div>' : '') . '
                    ' . ($resume['location'] ? '<div class="contact-item">' . htmlspecialchars($resume['location']) . '</div>' : '') . '
                </div>
            </div>
            <div class="clear"></div>
        </div>
        
        <!-- Body Section -->';
    
    // Professional Summary
    if ($resume['summary']) {
        $html .= '
            <div class="resume-section">
                <h2 class="section-title">Professional Summary</h2>
                <p class="summary-text">' . htmlspecialchars($resume['summary']) . '</p>
            </div>';
    }
    
    // Work Experience
    if (!empty($resume['work_experience'])) {
        $html .= '
            <div class="resume-section">
                <h2 class="section-title">Work Experience</h2>';
        
        foreach ($resume['work_experience'] as $exp) {
            if ($exp['job_title'] || $exp['company']) {
                $html .= '
                    <div class="experience-item">
                        <div class="item-header">
                            <h3 class="item-title">' . htmlspecialchars($exp['job_title']) . '</h3>
                            <h4 class="item-company">' . htmlspecialchars($exp['company']) . '</h4>
                            <div class="item-dates">
                                ' . ($exp['start_date'] ? htmlspecialchars($exp['start_date']) : '') . 
                                ($exp['end_date'] ? ' - ' . htmlspecialchars($exp['end_date']) : ($exp['start_date'] ? ' - Present' : '')) . '
                                ' . ($exp['location'] ? '<br>' . htmlspecialchars($exp['location']) : '') . '
                            </div>
                        </div>
                        ' . ($exp['description'] ? '<p class="item-description">' . htmlspecialchars($exp['description']) . '</p>' : '') . '
                        <div class="clear"></div>
                    </div>';
            }
        }
        
        $html .= '</div>';
    }
    
    // Education
    if (!empty($resume['education'])) {
        $html .= '
            <div class="resume-section">
                <h2 class="section-title">Education</h2>';
        
        foreach ($resume['education'] as $edu) {
            if ($edu['degree'] || $edu['field'] || $edu['school']) {
                $html .= '
                    <div class="education-item">
                        <div class="item-header">
                            <h3 class="item-title">' . htmlspecialchars($edu['degree']) . ' in ' . htmlspecialchars($edu['field']) . '</h3>
                            <h4 class="item-school">' . htmlspecialchars($edu['school']) . '</h4>
                            <div class="item-dates">
                                ' . ($edu['graduation_year'] ? 'Graduated: ' . htmlspecialchars($edu['graduation_year']) : '') . '
                                ' . ($edu['gpa'] ? ' | GPA: ' . htmlspecialchars($edu['gpa']) : '') . '
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>';
            }
        }
        
        $html .= '</div>';
    }
    
    // Skills
    if ($resume['skills']) {
        $skillsArray = array_filter(array_map('trim', explode(',', $resume['skills'])));
        $html .= '
            <div class="resume-section">
                <h2 class="section-title">Skills</h2>
                <div class="skills-list">
                    ' . implode('', array_map(function($skill) {
                        return '<span class="skill-item">' . htmlspecialchars($skill) . '</span>';
                    }, $skillsArray)) . '
                </div>
            </div>';
    }
    
    // Certifications
    if (!empty($resume['certifications'])) {
        $html .= '
            <div class="resume-section">
                <h2 class="section-title">Certifications</h2>';
        
        foreach ($resume['certifications'] as $cert) {
            if ($cert['name']) {
                $html .= '
                    <div class="certification-item">
                        <div class="item-header">
                            <h3 class="item-title">' . htmlspecialchars($cert['name']) . '</h3>
                            ' . ($cert['organization'] ? '<h4 class="item-organization">' . htmlspecialchars($cert['organization']) . '</h4>' : '') . '
                            <div class="certification-dates">
                                ' . ($cert['issue_date'] ? 'Issued: ' . htmlspecialchars($cert['issue_date']) : '') . '
                                ' . ($cert['expiry_date'] ? ' | Expires: ' . htmlspecialchars($cert['expiry_date']) : '') . '
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>';
            }
        }
        
        $html .= '</div>';
    }
    
    $html .= '
    </div>';
    
    return $html;
}

function generateModernPDF($resume, $profileImage) {
    $html = '
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f8f9fa;
            color: #333;
        }
        .modern-container {
            width: 100%;
            background: #f8f9fa;
        }
        .modern-header {
            background: white;
            padding: 30px;
            overflow: hidden;
            border-bottom: 1px solid #e9ecef;
        }
        .header-left {
            float: left;
            width: 60%;
        }
        .header-right {
            float: right;
            width: 35%;
        }
        .modern-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #233a8b;
            float: left;
            margin-right: 20px;
        }
        .modern-name {
            font-size: 24px;
            font-weight: bold;
            color: #233a8b;
            margin: 0 0 5px 0;
            padding-top: 10px;
        }
        .modern-title {
            font-size: 14px;
            color: #666;
            font-weight: normal;
            margin: 0 0 15px 0;
        }
        .contact-grid {
            font-size: 11px;
            line-height: 1.4;
        }
        .contact-item-modern {
            margin-bottom: 5px;
            color: #555;
        }
        .modern-body {
            padding: 30px;
        }
        .modern-section {
            background: white;
            padding: 25px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
        .modern-section-title {
            font-size: 16px;
            font-weight: bold;
            color: #233a8b;
            margin: 0 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #233a8b;
        }
        .modern-summary {
            font-size: 12px;
            line-height: 1.5;
            color: #444;
            margin: 0;
        }
        .modern-experience, .modern-education, .modern-certification {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .modern-experience:last-child, .modern-education:last-child, .modern-certification:last-child {
            border-bottom: none;
        }
        .modern-exp-header {
            margin-bottom: 5px;
            overflow: hidden;
        }
        .modern-job-title, .modern-degree, .modern-cert-name {
            font-size: 13px;
            font-weight: bold;
            color: #333;
            margin: 0;
            float: left;
            width: 60%;
        }
        .modern-company, .modern-school, .modern-cert-org {
            font-size: 12px;
            color: #233a8b;
            font-weight: bold;
            margin: 0 0 3px 0;
            float: left;
            width: 60%;
        }
        .modern-exp-dates, .modern-edu-dates, .modern-cert-dates {
            font-size: 10px;
            color: #666;
            margin-bottom: 8px;
            float: right;
            width: 35%;
            text-align: right;
        }
        .modern-description {
            font-size: 11px;
            line-height: 1.4;
            color: #555;
            margin: 5px 0 0 0;
            clear: both;
        }
        .modern-skills {
            font-size: 11px;
            line-height: 1.4;
        }
        .modern-skill {
            background: #233a8b;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 10px;
            font-weight: normal;
            margin-right: 8px;
            margin-bottom: 5px;
            display: inline-block;
        }
        .clear {
            clear: both;
        }
    </style>
    
    <div class="modern-container">
        <!-- Modern Header -->
        <div class="modern-header">
            <div class="header-left">
                ' . (!empty($profileImage) && file_exists($profileImage) ? '<img src="' . htmlspecialchars($profileImage) . '" alt="Profile Photo" class="modern-photo">' : '') . '
                <div class="header-info">
                    <h1 class="modern-name">' . htmlspecialchars($resume['firstname'] . ' ' . $resume['lastname']) . '</h1>
                    <div class="modern-title">Professional Resume</div>
                </div>
            </div>
            <div class="header-right">
                <div class="contact-grid">
                    ' . ($resume['email'] ? '<div class="contact-item-modern">📧 ' . htmlspecialchars($resume['email']) . '</div>' : '') . '
                    ' . ($resume['phone'] ? '<div class="contact-item-modern">📞 ' . htmlspecialchars($resume['phone']) . '</div>' : '') . '
                    ' . ($resume['location'] ? '<div class="contact-item-modern">📍 ' . htmlspecialchars($resume['location']) . '</div>' : '') . '
                    ' . ($resume['linkedin'] ? '<div class="contact-item-modern">💼 LinkedIn Profile</div>' : '') . '
                </div>
            </div>
        </div>
        
        <!-- Modern Body -->
        <div class="modern-body">';
    
    // Professional Summary
    if ($resume['summary']) {
        $html .= '
            <div class="modern-section">
                <h2 class="modern-section-title">Professional Summary</h2>
                <p class="modern-summary">' . htmlspecialchars($resume['summary']) . '</p>
            </div>';
    }
    
    // Work Experience
    if (!empty($resume['work_experience'])) {
        $html .= '
            <div class="modern-section">
                <h2 class="modern-section-title">Work Experience</h2>';
        
        foreach ($resume['work_experience'] as $exp) {
            if ($exp['job_title'] && $exp['company']) {
                $html .= '
                    <div class="modern-experience">
                        <div class="modern-exp-header">
                            <h3 class="modern-job-title">' . htmlspecialchars($exp['job_title']) . '</h3>
                            <span class="modern-company">' . htmlspecialchars($exp['company']) . '</span>
                            <div class="modern-exp-dates">
                                ' . ($exp['start_date'] ? htmlspecialchars($exp['start_date']) : '') . 
                                ($exp['end_date'] ? ' - ' . htmlspecialchars($exp['end_date']) : ($exp['start_date'] ? ' - Present' : '')) . '
                                ' . ($exp['location'] ? '<br>' . htmlspecialchars($exp['location']) : '') . '
                            </div>
                        </div>
                        ' . ($exp['description'] ? '<p class="modern-description">' . htmlspecialchars($exp['description']) . '</p>' : '') . '
                        <div class="clear"></div>
                    </div>';
            }
        }
        
        $html .= '</div>';
    }
    
    // Education
    if (!empty($resume['education'])) {
        $html .= '
            <div class="modern-section">
                <h2 class="modern-section-title">Education</h2>';
        
        foreach ($resume['education'] as $edu) {
            if ($edu['degree'] && $edu['field'] && $edu['school']) {
                $html .= '
                    <div class="modern-education">
                        <h3 class="modern-degree">' . htmlspecialchars($edu['degree']) . ' in ' . htmlspecialchars($edu['field']) . '</h3>
                        <span class="modern-school">' . htmlspecialchars($edu['school']) . '</span>
                        <div class="modern-edu-dates">
                            ' . ($edu['graduation_year'] ? 'Graduated: ' . htmlspecialchars($edu['graduation_year']) : '') . '
                            ' . ($edu['gpa'] ? ' | GPA: ' . htmlspecialchars($edu['gpa']) : '') . '
                        </div>
                        <div class="clear"></div>
                    </div>';
            }
        }
        
        $html .= '</div>';
    }
    
    // Skills
    if ($resume['skills']) {
        $skillsArray = array_filter(array_map('trim', explode(',', $resume['skills'])));
        $html .= '
            <div class="modern-section">
                <h2 class="modern-section-title">Skills</h2>
                <div class="modern-skills">
                    ' . implode('', array_map(function($skill) {
                        return '<span class="modern-skill">' . htmlspecialchars($skill) . '</span>';
                    }, $skillsArray)) . '
                </div>
            </div>';
    }
    
    // Certifications
    if (!empty($resume['certifications'])) {
        $html .= '
            <div class="modern-section">
                <h2 class="modern-section-title">Certifications</h2>';
        
        foreach ($resume['certifications'] as $cert) {
            if ($cert['name']) {
                $html .= '
                    <div class="modern-certification">
                        <h3 class="modern-cert-name">' . htmlspecialchars($cert['name']) . '</h3>
                        ' . ($cert['organization'] ? '<span class="modern-cert-org">' . htmlspecialchars($cert['organization']) . '</span>' : '') . '
                        <div class="modern-cert-dates">
                            ' . ($cert['issue_date'] ? 'Issued: ' . htmlspecialchars($cert['issue_date']) : '') . '
                            ' . ($cert['expiry_date'] ? ' | Expires: ' . htmlspecialchars($cert['expiry_date']) : '') . '
                        </div>
                        <div class="clear"></div>
                    </div>';
            }
        }
        
        $html .= '</div>';
    }
    
    $html .= '
        </div>
    </div>';
    
    return $html;
}

function generateCreativePDF($resume, $profileImage) {
    $html = '
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #667eea;
            color: white;
        }
        .creative-container {
            width: 100%;
            background: #667eea;
        }
        .creative-header {
            background: rgba(255,255,255,0.1);
            padding: 30px;
            overflow: hidden;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        .creative-left {
            float: left;
            width: 70%;
        }
        .creative-right {
            float: right;
            width: 25%;
        }
        .creative-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.3);
            object-fit: cover;
            float: right;
            margin-top: 10px;
        }
        .creative-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0 0 5px 0;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        .creative-title {
            font-size: 14px;
            font-weight: normal;
            margin: 0 0 15px 0;
            opacity: 0.9;
        }
        .creative-contact {
            font-size: 11px;
            line-height: 1.4;
        }
        .creative-contact-item {
            margin-bottom: 5px;
            opacity: 0.9;
        }
        .creative-body {
            padding: 30px;
        }
        .creative-section {
            background: rgba(255,255,255,0.1);
            padding: 25px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .creative-section-title {
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid rgba(255,255,255,0.3);
        }
        .creative-summary {
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
            opacity: 0.9;
        }
        .creative-experience, .creative-education, .creative-certification {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        .creative-experience:last-child, .creative-education:last-child, .creative-certification:last-child {
            border-bottom: none;
        }
        .creative-exp-header {
            margin-bottom: 5px;
            overflow: hidden;
        }
        .creative-job-title, .creative-degree, .creative-cert-name {
            font-size: 13px;
            font-weight: bold;
            margin: 0;
            float: left;
            width: 60%;
        }
        .creative-company, .creative-school, .creative-cert-org {
            font-size: 12px;
            opacity: 0.8;
            font-weight: bold;
            margin: 0 0 3px 0;
            float: left;
            width: 60%;
        }
        .creative-exp-dates, .creative-edu-dates, .creative-cert-dates {
            font-size: 10px;
            opacity: 0.7;
            margin-bottom: 8px;
            float: right;
            width: 35%;
            text-align: right;
        }
        .creative-description {
            font-size: 11px;
            line-height: 1.4;
            margin: 5px 0 0 0;
            opacity: 0.9;
            clear: both;
        }
        .creative-skills {
            font-size: 11px;
            line-height: 1.4;
        }
        .creative-skill {
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 10px;
            font-weight: normal;
            margin-right: 8px;
            margin-bottom: 5px;
            display: inline-block;
            border: 1px solid rgba(255,255,255,0.3);
        }
        .clear {
            clear: both;
        }
    </style>
    
    <div class="creative-container">
        <!-- Creative Header -->
        <div class="creative-header">
            <div class="creative-left">
                <div class="creative-info">
                    <h1 class="creative-name">' . htmlspecialchars($resume['firstname'] . ' ' . $resume['lastname']) . '</h1>
                    <div class="creative-title">Professional Resume</div>
                    <div class="creative-contact">
                        ' . ($resume['email'] ? '<div class="creative-contact-item">📧 ' . htmlspecialchars($resume['email']) . '</div>' : '') . '
                        ' . ($resume['phone'] ? '<div class="creative-contact-item">📞 ' . htmlspecialchars($resume['phone']) . '</div>' : '') . '
                        ' . ($resume['location'] ? '<div class="creative-contact-item">📍 ' . htmlspecialchars($resume['location']) . '</div>' : '') . '
                        ' . ($resume['linkedin'] ? '<div class="creative-contact-item">💼 LinkedIn Profile</div>' : '') . '
                    </div>
                </div>
            </div>
            <div class="creative-right">
                ' . (!empty($profileImage) && file_exists($profileImage) ? '<img src="' . htmlspecialchars($profileImage) . '" alt="Profile Photo" class="creative-photo">' : '') . '
            </div>
        </div>
        
        <!-- Creative Body -->
        <div class="creative-body">';
    
    // Professional Summary
    if ($resume['summary']) {
        $html .= '
            <div class="creative-section">
                <h2 class="creative-section-title">Professional Summary</h2>
                <p class="creative-summary">' . htmlspecialchars($resume['summary']) . '</p>
            </div>';
    }
    
    // Work Experience
    if (!empty($resume['work_experience'])) {
        $html .= '
            <div class="creative-section">
                <h2 class="creative-section-title">Work Experience</h2>';
        
        foreach ($resume['work_experience'] as $exp) {
            if ($exp['job_title'] && $exp['company']) {
                $html .= '
                    <div class="creative-experience">
                        <div class="creative-exp-header">
                            <h3 class="creative-job-title">' . htmlspecialchars($exp['job_title']) . '</h3>
                            <span class="creative-company">' . htmlspecialchars($exp['company']) . '</span>
                            <div class="creative-exp-dates">
                                ' . ($exp['start_date'] ? htmlspecialchars($exp['start_date']) : '') . 
                                ($exp['end_date'] ? ' - ' . htmlspecialchars($exp['end_date']) : ($exp['start_date'] ? ' - Present' : '')) . '
                                ' . ($exp['location'] ? '<br>' . htmlspecialchars($exp['location']) : '') . '
                            </div>
                        </div>
                        ' . ($exp['description'] ? '<p class="creative-description">' . htmlspecialchars($exp['description']) . '</p>' : '') . '
                        <div class="clear"></div>
                    </div>';
            }
        }
        
        $html .= '</div>';
    }
    
    // Education
    if (!empty($resume['education'])) {
        $html .= '
            <div class="creative-section">
                <h2 class="creative-section-title">Education</h2>';
        
        foreach ($resume['education'] as $edu) {
            if ($edu['degree'] && $edu['field'] && $edu['school']) {
                $html .= '
                    <div class="creative-education">
                        <h3 class="creative-degree">' . htmlspecialchars($edu['degree']) . ' in ' . htmlspecialchars($edu['field']) . '</h3>
                        <span class="creative-school">' . htmlspecialchars($edu['school']) . '</span>
                        <div class="creative-edu-dates">
                            ' . ($edu['graduation_year'] ? 'Graduated: ' . htmlspecialchars($edu['graduation_year']) : '') . '
                            ' . ($edu['gpa'] ? ' | GPA: ' . htmlspecialchars($edu['gpa']) : '') . '
                        </div>
                        <div class="clear"></div>
                    </div>';
            }
        }
        
        $html .= '</div>';
    }
    
    // Skills
    if ($resume['skills']) {
        $skillsArray = array_filter(array_map('trim', explode(',', $resume['skills'])));
        $html .= '
            <div class="creative-section">
                <h2 class="creative-section-title">Skills</h2>
                <div class="creative-skills">
                    ' . implode('', array_map(function($skill) {
                        return '<span class="creative-skill">' . htmlspecialchars($skill) . '</span>';
                    }, $skillsArray)) . '
                </div>
            </div>';
    }
    
    // Certifications
    if (!empty($resume['certifications'])) {
        $html .= '
            <div class="creative-section">
                <h2 class="creative-section-title">Certifications</h2>';
        
        foreach ($resume['certifications'] as $cert) {
            if ($cert['name']) {
                $html .= '
                    <div class="creative-certification">
                        <h3 class="creative-cert-name">' . htmlspecialchars($cert['name']) . '</h3>
                        ' . ($cert['organization'] ? '<span class="creative-cert-org">' . htmlspecialchars($cert['organization']) . '</span>' : '') . '
                        <div class="creative-cert-dates">
                            ' . ($cert['issue_date'] ? 'Issued: ' . htmlspecialchars($cert['issue_date']) : '') . '
                            ' . ($cert['expiry_date'] ? ' | Expires: ' . htmlspecialchars($cert['expiry_date']) : '') . '
                        </div>
                        <div class="clear"></div>
                    </div>';
            }
        }
        
        $html .= '</div>';
    }
    
    $html .= '
        </div>
    </div>';
    
    return $html;
}

$conn->close();
?>
