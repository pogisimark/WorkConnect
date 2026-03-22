-- One-time: track when admin has read a company's reply (REQUEST FOLLOW UP).
-- Run in phpMyAdmin if the column was not created by setup_complete_database.php.

ALTER TABLE admin_company_follow_up
  ADD COLUMN admin_response_read_at DATETIME NULL DEFAULT NULL AFTER responded_at;

-- Existing threads: treat as already read so notification count starts clean
UPDATE admin_company_follow_up
SET admin_response_read_at = COALESCE(responded_at, NOW())
WHERE status = 'answered' AND company_response IS NOT NULL AND TRIM(company_response) <> '';
