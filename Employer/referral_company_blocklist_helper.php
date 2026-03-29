<?php
/**
 * Companies a jobseeker must not be referred to again:
 * - Referral concluded: accepted (placement history) or rejected by company.
 * - Recommended Jobs: application Accepted, Closed (placement ended), or Rejected for that company's postings.
 */
require_once __DIR__ . '/referrals_schema.php';

if (!function_exists('workconnect_referral_blocked_company_map_batch')) {
    /**
     * @param list<int> $jobseeker_ids
     * @return array<int, list<int>> jobseeker_id => distinct company_ids
     */
    function workconnect_referral_blocked_company_map_batch(mysqli $conn, array $jobseeker_ids): array
    {
        $jobseeker_ids = array_values(array_unique(array_filter(array_map('intval', $jobseeker_ids))));
        $out = [];
        foreach ($jobseeker_ids as $id) {
            $out[$id] = [];
        }
        if (count($jobseeker_ids) === 0) {
            return $out;
        }

        ensure_jobseeker_referrals_table($conn);

        $placeholders = implode(',', array_fill(0, count($jobseeker_ids), '?'));
        $types = str_repeat('i', count($jobseeker_ids));

        $sqlRef = "SELECT jobseeker_id, company_id FROM jobseeker_company_referrals
            WHERE jobseeker_id IN ($placeholders) AND status IN ('accepted', 'rejected')";
        $st = $conn->prepare($sqlRef);
        if ($st) {
            $st->bind_param($types, ...$jobseeker_ids);
            $st->execute();
            $res = $st->get_result();
            while ($row = $res->fetch_assoc()) {
                $jid = (int) $row['jobseeker_id'];
                $cid = (int) $row['company_id'];
                if (!isset($out[$jid])) {
                    $out[$jid] = [];
                }
                if (!in_array($cid, $out[$jid], true)) {
                    $out[$jid][] = $cid;
                }
            }
            $st->close();
        }

        $tjae = @$conn->query("SHOW TABLES LIKE 'job_applications_extended'");
        $tjp = @$conn->query("SHOW COLUMNS FROM job_postings LIKE 'company_id'");
        if ($tjae && $tjae->num_rows > 0 && $tjp && $tjp->num_rows > 0) {
            $sqlJae = "SELECT DISTINCT jae.jobseeker_id AS jobseeker_id, jp.company_id AS company_id
                FROM job_applications_extended jae
                INNER JOIN job_postings jp ON jp.id = jae.job_posting_id
                WHERE jae.jobseeker_id IN ($placeholders)
                AND jp.company_id IS NOT NULL
                AND jae.status IN ('Accepted', 'Closed', 'Rejected')";
            $st2 = $conn->prepare($sqlJae);
            if ($st2) {
                $st2->bind_param($types, ...$jobseeker_ids);
                $st2->execute();
                $res2 = $st2->get_result();
                while ($row = $res2->fetch_assoc()) {
                    $jid = (int) $row['jobseeker_id'];
                    $cid = (int) $row['company_id'];
                    if (!isset($out[$jid])) {
                        $out[$jid] = [];
                    }
                    if (!in_array($cid, $out[$jid], true)) {
                        $out[$jid][] = $cid;
                    }
                }
                $st2->close();
            }
        }

        return $out;
    }
}

if (!function_exists('workconnect_referral_blocked_company_map')) {
    /** @return array<int, true> company_id => true */
    function workconnect_referral_blocked_company_map(mysqli $conn, int $jobseeker_id): array
    {
        $batch = workconnect_referral_blocked_company_map_batch($conn, [$jobseeker_id]);
        $ids = $batch[$jobseeker_id] ?? [];
        $m = [];
        foreach ($ids as $cid) {
            $m[(int) $cid] = true;
        }

        return $m;
    }
}
