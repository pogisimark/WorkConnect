<?php
// jobseekers.php: Returns jobseeker data as JSON for job.html
header('Content-Type: application/json');
$host = "workconnect.cp28esmqk7aq.ap-southeast-2.rds.amazonaws.com";
$user = "admin";
$pass = "Pogisimark";
$db   = "WorkConnect";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "DB connection failed"]);
    exit;
}
require_once __DIR__ . '/referrals_schema.php';
require_once __DIR__ . '/referral_company_blocklist_helper.php';
require_once __DIR__ . '/../jobseeker_expiry_helper.php';
ensure_jobseeker_referrals_table($conn);
workconnect_send_inactivity_warning_emails($conn, 25, 30);
workconnect_expire_inactive_jobseekers($conn, 30);

$sql = "SELECT *, 
    YEAR(CURDATE())-YEAR(dob) - (DATE_FORMAT(CURDATE(),'%m%d') < DATE_FORMAT(dob,'%m%d')) AS age,
    DAY(submission_date) AS submission_day,
    MONTH(submission_date) AS submission_month,
    YEAR(submission_date) AS submission_year
FROM jobseeker ORDER BY id ASC";
$res = $conn->query($sql);
$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

$blockedByJobseeker = [];
if (count($data) > 0) {
    $allJsIds = array_map('intval', array_column($data, 'id'));
    $blockedByJobseeker = workconnect_referral_blocked_company_map_batch($conn, $allJsIds);
}

$refMap = [];
if (count($data) > 0) {
    $ids = array_map('intval', array_column($data, 'id'));
    $ids = array_filter($ids);
    if (count($ids) > 0) {
        $in = implode(',', $ids);
        $rq = $conn->query("SELECT r.jobseeker_id, r.company_id, r.status, r.rejection_reason, c.company_name
            FROM jobseeker_company_referrals r
            INNER JOIN company_users c ON c.id = r.company_id
            WHERE r.jobseeker_id IN ($in)
            ORDER BY c.company_name ASC");
        if ($rq) {
            while ($r = $rq->fetch_assoc()) {
                $jid = (int)$r['jobseeker_id'];
                if (!isset($refMap[$jid])) {
                    $refMap[$jid] = [];
                }
                $refMap[$jid][] = [
                    'company_id' => (int)$r['company_id'],
                    'company_name' => $r['company_name'],
                    'status' => $r['status'],
                    'rejection_reason' => $r['rejection_reason'],
                ];
            }
        }
    }
}
$appMap = [];
$jaeCheck = $conn->query("SHOW TABLES LIKE 'job_applications_extended'");
if ($jaeCheck && $jaeCheck->num_rows > 0 && count($data) > 0) {
    $jsIds = array_map('intval', array_column($data, 'id'));
    $jsIds = array_values(array_filter($jsIds));
    if (count($jsIds) > 0) {
        $in = implode(',', $jsIds);
        $aq = $conn->query("
            SELECT jae.jobseeker_id, jae.viewed_date, jae.applied_date, jp.title, jp.company, cu.company_name
            FROM job_applications_extended jae
            INNER JOIN job_postings jp ON jp.id = jae.job_posting_id
            LEFT JOIN company_users cu ON cu.id = jp.company_id
            WHERE jae.jobseeker_id IN ($in) AND jae.status = 'Accepted'
            ORDER BY jae.viewed_date DESC, jae.applied_date DESC
        ");
        if ($aq) {
            while ($a = $aq->fetch_assoc()) {
                $jid = (int)$a['jobseeker_id'];
                if (!isset($appMap[$jid])) {
                    $cn = trim($a['company_name'] ?? '');
                    if ($cn === '') {
                        $cn = trim($a['company'] ?? '');
                    }
                    $appMap[$jid] = [
                        'accepted_job_title' => $a['title'] ?? '',
                        'accepted_company_name' => $cn,
                    ];
                }
            }
        }
    }
}

foreach ($data as &$row) {
    $jid = (int) $row['id'];
    $row['blocked_referral_company_ids'] = $blockedByJobseeker[$jid] ?? [];
    $row['referrals'] = $refMap[$jid] ?? [];
    if (!empty($appMap[$jid])) {
        $row['accepted_job_title'] = $appMap[$jid]['accepted_job_title'];
        $row['accepted_company_name'] = $appMap[$jid]['accepted_company_name'];
    } else {
        $row['accepted_job_title'] = null;
        $row['accepted_company_name'] = null;
    }
}
unset($row);
echo json_encode($data);
$conn->close();
?>
