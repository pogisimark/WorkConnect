<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

// Check for valid session (either admin or employee)
$isAdmin = isset($_SESSION['username']); // Admin session
$isEmployee = isset($_SESSION['user_id']); // Employee session

if (!$isAdmin && !$isEmployee) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get the request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            if ($method === 'POST') {
                createAnnouncement();
            } else {
                throw new Exception('Method not allowed');
            }
            break;
            
        case 'read':
            if ($method === 'GET') {
                readAnnouncements();
            } else {
                throw new Exception('Method not allowed');
            }
            break;
            
        case 'update':
            if ($method === 'PUT' || $method === 'POST') {
                updateAnnouncement();
            } else {
                throw new Exception('Method not allowed');
            }
            break;
            
        case 'delete':
            if ($method === 'DELETE' || $method === 'POST') {
                deleteAnnouncement();
            } else {
                throw new Exception('Method not allowed');
            }
            break;
            
        case 'get_single':
            if ($method === 'GET') {
                getSingleAnnouncement();
            } else {
                throw new Exception('Method not allowed');
            }
            break;
            
        case 'change_status':
            if ($method === 'POST') {
                changeStatus();
            } else {
                throw new Exception('Method not allowed');
            }
            break;
            
        case 'upload_file':
            if ($method === 'POST') {
                uploadFile();
            } else {
                throw new Exception('Method not allowed');
            }
            break;
            
        case 'delete_file':
            if ($method === 'POST') {
                deleteFile();
            } else {
                throw new Exception('Method not allowed');
            }
            break;
            
        case 'track_view':
            if ($method === 'POST') {
                trackView();
            } else {
                throw new Exception('Method not allowed');
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function createAnnouncement() {
    global $conn, $isAdmin;
    
    // Check admin privileges
    if (!$isAdmin) {
        throw new Exception('Admin privileges required');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($input['title']) || empty($input['category']) || empty($input['description'])) {
        throw new Exception('Title, category, and description are required');
    }
    
    $title = trim($input['title']);
    $category = trim($input['category']);
    $description = trim($input['description']);
    $status = $input['status'] ?? 'draft';
    $expiration_date = !empty($input['expiration_date']) ? $input['expiration_date'] : null;
    $tags = $input['tags'] ?? [];
    
    // Get admin ID from session
    $username = $_SESSION['username'] ?? null;
    if (!$username) {
        throw new Exception('Admin session not found');
    }
    
    // Get admin ID from database
    $stmt = $conn->prepare("SELECT id FROM admin_accounts WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // If not in admin_accounts table, it's the main admin (username = "Admin")
        // Check if ID 1 exists in admin_accounts
        $stmt_check = $conn->prepare("SELECT id FROM admin_accounts WHERE id = 1");
        $stmt_check->execute();
        $check_result = $stmt_check->get_result();
        
        if ($check_result->num_rows > 0) {
            // ID 1 exists, use it
            $admin_id = 1;
        } else {
            // ID 1 doesn't exist, try to get any admin ID
            $stmt_any = $conn->prepare("SELECT id FROM admin_accounts ORDER BY id ASC LIMIT 1");
            $stmt_any->execute();
            $any_result = $stmt_any->get_result();
            
            if ($any_result->num_rows > 0) {
                // Use the first available admin ID
                $any_admin = $any_result->fetch_assoc();
                $admin_id = $any_admin['id'];
            } else {
                // No admin accounts exist - create a default admin account with ID 1
                // This ensures the foreign key constraint is satisfied
                $default_password = password_hash('Password', PASSWORD_DEFAULT);
                $stmt_create = $conn->prepare("INSERT INTO admin_accounts (id, username, password) VALUES (1, 'Admin', ?)");
                $stmt_create->bind_param("s", $default_password);
                
                if ($stmt_create->execute()) {
                    $admin_id = 1;
                } else {
                    // If creation fails, this is a critical error
                    throw new Exception('Failed to create default admin account. Please ensure admin_accounts table exists and has proper permissions.');
                }
            }
        }
    } else {
        $admin_data = $result->fetch_assoc();
        $admin_id = $admin_data['id'];
    }
    
    // Validate status
    if (!in_array($status, ['draft', 'published', 'archived'])) {
        $status = 'draft';
    }
    
    // Validate category
    $valid_categories = ['Job Fair', 'Hiring Alert', 'Training', 'Update'];
    if (!in_array($category, $valid_categories)) {
        $category = 'Update';
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert announcement
        $stmt = $conn->prepare("INSERT INTO announcements (title, category, description, status, expiration_date, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $title, $category, $description, $status, $expiration_date, $admin_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create announcement');
        }
        
        $announcement_id = $conn->insert_id;
        
        // Insert tags
        if (!empty($tags)) {
            $tag_stmt = $conn->prepare("INSERT INTO announcement_tags (announcement_id, tag_name) VALUES (?, ?)");
            foreach ($tags as $tag) {
                $tag = trim($tag);
                if (!empty($tag)) {
                    $tag_stmt->bind_param("is", $announcement_id, $tag);
                    $tag_stmt->execute();
                }
            }
        }
        
        $conn->commit();
        
        // Send in-app notifications and emails if published
        if ($status === 'published') {
            try {
                include '../Employee/create_notification.php';
                $notification_result = createAnnouncementNotification($title, $description);
                error_log("Announcement notification sent to {$notification_result['sent']}/{$notification_result['total']} users");
            } catch (Exception $e) {
                error_log("Failed to send announcement notifications: " . $e->getMessage());
            }
            try {
                include 'send_announcement_emails.php';
                $email_result = sendAnnouncementEmailsToJobseekers($title, $description);
                error_log("Announcement email sent to {$email_result['sent']}/{$email_result['total']} jobseekers");
            } catch (Exception $e) {
                error_log("Failed to send announcement emails: " . $e->getMessage());
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Announcement created successfully',
            'announcement_id' => $announcement_id
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function readAnnouncements() {
    global $conn;
    
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 10);
    $status = $_GET['status'] ?? '';
    $category = $_GET['category'] ?? '';
    $search = $_GET['search'] ?? '';
    
    $offset = ($page - 1) * $limit;
    
    // Build query
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if (!empty($status)) {
        $where_conditions[] = "a.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    if (!empty($category)) {
        $where_conditions[] = "a.category = ?";
        $params[] = $category;
        $types .= 's';
    }
    
    if (!empty($search)) {
        $where_conditions[] = "(a.title LIKE ? OR a.description LIKE ? OR EXISTS (SELECT 1 FROM announcement_tags at WHERE at.announcement_id = a.id AND at.tag_name LIKE ?))";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'sss';
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM announcements a $where_clause";
    $count_stmt = $conn->prepare($count_query);
    
    if (!empty($params)) {
        $count_stmt->bind_param($types, ...$params);
    }
    
    $count_stmt->execute();
    $total = $count_stmt->get_result()->fetch_assoc()['total'];
    
    // Get announcements with pagination
    $query = "
        SELECT 
            a.id,
            a.title,
            a.category,
            a.description,
            a.status,
            a.date_posted,
            a.expiration_date,
            a.created_at,
            a.updated_at,
            aa.username as created_by_name,
            (SELECT COUNT(*) FROM announcement_views av WHERE av.announcement_id = a.id) as view_count,
            (SELECT COUNT(*) FROM announcement_clicks ac WHERE ac.announcement_id = a.id) as click_count,
            GROUP_CONCAT(at.tag_name SEPARATOR ',') as tags
        FROM announcements a
        LEFT JOIN admin_accounts aa ON a.created_by = aa.id
        LEFT JOIN announcement_tags at ON a.id = at.announcement_id
        $where_clause
        GROUP BY a.id
        ORDER BY a.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $announcements = [];
    
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'announcements' => $announcements,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

function getSingleAnnouncement() {
    global $conn;
    
    $id = intval($_GET['id'] ?? 0);
    
    if (!$id) {
        throw new Exception('Announcement ID is required');
    }
    
    // Get announcement details
    $stmt = $conn->prepare("
        SELECT 
            a.*,
            aa.username as created_by_name,
            GROUP_CONCAT(at.tag_name SEPARATOR ',') as tags
        FROM announcements a
        LEFT JOIN admin_accounts aa ON a.created_by = aa.id
        LEFT JOIN announcement_tags at ON a.id = at.announcement_id
        WHERE a.id = ?
        GROUP BY a.id
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $announcement = $stmt->get_result()->fetch_assoc();
    
    if (!$announcement) {
        throw new Exception('Announcement not found');
    }
    
    // Get attachments
    $stmt = $conn->prepare("SELECT * FROM announcement_attachments WHERE announcement_id = ? ORDER BY uploaded_at ASC");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $attachments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $announcement['attachments'] = $attachments;
    
    echo json_encode([
        'success' => true,
        'announcement' => $announcement
    ]);
}

function updateAnnouncement() {
    global $conn, $isAdmin;
    
    // Check admin privileges
    if (!$isAdmin) {
        throw new Exception('Admin privileges required');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($input['id'] ?? 0);
    if (!$id) {
        throw new Exception('Announcement ID is required');
    }
    
    // Validate required fields
    if (empty($input['title']) || empty($input['category']) || empty($input['description'])) {
        throw new Exception('Title, category, and description are required');
    }
    
    $title = trim($input['title']);
    $category = trim($input['category']);
    $description = trim($input['description']);
    $status = $input['status'] ?? 'draft';
    $expiration_date = !empty($input['expiration_date']) ? $input['expiration_date'] : null;
    $tags = $input['tags'] ?? [];
    
    // Validate status
    if (!in_array($status, ['draft', 'published', 'archived'])) {
        $status = 'draft';
    }
    
    // Validate category
    $valid_categories = ['Job Fair', 'Hiring Alert', 'Training', 'Update'];
    if (!in_array($category, $valid_categories)) {
        $category = 'Update';
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update announcement
        $stmt = $conn->prepare("UPDATE announcements SET title = ?, category = ?, description = ?, status = ?, expiration_date = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("sssssi", $title, $category, $description, $status, $expiration_date, $id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update announcement');
        }
        
        // Delete existing tags
        $conn->query("DELETE FROM announcement_tags WHERE announcement_id = $id");
        
        // Insert new tags
        if (!empty($tags)) {
            $tag_stmt = $conn->prepare("INSERT INTO announcement_tags (announcement_id, tag_name) VALUES (?, ?)");
            foreach ($tags as $tag) {
                $tag = trim($tag);
                if (!empty($tag)) {
                    $tag_stmt->bind_param("is", $id, $tag);
                    $tag_stmt->execute();
                }
            }
        }
        
        $conn->commit();
        
        // Send in-app notifications and emails if published
        if ($status === 'published') {
            try {
                include '../Employee/create_notification.php';
                $notification_result = createAnnouncementNotification($title, $description);
                error_log("Announcement notification sent to {$notification_result['sent']}/{$notification_result['total']} users");
            } catch (Exception $e) {
                error_log("Failed to send announcement notifications: " . $e->getMessage());
            }
            try {
                include 'send_announcement_emails.php';
                $email_result = sendAnnouncementEmailsToJobseekers($title, $description);
                error_log("Announcement email sent to {$email_result['sent']}/{$email_result['total']} jobseekers");
            } catch (Exception $e) {
                error_log("Failed to send announcement emails: " . $e->getMessage());
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Announcement updated successfully'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function deleteAnnouncement() {
    global $conn, $isAdmin;
    
    // Check admin privileges
    if (!$isAdmin) {
        throw new Exception('Admin privileges required');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);
    
    if (!$id) {
        throw new Exception('Announcement ID is required');
    }
    
    // Check if announcement exists
    $stmt = $conn->prepare("SELECT id FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    if (!$stmt->get_result()->fetch_assoc()) {
        throw new Exception('Announcement not found');
    }
    
    // Get attachments to delete files
    $stmt = $conn->prepare("SELECT file_path FROM announcement_attachments WHERE announcement_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $attachments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Delete announcement (cascade will handle related records)
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete announcement');
    }
    
    // Delete attachment files
    foreach ($attachments as $attachment) {
        $file_path = '../' . $attachment['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Announcement deleted successfully'
    ]);
}

function changeStatus() {
    global $conn, $isAdmin;
    
    // Check admin privileges
    if (!$isAdmin) {
        throw new Exception('Admin privileges required');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($input['id'] ?? 0);
    $status = $input['status'] ?? '';
    
    if (!$id) {
        throw new Exception('Announcement ID is required');
    }
    
    if (!in_array($status, ['draft', 'published', 'archived'])) {
        throw new Exception('Invalid status');
    }
    
    $stmt = $conn->prepare("UPDATE announcements SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update status');
    }
    
    // Send in-app notifications and emails if status changed to published
    if ($status === 'published') {
        try {
            // Get announcement details
            $stmt = $conn->prepare("SELECT title, description FROM announcements WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $announcement = $stmt->get_result()->fetch_assoc();
            
            if ($announcement) {
                include '../Employee/create_notification.php';
                $notification_result = createAnnouncementNotification($announcement['title'], $announcement['description']);
                error_log("Announcement notification sent to {$notification_result['sent']}/{$notification_result['total']} users");
                
                include 'send_announcement_emails.php';
                $email_result = sendAnnouncementEmailsToJobseekers($announcement['title'], $announcement['description']);
                error_log("Announcement email sent to {$email_result['sent']}/{$email_result['total']} jobseekers");
            }
        } catch (Exception $e) {
            error_log("Failed to send announcement notifications/emails: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully'
    ]);
}

function uploadFile() {
    global $conn, $isAdmin;
    
    // Check admin privileges
    if (!$isAdmin) {
        throw new Exception('Admin privileges required');
    }
    
    $announcement_id = intval($_POST['announcement_id'] ?? 0);
    
    if (!$announcement_id) {
        throw new Exception('Announcement ID is required');
    }
    
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error');
    }
    
    $file = $_FILES['file'];
    
    // Validate file type
    $allowed_types = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        throw new Exception('Invalid file type. Allowed: PDF, JPG, PNG, DOC, DOCX');
    }
    
    // Validate file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('File size too large. Maximum 5MB allowed');
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
    $upload_path = '../uploads/announcements/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        throw new Exception('Failed to upload file');
    }
    
    // Save file info to database
    $file_path = 'uploads/announcements/' . $filename;
    $file_type = $file['type'];
    $file_size = $file['size'];
    
    $stmt = $conn->prepare("INSERT INTO announcement_attachments (announcement_id, file_name, file_path, file_type, file_size) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $announcement_id, $file['name'], $file_path, $file_type, $file_size);
    
    if (!$stmt->execute()) {
        // Delete uploaded file if database insert fails
        unlink($upload_path);
        throw new Exception('Failed to save file information');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully',
        'file' => [
            'id' => $conn->insert_id,
            'name' => $file['name'],
            'path' => $file_path,
            'type' => $file_type,
            'size' => $file_size
        ]
    ]);
}

function deleteFile() {
    global $conn, $isAdmin;
    
    // Check admin privileges
    if (!$isAdmin) {
        throw new Exception('Admin privileges required');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $file_id = intval($input['file_id'] ?? 0);
    
    if (!$file_id) {
        throw new Exception('File ID is required');
    }
    
    // Get file info
    $stmt = $conn->prepare("SELECT file_path FROM announcement_attachments WHERE id = ?");
    $stmt->bind_param("i", $file_id);
    $stmt->execute();
    
    $file = $stmt->get_result()->fetch_assoc();
    
    if (!$file) {
        throw new Exception('File not found');
    }
    
    // Delete from database
    $stmt = $conn->prepare("DELETE FROM announcement_attachments WHERE id = ?");
    $stmt->bind_param("i", $file_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete file from database');
    }
    
    // Delete physical file
    $file_path = '../' . $file['file_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'File deleted successfully'
    ]);
}

function trackView() {
    global $conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $announcement_id = intval($input['announcement_id'] ?? 0);
    $user_id = $input['user_id'] ?? null;
    
    if (!$announcement_id) {
        throw new Exception('Announcement ID is required');
    }
    
    // Get IP address
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    
    // Insert view record
    $stmt = $conn->prepare("INSERT INTO announcement_views (announcement_id, user_id, ip_address) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $announcement_id, $user_id, $ip_address);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to track view: ' . $stmt->error);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'View tracked successfully'
    ]);
}
?>
