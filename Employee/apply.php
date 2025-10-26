<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

$host = "workconnect.cz2woayyket3.ap-southeast-2.rds.amazonaws.com";
$user = "admin";
$pass = "Pogisimark";
$db   = "WorkConnect";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    // Clear any output and return JSON error
    if (ob_get_level()) {
        ob_clean();
    }
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}


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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Debug: Log that we received a POST request
    error_log("POST request received");
    
    // Simple test response first
    if (isset($_POST['test'])) {
        sendJsonResponse(true, 'Test successful');
    }
    
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

    // Get submission month and year
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
    $self_type_other = $conn->real_escape_string(getval('self_type_other'));
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

    // Get user_id from session if logged in
    $user_id = null;
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }

    // Build SQL
    $sql = "INSERT INTO jobseeker (
        user_id, surname, firstname, middlename, suffix, dob, sex, religion, civilstatus, street, barangay, municipality, province, tin, height, contact, email,
        hasDisability, disability_speech, disability_hearing, disability_visual, disability_mental, disability_others, disability_other,
        employed, employment_type_wage, employment_type_self, self_employed_specify, self_type_voluntary, self_type_vendor, self_type_homebased, self_type_transport, self_type_domestic, self_type_fisherfolk, self_type_others, self_type_other,
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
        resume_file, esignature_file, submission_month, submission_year, application_status
    ) VALUES (
        " . ($user_id ? $user_id : 'NULL') . ", '$surname', '$firstname', '$middlename', '$suffix', '$dob', '$sex', '$religion', '$civilstatus', '$street', '$barangay', '$municipality', '$province', '$tin', '$height', '$contact', '$email',
        $hasDisability, $disability_speech, $disability_hearing, $disability_visual, $disability_mental, $disability_others, '$disability_other',
        $employed, $employment_type_wage, $employment_type_self, '$self_employed_specify', $self_type_voluntary, $self_type_vendor, $self_type_homebased, $self_type_transport, $self_type_domestic, $self_type_fisherfolk, $self_type_others, '$self_type_other',
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
        '$resume_filename', '$esignature_filename', $submission_month, $submission_year, 'Pending'
    )";

    try {
        if ($conn->query($sql) === TRUE) {
            sendJsonResponse(true, 'Registration saved successfully!');
        } else {
            error_log("Database insert error: " . $conn->error);
            sendJsonResponse(false, 'Database error: ' . $conn->error);
        }
    } catch (Exception $e) {
        error_log("Database insert exception: " . $e->getMessage());
        sendJsonResponse(false, 'Database insert failed: ' . $e->getMessage());
    }
} else {
    // Not a POST request
    sendJsonResponse(false, 'Invalid request method');
}
$conn->close();
?>