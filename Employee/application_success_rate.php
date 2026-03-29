<?php
/**
 * Success rates for job applications submitted from Recommended Jobs
 * (job_applications_extended), not NSRP / jobseeker.application_status.
 */
if (session_status() == PHP_SESSION_NONE) {
    require_once 'session_init.php';
}

header('Content-Type: application/json');

require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please log in']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$emptyPayload = [
    'success' => true,
    'data' => [
        'total_applications' => 0,
        'accepted_count' => 0,
        'rejected_count' => 0,
        'pending_count' => 0,
        'withdrawn_count' => 0,
        'closed_count' => 0,
        'success_rate' => 0,
        'rejection_rate' => 0,
        'pending_rate' => 0,
        'withdrawn_rate' => 0,
        'closed_rate' => 0,
        'top_rejection_reasons' => [],
        'monthly_trends' => [],
    ],
];

try {
    $tbl = $conn->query("SHOW TABLES LIKE 'job_applications_extended'");
    if (!$tbl || $tbl->num_rows === 0) {
        echo json_encode($emptyPayload);
        exit;
    }

    $sql = "SELECT jae.status, jae.applied_date, jae.notes
            FROM job_applications_extended jae
            INNER JOIN jobseeker j ON j.id = jae.jobseeker_id
            WHERE j.user_id = ?
            ORDER BY jae.applied_date DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $user_id);
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();

    $totalApplications = 0;
    $acceptedCount = 0;
    $rejectedCount = 0;
    $pendingCount = 0;
    $withdrawnCount = 0;
    $closedCount = 0;
    $rejectionReasons = [];
    $monthlyData = [];

    while ($row = $result->fetch_assoc()) {
        $totalApplications++;
        $status = isset($row['status']) && $row['status'] !== ''
            ? trim($row['status'])
            : 'Applied';
        $statusLower = strtolower($status);

        if ($statusLower === 'accepted') {
            $acceptedCount++;
        } elseif ($statusLower === 'rejected') {
            $rejectedCount++;
            if (!empty($row['notes'])) {
                $reason = trim($row['notes']);
                if ($reason !== '') {
                    if (!isset($rejectionReasons[$reason])) {
                        $rejectionReasons[$reason] = 0;
                    }
                    $rejectionReasons[$reason]++;
                }
            }
        } elseif ($statusLower === 'withdrawn') {
            $withdrawnCount++;
        } elseif ($statusLower === 'closed') {
            $closedCount++;
        } else {
            // Applied, Viewed, Interview, etc. — still awaiting employer decision
            $pendingCount++;
        }

        if (!empty($row['applied_date'])) {
            $ts = strtotime($row['applied_date']);
            if ($ts) {
                $monthKey = date('Y-m', $ts);
                if (!isset($monthlyData[$monthKey])) {
                    $monthlyData[$monthKey] = [
                        'total' => 0,
                        'accepted' => 0,
                        'rejected' => 0,
                        'pending' => 0,
                        'withdrawn' => 0,
                        'closed' => 0,
                    ];
                }
                $monthlyData[$monthKey]['total']++;
                if ($statusLower === 'accepted') {
                    $monthlyData[$monthKey]['accepted']++;
                } elseif ($statusLower === 'rejected') {
                    $monthlyData[$monthKey]['rejected']++;
                } elseif ($statusLower === 'withdrawn') {
                    $monthlyData[$monthKey]['withdrawn']++;
                } elseif ($statusLower === 'closed') {
                    $monthlyData[$monthKey]['closed']++;
                } else {
                    $monthlyData[$monthKey]['pending']++;
                }
            }
        }
    }
    $stmt->close();

    $successRate = $totalApplications > 0 ? round(($acceptedCount / $totalApplications) * 100, 1) : 0;
    $rejectionRate = $totalApplications > 0 ? round(($rejectedCount / $totalApplications) * 100, 1) : 0;
    $pendingRate = $totalApplications > 0 ? round(($pendingCount / $totalApplications) * 100, 1) : 0;
    $withdrawnRate = $totalApplications > 0 ? round(($withdrawnCount / $totalApplications) * 100, 1) : 0;
    $closedRate = $totalApplications > 0 ? round(($closedCount / $totalApplications) * 100, 1) : 0;

    arsort($rejectionReasons);
    $topRejectionReasons = array_slice($rejectionReasons, 0, 3, true);

    ksort($monthlyData);
    $monthlyTrends = [];
    foreach ($monthlyData as $month => $data) {
        $monthlyTrends[] = [
            'month' => $month,
            'total' => $data['total'],
            'accepted' => $data['accepted'],
            'rejected' => $data['rejected'],
            'pending' => $data['pending'],
            'withdrawn' => $data['withdrawn'] ?? 0,
            'closed' => $data['closed'] ?? 0,
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'total_applications' => $totalApplications,
            'accepted_count' => $acceptedCount,
            'rejected_count' => $rejectedCount,
            'pending_count' => $pendingCount,
            'withdrawn_count' => $withdrawnCount,
            'closed_count' => $closedCount,
            'success_rate' => $successRate,
            'rejection_rate' => $rejectionRate,
            'pending_rate' => $pendingRate,
            'withdrawn_rate' => $withdrawnRate,
            'closed_rate' => $closedRate,
            'top_rejection_reasons' => $topRejectionReasons,
            'monthly_trends' => array_slice($monthlyTrends, -6),
        ],
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log('Application Success Rate Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching application success rate: ' . $e->getMessage(),
        'error_details' => $conn->error ?? 'No database error',
    ]);
}

if (isset($conn)) {
    $conn->close();
}
