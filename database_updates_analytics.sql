-- Database updates for Application Analytics Dashboard
-- Run this in your MySQL database

-- Create application_analytics table
CREATE TABLE IF NOT EXISTS application_analytics (
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
);

-- Create application_timeline table
CREATE TABLE IF NOT EXISTS application_timeline (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    user_id INT NOT NULL,
    event_type ENUM('submitted', 'viewed', 'interview_scheduled', 'interview_completed', 'accepted', 'rejected') NOT NULL,
    event_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES employee_users(id) ON DELETE CASCADE,
    FOREIGN KEY (application_id) REFERENCES jobseeker(id) ON DELETE CASCADE
);

-- Create analytics_insights table for storing generated insights
CREATE TABLE IF NOT EXISTS analytics_insights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    insight_type ENUM('success_rate', 'response_time', 'skill_gap', 'timing', 'profile_completeness') NOT NULL,
    insight_text TEXT NOT NULL,
    insight_value DECIMAL(10,2),
    recommendation TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES employee_users(id) ON DELETE CASCADE
);

-- Create monthly_analytics table for trend tracking
CREATE TABLE IF NOT EXISTS monthly_analytics (
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
);

-- Insert sample analytics data for testing (optional - remove in production)
-- This will be populated by the analytics calculation script
