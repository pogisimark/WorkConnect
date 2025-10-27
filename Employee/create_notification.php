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

function createAnnouncementNotification($announcement_title, $announcement_description) {
    global $conn;
    
    // Get all user IDs
    $stmt = $conn->prepare("SELECT user_id FROM jobseeker");
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $success_count = 0;
    $total_count = count($users);
    
    foreach ($users as $user) {
        $title = "New Announcement: " . $announcement_title;
        $message = strlen($announcement_description) > 100 ? 
            substr($announcement_description, 0, 100) . "..." : 
            $announcement_description;
        
        if (createNotification($user['user_id'], $title, $message, 'announcement')) {
            $success_count++;
        }
    }
    
    return [
        'success' => $success_count > 0,
        'sent' => $success_count,
        'total' => $total_count
    ];
}

// API endpoint for creating notifications - only execute if this file is accessed directly
if (basename($_SERVER['PHP_SELF']) === 'create_notification.php' && $_SERVER['REQUEST_METHOD'] === 'POST') {
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
