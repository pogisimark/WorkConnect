-- New Resume Database Schema with Specific Columns
-- Run this SQL to create the new database structure

-- Create new resumes table with specific columns
CREATE TABLE IF NOT EXISTS resumes_new (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    template_id INT NOT NULL,
    resume_name VARCHAR(255) NOT NULL,
    
    -- Personal Information
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    location VARCHAR(255),
    linkedin VARCHAR(255),
    summary TEXT,
    profile_image VARCHAR(255),
    
    -- Skills
    skills TEXT,
    languages TEXT,
    
    -- Resume Settings
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES employee_users(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES resume_templates(id) ON DELETE RESTRICT
);

-- Create work_experience table for multiple work experiences
CREATE TABLE IF NOT EXISTS resume_work_experience (
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
    
    FOREIGN KEY (resume_id) REFERENCES resumes_new(id) ON DELETE CASCADE
);

-- Create education table for multiple education entries
CREATE TABLE IF NOT EXISTS resume_education (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resume_id INT NOT NULL,
    degree VARCHAR(255) NOT NULL,
    field VARCHAR(255) NOT NULL,
    school VARCHAR(255) NOT NULL,
    graduation_year VARCHAR(10),
    gpa VARCHAR(20),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (resume_id) REFERENCES resumes_new(id) ON DELETE CASCADE
);

-- Create certifications table for multiple certifications
CREATE TABLE IF NOT EXISTS resume_certifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resume_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    organization VARCHAR(255),
    issue_date VARCHAR(50),
    expiry_date VARCHAR(50),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (resume_id) REFERENCES resumes_new(id) ON DELETE CASCADE
);

-- Add indexes for better performance
CREATE INDEX idx_resumes_user_id ON resumes_new(user_id);
CREATE INDEX idx_resumes_is_default ON resumes_new(is_default);
CREATE INDEX idx_work_exp_resume_id ON resume_work_experience(resume_id);
CREATE INDEX idx_education_resume_id ON resume_education(resume_id);
CREATE INDEX idx_certifications_resume_id ON resume_certifications(resume_id);
