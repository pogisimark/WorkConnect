-- Simple database setup for rejection reason and notifications
-- Run these commands one by one in your MySQL database

-- Step 1: Try to add rejection_reason column (ignore error if it already exists)
ALTER TABLE jobseeker ADD COLUMN rejection_reason TEXT;

-- Step 2: Create notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
