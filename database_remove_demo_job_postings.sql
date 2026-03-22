-- One-time cleanup: remove demo/seed job rows that have no linked company.
-- Run in phpMyAdmin or mysql CLI after confirming `company_id` exists on `job_postings`.
-- Real company jobs always have company_id set.

DELETE FROM job_postings WHERE company_id IS NULL;
