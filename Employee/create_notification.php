<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// When this file is requested as an API, buffer output; when included for helpers only, do not touch output buffers.
$workconnectCreateNotificationIsDirect = (basename($_SERVER['PHP_SELF'] ?? '') === 'create_notification.php');
if ($workconnectCreateNotificationIsDirect) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ob_start();
}

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
    
    if (!$conn || $conn->connect_error) {
        return ['success' => false, 'sent' => 0, 'total' => 0];
    }
    
    $title = 'New Announcement: ' . $announcement_title;
    $plain = preg_replace('/\s+/', ' ', trim(strip_tags($announcement_description)));
    $plain = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $snippet = function_exists('mb_substr')
        ? (mb_strlen($plain) > 160 ? mb_substr($plain, 0, 160) . '…' : $plain)
        : (strlen($plain) > 160 ? substr($plain, 0, 160) . '…' : $plain);
    $message = $snippet;
    
    $cntRes = $conn->query('SELECT COUNT(*) AS c FROM employee_users');
    $total_count = $cntRes ? (int) ($cntRes->fetch_assoc()['c'] ?? 0) : 0;
    
    // One INSERT…SELECT is far faster than N single-row inserts
    $check_column = $conn->query("SHOW COLUMNS FROM notifications LIKE 'type'");
    $has_type_column = $check_column && $check_column->num_rows > 0;
    
    if ($has_type_column) {
        $bulk = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) SELECT id, ?, ?, 'announcement' FROM employee_users");
        $bulk->bind_param('ss', $title, $message);
    } else {
        $bulk = $conn->prepare('INSERT INTO notifications (user_id, title, message) SELECT id, ?, ? FROM employee_users');
        $bulk->bind_param('ss', $title, $message);
    }
    
    if ($bulk && $bulk->execute()) {
        $success_count = (int) $conn->affected_rows;
        $bulk->close();
        return [
            'success' => $success_count > 0,
            'sent' => $success_count,
            'total' => $total_count
        ];
    }
    
    if (isset($bulk) && $bulk) {
        error_log('createAnnouncementNotification bulk insert failed: ' . $bulk->error);
        $bulk->close();
    }
    
    // Fallback: per-user inserts (older schemas / edge cases)
    $stmt = $conn->prepare('SELECT id AS user_id FROM employee_users');
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $success_count = 0;
    $total_count = count($users);
    foreach ($users as $user) {
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
if ($workconnectCreateNotificationIsDirect && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
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
