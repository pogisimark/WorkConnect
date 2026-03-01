<?php
date_default_timezone_set('Asia/Manila');
require_once 'session_check.php';
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$conn) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$company_id = (int) $_SESSION['company_id'];
$input = json_decode(file_get_contents('php://input'), true);
$ids = isset($input['ids']) && is_array($input['ids']) ? $input['ids'] : [];
$ids = array_map('intval', array_filter($ids));
if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'No request(s) selected']);
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $conn->prepare("UPDATE admin_company_follow_up SET hidden_by_company = 1 WHERE company_id = ? AND id IN ($placeholders)");
$types = 'i' . str_repeat('i', count($ids));
$params = array_merge([$company_id], $ids);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$hidden = $stmt->affected_rows;
$stmt->close();

$stmt2 = $conn->prepare("DELETE FROM admin_company_follow_up WHERE company_id = ? AND id IN ($placeholders) AND hidden_by_admin = 1 AND hidden_by_company = 1");
$stmt2->bind_param($types, ...$params);
$stmt2->execute();
$stmt2->close();
$conn->close();

echo json_encode([
    'success' => true,
    'message' => $hidden === 1 ? '1 request removed from your list.' : $hidden . ' requests removed from your list.',
    'deleted' => $hidden
]);
