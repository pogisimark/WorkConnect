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
require_once __DIR__ . '/admin_audit_helper.php';

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

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $conn->prepare("UPDATE admin_company_follow_up SET hidden_by_admin = 1 WHERE id IN ($placeholders)");
$types = str_repeat('i', count($ids));
$stmt->bind_param($types, ...$ids);
$stmt->execute();
$hidden = $stmt->affected_rows;
$stmt->close();
admin_audit_log(
    $conn,
    'FOLLOW_UP_REQUEST_HIDE',
    'admin_company_follow_up',
    null,
    'Admin hid follow-up request(s) from list.',
    ['ids' => $ids, 'hidden_count' => $hidden]
);

$stmt2 = $conn->prepare("DELETE FROM admin_company_follow_up WHERE id IN ($placeholders) AND hidden_by_admin = 1 AND hidden_by_company = 1");
$stmt2->bind_param($types, ...$ids);
$stmt2->execute();
$stmt2->close();
$conn->close();

echo json_encode([
    'success' => true,
    'message' => $hidden === 1 ? '1 request removed from your list.' : $hidden . ' requests removed from your list.',
    'deleted' => $hidden
]);
