<?php
/**
 * Ensure job_applications_extended can use Withdrawn; cascade when one acceptance wins.
 */
if (!function_exists('ensure_withdrawn_status_job_applications_extended')) {
    function ensure_withdrawn_status_job_applications_extended($conn)
    {
        static $checked = false;
        if ($checked || !$conn) {
            return;
        }
        $checked = true;
        $t = @$conn->query("SHOW TABLES LIKE 'job_applications_extended'");
        if (!$t || $t->num_rows === 0) {
            return;
        }
        $r = @$conn->query("SHOW COLUMNS FROM job_applications_extended WHERE Field = 'status'");
        if (!$r || $r->num_rows === 0) {
            return;
        }
        $row = $r->fetch_assoc();
        $type = strtolower($row['Type'] ?? '');
        if (strpos($type, 'withdrawn') !== false) {
            return;
        }
        // ENUM extension (common schema from setup_complete_database.php)
        $alter = "ALTER TABLE job_applications_extended MODIFY COLUMN status ENUM('Applied', 'Viewed', 'Interview', 'Accepted', 'Rejected', 'Withdrawn') DEFAULT 'Applied'";
        @$conn->query($alter);
    }
}

if (!function_exists('withdraw_open_job_applications_for_jobseeker')) {
    /**
     * Mark non-terminal applications as Withdrawn (e.g. jobseeker accepted elsewhere).
     *
     * @param mysqli $conn
     * @param int    $jobseeker_id
     * @param int    $except_application_id 0 = withdraw all open rows
     */
    function withdraw_open_job_applications_for_jobseeker($conn, $jobseeker_id, $except_application_id = 0)
    {
        ensure_withdrawn_status_job_applications_extended($conn);
        $jobseeker_id = (int) $jobseeker_id;
        $except_application_id = (int) $except_application_id;
        if ($jobseeker_id <= 0 || !$conn) {
            return;
        }
        if ($except_application_id > 0) {
            $stmt = $conn->prepare(
                "UPDATE job_applications_extended SET status = 'Withdrawn', viewed_date = COALESCE(viewed_date, NOW())
                WHERE jobseeker_id = ? AND id != ? AND status IN ('Applied', 'Viewed', 'Interview')"
            );
            if ($stmt) {
                $stmt->bind_param('ii', $jobseeker_id, $except_application_id);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $stmt = $conn->prepare(
                "UPDATE job_applications_extended SET status = 'Withdrawn', viewed_date = COALESCE(viewed_date, NOW())
                WHERE jobseeker_id = ? AND status IN ('Applied', 'Viewed', 'Interview')"
            );
            if ($stmt) {
                $stmt->bind_param('i', $jobseeker_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

if (!function_exists('reconcile_stale_open_applications_for_accepted_jobseeker')) {
    /**
     * Fix stale Applied/Viewed/Interview rows when jobseeker.application_status is already Accepted
     * (referral accept before Withdrawn existed, failed ALTER, or legacy data).
     */
    function reconcile_stale_open_applications_for_accepted_jobseeker($conn, $jobseeker_id)
    {
        $jobseeker_id = (int) $jobseeker_id;
        if ($jobseeker_id <= 0 || !$conn) {
            return;
        }
        $st = $conn->prepare('SELECT application_status FROM jobseeker WHERE id = ?');
        if (!$st) {
            return;
        }
        $st->bind_param('i', $jobseeker_id);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$row || strcasecmp(trim($row['application_status'] ?? ''), 'Accepted') !== 0) {
            return;
        }
        withdraw_open_job_applications_for_jobseeker($conn, $jobseeker_id, 0);
    }
}

if (!function_exists('reconcile_stale_applications_for_company_jobs')) {
    /**
     * Company portal: withdraw open applications for jobseekers already Accepted (e.g. via referral
     * at another company) but still showing Applied on this company's postings.
     */
    function reconcile_stale_applications_for_company_jobs($conn, $company_id)
    {
        $company_id = (int) $company_id;
        if ($company_id <= 0 || !$conn) {
            return;
        }
        $t = @$conn->query("SHOW TABLES LIKE 'job_applications_extended'");
        $t2 = @$conn->query("SHOW TABLES LIKE 'job_postings'");
        if (!$t || !$t2 || $t->num_rows === 0 || $t2->num_rows === 0) {
            return;
        }
        ensure_withdrawn_status_job_applications_extended($conn);
        $sql = 'UPDATE job_applications_extended jae
            INNER JOIN jobseeker j ON j.id = jae.jobseeker_id
            INNER JOIN job_postings jp ON jp.id = jae.job_posting_id
            SET jae.status = \'Withdrawn\', jae.viewed_date = COALESCE(jae.viewed_date, NOW())
            WHERE jp.company_id = ?
            AND j.application_status = \'Accepted\'
            AND jae.status IN (\'Applied\', \'Viewed\', \'Interview\')';
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $company_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

if (!function_exists('withdraw_pending_referrals_for_jobseeker')) {
    function withdraw_pending_referrals_for_jobseeker($conn, $jobseeker_id)
    {
        $jobseeker_id = (int) $jobseeker_id;
        if ($jobseeker_id <= 0 || !$conn) {
            return;
        }
        $t = @$conn->query("SHOW TABLES LIKE 'jobseeker_company_referrals'");
        if (!$t || $t->num_rows === 0) {
            return;
        }
        $stmt = $conn->prepare(
            "UPDATE jobseeker_company_referrals SET status = 'withdrawn' WHERE jobseeker_id = ? AND status = 'pending'"
        );
        if ($stmt) {
            $stmt->bind_param('i', $jobseeker_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}
