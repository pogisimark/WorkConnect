<?php
// Suppress error output
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any unwanted output
ob_start();
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

require_once 'db.php';

function createNotification($user_id, $title, $message, $type = 'info') {
    global $conn;
    
    // Check if connection is valid
    if (!$conn || $conn->connect_error) {
        error_log("Database connection error in createNotification");
        return false;
    }
    
    // Check if type column exists in notifications table
    $check_column = $conn->query("SHOW COLUMNS FROM notifications LIKE 'type'");
    $has_type_column = $check_column && $check_column->num_rows > 0;
    
    if ($has_type_column) {
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $title, $message, $type);
    } else {
        // Table doesn't have type column, insert without it
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $title, $message);
    }
    
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        error_log("Failed to create notification: " . $stmt->error);
        $stmt->close();
        return false;
    }
}

function createAnnouncementNotification($announcement_title, $announcement_description) {
    global $conn;
    
    // Send to all employee accounts, not only users with jobseeker rows.
    $stmt = $conn->prepare("SELECT id AS user_id FROM employee_users");
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $success_count = 0;
    $total_count = count($users);
    
    foreach ($users as $user) {
        $title = "New Announcement: " . $announcement_title;
        $plain = preg_replace('/\s+/', ' ', trim(strip_tags($announcement_description)));
        $plain = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $snippet = function_exists('mb_substr')
            ? (mb_strlen($plain) > 160 ? mb_substr($plain, 0, 160) . '…' : $plain)
            : (strlen($plain) > 160 ? substr($plain, 0, 160) . '…' : $plain);
        $message = $snippet;
        
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
    
    // Check if database connection is valid
    if (!$conn || $conn->connect_error) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }
    
    if (isset($input['user_id'], $input['title'], $input['message'])) {
        $type = $input['type'] ?? 'info';
        
        if (createNotification($input['user_id'], $input['title'], $input['message'], $type)) {
            ob_clean();
            echo json_encode(['success' => true, 'message' => 'Notification created successfully']);
        } else {
            ob_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to create notification. Check server logs for details.']);
        }
    } else {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required parameters: user_id, title, message']);
    }
    exit;
}
?>
