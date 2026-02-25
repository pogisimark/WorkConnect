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

// Eligible: has a Pending application (7-day restriction disabled for testing; re-enable by uncommenting AND below)
$sql = "SELECT j.id AS jobseeker_id,
        COALESCE(j.submission_date, j.created_at) AS pending_since,
        DATEDIFF(CURDATE(), COALESCE(j.submission_date, j.created_at)) AS days_pending
        FROM jobseeker j
        WHERE j.user_id = ?
        AND (j.application_status IS NULL OR j.application_status = '' OR j.application_status = 'Pending')
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
        'message' => 'You have no pending application.'
    ]);
    exit;
}

$row = $result->fetch_assoc();
$jobseeker_id = (int) $row['jobseeker_id'];
$days_pending = (int) $row['days_pending'];

// Check if there is already a pending follow-up request for this jobseeker
$check = $conn->prepare("SELECT id, status, message, admin_response, responded_at FROM follow_up_requests WHERE jobseeker_id = ? ORDER BY id DESC LIMIT 1");
$check->bind_param("i", $jobseeker_id);
$check->execute();
$reqResult = $check->get_result();
$check->close();

$existing_pending = false;
$existing_answered = null;

if ($reqResult->num_rows > 0) {
    $req = $reqResult->fetch_assoc();
    if ($req['status'] === 'pending') {
        $existing_pending = true;
    } else {
        $existing_answered = [
            'message' => $req['message'],
            'admin_response' => $req['admin_response'],
            'responded_at' => $req['responded_at']
        ];
    }
}

echo json_encode([
    'success' => true,
    'eligible' => true,
    'jobseeker_id' => $jobseeker_id,
    'days_pending' => $days_pending,
    'already_pending' => $existing_pending,
    'last_response' => $existing_answered
]);
$conn->close();
