<?php
header('Content-Type: application/json');
require_once 'db.php';
require_once __DIR__ . '/referrals_schema.php';

try {
    ensure_jobseeker_referrals_table($conn);

    $sql = "
        SELECT
            c.id,
            c.company_name,
            COUNT(r.id) AS referral_count
        FROM company_users c
        LEFT JOIN jobseeker_company_referrals r
            ON r.company_id = c.id
        GROUP BY c.id, c.company_name
        ORDER BY c.company_name ASC
    ";
    $res = $conn->query($sql);
    if (!$res) {
        throw new Exception('Unable to load company referral analytics.');
    }

    $companies = [];
    $totalReferrals = 0;
    while ($row = $res->fetch_assoc()) {
        $count = (int)($row['referral_count'] ?? 0);
        $totalReferrals += $count;
        $companies[] = [
            'company_id' => (int)$row['id'],
            'company_name' => (string)$row['company_name'],
            'referral_count' => $count
        ];
    }

    usort($companies, function ($a, $b) {
        if ($a['referral_count'] === $b['referral_count']) {
            return strcasecmp($a['company_name'], $b['company_name']);
        }
        return $b['referral_count'] <=> $a['referral_count'];
    });

    echo json_encode([
        'success' => true,
        'total_referrals' => $totalReferrals,
        'total_companies' => count($companies),
        'companies' => $companies
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'total_referrals' => 0,
        'total_companies' => 0,
        'companies' => []
    ]);
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
