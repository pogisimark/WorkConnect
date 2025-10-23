<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

require_once 'db.php';

function createNotification($user_id, $title, $message, $type = 'info') {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $title, $message, $type);
    
    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}

// API endpoint for creating notifications
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['user_id'], $input['title'], $input['message'])) {
        $type = $input['type'] ?? 'info';
        
        if (createNotification($input['user_id'], $input['title'], $input['message'], $type)) {
            echo json_encode(['success' => true, 'message' => 'Notification created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create notification']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    }
}
?>
