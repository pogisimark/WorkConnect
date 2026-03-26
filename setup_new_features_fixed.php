<?php
// Database setup script for all three new features
$host = "workconnect.cp28esmqk7aq.ap-southeast-2.rds.amazonaws.com";
$user = "admin";
$pass = "Pogisimark";
$db   = "WorkConnect";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected to database successfully.<br><br>";

// Execute SQL statements directly instead of reading from files
echo "Setting up Job Matching System...<br>";

// Create job_postings table
$sql1 = "CREATE TABLE IF NOT EXISTS job_postings (
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

if ($conn->query($sql1) === TRUE) {
    echo "✅ job_postings table created successfully.<br>";
} else {
    echo "ℹ️ " . $conn->error . "<br>";
}

// Create user_preferences table
$sql2 = "CREATE TABLE IF NOT EXISTS user_preferences (
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

if ($conn->query($sql2) === TRUE) {
    echo "✅ user_preferences table created successfully.<br>";
} else {
    echo "ℹ️ " . $conn->error . "<br>";
}

// Add new columns to jobseeker table (check if they exist first)
$columns_to_add = [
    'skills_array' => 'JSON',
    'years_experience' => 'INT DEFAULT 0',
    'preferred_job_type' => 'VARCHAR(100)',
    'compatibility_score' => 'DECIMAL(5,2) DEFAULT 0.00'
];

foreach ($columns_to_add as $column_name => $column_definition) {
    // Check if column exists
    $check_sql = "SHOW COLUMNS FROM jobseeker LIKE '$column_name'";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows == 0) {
        // Column doesn't exist, add it
        $add_sql = "ALTER TABLE jobseeker ADD COLUMN $column_name $column_definition";
        if ($conn->query($add_sql) === TRUE) {
            echo "✅ Added column '$column_name' to jobseeker table.<br>";
        } else {
            echo "❌ Error adding column '$column_name': " . $conn->error . "<br>";
        }
    } else {
        echo "ℹ️ Column '$column_name' already exists in jobseeker table.<br>";
    }
}

// Create job_applications_extended table
$sql4 = "CREATE TABLE IF NOT EXISTS job_applications_extended (
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

if ($conn->query($sql4) === TRUE) {
    echo "✅ job_applications_extended table created successfully.<br>";
} else {
    echo "ℹ️ " . $conn->error . "<br>";
}

echo "<br>Setting up Resume Builder System...<br>";

// Create resume_templates table
$sql5 = "CREATE TABLE IF NOT EXISTS resume_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    html_structure LONGTEXT NOT NULL,
    css_styles LONGTEXT NOT NULL,
    preview_image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql5) === TRUE) {
    echo "✅ resume_templates table created successfully.<br>";
} else {
    echo "ℹ️ " . $conn->error . "<br>";
}

// Create resumes table
$sql6 = "CREATE TABLE IF NOT EXISTS resumes (
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

if ($conn->query($sql6) === TRUE) {
    echo "✅ resumes table created successfully.<br>";
} else {
    echo "ℹ️ " . $conn->error . "<br>";
}

echo "<br>Setting up Analytics System...<br>";

// Create application_analytics table
$sql7 = "CREATE TABLE IF NOT EXISTS application_analytics (
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

if ($conn->query($sql7) === TRUE) {
    echo "✅ application_analytics table created successfully.<br>";
} else {
    echo "ℹ️ " . $conn->error . "<br>";
}

// Create application_timeline table
$sql8 = "CREATE TABLE IF NOT EXISTS application_timeline (
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

if ($conn->query($sql8) === TRUE) {
    echo "✅ application_timeline table created successfully.<br>";
} else {
    echo "ℹ️ " . $conn->error . "<br>";
}

// Create analytics_insights table
$sql9 = "CREATE TABLE IF NOT EXISTS analytics_insights (
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

if ($conn->query($sql9) === TRUE) {
    echo "✅ analytics_insights table created successfully.<br>";
} else {
    echo "ℹ️ " . $conn->error . "<br>";
}

// Create monthly_analytics table
$sql10 = "CREATE TABLE IF NOT EXISTS monthly_analytics (
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

if ($conn->query($sql10) === TRUE) {
    echo "✅ monthly_analytics table created successfully.<br>";
} else {
    echo "ℹ️ " . $conn->error . "<br>";
}

echo "<br>Inserting sample data...<br>";
echo "ℹ️ Sample job postings skipped — only company-created jobs are used.<br>";

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
echo "✅ Resume templates inserted successfully.<br>";

echo "<br><strong>🎉 Database setup completed successfully for all three features!</strong><br>";
echo "<br>You can now:";
echo "<ul>";
echo "<li>✅ Use the Job Matching/Recommendation System</li>";
echo "<li>✅ Create professional resumes with the Resume Builder</li>";
echo "<li>✅ View analytics and insights in the Analytics Dashboard</li>";
echo "</ul>";
echo "<br><a href='Employee/dashboard.php' style='background: #233a8b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Employee Dashboard</a>";
echo " <a href='Employer/Dashboard.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Employer Dashboard</a>";

$conn->close();
?>
