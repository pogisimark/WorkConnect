<?php
date_default_timezone_set('Asia/Manila');
require_once 'session_check.php';
require_once 'db.php';

header('Content-Type: application/json');

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

// Get current user's jobseeker_id
$js = $conn->prepare("SELECT id FROM jobseeker WHERE user_id = ? LIMIT 1");
$js->bind_param("i", $user_id);
$js->execute();
$jsResult = $js->get_result();
$js->close();
if ($jsResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Jobseeker record not found']);
    exit;
}
$jobseeker_id = (int) $jsResult->fetch_assoc()['id'];

$input = json_decode(file_get_contents('php://input'), true);
$ids = isset($input['ids']) && is_array($input['ids']) ? $input['ids'] : [];
$ids = array_map('intval', array_filter($ids));
if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'No request(s) selected']);
    exit;
}

// Only allow deleting requests that have been answered by admin (status != 'pending')
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$checkStmt = $conn->prepare("SELECT id FROM follow_up_requests WHERE jobseeker_id = ? AND id IN ($placeholders) AND status = 'pending'");
$types = 'i' . str_repeat('i', count($ids));
$params = array_merge([$jobseeker_id], $ids);
$checkStmt->bind_param($types, ...$params);
$checkStmt->execute();
$pendingResult = $checkStmt->get_result();
$checkStmt->close();
if ($pendingResult->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You cannot delete a follow-up request that is still awaiting admin response.']);
    exit;
}

// Hide for jobseeker only (admin still sees the request)
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $conn->prepare("UPDATE follow_up_requests SET hidden_by_jobseeker = 1 WHERE jobseeker_id = ? AND id IN ($placeholders)");
$types = 'i' . str_repeat('i', count($ids));
$params = array_merge([$jobseeker_id], $ids);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$hidden = $stmt->affected_rows;
$stmt->close();

// If admin already hid these, remove from DB
$stmt2 = $conn->prepare("DELETE FROM follow_up_requests WHERE jobseeker_id = ? AND id IN ($placeholders) AND hidden_by_jobseeker = 1 AND hidden_by_admin = 1");
$stmt2->bind_param($types, ...$params);
$stmt2->execute();
$stmt2->close();
$conn->close();

echo json_encode([
    'success' => true,
    'message' => $hidden === 1 ? '1 request removed.' : $hidden . ' requests removed.',
    'deleted' => $hidden
]);