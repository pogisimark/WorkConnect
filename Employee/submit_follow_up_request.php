<?php
date_default_timezone_set('Asia/Manila');
require_once 'session_check.php';
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$message = isset($input['message']) ? trim($conn->real_escape_string($input['message'])) : '';
$message = $message === '' ? null : $message;

// Message is required
if ($message === null || $message === '') {
    echo json_encode(['success' => false, 'message' => 'Please enter a message before submitting your follow-up request.']);
    exit;
}

// Re-check eligibility: Pending or Referred application (disabled when Rejected or Accepted)
$sql = "SELECT j.id AS jobseeker_id
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
    echo json_encode(['success' => false, 'message' => 'You have no pending or referred application. Follow-up requests are only available when your application status is Pending or Referred.']);
    exit;
}

$row = $result->fetch_assoc();
$jobseeker_id = (int) $row['jobseeker_id'];

// Prevent duplicate pending request for same jobseeker
$dup = $conn->prepare("SELECT id FROM follow_up_requests WHERE jobseeker_id = ? AND status = 'pending'");
$dup->bind_param("i", $jobseeker_id);
$dup->execute();
$dupResult = $dup->get_result();
$dup->close();

if ($dupResult->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You already have a pending follow-up request. Please wait for a response.']);
    exit;
}

$insert = $conn->prepare("INSERT INTO follow_up_requests (jobseeker_id, message, status) VALUES (?, ?, 'pending')");
$insert->bind_param("is", $jobseeker_id, $message);
if ($insert->execute()) {
    $insert->close();
    $conn->close();
    echo json_encode(['success' => true, 'message' => 'Your follow-up request has been submitted. You will be notified when admin responds.']);
} else {
    $insert->close();
    $conn->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to submit request. Please try again.']);
}
