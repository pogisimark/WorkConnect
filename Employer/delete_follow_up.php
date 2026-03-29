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
$ids = isset($input['ids']) && is_array($input['ids']) ? $input['ids'] : [];

$ids = array_map('intval', array_filter($ids));
if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'No request(s) selected']);
    exit;
}

// Hide for admin only (jobseeker still sees the request).
// Pending requests must be answered before they can be removed (UI + API).
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $conn->prepare("UPDATE follow_up_requests SET hidden_by_admin = 1 WHERE id IN ($placeholders) AND status = 'answered'");
$types = str_repeat('i', count($ids));
$stmt->bind_param($types, ...$ids);
$stmt->execute();
$hidden = $stmt->affected_rows;
$stmt->close();

$requested = count($ids);
if ($hidden === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Pending follow-up requests cannot be removed until you send a response.',
    ]);
    $conn->close();
    exit;
}

// If jobseeker already hid these, remove from DB
$stmt2 = $conn->prepare("DELETE FROM follow_up_requests WHERE id IN ($placeholders) AND hidden_by_jobseeker = 1 AND hidden_by_admin = 1");
$stmt2->bind_param($types, ...$ids);
$stmt2->execute();
$stmt2->close();
$conn->close();

$skipped = $requested - $hidden;
$baseMsg = $hidden === 1 ? '1 request removed from your list.' : $hidden . ' requests removed from your list.';
if ($skipped > 0) {
    $baseMsg .= ' ' . $skipped . ' pending request(s) were skipped (respond first).';
}

echo json_encode([
    'success' => true,
    'message' => $baseMsg,
    'deleted' => $hidden
]);