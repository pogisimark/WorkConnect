<?php
date_default_timezone_set('Asia/Manila');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$conn) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$request_id = isset($input['request_id']) ? (int) $input['request_id'] : 0;
$response_text = isset($input['response']) ? trim($conn->real_escape_string($input['response'])) : '';

if ($request_id <= 0 || $response_text === '') {
    echo json_encode(['success' => false, 'message' => 'Missing request ID or response text']);
    exit;
}

// Update follow_up_requests
$stmt = $conn->prepare("UPDATE follow_up_requests SET admin_response = ?, status = 'answered', responded_at = NOW() WHERE id = ? AND status = 'pending'");
$stmt->bind_param("si", $response_text, $request_id);
$stmt->execute();
if ($stmt->affected_rows === 0) {
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Request not found or already answered']);
    exit;
}
$stmt->close();

// Get jobseeker user_id for notification
$get = $conn->prepare("SELECT j.user_id FROM follow_up_requests f JOIN jobseeker j ON f.jobseeker_id = j.id WHERE f.id = ?");
$get->bind_param("i", $request_id);
$get->execute();
$res = $get->get_result();
$get->close();
if ($res->num_rows === 0) {
    echo json_encode(['success' => true, 'message' => 'Response saved. Notification could not be sent.']);
    $conn->close();
    exit;
}
$row = $res->fetch_assoc();
$user_id = (int) $row['user_id'];

// Create notification for jobseeker (notifications table may have optional 'type' column)
$title = 'Follow-up response';
$msg = strlen($response_text) > 200 ? substr($response_text, 0, 200) . '...' : $response_text;
$check_col = $conn->query("SHOW COLUMNS FROM notifications LIKE 'type'");
$has_type = $check_col && $check_col->num_rows > 0;
if ($has_type) {
    $ins = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'follow_up')");
    $ins->bind_param("iss", $user_id, $title, $msg);
} else {
    $ins = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    $ins->bind_param("iss", $user_id, $title, $msg);
}
$ins->execute();
$ins->close();
$conn->close();

echo json_encode(['success' => true, 'message' => 'Response sent. Jobseeker has been notified.']);
