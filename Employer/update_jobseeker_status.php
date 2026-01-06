<?php
header('Content-Type: application/json');

$host = "workconnect.ct26qyouyans.ap-southeast-2.rds.amazonaws.com";
$user = "admin";
$pass = "Pogisimark";
$db   = "WorkConnect";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['jobseeker_id']) || !isset($input['status'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }
    
    $jobseeker_id = intval($input['jobseeker_id']);
    $status = $conn->real_escape_string($input['status']);
    $rejection_reason = isset($input['rejection_reason']) ? $conn->real_escape_string($input['rejection_reason']) : null;
    
    // Update application status and rejection reason if provided
    if ($rejection_reason && $status === 'Rejected') {
        // Check if rejection_reason column exists
        $check_column = "SHOW COLUMNS FROM jobseeker LIKE 'rejection_reason'";
        $column_result = $conn->query($check_column);
        
        if ($column_result && $column_result->num_rows > 0) {
            $sql = "UPDATE jobseeker SET application_status = '$status', rejection_reason = '$rejection_reason' WHERE id = $jobseeker_id";
        } else {
            $sql = "UPDATE jobseeker SET application_status = '$status' WHERE id = $jobseeker_id";
        }
    } else {
        $sql = "UPDATE jobseeker SET application_status = '$status' WHERE id = $jobseeker_id";
    }
    
    if ($conn->query($sql) === TRUE) {
        // If rejected with reason, create notification for the jobseeker
        if ($status === 'Rejected' && $rejection_reason) {
            // Get jobseeker's user_id
            $user_sql = "SELECT user_id FROM jobseeker WHERE id = $jobseeker_id";
            $user_result = $conn->query($user_sql);
            if ($user_result && $user_result->num_rows > 0) {
                $jobseeker = $user_result->fetch_assoc();
                $user_id = $jobseeker['user_id'];
                
                // Check if notifications table exists before creating notification
                $check_table = "SHOW TABLES LIKE 'notifications'";
                $table_result = $conn->query($check_table);
                
                if ($table_result && $table_result->num_rows > 0) {
                    // Create notification
                    $notification_sql = "INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES ($user_id, 'Application Rejected', 'Your job application has been rejected. Reason: $rejection_reason', 0, NOW())";
                    $conn->query($notification_sql);
                }
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

$conn->close();
?>
