<?php
/**
 * Job applications awaiting company accept/reject — sidebar "View Applicants" badge.
 * Matches logic in view_applicants.php (Accept/Reject buttons when status is not accepted/rejected).
 */
if (!function_exists('company_pending_applicants_from_applicant_rows')) {
    function company_pending_applicants_from_applicant_rows(array $applicants)
    {
        $n = 0;
        foreach ($applicants as $a) {
            $s = strtolower(trim($a['application_status'] ?? ''));
            if (!in_array($s, ['accepted', 'rejected', 'withdrawn'], true)) {
                $n++;
            }
        }

        return $n;
    }
}

if (!function_exists('company_pending_applicants_count_for_sidebar')) {
    function company_pending_applicants_count_for_sidebar($conn, $company_id)
    {
        $company_id = (int) $company_id;
        if (!$conn || $company_id <= 0) {
            return 0;
        }
        $tJae = @$conn->query("SHOW TABLES LIKE 'job_applications_extended'");
        $tJp = @$conn->query("SHOW TABLES LIKE 'job_postings'");
        if (!$tJae || !$tJp || $tJae->num_rows === 0 || $tJp->num_rows === 0) {
            return 0;
        }
        $stmt = $conn->prepare(
            'SELECT COUNT(*) AS c
            FROM job_applications_extended jae
            INNER JOIN job_postings jp ON jae.job_posting_id = jp.id
            WHERE jp.company_id = ?
            AND LOWER(TRIM(COALESCE(jae.status, \'\'))) NOT IN (\'accepted\', \'rejected\', \'withdrawn\')'
        );
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('i', $company_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row['c'] ?? 0);
    }
}

if (!function_exists('company_pending_applicants_badge_html')) {
    function company_pending_applicants_badge_html($count)
    {
        $n = (int) $count;
        if ($n < 1) {
            return '';
        }
        $display = $n > 99 ? '99+' : (string) $n;
        $title = $n . ' application' . ($n === 1 ? '' : 's') . ' pending your review';
        $style = 'display:inline-flex;align-items:center;justify-content:center;align-self:center;min-width:20px;height:20px;padding:0 7px;'
            . 'margin-left:8px;margin-top:0;vertical-align:middle;background:#f44336;color:#fff;font-size:0.68rem;font-weight:700;border-radius:999px;'
            . 'line-height:1;box-shadow:0 1px 3px rgba(0,0,0,0.22);flex-shrink:0;';

        return '<span class="company-pending-applicants-badge" style="' . $style . '" title="' . htmlspecialchars($title) . '" aria-label="' . htmlspecialchars($title) . '">'
            . htmlspecialchars($display) . '</span>';
    }
}
