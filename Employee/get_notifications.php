<?php
// Prevent PHP notices/warnings from breaking JSON response - must be first
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

require_once 'db.php';

// Start session without redirecting (API must always return JSON)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
$stmt = $conn->prepare("SELECT id, title, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
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
        $notifications[] = [
            'id' => (int)($row['id'] ?? 0),
            'title' => $row['title'] ?? '',
            'message' => $row['message'] ?? '',
            'is_read' => (bool)($row['is_read'] ?? 0),
            'created_at' => date('M j, Y g:i A', strtotime($row['created_at'] ?? 'now'))
        ];
    }
}

echo json_encode(['notifications' => $notifications]);
$stmt->close();
$conn->close();
?>
