<?php
/**
 * Ensures jobseeker_company_referrals exists and backfills from legacy referred_to_company_id.
 */
function ensure_jobseeker_referrals_table(mysqli $conn): bool
{
    $sql = "CREATE TABLE IF NOT EXISTS jobseeker_company_referrals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        jobseeker_id INT NOT NULL,
        company_id INT NOT NULL,
        status ENUM('pending','accepted','rejected','withdrawn') NOT NULL DEFAULT 'pending',
        rejection_reason TEXT NULL,
        referred_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_js_company (jobseeker_id, company_id),
        KEY idx_jobseeker (jobseeker_id),
        KEY idx_company_status (company_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!$conn->query($sql)) {
        error_log('ensure_jobseeker_referrals_table create: ' . $conn->error);
        return false;
    }
    // Legacy rows: one referral per referred jobseeker
    $conn->query("INSERT IGNORE INTO jobseeker_company_referrals (jobseeker_id, company_id, status)
        SELECT id, referred_to_company_id, 'pending' FROM jobseeker
        WHERE application_status = 'Referred' AND referred_to_company_id IS NOT NULL");
    return true;
}

/** @return list<array{company_id:int,company_name:string,status:string,rejection_reason:?string}> */
function fetch_jobseeker_referrals_for_api(mysqli $conn, int $jobseeker_id): array
{
    $out = [];
    $sql = "SELECT r.company_id, r.status, r.rejection_reason, c.company_name
            FROM jobseeker_company_referrals r
            INNER JOIN company_users c ON c.id = r.company_id
            WHERE r.jobseeker_id = ?
            ORDER BY c.company_name ASC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return $out;
    }
    $stmt->bind_param('i', $jobseeker_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $out[] = [
            'company_id' => (int)$row['company_id'],
            'company_name' => $row['company_name'],
            'status' => $row['status'],
            'rejection_reason' => $row['rejection_reason'],
        ];
    }
    $stmt->close();
    return $out;
}
