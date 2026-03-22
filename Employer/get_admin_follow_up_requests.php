<?php
date_default_timezone_set('Asia/Manila');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'requests' => []]);
    exit;
}

require_once 'db.php';
require_once __DIR__ . '/admin_company_follow_up_badge.php';

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'requests' => []]);
    exit;
}

acfu_ensure_admin_response_read_column($conn);

$sql = "SELECT f.id, f.company_id, f.message, f.status, f.company_response, f.responded_at, f.created_at,
        f.admin_response_read_at,
        c.company_name
        FROM admin_company_follow_up f
        JOIN company_users c ON f.company_id = c.id
        WHERE (COALESCE(f.hidden_by_admin, 0) = 0)
        ORDER BY f.created_at DESC";
$result = $conn->query($sql);
$requests = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $hasReply = $row['status'] === 'answered'
            && $row['company_response'] !== null
            && trim((string) $row['company_response']) !== '';
        $readAt = $row['admin_response_read_at'] ?? null;
        $unreadReply = $hasReply && ($readAt === null || $readAt === '');

        $requests[] = [
            'id' => (int) $row['id'],
            'company_id' => (int) $row['company_id'],
            'company_name' => $row['company_name'],
            'message' => $row['message'],
            'status' => $row['status'],
            'company_response' => $row['company_response'],
            'responded_at' => $row['responded_at'] ? date('c', strtotime($row['responded_at'])) : null,
            'created_at' => $row['created_at'] ? date('c', strtotime($row['created_at'])) : null,
            'admin_response_read_at' => $readAt ? date('c', strtotime($readAt)) : null,
            'unread_company_reply' => $unreadReply,
        ];
    }
}
$conn->close();

echo json_encode(['success' => true, 'requests' => $requests]);
