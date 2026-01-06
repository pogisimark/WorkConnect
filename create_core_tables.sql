

-- 1. employee_users table
-- Stores employee user accounts for login/signup
CREATE TABLE IF NOT EXISTS employee_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. admin_accounts table
-- Stores admin user accounts for employer/admin login
CREATE TABLE IF NOT EXISTS admin_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. jobseeker table
-- Stores comprehensive job application data
CREATE TABLE IF NOT EXISTS jobseeker (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    
    -- Personal Information
    surname VARCHAR(100),
    firstname VARCHAR(100),
    middlename VARCHAR(100),
    suffix VARCHAR(10),
    dob DATE,
    sex VARCHAR(20),
    religion VARCHAR(100),
    civilstatus VARCHAR(50),
    
    -- Address Information
    street VARCHAR(255),
    barangay VARCHAR(100),
    municipality VARCHAR(100),
    province VARCHAR(100),
    
    -- Contact & Identification
    tin VARCHAR(50),
    height VARCHAR(20),
    contact VARCHAR(50),
    email VARCHAR(255),
    
    -- Disability Information
    hasDisability TINYINT(1) DEFAULT 0,
    disability_speech TINYINT(1) DEFAULT 0,
    disability_hearing TINYINT(1) DEFAULT 0,
    disability_visual TINYINT(1) DEFAULT 0,
    disability_mental TINYINT(1) DEFAULT 0,
    disability_others TINYINT(1) DEFAULT 0,
    disability_other TEXT,
    
    -- Employment Status
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
    
    -- Unemployment Information
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
    
    -- OFW Information
    ofw VARCHAR(50),
    ofw_country VARCHAR(100),
    returnee VARCHAR(50),
    deployment_country VARCHAR(100),
    return_month VARCHAR(50),
    return_year INT,
    abroad VARCHAR(50),
    beneficiary VARCHAR(50),
    household_id VARCHAR(50),
    
    -- Occupation Preferences
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
    
    -- Language Skills
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
    
    -- Education Information
    inschool VARCHAR(50),
    level VARCHAR(100),
    course VARCHAR(255),
    year_graduated VARCHAR(50),
    level_reached VARCHAR(100),
    last_attended VARCHAR(100),
    
    -- Training Information (3 entries)
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
    
    -- Eligibility Information
    eligibility_1 VARCHAR(255),
    eligibility_date_1 VARCHAR(50),
    eligibility_2 VARCHAR(255),
    eligibility_date_2 VARCHAR(50),
    prc_1 VARCHAR(255),
    prc_valid_1 VARCHAR(50),
    prc_2 VARCHAR(255),
    prc_valid_2 VARCHAR(50),
    
    -- Work Experience (3 entries)
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
    
    -- Skills
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
    
    -- Files and Submission
    resume_file VARCHAR(255),
    esignature_file VARCHAR(255),
    submission_date DATE,
    submission_month INT,
    submission_year INT,
    application_status ENUM('Pending', 'Accepted', 'Rejected') DEFAULT 'Pending',
    rejection_reason TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Key
    FOREIGN KEY (user_id) REFERENCES employee_users(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_application_status (application_status),
    INDEX idx_submission_date (submission_date),
    INDEX idx_barangay (barangay)
);

-- 4. skill_registry table
-- Stores skill registry data by barangay
CREATE TABLE IF NOT EXISTS skill_registry (
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
    
    -- Indexes
    INDEX idx_barangay (barangay),
    INDEX idx_survey_date (survey_date)
);



