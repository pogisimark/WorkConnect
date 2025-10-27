-- Database updates for Job Matching/Recommendation System
-- Run this in your MySQL database

-- Create job_postings table
CREATE TABLE IF NOT EXISTS job_postings (
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
);

-- Create user_preferences table
CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    preferred_locations JSON,
    preferred_job_types JSON,
    min_salary DECIMAL(10,2),
    preferred_industries JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES employee_users(id) ON DELETE CASCADE
);

-- Add new columns to jobseeker table
ALTER TABLE jobseeker 
ADD COLUMN IF NOT EXISTS skills_array JSON,
ADD COLUMN IF NOT EXISTS years_experience INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS preferred_job_type VARCHAR(100),
ADD COLUMN IF NOT EXISTS compatibility_score DECIMAL(5,2) DEFAULT 0.00;

-- Create job_applications_extended table for enhanced tracking
CREATE TABLE IF NOT EXISTS job_applications_extended (
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
);

-- Insert sample job postings for testing
INSERT INTO job_postings (title, company, description, requirements, salary_range, location, job_type, industry) VALUES
('Software Developer', 'TechCorp Inc.', 'We are looking for a skilled software developer to join our team.', 'Bachelor degree in Computer Science, 2+ years experience, PHP/MySQL knowledge', '25000-35000', 'Manila', 'Full-time', 'Technology'),
('Marketing Assistant', 'Digital Solutions', 'Assist in marketing campaigns and social media management.', 'Marketing degree preferred, social media experience, creative thinking', '18000-25000', 'Quezon City', 'Full-time', 'Marketing'),
('Customer Service Representative', 'Service Plus', 'Handle customer inquiries and provide excellent service.', 'High school graduate, good communication skills, customer service experience', '15000-20000', 'Makati', 'Full-time', 'Customer Service'),
('Data Analyst', 'Analytics Pro', 'Analyze data and create reports for business insights.', 'Statistics or Math degree, Excel skills, analytical thinking', '22000-30000', 'Taguig', 'Full-time', 'Analytics'),
('Graphic Designer', 'Creative Studio', 'Create visual designs for various marketing materials.', 'Design degree or portfolio, Adobe Creative Suite skills', '20000-28000', 'Pasig', 'Full-time', 'Design');
