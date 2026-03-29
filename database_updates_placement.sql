-- Jobseeker placement lifecycle (re-use account after employment ends)
-- Safe to run multiple times: check columns first if your tool does not support IF NOT EXISTS.

ALTER TABLE jobseeker
  ADD COLUMN placement_active TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=currently placed' AFTER application_status,
  ADD COLUMN placement_ended_at DATETIME NULL AFTER placement_active,
  ADD COLUMN placement_end_reason VARCHAR(255) NULL AFTER placement_ended_at;

-- If ADD COLUMN fails because columns exist, skip the ALTER above.

UPDATE jobseeker SET placement_active = 1 WHERE application_status = 'Accepted' AND placement_active = 0;
