<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please log in']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get all applications for this user
    $sql = "SELECT 
        application_status, 
        submission_year, 
        submission_month,
        submission_date,
        rejection_reason
        FROM jobseeker 
        WHERE user_id = ? 
        ORDER BY submission_year DESC, submission_month DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    $totalApplications = 0;
    $acceptedCount = 0;
    $rejectedCount = 0;
    $pendingCount = 0;
    $rejectionReasons = [];
    $monthlyData = [];
    
    while ($row = $result->fetch_assoc()) {
        $totalApplications++;
        $status = isset($row['application_status']) && !empty($row['application_status']) 
            ? $row['application_status'] 
            : 'Pending';
        
        if ($status === 'Accepted') {
            $acceptedCount++;
        } elseif ($status === 'Rejected') {
            $rejectedCount++;
            if (isset($row['rejection_reason']) && !empty($row['rejection_reason'])) {
                $reason = trim($row['rejection_reason']);
                if (!empty($reason)) {
                    if (!isset($rejectionReasons[$reason])) {
                        $rejectionReasons[$reason] = 0;
                    }
                    $rejectionReasons[$reason]++;
                }
            }
        } else {
            $pendingCount++;
        }
        
        // Track monthly data
        if (isset($row['submission_year']) && isset($row['submission_month']) && 
            $row['submission_year'] && $row['submission_month']) {
            $monthKey = $row['submission_year'] . '-' . str_pad($row['submission_month'], 2, '0', STR_PAD_LEFT);
            if (!isset($monthlyData[$monthKey])) {
                $monthlyData[$monthKey] = [
                    'total' => 0,
                    'accepted' => 0,
                    'rejected' => 0,
                    'pending' => 0
                ];
            }
            $monthlyData[$monthKey]['total']++;
            if ($status === 'Accepted') {
                $monthlyData[$monthKey]['accepted']++;
            } elseif ($status === 'Rejected') {
                $monthlyData[$monthKey]['rejected']++;
            } else {
                $monthlyData[$monthKey]['pending']++;
            }
        }
    }
    $stmt->close();
    
    // Calculate success rate
    $successRate = $totalApplications > 0 ? round(($acceptedCount / $totalApplications) * 100, 1) : 0;
    $rejectionRate = $totalApplications > 0 ? round(($rejectedCount / $totalApplications) * 100, 1) : 0;
    $pendingRate = $totalApplications > 0 ? round(($pendingCount / $totalApplications) * 100, 1) : 0;
    
    // Sort rejection reasons by frequency
    arsort($rejectionReasons);
    $topRejectionReasons = array_slice($rejectionReasons, 0, 3, true);
    
    // Sort monthly data by date
    ksort($monthlyData);
    $monthlyTrends = [];
    foreach ($monthlyData as $month => $data) {
        $monthlyTrends[] = [
            'month' => $month,
            'total' => $data['total'],
            'accepted' => $data['accepted'],
            'rejected' => $data['rejected'],
            'pending' => $data['pending']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_applications' => $totalApplications,
            'accepted_count' => $acceptedCount,
            'rejected_count' => $rejectedCount,
            'pending_count' => $pendingCount,
            'success_rate' => $successRate,
            'rejection_rate' => $rejectionRate,
            'pending_rate' => $pendingRate,
            'top_rejection_reasons' => $topRejectionReasons,
            'monthly_trends' => array_slice($monthlyTrends, -6) // Last 6 months
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('Application Success Rate Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching application success rate: ' . $e->getMessage(),
        'error_details' => $conn->error ?? 'No database error'
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>

