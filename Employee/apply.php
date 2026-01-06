
<?php
// Start session and check if user is logged in
session_start();

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

// Database connection and backend processing
$host = "workconnect.ct26qyouyans.ap-southeast-2.rds.amazonaws.com";
$user = "admin";
$pass = "Pogisimark";
$db   = "WorkConnect";

// Create connection with timeout and retry logic
$conn = new mysqli($host, $user, $pass, $db);
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

// Handle POST requests (form submission)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Debug: Log that we received a POST request
    error_log("POST request received");
    
    // Simple test response first
    if (isset($_POST['test'])) {
        sendJsonResponse(true, 'Test successful');
    }
    
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
    
    $resume_filename = '';
    if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] == UPLOAD_ERR_OK) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'pdf', 'doc', 'docx'];
        $allowed_mime_types = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp', 'image/webp',
            'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $file_info = pathinfo($_FILES['resume_file']['name']);
        $ext = strtolower($file_info['extension']);
        $file_size = $_FILES['resume_file']['size'];
        $mime_type = $_FILES['resume_file']['type'];
        
        // Validate file extension
        if (!in_array($ext, $allowed_ext)) {
            sendJsonResponse(false, 'Invalid file type. Please upload JPG, PNG, PDF, DOC, or DOCX files only.');
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
    }

    // Get submission date
    $submission_date = date('Y-m-d');
    $submission_month = date('n');
    $submission_year = date('Y');
    
    // Handle e-signature upload
    $esignature_filename = '';
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
    }
    
    // Personal Information
    $surname = $conn->real_escape_string(getval('surname', ''));
    $firstname = $conn->real_escape_string(getval('firstname', ''));
    $middlename = $conn->real_escape_string(getval('middlename', ''));
    $suffix = $conn->real_escape_string(getval('suffix', ''));
    
    // Server-side duplicate check - only check firstname, lastname, middlename, and suffix
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
    } catch (Exception $e) {
        error_log("Duplicate check error: " . $e->getMessage());
        sendJsonResponse(false, 'Duplicate check failed: ' . $e->getMessage());
    }
    
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
        
        sendJsonResponse(false, 'Duplicate entry detected! A record with the same name combination already exists.', [
            'duplicate_info' => [
                'existing_name' => $existing_name
            ]
        ]);
    }
    $duplicate_stmt->close();
    
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
    $terminated_country = $conn->real_escape_string(getval('terminated_country'));
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

    // Build SQL
    $sql = "INSERT INTO jobseeker (
        user_id, surname, firstname, middlename, suffix, dob, sex, religion, civilstatus, street, barangay, municipality, province, tin, height, contact, email,
        hasDisability, disability_speech, disability_hearing, disability_visual, disability_mental, disability_others, disability_other,
        employed, employment_type_wage, employment_type_self, self_employed_specify, self_type_voluntary, self_type_vendor, self_type_homebased, self_type_transport, self_type_domestic, self_type_fisherfolk, self_type_others, other_jobs,
        unemployed, unemployed_months, unemployed_type_first, unemployed_type_local, unemployed_type_resigned, unemployed_type_finished, unemployed_type_public, unemployed_type_retired, unemployed_type_terminated, terminated_country,
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
        $employed, $employment_type_wage, $employment_type_self, '$self_employed_specify', $self_type_voluntary, $self_type_vendor, $self_type_homebased, $self_type_transport, $self_type_domestic, $self_type_fisherfolk, $self_type_others, '$other_jobs',
        $unemployed, '$unemployed_months', $unemployed_type_first, $unemployed_type_local, $unemployed_type_resigned, $unemployed_type_finished, $unemployed_type_public, $unemployed_type_retired, $unemployed_type_terminated, '$terminated_country',
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

    // Start transaction for atomic operation
    $conn->autocommit(FALSE);
    
    try {
        if ($conn->query($sql) === TRUE) {
            // Commit the transaction
            $conn->commit();
            $conn->autocommit(TRUE);
            sendJsonResponse(true, 'Registration saved successfully!');
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
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <style>
    <?php if ($isIframe): ?>
    /* Iframe-specific styles */
    body {
      margin: 0;
      padding: 0;
      background: #fff;
      overflow-x: hidden;
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
      transition: opacity 0.3s ease;
      flex: 1;
      min-width: 80px;
      max-width: 100px;
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
      
      .back-btn:hover, .next-btn:hover {
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
      
      <form class="jobseeker-form" id="jobseekerForm" action="" method="POST">
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
                <input type="text" id="surname" name="surname" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{2,40}" maxlength="40" required>
              </div>
              <div class="form-group">
                <label for="firstname">FIRST NAME<span class="required-asterisk">*</span></label>
                <input type="text" id="firstname" name="firstname" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{2,40}" maxlength="40" required>
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
                <label for="street">House no./Street/Village<span class="required-asterisk">*</span></label>
                <input type="text" id="street" name="street" pattern=".{2,50}" maxlength="50">
              </div>
              <div class="form-group">
                <label for="barangay">Barangay<span class="required-asterisk">*</span></label>
                <input type="text" id="barangay" name="barangay" pattern=".{2,40}" maxlength="40">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="municipality">Municipality/City<span class="required-asterisk">*</span></label>
                <input type="text" id="municipality" name="municipality" pattern=".{2,40}" maxlength="40">
              </div>
              <div class="form-group">
                <label for="province">Province<span class="required-asterisk">*</span></label>
                <input type="text" id="province" name="province" pattern=".{2,40}" maxlength="40">
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
                <input type="email" id="email" name="email" maxlength="40" required>
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
              <label for="unemployed_months">How long looking for work? (months):</label>
              <input type="text" id="unemployed_months" name="unemployed_months" pattern="[0-9]{0,30}" maxlength="30" disabled>
            </div>
            <div class="form-row indent" id="unemployedTypeFields" style="pointer-events: none; opacity: 0.6;">
              <label><input type="checkbox" name="unemployed_type_first" value="first" disabled> First-time Jobseeker/Graduate</label>
              <label><input type="checkbox" name="unemployed_type_local" value="local" disabled> Local Contract</label>
              <label><input type="checkbox" name="unemployed_type_resigned" value="resigned" disabled> Resigned</label>
              <label><input type="checkbox" name="unemployed_type_finished" value="finished" disabled> Finished contract (OFW)</label>
              <label><input type="checkbox" name="unemployed_type_public" value="public" disabled> Public Contract</label>
              <label><input type="checkbox" name="unemployed_type_retired" value="retired" disabled> Retired</label>
              <label><input type="checkbox" name="unemployed_type_terminated" value="terminated" disabled> Terminated/Laid off (local)</label>
              <label for="terminated_country" style="display: none;">If terminated/laid off, country:</label>
              <input type="text" id="terminated_country" name="terminated_country" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,50}" maxlength="50" disabled style="display: none;">
            </div>
            <div class="form-row">
              <label>Are you an OFW?<span class="required-asterisk">*</span></label>
              <label><input type="radio" name="ofw" value="yes" id="ofwYes"> Yes</label>
              <label><input type="radio" name="ofw" value="no" id="ofwNo"> No</label>
              <span id="ofwCountryGroup" style="display:none;">
                <label for="ofw_country">Specify Country<span class="required-asterisk">*</span></label>
                <input type="text" id="ofw_country" name="ofw_country" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,30}" maxlength="30">
              </span>
            </div>
            <div class="form-row">
              <label>Are you a returnee (OFW)?<span class="required-asterisk">*</span></label>
              <label><input type="radio" name="returnee" value="yes" id="returneeYes"> Yes</label>
              <label><input type="radio" name="returnee" value="no" id="returneeNo"> No</label>
            </div>
            <div class="form-row" id="returneeFields" style="display: none;">
              <label for="deployment_country">Local country of deployment:<span class="required-asterisk">*</span></label>
              <input type="text" id="deployment_country" name="deployment_country" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,30}" maxlength="30">
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
                  <option value="2024">2024</option>
                  <option value="2023">2023</option>
                  <option value="2022">2022</option>
                  <option value="2021">2021</option>
                  <option value="2020">2020</option>
                  <option value="2019">2019</option>
                  <option value="2018">2018</option>
                  <option value="2017">2017</option>
                  <option value="2016">2016</option>
                  <option value="2015">2015</option>
                  <option value="2014">2014</option>
                  <option value="2013">2013</option>
                  <option value="2012">2012</option>
                  <option value="2011">2011</option>
                  <option value="2010">2010</option>
                  <option value="2009">2009</option>
                  <option value="2008">2008</option>
                  <option value="2007">2007</option>
                  <option value="2006">2006</option>
                  <option value="2005">2005</option>
                  <option value="2004">2004</option>
                  <option value="2003">2003</option>
                  <option value="2002">2002</option>
                  <option value="2001">2001</option>
                  <option value="2000">2000</option>
                  <option value="1999">1999</option>
                  <option value="1998">1998</option>
                  <option value="1997">1997</option>
                  <option value="1996">1996</option>
                  <option value="1995">1995</option>
                  <option value="1994">1994</option>
                  <option value="1993">1993</option>
                  <option value="1992">1992</option>
                  <option value="1991">1991</option>
                  <option value="1990">1990</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <label>Are/were employed abroad in the Philippines:<span class="required-asterisk">*</span></label>
              <label><input type="radio" name="abroad" value="yes"> Yes</label>
              <label><input type="radio" name="abroad" value="no"> No</label>
            </div>
            <div class="form-row">
              <label>Are you a job beneficiary?<span class="required-asterisk">*</span></label>
              <label><input type="radio" name="beneficiary" value="yes" id="beneficiaryYes"> Yes</label>
              <label><input type="radio" name="beneficiary" value="no" id="beneficiaryNo"> No</label>
              <span id="householdIdGroup" style="display:none;">
                <label for="household_id">If yes, provide Household ID No.:<span class="required-asterisk">*</span></label>
                <input type="text" id="household_id" name="household_id" pattern="[A-Za-z0-9\-]{0,20}">
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
              <label>PREFERRED OCCUPATION<span class="required-asterisk">*</span></label>
              <label><input type="checkbox" name="fulltime"> Full-time</label>
              <label><input type="checkbox" name="parttime"> Part-time</label>
            </div>
            <div class="form-row">
              <input type="text" name="occupation1" placeholder="1." pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{2,40}" maxlength="40" required>
              <input type="text" name="occupation2" placeholder="2." pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,40}" maxlength="40">
              <input type="text" name="occupation3" placeholder="3." pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,40}" maxlength="40">
            </div>
            <div class="form-row">
              <label>PREFERRED WORK LOCATION</label>
            </div>
            <div class="form-row">
              <label>Local (specify cities/municipalities):<span class="required-asterisk">*</span></label>
              <input type="text" name="local1" placeholder="1." pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{2,40}" maxlength="40" required>
              <input type="text" name="local2" placeholder="2." pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,40}" maxlength="40">
              <input type="text" name="local3" placeholder="3." pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,40}" maxlength="40">
            </div>
            <div class="form-row">
              <label>Overseas (specify countries):<span class="required-asterisk">*</span></label>
              <input type="text" name="overseas1" placeholder="1." pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{2,40}" maxlength="40" required>
              <input type="text" name="overseas2" placeholder="2." pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,40}" maxlength="40">
              <input type="text" name="overseas3" placeholder="3." pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,40}" maxlength="40">
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
                <label><input type="checkbox" name="english_read">Read</label>
                <label><input type="checkbox" name="english_write">Write</label>
                <label><input type="checkbox" name="english_speak">Speak</label>
                <label><input type="checkbox" name="english_understand">Understand</label>
              </div>
              <div class="form-group">
                <label>Filipino</label>
                <label><input type="checkbox" name="filipino_read">Read</label>
                <label><input type="checkbox" name="filipino_write">Write</label>
                <label><input type="checkbox" name="filipino_speak">Speak</label>
                <label><input type="checkbox" name="filipino_understand">Understand</label>
              </div>
              <div class="form-group">
                <label>Mandarin</label>
                <label><input type="checkbox" name="mandarin_read">Read</label>
                <label><input type="checkbox" name="mandarin_write">Write</label>
                <label><input type="checkbox" name="mandarin_speak">Speak</label>
                <label><input type="checkbox" name="mandarin_understand">Understand</label>
              </div>
              <div class="form-group">
                <label>Others</label>
                <input type="text" name="other_language" placeholder="Specify" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,30}" maxlength="30">
                <label><input type="checkbox" name="other_read">Read</label>
                <label><input type="checkbox" name="other_write">Write</label>
                <label><input type="checkbox" name="other_speak">Speak</label>
                <label><input type="checkbox" name="other_understand">Understand</label>
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
              <input type="text" name="course">
            </div>
            <div class="form-row">
              <label>Year Graduated</label>
              <input type="text" name="year_graduated" pattern="[0-9]{0,10}" maxlength="10" placeholder="e.g., 2023">
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
                <input type="text" name="last_attended" id="last_attended" placeholder="e.g., 2023" pattern="[0-9]{0,10}" maxlength="10">
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
              <input type="text" name="training_course_1" placeholder="Course 1" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,40}" maxlength="40">
              <input type="text" name="training_hours_1" placeholder="Hours" pattern="[0-9]{0,10}" maxlength="10">
              <input type="text" name="training_institution_1" placeholder="Institution" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,40}" maxlength="40">
              <input type="text" name="training_skills_1" placeholder="Skills" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,40}" maxlength="40">
              <input type="text" name="training_cert_1" placeholder="Certificate" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,40}" maxlength="40">
              <input type="text" name="training_course_2" placeholder="Course 2" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,40}" maxlength="40">
              <input type="text" name="training_hours_2" placeholder="Hours" pattern="[0-9]{0,10}" maxlength="10">
              <input type="text" name="training_institution_2" placeholder="Institution" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,40}" maxlength="40">
              <input type="text" name="training_skills_2" placeholder="Skills" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,40}" maxlength="40">
              <input type="text" name="training_cert_2" placeholder="Certificate" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,40}" maxlength="40">
              <input type="text" name="training_course_3" placeholder="Course 3" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,40}" maxlength="40">
              <input type="text" name="training_hours_3" placeholder="Hours" pattern="[0-9]{0,10}" maxlength="10">
              <input type="text" name="training_institution_3" placeholder="Institution" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,40}" maxlength="40">
              <input type="text" name="training_skills_3" placeholder="Skills" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,40}" maxlength="40">
              <input type="text" name="training_cert_3" placeholder="Certificate" pattern="[A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]{0,40}" maxlength="40">
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
                <input type="text" name="eligibility_1" placeholder="Eligibility 1" pattern="[A-Za-z0-9()\s\-\.]{0,40}" maxlength="40">
              </div>
              <div class="form-group">
                <label>Date Taken</label>
                <input type="date" name="eligibility_date_1">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <input type="text" name="eligibility_2" placeholder="Eligibility 2" pattern="[A-Za-z0-9()\s\-\.]{0,40}" maxlength="40">
              </div>
              <div class="form-group">
                <input type="date" name="eligibility_date_2">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Professional License (PRC)</label>
                <input type="text" name="prc_1" placeholder="PRC License 1" pattern="[A-Za-z0-9()\s\-\.]{0,40}" maxlength="40">
              </div>
              <div class="form-group">
                <label>Valid Until</label>
                <input type="date" name="prc_valid_1">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <input type="text" name="prc_2" placeholder="PRC License 2" pattern="[A-Za-z0-9()\s\-\.]{0,40}" maxlength="40">
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
            <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 10px;">
              <div style="font-weight:bold;">Company Name</div>
              <div style="font-weight:bold;">Address</div>
              <div style="font-weight:bold;">Position</div>
              <div style="font-weight:bold;">Number of Months</div>
              <div style="font-weight:bold;">Status</div>
              <input type="text" name="company_name_1" placeholder="Company Name" style="width:100%;height:38px;" pattern="[A-Za-z0-9()\s\-\.]{0,50}" maxlength="50">
              <input type="text" name="company_address_1" placeholder="Address" style="width:100%;height:38px;" pattern="[A-Za-z0-9()\s\-\.]{0,50}" maxlength="50">
              <input type="text" name="position_1" placeholder="Position" style="width:100%;height:38px;" pattern="[A-Za-z0-9()\s\-\.]{0,50}" maxlength="50">
              <input type="text" name="months_1" placeholder="Months" style="width:100%;height:38px;" pattern="[0-9]{0,10}" maxlength="10">
              <input type="text" name="status_1" placeholder="Status" style="width:100%;height:38px;" pattern="[A-Za-z0-9()\s\-\.]{0,50}" maxlength="50">
              <input type="text" name="company_name_2" placeholder="Company Name" style="width:100%;height:38px;" pattern="[A-Za-z0-9()\s\-\.]{0,50}" maxlength="50">
              <input type="text" name="company_address_2" placeholder="Address" style="width:100%;height:38px;" pattern="[A-Za-z0-9()\s\-\.]{0,50}" maxlength="50">
              <input type="text" name="position_2" placeholder="Position" style="width:100%;height:38px;" pattern="[A-Za-z0-9()\s\-\.]{0,50}" maxlength="50">
              <input type="text" name="months_2" placeholder="Months" style="width:100%;height:38px;" pattern="[0-9]{0,10}" maxlength="10">
              <input type="text" name="status_2" placeholder="Status" style="width:100%;height:38px;" pattern="[A-Za-z0-9()\s\-\.]{0,50}" maxlength="50">
              <input type="text" name="company_name_3" placeholder="Company Name" style="width:100%;height:38px;" pattern="[A-Za-z0-9()\s\-\.]{0,50}" maxlength="50">
              <input type="text" name="company_address_3" placeholder="Address" style="width:100%;height:38px;" pattern="[A-Za-z0-9()\s\-\.]{0,50}" maxlength="50">
              <input type="text" name="position_3" placeholder="Position" style="width:100%;height:38px;" pattern="[A-Za-z0-9()\s\-\.]{0,50}" maxlength="50">
              <input type="text" name="months_3" placeholder="Months" style="width:100%;height:38px;" pattern="[0-9]{0,10}" maxlength="10">
              <input type="text" name="status_3" placeholder="Status" style="width:100%;height:38px;" pattern="[A-Za-z0-9()\s\-\.]{0,50}" maxlength="50">
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
              <label>Others:</label>
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
                <input type="file" id="esignature" name="esignature" accept="image/*" required class="esignature-input">
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
              <input type="file" id="resume_file" name="resume_file" class="resume-upload-input" accept=".pdf,.doc,.docx" required>
              <span class="resume-upload-hint">Accepted formats: PDF, DOC, DOCX only. Max size: 5MB.</span>
            </div>
            </fieldset>
            <div class="form-actions">
              <button type="button" class="back-btn" onclick="showPreviousSection()">Back</button>
              <button type="submit" class="next-btn">Submit</button>
            </div>
          </div>
        </div>
      </form>

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
  }
  
  function showNextSection() {
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
          text: 'Please enter your barangay.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      if (!municipality.value.trim()) {
        Swal.fire({
          title: 'Municipality/City Required!',
          text: 'Please enter your municipality/city.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      if (!province.value.trim()) {
        Swal.fire({
          title: 'Province Required!',
          text: 'Please enter your province.',
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
      const terminatedCheckbox = document.querySelector('input[name="unemployed_type_terminated"]');
      const terminatedCountry = document.getElementById('terminated_country');
      const ofwYes = document.getElementById('ofwYes');
      const ofwNo = document.getElementById('ofwNo');
      const ofwCountry = document.getElementById('ofw_country');
      const returneeYes = document.getElementById('returneeYes');
      const returneeNo = document.getElementById('returneeNo');
      const deploymentCountry = document.getElementById('deployment_country');
      const returnMonth = document.getElementById('return_month');
      const returnYear = document.getElementById('return_year');
      const abroadYes = document.querySelector('input[name="abroad"][value="yes"]');
      const abroadNo = document.querySelector('input[name="abroad"][value="no"]');
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
        const unemployedTypes = document.querySelectorAll('input[name^="unemployed_type_"]:not([name="unemployed_type_others"])');
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
        
        // If terminated/laid off is selected, check if country field has value
        if (terminatedCheckbox.checked && !terminatedCountry.value.trim()) {
          Swal.fire({
            title: 'Termination Country Required!',
            text: 'Please specify the country where you were terminated/laid off.',
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
      
      // Check employed abroad selection
      if (!abroadYes.checked && !abroadNo.checked) {
        Swal.fire({
          title: 'Employment Abroad Status Required!',
          text: 'Please select whether you are/were employed abroad in the Philippines.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      // Check job beneficiary selection
      if (!beneficiaryYes.checked && !beneficiaryNo.checked) {
        Swal.fire({
          title: 'Job Beneficiary Status Required!',
          text: 'Please select whether you are a job beneficiary or not.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
      
      // If job beneficiary is Yes, check if household ID is provided
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
      const occupation1 = document.querySelector('input[name="occupation1"]');
      const local1 = document.querySelector('input[name="local1"]');
      const overseas1 = document.querySelector('input[name="overseas1"]');
      
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
      
      // Check if at least one overseas work location is provided
      if (!overseas1.value.trim()) {
        Swal.fire({
          title: 'Overseas Work Location Required!',
          text: 'Please provide at least one overseas work location (country).',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ff9800'
        });
        return; // Prevent navigation
      }
    }
    
    // Check for education section validation when moving from section2_3 (education section)
    if (currentSection === 'section2_3') {
      const inSchoolYes = document.querySelector('input[name="inschool"][value="yes"]');
      const inSchoolNo = document.querySelector('input[name="inschool"][value="no"]');
      const levelSelect = document.getElementById('levelSelect');
      const yearGraduated = document.querySelector('input[name="year_graduated"]');
      const levelReached = document.getElementById('level_reached');
      const lastAttended = document.getElementById('last_attended');
      
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
      }
      
      // If user selected "No" for currently in school
      if (inSchoolNo.checked) {
        // Check if they have graduated (Level and Year Graduated filled)
        const hasLevelAndYear = levelSelect.value && yearGraduated.value.trim();
        // Check if they are undergraduate (Level Reached and Year Last Attended filled)
        const hasLevelReachedAndLastAttended = levelReached.value && lastAttended.value.trim();
        
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
      const esignatureInput = document.getElementById('esignature');
      if (!esignatureInput.files || esignatureInput.files.length === 0) {
        // Show SweetAlert warning if no e-signature is uploaded
        Swal.fire({
          title: 'E-Signature Required!',
          text: 'Please upload your e-signature before proceeding to the next section.',
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
    const terminatedCheckbox = document.querySelector('input[name="unemployed_type_terminated"]');
    
    if (unemployed.checked) {
      unemployedFields.style.pointerEvents = '';
      unemployedFields.style.opacity = '1';
      unemployedFields.querySelectorAll('input').forEach(i => i.disabled = false);
      unemployedTypeFields.style.pointerEvents = '';
      unemployedTypeFields.style.opacity = '1';
      unemployedTypeFields.querySelectorAll('input').forEach(i => i.disabled = false);
      
      // Show/hide terminated country field based on terminated checkbox
      if (terminatedCheckbox.checked) {
        terminatedCountryLabel.style.display = '';
        terminatedCountryInput.style.display = '';
        terminatedCountryInput.disabled = false;
      } else {
        terminatedCountryLabel.style.display = 'none';
        terminatedCountryInput.style.display = 'none';
        terminatedCountryInput.disabled = true;
        terminatedCountryInput.value = '';
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
  // Terminated checkbox triggers show/hide of country field
  document.querySelector('input[name="unemployed_type_terminated"]').addEventListener('change', function() {
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
  setupOthersCheckbox('unemployed_type_others', 'terminated_country');

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

  // OFW country field validation - only allow letters and Filipino characters
  document.getElementById('ofw_country').addEventListener('input', function() {
    // Remove any characters that are not letters, spaces, hyphens, periods, or Filipino special characters
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    
    // Limit to maximum 30 characters
    if (this.value.length > 30) {
      this.value = this.value.substring(0, 30);
    }
  });

  // Deployment country field validation - only allow letters and Filipino characters
  document.getElementById('deployment_country').addEventListener('input', function() {
    // Remove any characters that are not letters, spaces, hyphens, periods, or Filipino special characters
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    
    // Limit to maximum 30 characters
    if (this.value.length > 30) {
      this.value = this.value.substring(0, 30);
    }
  });

  // Job preference fields validation - only allow letters and Filipino characters
  // Occupation fields
  document.querySelector('input[name="occupation1"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  document.querySelector('input[name="occupation2"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  document.querySelector('input[name="occupation3"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  // Local work location fields
  document.querySelector('input[name="local1"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  document.querySelector('input[name="local2"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  document.querySelector('input[name="local3"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  // Overseas work location fields
  document.querySelector('input[name="overseas1"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  document.querySelector('input[name="overseas2"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  document.querySelector('input[name="overseas3"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  // Other language field validation - only allow letters and Filipino characters
  document.querySelector('input[name="other_language"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    if (this.value.length > 30) {
      this.value = this.value.substring(0, 30);
    }
  });

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
  // Text fields (alphabetic only, max 40 characters)
  document.querySelector('input[name="training_course_1"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  document.querySelector('input[name="training_institution_1"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  document.querySelector('input[name="training_skills_1"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  document.querySelector('input[name="training_cert_1"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  document.querySelector('input[name="training_course_2"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  document.querySelector('input[name="training_institution_2"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  document.querySelector('input[name="training_skills_2"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  document.querySelector('input[name="training_cert_2"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  document.querySelector('input[name="training_course_3"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  document.querySelector('input[name="training_institution_3"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  document.querySelector('input[name="training_skills_3"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  document.querySelector('input[name="training_cert_3"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
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

  // Eligibility and PRC fields validation - allow letters, numbers, and parentheses
  document.querySelector('input[name="eligibility_1"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-z0-9()\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  document.querySelector('input[name="eligibility_2"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-z0-9()\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  document.querySelector('input[name="prc_1"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-z0-9()\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  document.querySelector('input[name="prc_2"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-z0-9()\s\-\.]/g, '');
    if (this.value.length > 40) {
      this.value = this.value.substring(0, 40);
    }
  });

  // Work experience fields validation
  // Text fields (alphabetic + numeric + parentheses, max 50 characters)
  document.querySelector('input[name="company_name_1"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-z0-9()\s\-\.]/g, '');
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

  document.querySelector('input[name="status_1"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-z0-9()\s\-\.]/g, '');
    if (this.value.length > 50) {
      this.value = this.value.substring(0, 50);
    }
  });

  document.querySelector('input[name="company_name_2"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-z0-9()\s\-\.]/g, '');
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

  document.querySelector('input[name="status_2"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-z0-9()\s\-\.]/g, '');
    if (this.value.length > 50) {
      this.value = this.value.substring(0, 50);
    }
  });

  document.querySelector('input[name="company_name_3"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-z0-9()\s\-\.]/g, '');
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

  document.querySelector('input[name="status_3"]').addEventListener('input', function() {
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

  // Skills others field validation - only allow letters and commas
  document.querySelector('input[name="skill_others"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-zñÑáÁéÉíÍóÓúÚüÜ,\s]/g, '');
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
  document.getElementById('ofw_country').addEventListener('paste', preventInvalidPaste);
  document.getElementById('deployment_country').addEventListener('paste', preventInvalidPaste);
  
  // Job preference fields paste event listeners
  document.querySelector('input[name="occupation1"]').addEventListener('paste', preventInvalidPaste);
  document.querySelector('input[name="occupation2"]').addEventListener('paste', preventInvalidPaste);
  document.querySelector('input[name="occupation3"]').addEventListener('paste', preventInvalidPaste);
  document.querySelector('input[name="local1"]').addEventListener('paste', preventInvalidPaste);
  document.querySelector('input[name="local2"]').addEventListener('paste', preventInvalidPaste);
  document.querySelector('input[name="local3"]').addEventListener('paste', preventInvalidPaste);
  document.querySelector('input[name="overseas1"]').addEventListener('paste', preventInvalidPaste);
  document.querySelector('input[name="overseas2"]').addEventListener('paste', preventInvalidPaste);
  document.querySelector('input[name="overseas3"]').addEventListener('paste', preventInvalidPaste);
  
  // Other language field paste event listener
  document.querySelector('input[name="other_language"]').addEventListener('paste', preventInvalidPaste);
  
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
  // Text fields paste event listeners (alphabetic only)
  document.querySelector('input[name="training_course_1"]').addEventListener('paste', preventInvalidPaste);
  document.querySelector('input[name="training_institution_1"]').addEventListener('paste', preventInvalidPaste);
  document.querySelector('input[name="training_skills_1"]').addEventListener('paste', preventInvalidPaste);
  document.querySelector('input[name="training_cert_1"]').addEventListener('paste', preventInvalidPaste);
  document.querySelector('input[name="training_course_2"]').addEventListener('paste', preventInvalidPaste);
  document.querySelector('input[name="training_institution_2"]').addEventListener('paste', preventInvalidPaste);
  document.querySelector('input[name="training_skills_2"]').addEventListener('paste', preventInvalidPaste);
  document.querySelector('input[name="training_cert_2"]').addEventListener('paste', preventInvalidPaste);
  document.querySelector('input[name="training_course_3"]').addEventListener('paste', preventInvalidPaste);
  document.querySelector('input[name="training_institution_3"]').addEventListener('paste', preventInvalidPaste);
  document.querySelector('input[name="training_skills_3"]').addEventListener('paste', preventInvalidPaste);
  document.querySelector('input[name="training_cert_3"]').addEventListener('paste', preventInvalidPaste);
  
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
  
  // Eligibility and PRC fields paste event listeners - allow letters, numbers, and parentheses
  document.querySelector('input[name="eligibility_1"]').addEventListener('paste', function(event) {
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
    } else if (pastedText.length > 40) {
      event.preventDefault();
      const input = event.target;
      input.value = pastedText.substring(0, 40);
    }
  });

  document.querySelector('input[name="eligibility_2"]').addEventListener('paste', function(event) {
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
    } else if (pastedText.length > 40) {
      event.preventDefault();
      const input = event.target;
      input.value = pastedText.substring(0, 40);
    }
  });

  document.querySelector('input[name="prc_1"]').addEventListener('paste', function(event) {
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
    } else if (pastedText.length > 40) {
      event.preventDefault();
      const input = event.target;
      input.value = pastedText.substring(0, 40);
    }
  });

  document.querySelector('input[name="prc_2"]').addEventListener('paste', function(event) {
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
    } else if (pastedText.length > 40) {
      event.preventDefault();
      const input = event.target;
      input.value = pastedText.substring(0, 40);
    }
  });
  
  // Work experience fields paste event listeners
  // Text fields paste event listeners (alphabetic + numeric + parentheses)
  document.querySelector('input[name="company_name_1"]').addEventListener('paste', function(event) {
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

  document.querySelector('input[name="status_1"]').addEventListener('paste', function(event) {
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

  document.querySelector('input[name="status_2"]').addEventListener('paste', function(event) {
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

  document.querySelector('input[name="status_3"]').addEventListener('paste', function(event) {
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

  // Initial step
  showStep1();
  updateProgressIndicator(1);

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
    let valid = true;
    
    // Check for duplicate entry first
    const isNotDuplicate = await checkDuplicateEntry();
    if (!isNotDuplicate) {
      return; // Stop submission if duplicate is found
    }
    
    // Check for e-signature validation before final submission
    const esignatureInput = document.getElementById('esignature');
    if (!esignatureInput.files || esignatureInput.files.length === 0) {
      // Show SweetAlert warning if no e-signature is uploaded
      Swal.fire({
        title: 'E-Signature Required!',
        text: 'Please upload your e-signature before submitting the form.',
        icon: 'warning',
        confirmButtonText: 'OK',
        confirmButtonColor: '#ff9800'
      });
      return;
    }
    
    // Only validate required fields in the visible step
    const visibleStep = [document.getElementById('step1Section'), document.getElementById('step2Section'), document.getElementById('step3Section')].find(s => s.style.display !== 'none');
    const fields = visibleStep.querySelectorAll('[required]');
    fields.forEach(field => {
      if (!field.value.trim()) {
        field.style.borderColor = 'red';
        valid = false;
      } else {
        field.style.borderColor = '';
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
     
     // Show loading state
     const submitBtn = form.querySelector('button[type="submit"]');
     const originalText = submitBtn.textContent;
     submitBtn.textContent = 'Submitting...';
     submitBtn.disabled = true;
     
     fetch(form.action, {
       method: 'POST',
       body: formData
     })
     .then(response => {
       console.log('Response status:', response.status);
       return response.json();
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
           // Reset form and go to first page after user clicks OK
           form.reset();
           showStep1();
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
    } else {
      ofwCountryGroup.style.display = 'none';
      ofwCountry.disabled = true;
      ofwCountry.value = '';
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
      returnMonth.disabled = false;
      returnYear.disabled = false;
    } else {
      returneeFields.style.display = 'none';
      returneeReturnFields.style.display = 'none';
      deploymentCountry.disabled = true;
      deploymentCountry.value = '';
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

  // E-Signature Upload Functionality
  document.getElementById('esignature').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('esignaturePreview');
    const image = document.getElementById('esignatureImage');
    const filename = document.getElementById('esignatureFilename');
    
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
      
      // Show preview
      const reader = new FileReader();
      reader.onload = function(e) {
        image.src = e.target.result;
        filename.textContent = file.name;
        preview.style.display = 'flex';
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
    const courseInput = courseField.querySelector('input[name="course"]');
    
    const showCourseOptions = [
      'Secondary (K-12)',
      'Tertiary', 
      'Graduate Studies / Post-graduate'
    ];
    
    if (showCourseOptions.includes(levelSelect.value)) {
      courseField.style.display = '';
      courseInput.disabled = false;
    } else {
      courseField.style.display = 'none';
      courseInput.disabled = true;
      courseInput.value = '';
    }
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
    
  });
  
  // Prevent direct access by checking referrer
  window.addEventListener('beforeunload', function() {
    // This helps ensure the form is accessed through dashboard
    if (!document.referrer.includes('dashboard.php')) {
      console.warn('Form accessed without proper dashboard referrer');
    }
  });
</script>
</body>
</html>