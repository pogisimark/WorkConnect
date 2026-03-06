<?php
date_default_timezone_set('Asia/Manila');
require_once 'session_check.php';
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'eligible' => false, 'message' => 'Not authenticated']);
    exit;
}

if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'eligible' => false, 'message' => 'Database connection failed']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Eligible: has a Pending or Referred application (disabled when Rejected or Accepted)
$sql = "SELECT j.id AS jobseeker_id,
        COALESCE(j.submission_date, j.created_at) AS pending_since,
        DATEDIFF(CURDATE(), COALESCE(j.submission_date, j.created_at)) AS days_pending
        FROM jobseeker j
        WHERE j.user_id = ?
        AND (j.application_status IS NULL OR j.application_status = '' OR j.application_status = 'Pending' OR j.application_status = 'Referred')
        /* AND COALESCE(j.submission_date, j.created_at) <= DATE_SUB(CURDATE(), INTERVAL 7 DAY) */
        ORDER BY j.id DESC
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => true,
        'eligible' => false,
        'message' => 'You have no pending or referred application. Follow-up requests are only available when your application status is Pending or Referred.'
    ]);
    exit;
}

$row = $result->fetch_assoc();
$jobseeker_id = (int) $row['jobseeker_id'];
$days_pending = (int) $row['days_pending'];

// Fetch all follow-up requests for this jobseeker (newest first) for conversation history
$listStmt = $conn->prepare("SELECT id, message, status, admin_response, responded_at, created_at FROM follow_up_requests WHERE jobseeker_id = ? AND (COALESCE(hidden_by_jobseeker, 0) = 0) ORDER BY created_at DESC, id DESC");
$listStmt->bind_param("i", $jobseeker_id);
$listStmt->execute();
$listResult = $listStmt->get_result();
$requests = [];
while ($r = $listResult->fetch_assoc()) {
    // Send datetimes as ISO 8601 with +08:00 so frontend always displays correct PH time
    $createdAt = $r['created_at'] ? date('c', strtotime($r['created_at'])) : null;
    $respondedAt = $r['responded_at'] ? date('c', strtotime($r['responded_at'])) : null;
    $requests[] = [
        'id' => (int) $r['id'],
        'message' => $r['message'],
        'status' => $r['status'],
        'admin_response' => $r['admin_response'],
        'responded_at' => $respondedAt,
        'created_at' => $createdAt
    ];
}
$listStmt->close();

$existing_pending = false;
foreach ($requests as $r) {
    if ($r['status'] === 'pending') {
        $existing_pending = true;
        break;
    }
}

echo json_encode([
    'success' => true,
    'eligible' => true,
    'jobseeker_id' => $jobseeker_id,
    'days_pending' => $days_pending,
    'already_pending' => $existing_pending,
    'requests' => $requests
]);
$conn->close();
