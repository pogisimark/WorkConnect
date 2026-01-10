<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

require_once 'session_protect.php';
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_GET['job_id'])) {
    echo json_encode(['success' => false, 'message' => 'Job ID is required']);
    exit;
}

$job_id = intval($_GET['job_id']);

try {
    // Check if job_applications_extended table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'job_applications_extended'");
    
    if ($table_check && $table_check->num_rows > 0) {
        // Get application statistics
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_applications,
                SUM(CASE WHEN status = 'Applied' THEN 1 ELSE 0 END) as applied_count,
                SUM(CASE WHEN status = 'Viewed' THEN 1 ELSE 0 END) as viewed_count,
                SUM(CASE WHEN status = 'Interview' THEN 1 ELSE 0 END) as interview_count,
                SUM(CASE WHEN status = 'Accepted' THEN 1 ELSE 0 END) as accepted_count,
                SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected_count
            FROM job_applications_extended
            WHERE job_posting_id = ?
        ");
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Get recent applications (last 5)
        $stmt = $conn->prepare("
            SELECT 
                jae.id as application_id,
                jae.status as application_status,
                jae.applied_date,
                jae.compatibility_score,
                j.firstname,
                j.middlename,
                j.surname,
                j.email,
                j.contact
            FROM job_applications_extended jae
            INNER JOIN jobseeker j ON jae.jobseeker_id = j.id
            WHERE jae.job_posting_id = ?
            ORDER BY jae.applied_date DESC
            LIMIT 5
        ");
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $recent_applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'recent_applications' => $recent_applications
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'stats' => [
                'total_applications' => 0,
                'applied_count' => 0,
                'viewed_count' => 0,
                'interview_count' => 0,
                'accepted_count' => 0,
                'rejected_count' => 0
            ],
            'recent_applications' => []
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching applications: ' . $e->getMessage()]);
}

$conn->close();
?>
