<?php
/**
 * Mark company reply(ies) as read by admin (admin_company_follow_up.admin_response_read_at).
 */
date_default_timezone_set('Asia/Manila');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/admin_company_follow_up_badge.php';

if (!$conn || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

acfu_ensure_admin_response_read_column($conn);

$input = json_decode(file_get_contents('php://input'), true);
$mark_all = !empty($input['mark_all']);

if ($mark_all) {
    $sql = "UPDATE admin_company_follow_up SET admin_response_read_at = NOW() 
        WHERE COALESCE(hidden_by_admin, 0) = 0 
        AND status = 'answered' 
        AND company_response IS NOT NULL AND TRIM(company_response) <> '' 
        AND admin_response_read_at IS NULL";
    $ok = $conn->query($sql);
    $remaining = acfu_get_unread_response_count($conn);
    $conn->close();
    echo json_encode(['success' => (bool) $ok, 'message' => $ok ? 'Marked all as read.' : 'Failed to update.', 'remaining_unread' => $remaining]);
    exit;
}

$ids = isset($input['ids']) && is_array($input['ids']) ? $input['ids'] : [];
$ids = array_values(array_filter(array_map('intval', $ids), function ($x) {
    return $x > 0;
}));
if (count($ids) === 0) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'No ids provided.']);
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));
$sql = "UPDATE admin_company_follow_up SET admin_response_read_at = NOW() 
    WHERE id IN ($placeholders) 
    AND COALESCE(hidden_by_admin, 0) = 0 
    AND status = 'answered' 
    AND company_response IS NOT NULL AND TRIM(company_response) <> '' 
    AND admin_response_read_at IS NULL";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Database error.']);
    exit;
}
$stmt->bind_param($types, ...$ids);
$ok = $stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();
$remaining = acfu_get_unread_response_count($conn);
$conn->close();

echo json_encode(['success' => $ok, 'message' => 'Marked as read.', 'affected' => $affected, 'remaining_unread' => $remaining]);
