<?php
/** JSON: { "success": true, "count": N } — unread company replies on admin follow-up threads */
date_default_timezone_set('Asia/Manila');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/admin_company_follow_up_badge.php';

$n = acfu_get_unread_response_count($conn);
if ($conn) {
    $conn->close();
}
echo json_encode(['success' => true, 'count' => $n]);
