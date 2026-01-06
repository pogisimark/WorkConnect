<?php
// Complete Database Setup Script for WorkConnect
// This script automatically creates the database and ALL tables
// Run this once to set up everything automatically

$host = "workconnect.ct26qyouyans.ap-southeast-2.rds.amazonaws.com";
$user = "admin";
$pass = "Pogisimark";
$db_name = "WorkConnect";

echo "<!DOCTYPE html>
<html>
<head>
    <title>WorkConnect Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #233a8b; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        .step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #233a8b; }
    </style>
</head>
<body>
<div class='container'>
<h1>🚀 WorkConnect Complete Database Setup</h1>";

// Step 1: Connect without database first
echo "<div class='step'><h2>Step 1: Connecting to MySQL Server...</h2>";
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("<p class='error'>❌ Connection failed: " . $conn->connect_error . "</p></div></div></body></html>");
}
echo "<p class='success'>✅ Connected to MySQL server successfully</p></div>";

// Step 2: Create database if it doesn't exist
echo "<div class='step'><h2>Step 2: Creating Database...</h2>";
$sql = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ Database '$db_name' created or already exists</p>";
} else {
    die("<p class='error'>❌ Error creating database: " . $conn->error . "</p></div></div></body></html>");
}
$conn->close();

// Step 3: Connect to the database
echo "</div><div class='step'><h2>Step 3: Connecting to Database...</h2>";
$conn = new mysqli($host, $user, $pass, $db_name);
if ($conn->connect_error) {
    die("<p class='error'>❌ Connection failed: " . $conn->connect_error . "</p></div></div></body></html>");
}
echo "<p class='success'>✅ Connected to database '$db_name' successfully</p></div>";

// Step 4: Create Core Tables
echo "<div class='step'><h2>Step 4: Creating Core Tables (4 tables)...</h2>";

// 4.1 employee_users
$sql = "CREATE TABLE IF NOT EXISTS employee_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ employee_users table created</p>";
} else {
    echo "<p class='error'>❌ Error creating employee_users: " . $conn->error . "</p>";
}

// 4.2 admin_accounts
$sql = "CREATE TABLE IF NOT EXISTS admin_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ admin_accounts table created</p>";
} else {
    echo "<p class='error'>❌ Error creating admin_accounts: " . $conn->error . "</p>";
}

// 4.3 jobseeker (large table)
echo "<p class='info'>⏳ Creating jobseeker table (this may take a moment)...</p>";
$sql = "CREATE TABLE IF NOT EXISTS jobseeker (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    surname VARCHAR(100),
    firstname VARCHAR(100),
    middlename VARCHAR(100),
    suffix VARCHAR(10),
    dob DATE,
    sex VARCHAR(20),
    religion VARCHAR(100),
    civilstatus VARCHAR(50),
    street VARCHAR(255),
    barangay VARCHAR(100),
    municipality VARCHAR(100),
    province VARCHAR(100),
    tin VARCHAR(50),
    height VARCHAR(20),
    contact VARCHAR(50),
    email VARCHAR(255),
    hasDisability TINYINT(1) DEFAULT 0,
    disability_speech TINYINT(1) DEFAULT 0,
    disability_hearing TINYINT(1) DEFAULT 0,
    disability_visual TINYINT(1) DEFAULT 0,
    disability_mental TINYINT(1) DEFAULT 0,
    disability_others TINYINT(1) DEFAULT 0,
    disability_other TEXT,
    employed TINYINT(1) DEFAULT 0,
    employment_type_wage TINYINT(1) DEFAULT 0,
    employment_type_self TINYINT(1) DEFAULT 0,
    self_employed_specify VARCHAR(255),
    self_type_voluntary TINYINT(1) DEFAULT 0,
    self_type_vendor TINYINT(1) DEFAULT 0,
    self_type_homebased TINYINT(1) DEFAULT 0,
    self_type_transport TINYINT(1) DEFAULT 0,
    self_type_domestic TINYINT(1) DEFAULT 0,
    self_type_fisherfolk TINYINT(1) DEFAULT 0,
    self_type_others TINYINT(1) DEFAULT 0,
    other_jobs VARCHAR(255),
    unemployed TINYINT(1) DEFAULT 0,
    unemployed_months VARCHAR(50),
    unemployed_type_first TINYINT(1) DEFAULT 0,
    unemployed_type_local TINYINT(1) DEFAULT 0,
    unemployed_type_resigned TINYINT(1) DEFAULT 0,
    unemployed_type_finished TINYINT(1) DEFAULT 0,
    unemployed_type_public TINYINT(1) DEFAULT 0,
    unemployed_type_retired TINYINT(1) DEFAULT 0,
    unemployed_type_terminated TINYINT(1) DEFAULT 0,
    terminated_country VARCHAR(100),
    ofw VARCHAR(50),
    ofw_country VARCHAR(100),
    returnee VARCHAR(50),
    deployment_country VARCHAR(100),
    return_month VARCHAR(50),
    return_year INT,
    abroad VARCHAR(50),
    beneficiary VARCHAR(50),
    household_id VARCHAR(50),
    occupation1 VARCHAR(255),
    occupation2 VARCHAR(255),
    occupation3 VARCHAR(255),
    fulltime TINYINT(1) DEFAULT 0,
    parttime TINYINT(1) DEFAULT 0,
    local1 VARCHAR(255),
    local2 VARCHAR(255),
    local3 VARCHAR(255),
    overseas1 VARCHAR(255),
    overseas2 VARCHAR(255),
    overseas3 VARCHAR(255),
    english_read TINYINT(1) DEFAULT 0,
    english_write TINYINT(1) DEFAULT 0,
    english_speak TINYINT(1) DEFAULT 0,
    english_understand TINYINT(1) DEFAULT 0,
    filipino_read TINYINT(1) DEFAULT 0,
    filipino_write TINYINT(1) DEFAULT 0,
    filipino_speak TINYINT(1) DEFAULT 0,
    filipino_understand TINYINT(1) DEFAULT 0,
    mandarin_read TINYINT(1) DEFAULT 0,
    mandarin_write TINYINT(1) DEFAULT 0,
    mandarin_speak TINYINT(1) DEFAULT 0,
    mandarin_understand TINYINT(1) DEFAULT 0,
    other_language VARCHAR(100),
    other_read TINYINT(1) DEFAULT 0,
    other_write TINYINT(1) DEFAULT 0,
    other_speak TINYINT(1) DEFAULT 0,
    other_understand TINYINT(1) DEFAULT 0,
    inschool VARCHAR(50),
    level VARCHAR(100),
    course VARCHAR(255),
    year_graduated VARCHAR(50),
    level_reached VARCHAR(100),
    last_attended VARCHAR(100),
    training_course_1 VARCHAR(255),
    training_hours_1 VARCHAR(50),
    training_institution_1 VARCHAR(255),
    training_skills_1 VARCHAR(255),
    training_cert_1 VARCHAR(50),
    training_course_2 VARCHAR(255),
    training_hours_2 VARCHAR(50),
    training_institution_2 VARCHAR(255),
    training_skills_2 VARCHAR(255),
    training_cert_2 VARCHAR(50),
    training_course_3 VARCHAR(255),
    training_hours_3 VARCHAR(50),
    training_institution_3 VARCHAR(255),
    training_skills_3 VARCHAR(255),
    training_cert_3 VARCHAR(50),
    eligibility_1 VARCHAR(255),
    eligibility_date_1 VARCHAR(50),
    eligibility_2 VARCHAR(255),
    eligibility_date_2 VARCHAR(50),
    prc_1 VARCHAR(255),
    prc_valid_1 VARCHAR(50),
    prc_2 VARCHAR(255),
    prc_valid_2 VARCHAR(50),
    company_name_1 VARCHAR(255),
    company_address_1 VARCHAR(255),
    position_1 VARCHAR(255),
    months_1 VARCHAR(50),
    status_1 VARCHAR(50),
    company_name_2 VARCHAR(255),
    company_address_2 VARCHAR(255),
    position_2 VARCHAR(255),
    months_2 VARCHAR(50),
    status_2 VARCHAR(50),
    company_name_3 VARCHAR(255),
    company_address_3 VARCHAR(255),
    position_3 VARCHAR(255),
    months_3 VARCHAR(50),
    status_3 VARCHAR(50),
    skill_auto_mechanic TINYINT(1) DEFAULT 0,
    skill_electrician TINYINT(1) DEFAULT 0,
    skill_photography TINYINT(1) DEFAULT 0,
    skill_beautician TINYINT(1) DEFAULT 0,
    skill_embroidery TINYINT(1) DEFAULT 0,
    skill_plumbing TINYINT(1) DEFAULT 0,
    skill_carpentry TINYINT(1) DEFAULT 0,
    skill_gardening TINYINT(1) DEFAULT 0,
    skill_sewing TINYINT(1) DEFAULT 0,
    skill_computer TINYINT(1) DEFAULT 0,
    skill_masonry TINYINT(1) DEFAULT 0,
    skill_stenography TINYINT(1) DEFAULT 0,
    skill_domestic TINYINT(1) DEFAULT 0,
    skill_painter TINYINT(1) DEFAULT 0,
    skill_tailoring TINYINT(1) DEFAULT 0,
    skill_driver TINYINT(1) DEFAULT 0,
    skill_painting TINYINT(1) DEFAULT 0,
    skill_others VARCHAR(255),
    resume_file VARCHAR(255),
    esignature_file VARCHAR(255),
    submission_date DATE,
    submission_month INT,
    submission_year INT,
    application_status ENUM('Pending', 'Accepted', 'Rejected') DEFAULT 'Pending',
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES employee_users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_application_status (application_status),
    INDEX idx_submission_date (submission_date),
    INDEX idx_barangay (barangay)
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ jobseeker table created</p>";
} else {
    echo "<p class='error'>❌ Error creating jobseeker: " . $conn->error . "</p>";
}

// 4.4 skill_registry
$sql = "CREATE TABLE IF NOT EXISTS skill_registry (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barangay VARCHAR(100) NOT NULL,
    city VARCHAR(100) DEFAULT 'Norzagaray',
    survey_date DATE,
    printed_name VARCHAR(255),
    dob DATE,
    ftjs VARCHAR(50),
    covid VARCHAR(50),
    marital VARCHAR(50),
    address TEXT,
    contact VARCHAR(50),
    education VARCHAR(255),
    age VARCHAR(10),
    sex VARCHAR(20),
    we_position VARCHAR(255),
    we_months VARCHAR(50),
    se_business VARCHAR(255),
    se_months VARCHAR(50),
    ue VARCHAR(50),
    skills TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_barangay (barangay),
    INDEX idx_survey_date (survey_date)
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ skill_registry table created</p>";
} else {
    echo "<p class='error'>❌ Error creating skill_registry: " . $conn->error . "</p>";
}

echo "</div>";

// Step 5: Create notifications table
echo "<div class='step'><h2>Step 5: Creating Notifications Table...</h2>";
$sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ notifications table created</p>";
} else {
    echo "<p class='error'>❌ Error creating notifications: " . $conn->error . "</p>";
}

// Add rejection_reason column to jobseeker if it doesn't exist
$check_column = "SHOW COLUMNS FROM jobseeker LIKE 'rejection_reason'";
$result = $conn->query($check_column);
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE jobseeker ADD COLUMN rejection_reason TEXT";
    if ($conn->query($sql) === TRUE) {
        echo "<p class='success'>✅ Added rejection_reason column to jobseeker table</p>";
    }
}

// Create password_resets table
$sql = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ password_resets table created</p>";
} else {
    echo "<p class='error'>❌ Error creating password_resets: " . $conn->error . "</p>";
}

echo "</div>";

// Step 6: Create Feature Tables (from setup_new_features_fixed.php)
echo "<div class='step'><h2>Step 6: Creating Feature Tables (9 tables)...</h2>";

// 6.1 job_postings
$sql = "CREATE TABLE IF NOT EXISTS job_postings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    company VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT NOT NULL,
    salary_range VARCHAR(100),
    location VARCHAR(255) NOT NULL,
    job_type ENUM('Full-time', 'Part-time', 'Contract', 'Internship') DEFAULT 'Full-time',
    industry VARCHAR(100),
    status ENUM('Active', 'Closed', 'Draft') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ job_postings table created</p>";
} else {
    echo "<p class='error'>❌ Error: " . $conn->error . "</p>";
}

// 6.2 user_preferences
$sql = "CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    preferred_locations JSON,
    preferred_job_types JSON,
    min_salary DECIMAL(10,2),
    preferred_industries JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES employee_users(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ user_preferences table created</p>";
} else {
    echo "<p class='error'>❌ Error: " . $conn->error . "</p>";
}

// 6.3 Add columns to jobseeker
$columns_to_add = [
    'skills_array' => 'JSON',
    'years_experience' => 'INT DEFAULT 0',
    'preferred_job_type' => 'VARCHAR(100)',
    'compatibility_score' => 'DECIMAL(5,2) DEFAULT 0.00'
];

foreach ($columns_to_add as $column_name => $column_definition) {
    $check_sql = "SHOW COLUMNS FROM jobseeker LIKE '$column_name'";
    $result = $conn->query($check_sql);
    if ($result->num_rows == 0) {
        $add_sql = "ALTER TABLE jobseeker ADD COLUMN $column_name $column_definition";
        if ($conn->query($add_sql) === TRUE) {
            echo "<p class='success'>✅ Added column '$column_name' to jobseeker</p>";
        }
    }
}

// 6.4 job_applications_extended
$sql = "CREATE TABLE IF NOT EXISTS job_applications_extended (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jobseeker_id INT NOT NULL,
    job_posting_id INT NOT NULL,
    compatibility_score DECIMAL(5,2) DEFAULT 0.00,
    applied_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    viewed_date TIMESTAMP NULL,
    status ENUM('Applied', 'Viewed', 'Interview', 'Accepted', 'Rejected') DEFAULT 'Applied',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (jobseeker_id) REFERENCES jobseeker(id) ON DELETE CASCADE,
    FOREIGN KEY (job_posting_id) REFERENCES job_postings(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ job_applications_extended table created</p>";
} else {
    echo "<p class='error'>❌ Error: " . $conn->error . "</p>";
}

// 6.5 resume_templates
$sql = "CREATE TABLE IF NOT EXISTS resume_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    html_structure LONGTEXT NOT NULL,
    css_styles LONGTEXT NOT NULL,
    preview_image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ resume_templates table created</p>";
} else {
    echo "<p class='error'>❌ Error: " . $conn->error . "</p>";
}

// 6.6 resumes
$sql = "CREATE TABLE IF NOT EXISTS resumes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    template_id INT NOT NULL,
    personal_info JSON,
    work_experience JSON,
    education JSON,
    skills JSON,
    certifications JSON,
    additional_sections JSON,
    resume_name VARCHAR(255) NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES employee_users(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES resume_templates(id) ON DELETE RESTRICT
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ resumes table created</p>";
} else {
    echo "<p class='error'>❌ Error: " . $conn->error . "</p>";
}

// 6.7 application_analytics
$sql = "CREATE TABLE IF NOT EXISTS application_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_applications INT DEFAULT 0,
    pending_count INT DEFAULT 0,
    accepted_count INT DEFAULT 0,
    rejected_count INT DEFAULT 0,
    response_rate DECIMAL(5,2) DEFAULT 0.00,
    avg_response_time_days DECIMAL(5,2) DEFAULT 0.00,
    success_rate DECIMAL(5,2) DEFAULT 0.00,
    last_calculated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES employee_users(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ application_analytics table created</p>";
} else {
    echo "<p class='error'>❌ Error: " . $conn->error . "</p>";
}

// 6.8 application_timeline
$sql = "CREATE TABLE IF NOT EXISTS application_timeline (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    user_id INT NOT NULL,
    event_type ENUM('submitted', 'viewed', 'interview_scheduled', 'interview_completed', 'accepted', 'rejected') NOT NULL,
    event_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES employee_users(id) ON DELETE CASCADE,
    FOREIGN KEY (application_id) REFERENCES jobseeker(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ application_timeline table created</p>";
} else {
    echo "<p class='error'>❌ Error: " . $conn->error . "</p>";
}

// 6.9 analytics_insights
$sql = "CREATE TABLE IF NOT EXISTS analytics_insights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    insight_type ENUM('success_rate', 'response_time', 'skill_gap', 'timing', 'profile_completeness') NOT NULL,
    insight_text TEXT NOT NULL,
    insight_value DECIMAL(10,2),
    recommendation TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES employee_users(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ analytics_insights table created</p>";
} else {
    echo "<p class='error'>❌ Error: " . $conn->error . "</p>";
}

// 6.10 monthly_analytics
$sql = "CREATE TABLE IF NOT EXISTS monthly_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    year INT NOT NULL,
    month INT NOT NULL,
    applications_submitted INT DEFAULT 0,
    applications_accepted INT DEFAULT 0,
    applications_rejected INT DEFAULT 0,
    avg_response_time DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES employee_users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_month (user_id, year, month)
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ monthly_analytics table created</p>";
} else {
    echo "<p class='error'>❌ Error: " . $conn->error . "</p>";
}

echo "</div>";

// Step 7: Insert sample data
echo "<div class='step'><h2>Step 7: Inserting Sample Data...</h2>";

// Insert sample job postings
$sampleJobs = [
    ['Software Developer', 'TechCorp Inc.', 'We are looking for a skilled software developer to join our team.', 'Bachelor degree in Computer Science, 2+ years experience, PHP/MySQL knowledge', '25000-35000', 'Manila', 'Full-time', 'Technology'],
    ['Marketing Assistant', 'Digital Solutions', 'Assist in marketing campaigns and social media management.', 'Marketing degree preferred, social media experience, creative thinking', '18000-25000', 'Quezon City', 'Full-time', 'Marketing'],
    ['Customer Service Representative', 'Service Plus', 'Handle customer inquiries and provide excellent service.', 'High school graduate, good communication skills, customer service experience', '15000-20000', 'Makati', 'Full-time', 'Customer Service'],
    ['Data Analyst', 'Analytics Pro', 'Analyze data and create reports for business insights.', 'Statistics or Math degree, Excel skills, analytical thinking', '22000-30000', 'Taguig', 'Full-time', 'Analytics'],
    ['Graphic Designer', 'Creative Studio', 'Create visual designs for various marketing materials.', 'Design degree or portfolio, Adobe Creative Suite skills', '20000-28000', 'Pasig', 'Full-time', 'Design']
];

$stmt = $conn->prepare("INSERT IGNORE INTO job_postings (title, company, description, requirements, salary_range, location, job_type, industry) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($sampleJobs as $job) {
    $stmt->bind_param("ssssssss", $job[0], $job[1], $job[2], $job[3], $job[4], $job[5], $job[6], $job[7]);
    $stmt->execute();
}
$stmt->close();
echo "<p class='success'>✅ Sample job postings inserted</p>";

// Insert resume templates
$templates = [
    [
        'Modern',
        'Clean and contemporary design with bold headers and modern typography',
        '<div class="resume-modern"><header class="resume-header"><h1 class="name">{{personal_info.firstname}} {{personal_info.lastname}}</h1><div class="contact-info"><span>{{personal_info.email}}</span> | <span>{{personal_info.phone}}</span> | <span>{{personal_info.location}}</span></div></header><section class="resume-section"><h2>Professional Summary</h2><p>{{personal_info.summary}}</p></section><section class="resume-section"><h2>Work Experience</h2>{{#work_experience}}<div class="experience-item"><h3>{{job_title}} - {{company}}</h3><div class="experience-meta">{{start_date}} - {{end_date}} | {{location}}</div><p>{{description}}</p></div>{{/work_experience}}</section><section class="resume-section"><h2>Education</h2>{{#education}}<div class="education-item"><h3>{{degree}} in {{field}}</h3><div class="education-meta">{{school}} | {{graduation_year}}</div></div>{{/education}}</section><section class="resume-section"><h2>Skills</h2><div class="skills-list">{{skills}}</div></section></div>',
        'body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; } .resume-modern { max-width: 800px; margin: 0 auto; background: white; padding: 40px; box-shadow: 0 0 20px rgba(0,0,0,0.1); } .resume-header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #2c5aa0; padding-bottom: 20px; } .name { font-size: 2.5em; color: #2c5aa0; margin: 0; font-weight: bold; } .contact-info { font-size: 1.1em; color: #666; margin-top: 10px; } .resume-section { margin-bottom: 25px; } .resume-section h2 { color: #2c5aa0; font-size: 1.4em; border-bottom: 2px solid #e0e0e0; padding-bottom: 5px; margin-bottom: 15px; } .experience-item, .education-item { margin-bottom: 20px; } .experience-item h3, .education-item h3 { color: #333; margin: 0 0 5px 0; font-size: 1.2em; } .experience-meta, .education-meta { color: #666; font-style: italic; margin-bottom: 10px; } .skills-list { display: flex; flex-wrap: wrap; gap: 10px; } .skills-list span { background: #e3f0ff; padding: 5px 12px; border-radius: 15px; font-size: 0.9em; }'
    ],
    [
        'Classic',
        'Traditional professional layout with clean lines and formal styling',
        '<div class="resume-classic"><header class="resume-header"><h1 class="name">{{personal_info.firstname}} {{personal_info.lastname}}</h1><div class="contact-info"><div>{{personal_info.email}}</div><div>{{personal_info.phone}}</div><div>{{personal_info.location}}</div></div></header><section class="resume-section"><h2>OBJECTIVE</h2><p>{{personal_info.summary}}</p></section><section class="resume-section"><h2>EXPERIENCE</h2>{{#work_experience}}<div class="experience-item"><div class="experience-header"><span class="job-title">{{job_title}}</span><span class="company">{{company}}</span><span class="dates">{{start_date}} - {{end_date}}</span></div><div class="location">{{location}}</div><p>{{description}}</p></div>{{/work_experience}}</section><section class="resume-section"><h2>EDUCATION</h2>{{#education}}<div class="education-item"><div class="education-header"><span class="degree">{{degree}} in {{field}}</span><span class="school">{{school}}</span><span class="year">{{graduation_year}}</span></div></div>{{/education}}</section><section class="resume-section"><h2>SKILLS</h2><p>{{skills}}</p></section></div>',
        'body { font-family: "Times New Roman", serif; margin: 0; padding: 20px; background: white; } .resume-classic { max-width: 800px; margin: 0 auto; padding: 40px; } .resume-header { margin-bottom: 30px; } .name { font-size: 2.2em; color: #000; margin: 0; font-weight: bold; text-align: center; } .contact-info { text-align: center; margin-top: 15px; line-height: 1.6; } .resume-section { margin-bottom: 25px; } .resume-section h2 { color: #000; font-size: 1.3em; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; border-bottom: 1px solid #000; padding-bottom: 3px; } .experience-item, .education-item { margin-bottom: 20px; } .experience-header, .education-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; } .job-title, .degree { font-weight: bold; } .company, .school { font-style: italic; } .dates, .year { color: #666; } .location { color: #666; font-size: 0.9em; margin-bottom: 8px; }'
    ],
    [
        'Minimal',
        'Simple and clean design focusing on content with minimal styling',
        '<div class="resume-minimal"><header class="resume-header"><h1>{{personal_info.firstname}} {{personal_info.lastname}}</h1><div class="contact">{{personal_info.email}} • {{personal_info.phone}} • {{personal_info.location}}</div></header><section><h2>Summary</h2><p>{{personal_info.summary}}</p></section><section><h2>Experience</h2>{{#work_experience}}<div class="item"><div class="item-header"><strong>{{job_title}}</strong> at {{company}}<span class="date">{{start_date}} - {{end_date}}</span></div><div class="location">{{location}}</div><p>{{description}}</p></div>{{/work_experience}}</section><section><h2>Education</h2>{{#education}}<div class="item"><div class="item-header"><strong>{{degree}} in {{field}}</strong><span class="date">{{graduation_year}}</span></div><div>{{school}}</div></div>{{/education}}</section><section><h2>Skills</h2><p>{{skills}}</p></section></div>',
        'body { font-family: "Helvetica Neue", Arial, sans-serif; margin: 0; padding: 20px; background: white; line-height: 1.6; } .resume-minimal { max-width: 700px; margin: 0 auto; } .resume-header { margin-bottom: 40px; } .resume-header h1 { font-size: 2em; margin: 0; color: #333; } .contact { color: #666; margin-top: 10px; } section { margin-bottom: 30px; } h2 { font-size: 1.2em; color: #333; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 1px solid #eee; } .item { margin-bottom: 20px; } .item-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; } .date { color: #666; font-size: 0.9em; } .location { color: #666; font-size: 0.9em; margin-bottom: 8px; }'
    ]
];

$stmt = $conn->prepare("INSERT IGNORE INTO resume_templates (name, description, html_structure, css_styles) VALUES (?, ?, ?, ?)");
foreach ($templates as $template) {
    $stmt->bind_param("ssss", $template[0], $template[1], $template[2], $template[3]);
    $stmt->execute();
}
$stmt->close();
echo "<p class='success'>✅ Resume templates inserted</p>";

echo "</div>";

// Step 8: Create Announcement Tables (5 tables)
echo "<div class='step'><h2>Step 8: Creating Announcement Tables (5 tables)...</h2>";

// 8.1 announcements
$sql = "CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    date_posted DATETIME DEFAULT CURRENT_TIMESTAMP,
    expiration_date DATE NULL,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin_accounts(id),
    INDEX idx_announcements_status (status),
    INDEX idx_announcements_category (category),
    INDEX idx_announcements_expiration (expiration_date),
    INDEX idx_announcements_created_by (created_by)
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ announcements table created</p>";
} else {
    echo "<p class='error'>❌ Error: " . $conn->error . "</p>";
}

// 8.2 announcement_attachments
$sql = "CREATE TABLE IF NOT EXISTS announcement_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ announcement_attachments table created</p>";
} else {
    echo "<p class='error'>❌ Error: " . $conn->error . "</p>";
}

// 8.3 announcement_tags
$sql = "CREATE TABLE IF NOT EXISTS announcement_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    tag_name VARCHAR(50) NOT NULL,
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    INDEX idx_announcement_tags_tag_name (tag_name)
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ announcement_tags table created</p>";
} else {
    echo "<p class='error'>❌ Error: " . $conn->error . "</p>";
}

// 8.4 announcement_views
$sql = "CREATE TABLE IF NOT EXISTS announcement_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    user_id INT NULL,
    viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES jobseeker(user_id) ON DELETE SET NULL,
    INDEX idx_announcement_views_announcement_id (announcement_id)
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ announcement_views table created</p>";
} else {
    echo "<p class='error'>❌ Error: " . $conn->error . "</p>";
}

// 8.5 announcement_clicks
$sql = "CREATE TABLE IF NOT EXISTS announcement_clicks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    user_id INT NULL,
    clicked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    click_type VARCHAR(50) NOT NULL,
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES jobseeker(user_id) ON DELETE SET NULL,
    INDEX idx_announcement_clicks_announcement_id (announcement_id)
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ announcement_clicks table created</p>";
} else {
    echo "<p class='error'>❌ Error: " . $conn->error . "</p>";
}

echo "</div>";

// Step 9: Create Resume Builder New Schema Tables (4 tables)
echo "<div class='step'><h2>Step 9: Creating Resume Builder New Schema Tables (4 tables)...</h2>";

// 9.1 resumes_new
$sql = "CREATE TABLE IF NOT EXISTS resumes_new (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    template_id INT NOT NULL,
    resume_name VARCHAR(255) NOT NULL,
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    location VARCHAR(255),
    linkedin VARCHAR(255),
    summary TEXT,
    profile_image VARCHAR(255),
    skills TEXT,
    languages TEXT,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES employee_users(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES resume_templates(id) ON DELETE RESTRICT,
    INDEX idx_resumes_user_id (user_id),
    INDEX idx_resumes_is_default (is_default)
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ resumes_new table created</p>";
} else {
    echo "<p class='error'>❌ Error: " . $conn->error . "</p>";
}

// 9.2 resume_work_experience
$sql = "CREATE TABLE IF NOT EXISTS resume_work_experience (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resume_id INT NOT NULL,
    job_title VARCHAR(255) NOT NULL,
    company VARCHAR(255) NOT NULL,
    start_date VARCHAR(50),
    end_date VARCHAR(50),
    location VARCHAR(255),
    description TEXT,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resume_id) REFERENCES resumes_new(id) ON DELETE CASCADE,
    INDEX idx_work_exp_resume_id (resume_id)
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ resume_work_experience table created</p>";
} else {
    echo "<p class='error'>❌ Error: " . $conn->error . "</p>";
}

// 9.3 resume_education
$sql = "CREATE TABLE IF NOT EXISTS resume_education (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resume_id INT NOT NULL,
    degree VARCHAR(255) NOT NULL,
    field VARCHAR(255) NOT NULL,
    school VARCHAR(255) NOT NULL,
    graduation_year VARCHAR(10),
    gpa VARCHAR(20),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resume_id) REFERENCES resumes_new(id) ON DELETE CASCADE,
    INDEX idx_education_resume_id (resume_id)
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ resume_education table created</p>";
} else {
    echo "<p class='error'>❌ Error: " . $conn->error . "</p>";
}

// 9.4 resume_certifications
$sql = "CREATE TABLE IF NOT EXISTS resume_certifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resume_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    organization VARCHAR(255),
    issue_date VARCHAR(50),
    expiry_date VARCHAR(50),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resume_id) REFERENCES resumes_new(id) ON DELETE CASCADE,
    INDEX idx_certifications_resume_id (resume_id)
)";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ resume_certifications table created</p>";
} else {
    echo "<p class='error'>❌ Error: " . $conn->error . "</p>";
}

echo "</div>";

// Final Summary
echo "<div class='step' style='background: #d4edda; border-left-color: #28a745;'>
<h2 style='color: #28a745;'>✅ Setup Complete!</h2>
<p><strong>All 24 database tables have been created successfully!</strong></p>
<p><strong>Tables Created:</strong></p>
<ul>
    <li>✅ 4 Core Tables (employee_users, admin_accounts, jobseeker, skill_registry)</li>
    <li>✅ 2 Utility Tables (notifications, password_resets)</li>
    <li>✅ 9 Feature Tables (job_postings, user_preferences, job_applications_extended, resume_templates, resumes, application_analytics, application_timeline, analytics_insights, monthly_analytics)</li>
    <li>✅ 5 Announcement Tables (announcements, announcement_attachments, announcement_tags, announcement_views, announcement_clicks)</li>
    <li>✅ 4 Resume Builder New Schema Tables (resumes_new, resume_work_experience, resume_education, resume_certifications)</li>
</ul>
<p>You can now:</p>
<ul>
    <li>✅ Use Employee login/signup</li>
    <li>✅ Use Employer/Admin login</li>
    <li>✅ Submit job applications</li>
    <li>✅ Use Job Matching/Recommendation System</li>
    <li>✅ Create professional resumes (both old and new schema)</li>
    <li>✅ View analytics and insights</li>
    <li>✅ Create and manage announcements</li>
</ul>
<p><a href='Employee/login.php' style='background: #233a8b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>Go to Employee Login</a>
<a href='Employer/login.html' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>Go to Employer Login</a></p>
</div>";

$conn->close();
echo "</div></body></html>";
?>

