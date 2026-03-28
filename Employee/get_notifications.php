<?php
// Prevent PHP notices/warnings from breaking JSON response - must be first
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

require_once 'db.php';

/**
 * Announcement bodies were historically stored as HTML snippets; escapeHtml in the UI then showed raw tags.
 * New rows are plain text from createAnnouncementNotification; this normalizes old rows at read time.
 */
function workconnect_plain_announcement_notification_message($message, $type, $title) {
    $t = (string)($title ?? '');
    $ty = strtolower((string)($type ?? ''));
    $isAnnouncement = ($ty === 'announcement') || (stripos($t, 'new announcement:') === 0);
    if (!$isAnnouncement) {
        return $message;
    }
    $m = (string)($message ?? '');
    $plain = preg_replace('/\s+/', ' ', trim(strip_tags($m)));
    $plain = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if (function_exists('mb_strlen') && mb_strlen($plain) > 600) {
        return mb_substr($plain, 0, 600) . '…';
    }
    if (strlen($plain) > 600) {
        return substr($plain, 0, 600) . '…';
    }
    return $plain;
}

// Start session without redirecting (API must always return JSON)
if (session_status() === PHP_SESSION_NONE) {
    require_once 'session_init.php';
}

// Discard any stray output (PHP notices, etc.) before sending JSON
ob_end_clean();
header('Content-Type: application/json');

// Check connection before use
if (!$conn || $conn->connect_error) {
    echo json_encode(['notifications' => [], 'error' => 'Database unavailable']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['notifications' => []]);
    exit;
}

// Get notifications for the current user
$hasTypeColumn = false;
$typeCheck = $conn->query("SHOW COLUMNS FROM notifications LIKE 'type'");
if ($typeCheck && $typeCheck->num_rows > 0) {
    $hasTypeColumn = true;
}

$selectSql = $hasTypeColumn
    ? "SELECT id, title, message, type, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30"
    : "SELECT id, title, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30";

$stmt = $conn->prepare($selectSql);
if (!$stmt) {
    echo json_encode(['notifications' => []]);
    exit;
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $typeVal = $hasTypeColumn ? ($row['type'] ?? 'info') : 'info';
        $titleVal = $row['title'] ?? '';
        $msgVal = workconnect_plain_announcement_notification_message($row['message'] ?? '', $typeVal, $titleVal);
        $notifications[] = [
            'id' => (int)($row['id'] ?? 0),
            'title' => $titleVal,
            'message' => $msgVal,
            'type' => $typeVal,
            'is_read' => (bool)($row['is_read'] ?? 0),
            'created_at' => date('M j, Y g:i A', strtotime($row['created_at'] ?? 'now')),
            'created_at_iso' => date('c', strtotime($row['created_at'] ?? 'now'))
        ];
    }
}

echo json_encode(['notifications' => $notifications]);
$stmt->close();
$conn->close();
?>
