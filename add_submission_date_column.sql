-- Add submission_date column to jobseeker table
-- This script adds a new column to store the full submission date

ALTER TABLE jobseeker 
ADD COLUMN submission_date DATE DEFAULT NULL 
AFTER submission_year;

-- Simple: Set all existing records to October 26, 2025
UPDATE jobseeker 
SET submission_date = '2025-10-26'
WHERE id > 0;

-- Make the column NOT NULL after updating existing records
ALTER TABLE jobseeker 
MODIFY COLUMN submission_date DATE NOT NULL;
