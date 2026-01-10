<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Suppress error output
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any unwanted output
ob_start();

header('Content-Type: application/json');

$host = "workconnect.ct26qyouyans.ap-southeast-2.rds.amazonaws.com";
$user = "admin";
$pass = "Pogisimark";
$db   = "WorkConnect";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['jobseeker_id']) || !isset($input['status'])) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }
    
    $jobseeker_id = intval($input['jobseeker_id']);
    $status = trim($conn->real_escape_string($input['status'])); // Trim whitespace and escape
    $rejection_reason = isset($input['rejection_reason']) ? $conn->real_escape_string($input['rejection_reason']) : null;
    $referred_to_company_id = isset($input['referred_to_company_id']) ? intval($input['referred_to_company_id']) : null;
    
    // Debug logging (remove in production)
    error_log("Update Jobseeker Status - Jobseeker ID: $jobseeker_id, Status: '$status' (length: " . strlen($status) . "), Referred to Company ID: " . ($referred_to_company_id ?? 'NULL'));
    error_log("Raw input status: " . var_export($input['status'], true));
    error_log("Raw input referred_to_company_id: " . var_export($input['referred_to_company_id'] ?? 'NOT SET', true));
    
    // Check if application_status column is ENUM and if it includes 'Referred'
    $check_status_column = $conn->query("SHOW COLUMNS FROM jobseeker WHERE Field = 'application_status'");
    $status_column_info = null;
    $status_is_enum = false;
    $status_has_referred = false;
    
    if ($check_status_column && $check_status_column->num_rows > 0) {
        $status_column_info = $check_status_column->fetch_assoc();
        if (stripos($status_column_info['Type'], 'enum') !== false) {
            $status_is_enum = true;
            // Check if 'Referred' is in the ENUM values
            if (stripos($status_column_info['Type'], 'referred') !== false || stripos($status_column_info['Type'], "'Referred'") !== false) {
                $status_has_referred = true;
            }
        }
    }
    
    // If application_status is ENUM and doesn't include 'Referred', update it
    if ($status_is_enum && !$status_has_referred && strtolower(trim($status)) === 'referred') {
        error_log("application_status is ENUM without 'Referred' - updating ENUM to include 'Referred'");
        $alter_enum = $conn->query("ALTER TABLE jobseeker MODIFY COLUMN application_status ENUM('Pending', 'Referred', 'Accepted', 'Rejected') DEFAULT 'Pending'");
        if ($alter_enum) {
            error_log("Successfully updated application_status ENUM to include 'Referred'");
            $status_has_referred = true;
        } else {
            error_log("Failed to update application_status ENUM: " . $conn->error);
            // Try alternative: convert to VARCHAR
            $alter_varchar = $conn->query("ALTER TABLE jobseeker MODIFY COLUMN application_status VARCHAR(50) DEFAULT 'Pending'");
            if ($alter_varchar) {
                error_log("Converted application_status from ENUM to VARCHAR");
            } else {
                error_log("Failed to convert application_status to VARCHAR: " . $conn->error);
            }
        }
    }
    
    // Check if referred_to_company_id column exists
    $check_referred_column = "SHOW COLUMNS FROM jobseeker LIKE 'referred_to_company_id'";
    $referred_column_result = $conn->query($check_referred_column);
    $has_referred_column = $referred_column_result && $referred_column_result->num_rows > 0;
    
    // If column doesn't exist and we need it, create it
    if (!$has_referred_column && strtolower(trim($status)) === 'referred' && $referred_to_company_id) {
        $alter_result = $conn->query("ALTER TABLE jobseeker ADD COLUMN referred_to_company_id INT NULL AFTER application_status");
        if ($alter_result) {
            $has_referred_column = true;
            error_log("Created referred_to_company_id column");
        } else {
            error_log("Failed to create referred_to_company_id column: " . $conn->error);
        }
    }
    
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
    } elseif (strtolower(trim($status)) === 'referred' && $referred_to_company_id && $has_referred_column) {
        // Check if jobseeker exists and get current status
        $check_stmt = $conn->prepare("SELECT id, application_status FROM jobseeker WHERE id = ?");
        $check_stmt->bind_param("i", $jobseeker_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) {
            $current = $check_result->fetch_assoc();
            error_log("Jobseeker exists - Current status: " . ($current['application_status'] ?? 'NULL'));
        } else {
            error_log("ERROR: Jobseeker with ID $jobseeker_id does not exist!");
            $check_stmt->close();
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Jobseeker not found']);
            exit;
        }
        $check_stmt->close();
        
        // Update status and referred_to_company_id - always use 'Referred' with capital R
        $sql = "UPDATE jobseeker SET application_status = 'Referred', referred_to_company_id = $referred_to_company_id WHERE id = $jobseeker_id";
        error_log("SQL Query: $sql");
        error_log("Updating jobseeker_id: $jobseeker_id, status: 'Referred', referred_to_company_id: $referred_to_company_id");
    } elseif ($status === 'Referred' && !$referred_to_company_id && $has_referred_column) {
        // If status is Referred but no company_id provided, just update status (backward compatibility)
        $sql = "UPDATE jobseeker SET application_status = '$status' WHERE id = $jobseeker_id";
        error_log("WARNING: Referred status without company_id - SQL: $sql");
    } else {
        $sql = "UPDATE jobseeker SET application_status = '$status' WHERE id = $jobseeker_id";
    }
    
    error_log("Executing SQL: $sql");
    $query_result = $conn->query($sql);
    
    if ($query_result === TRUE) {
        $rows_affected = $conn->affected_rows;
        error_log("Update successful - Rows affected: $rows_affected");
        
        if ($rows_affected === 0) {
            error_log("WARNING: UPDATE query succeeded but 0 rows were affected! Jobseeker ID $jobseeker_id may not exist or WHERE clause didn't match.");
        }
        
        // Verify the update if it was a Referred status with company_id
        if (strtolower(trim($status)) === 'referred' && $referred_to_company_id && $has_referred_column) {
            // Verify both status and company_id
            $verify_stmt = $conn->prepare("SELECT application_status, referred_to_company_id FROM jobseeker WHERE id = ?");
            $verify_stmt->bind_param("i", $jobseeker_id);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result()->fetch_assoc();
            $verify_stmt->close();
            error_log("Verified - Status: " . ($verify_result['application_status'] ?? 'NULL') . ", Company ID: " . ($verify_result['referred_to_company_id'] ?? 'NULL'));
            
            // If verification fails, log warning
            if ($verify_result['application_status'] !== 'Referred') {
                error_log("WARNING: Status verification failed! Expected 'Referred', got: " . ($verify_result['application_status'] ?? 'NULL'));
            }
            if ($verify_result['referred_to_company_id'] != $referred_to_company_id) {
                error_log("WARNING: Company ID verification failed! Expected $referred_to_company_id, got: " . ($verify_result['referred_to_company_id'] ?? 'NULL'));
            }
        }
        
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
        
        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        ob_clean();
        http_response_code(500);
        $error_msg = $conn->error ? $conn->error : 'Unknown database error';
        $error_code = $conn->errno ? $conn->errno : 'Unknown';
        error_log("Database error (Code: $error_code): $error_msg");
        error_log("Failed SQL: $sql");
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $error_msg]);
    }
} else {
    ob_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

if (isset($conn)) {
    $conn->close();
}
?>
