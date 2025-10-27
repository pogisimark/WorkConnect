-- Announcement System Database Tables
-- Run this SQL script to create all necessary tables for the announcement system

-- 1. announcements table
CREATE TABLE announcements (
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
    FOREIGN KEY (created_by) REFERENCES admin_accounts(id)
);

-- 2. announcement_attachments table
CREATE TABLE announcement_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE
);

-- 3. announcement_tags table
CREATE TABLE announcement_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    tag_name VARCHAR(50) NOT NULL,
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE
);

-- 4. announcement_views table
CREATE TABLE announcement_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    user_id INT NULL,
    viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES jobseeker(user_id) ON DELETE SET NULL
);

-- 5. announcement_clicks table
CREATE TABLE announcement_clicks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    user_id INT NULL,
    clicked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    click_type VARCHAR(50) NOT NULL,
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES jobseeker(user_id) ON DELETE SET NULL
);

-- Add indexes for better performance
CREATE INDEX idx_announcements_status ON announcements(status);
CREATE INDEX idx_announcements_category ON announcements(category);
CREATE INDEX idx_announcements_expiration ON announcements(expiration_date);
CREATE INDEX idx_announcements_created_by ON announcements(created_by);
CREATE INDEX idx_announcement_views_announcement_id ON announcement_views(announcement_id);
CREATE INDEX idx_announcement_clicks_announcement_id ON announcement_clicks(announcement_id);
CREATE INDEX idx_announcement_tags_tag_name ON announcement_tags(tag_name);
