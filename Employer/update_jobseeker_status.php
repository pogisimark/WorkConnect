<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Suppress error output
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any unwanted output
ob_start();

header('Content-Type: application/json');

$host = "workconnect.cp28esmqk7aq.ap-southeast-2.rds.amazonaws.com";
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
require_once __DIR__ . '/referrals_schema.php';

// Email sender setup (server-side guarantee for status updates)
$phpmailer_available = false;
if (file_exists('../vendor/autoload.php')) {
    try {
        require_once '../vendor/autoload.php';
        require_once 'email_config.php';
        $phpmailer_available = class_exists('PHPMailer\PHPMailer\PHPMailer');
    } catch (Exception $e) {
        $phpmailer_available = false;
    } catch (Error $e) {
        $phpmailer_available = false;
    }
}

function sendStatusEmail($to, $subject, $htmlBody, $plainBody, $phpmailer_available) {
    if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    if ($phpmailer_available && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $plainBody;
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Status email PHPMailer failed: " . $e->getMessage());
        }
    }

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: WorkConnect <noreply@workconnect.com>\r\n";
    return @mail($to, $subject, $htmlBody, $headers);
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
    
    $query_result = false;
    $sql = '';
    $referrals_payload = null;

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
        error_log("Executing SQL: $sql");
        $query_result = $conn->query($sql);
    } elseif (strtolower(trim($status)) === 'referred') {
        ensure_jobseeker_referrals_table($conn);

        $company_ids = [];
        if (!empty($input['referred_company_ids']) && is_array($input['referred_company_ids'])) {
            foreach ($input['referred_company_ids'] as $x) {
                $cid = (int)$x;
                if ($cid > 0 && !in_array($cid, $company_ids, true)) {
                    $company_ids[] = $cid;
                }
            }
        }
        if (count($company_ids) === 0 && $referred_to_company_id) {
            $company_ids[] = $referred_to_company_id;
        }
        if (count($company_ids) === 0) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Select at least one verified company to refer.']);
            exit;
        }

        $verified_ids = [];
        $evColChk = $conn->query("SHOW COLUMNS FROM company_users LIKE 'email_verified'");
        $hasEmailVerifiedCol = $evColChk && $evColChk->num_rows > 0;
        $vSql = $hasEmailVerifiedCol
            ? "SELECT id FROM company_users WHERE id = ? AND COALESCE(email_verified, 0) = 1"
            : "SELECT id FROM company_users WHERE id = ?";
        $vstmt = $conn->prepare($vSql);
        foreach ($company_ids as $cid) {
            $vstmt->bind_param("i", $cid);
            $vstmt->execute();
            $vr = $vstmt->get_result();
            if ($vr && $vr->num_rows > 0) {
                $verified_ids[] = $cid;
            }
        }
        $vstmt->close();

        if (count($verified_ids) !== count($company_ids)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'One or more companies are invalid or have not verified their email.']);
            exit;
        }
        $company_ids = $verified_ids;

        $check_stmt = $conn->prepare("SELECT id FROM jobseeker WHERE id = ?");
        $check_stmt->bind_param("i", $jobseeker_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows === 0) {
            $check_stmt->close();
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Jobseeker not found']);
            exit;
        }
        $check_stmt->close();

        if (!$has_referred_column) {
            $conn->query("ALTER TABLE jobseeker ADD COLUMN referred_to_company_id INT NULL AFTER application_status");
            $has_referred_column = true;
        }

        $first_company = (int)$company_ids[0];
        $referred_to_company_id = $first_company;
        $sql = "UPDATE jobseeker SET application_status = 'Referred', referred_to_company_id = $first_company WHERE id = $jobseeker_id";
        error_log("Executing SQL: $sql");
        $query_result = $conn->query($sql);

        if ($query_result) {
            $ins = $conn->prepare("INSERT INTO jobseeker_company_referrals (jobseeker_id, company_id, status) VALUES (?, ?, 'pending') ON DUPLICATE KEY UPDATE status = 'pending', rejection_reason = NULL");
            foreach ($company_ids as $cid) {
                $cid = (int)$cid;
                $ins->bind_param("ii", $jobseeker_id, $cid);
                $ins->execute();
            }
            $ins->close();
            $referrals_payload = fetch_jobseeker_referrals_for_api($conn, $jobseeker_id);
        }
    } elseif ($status === 'Referred' && !$referred_to_company_id && $has_referred_column) {
        $sql = "UPDATE jobseeker SET application_status = '$status' WHERE id = $jobseeker_id";
        error_log("WARNING: Referred status without company_id - SQL: $sql");
        error_log("Executing SQL: $sql");
        $query_result = $conn->query($sql);
    } else {
        $sql = "UPDATE jobseeker SET application_status = '$status' WHERE id = $jobseeker_id";
        error_log("Executing SQL: $sql");
        $query_result = $conn->query($sql);
    }
    
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
        
        // Create in-app notification for status changes + send email
        if (in_array($status, ['Referred', 'Accepted', 'Rejected'], true)) {
            $user_sql = "SELECT user_id, email, firstname, surname FROM jobseeker WHERE id = $jobseeker_id";
            $user_result = $conn->query($user_sql);
            if ($user_result && $user_result->num_rows > 0) {
                $jobseeker = $user_result->fetch_assoc();
                $user_id = (int)$jobseeker['user_id'];
                $jobseeker_email = trim($jobseeker['email'] ?? '');
                $jobseeker_name = trim(($jobseeker['firstname'] ?? '') . ' ' . ($jobseeker['surname'] ?? ''));
                if ($jobseeker_name === '') {
                    $jobseeker_name = 'Applicant';
                }

                $check_table = "SHOW TABLES LIKE 'notifications'";
                $table_result = $conn->query($check_table);
                $check_col = $conn->query("SHOW COLUMNS FROM notifications LIKE 'type'");
                $has_type = $check_col && $check_col->num_rows > 0;

                if ($table_result && $table_result->num_rows > 0) {
                    $notifTitle = 'Application Update';
                    $notifMsg = 'Your application status has been updated.';
                    if ($status === 'Referred') {
                        $notifTitle = 'Application Referred';
                        $refRows = fetch_jobseeker_referrals_for_api($conn, $jobseeker_id);
                        $names = [];
                        foreach ($refRows as $rr) {
                            if (($rr['status'] ?? '') === 'pending') {
                                $names[] = $rr['company_name'];
                            }
                        }
                        if (count($names) === 0 && !empty($referred_to_company_id)) {
                            $company_stmt = $conn->prepare("SELECT company_name FROM company_users WHERE id = ?");
                            if ($company_stmt) {
                                $company_stmt->bind_param("i", $referred_to_company_id);
                                $company_stmt->execute();
                                $company_data = $company_stmt->get_result()->fetch_assoc();
                                $company_stmt->close();
                                if (!empty($company_data['company_name'])) {
                                    $names[] = $company_data['company_name'];
                                }
                            }
                        }
                        if (count($names) > 1) {
                            $notifMsg = 'Your application has been referred to these companies for review: ' . implode(', ', $names) . '.';
                        } elseif (count($names) === 1) {
                            $notifMsg = 'Your application has been referred to ' . $names[0] . ' for review.';
                        } else {
                            $notifMsg = 'Your application has been referred for review.';
                        }
                    } elseif ($status === 'Accepted') {
                        $notifTitle = 'Application Accepted';
                        $notifMsg = 'Congratulations! Your application has been accepted.';
                    } elseif ($status === 'Rejected') {
                        $notifTitle = 'Application Rejected';
                        $notifMsg = $rejection_reason
                            ? "Your application has been rejected. Reason: $rejection_reason"
                            : 'Your application has been rejected.';
                    }

                    if ($has_type) {
                        $ins = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'application')");
                        if ($ins) {
                            $ins->bind_param("iss", $user_id, $notifTitle, $notifMsg);
                            $ins->execute();
                            $ins->close();
                        }
                    } else {
                        $ins = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
                        if ($ins) {
                            $ins->bind_param("iss", $user_id, $notifTitle, $notifMsg);
                            $ins->execute();
                            $ins->close();
                        }
                    }

                    // Email parity with in-app notifications
                    $emailSubject = "WorkConnect Application Update: {$status}";
                    $emailPlain = "Hi {$jobseeker_name},\n\n{$notifMsg}\n\n- WorkConnect";
                    $emailHtml = "<p>Hi " . htmlspecialchars($jobseeker_name) . ",</p><p>" . nl2br(htmlspecialchars($notifMsg)) . "</p><p>- WorkConnect</p>";
                    sendStatusEmail($jobseeker_email, $emailSubject, $emailHtml, $emailPlain, $phpmailer_available);
                }
            }
        }
        
        ob_clean();
        $out = ['success' => true, 'message' => 'Status updated successfully'];
        if ($referrals_payload !== null) {
            $out['referrals'] = $referrals_payload;
        }
        echo json_encode($out);
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
