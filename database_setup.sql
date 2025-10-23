-- Database setup for rejection reason and notifications
-- Run this in your MySQL database

-- Add rejection_reason column to jobseeker table if it doesn't exist
-- First check if column exists, then add it
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_NAME = 'jobseeker' 
     AND COLUMN_NAME = 'rejection_reason' 
     AND TABLE_SCHEMA = DATABASE()) = 0,
    'ALTER TABLE jobseeker ADD COLUMN rejection_reason TEXT',
    'SELECT "Column already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create notifications table if it doesn't exist
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add foreign key constraint if users table exists
-- ALTER TABLE notifications ADD CONSTRAINT fk_notifications_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
