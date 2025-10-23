-- Simple command to add rejection_reason column
-- If the column already exists, you'll get an error but that's okay

ALTER TABLE jobseeker ADD COLUMN rejection_reason TEXT;
