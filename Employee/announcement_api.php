<?php
include 'session_check.php';
include 'db.php';

header('Content-Type: application/json');

// Get the request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'read':
            readAnnouncements();
            break;
            
        case 'track_view':
            if ($method === 'POST') {
                trackView();
            } else {
                throw new Exception('Method not allowed');
            }
            break;
            
        case 'check':
            // Lightweight poll: return count and latest id so jobseeker can detect new announcements
            checkNewAnnouncements();
            break;
            
        case 'test':
            // Simple test endpoint to verify API is working
            echo json_encode([
                'success' => true,
                'message' => 'API is working',
                'session' => $_SESSION,
                'method' => $method,
                'action' => $action,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function checkNewAnnouncements() {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt, COALESCE(MAX(id), 0) as latest_id FROM announcements WHERE status = 'published' AND (expiration_date IS NULL OR expiration_date >= CURDATE())");
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    echo json_encode([
        'success' => true,
        'count' => (int) ($row['cnt'] ?? 0),
        'latest_id' => (int) ($row['latest_id'] ?? 0)
    ]);
}

function readAnnouncements() {
    global $conn;
    
    $status = $_GET['status'] ?? 'published';
    $limit = intval($_GET['limit'] ?? 10);
    $category = $_GET['category'] ?? '';
    $search = $_GET['search'] ?? '';
    
    // Build query
    $query = "SELECT a.*, 
                     GROUP_CONCAT(DISTINCT at.tag_name) as tags,
                     (SELECT COUNT(*) FROM announcement_views av WHERE av.announcement_id = a.id) as view_count
              FROM announcements a 
              LEFT JOIN announcement_tags at ON a.id = at.announcement_id 
              WHERE a.status = ?";
    
    $params = [$status];
    $types = "s";
    
    // Add category filter
    if (!empty($category)) {
        $query .= " AND a.category = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    // Add search filter
    if (!empty($search)) {
        $query .= " AND (a.title LIKE ? OR a.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= "ss";
    }
    
    // Filter out expired announcements
    $query .= " AND (a.expiration_date IS NULL OR a.expiration_date >= CURDATE())";
    
    $query .= " GROUP BY a.id ORDER BY a.date_posted DESC";
    
    if ($limit > 0) {
        $query .= " LIMIT ?";
        $params[] = $limit;
        $types .= "i";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $announcements = [];
    while ($row = $result->fetch_assoc()) {
        // Convert tags string to array
        $row['tags'] = $row['tags'] ? explode(',', $row['tags']) : [];
        
        // Get attachments
        $attachments_query = "SELECT file_name, file_path, file_type FROM announcement_attachments WHERE announcement_id = ?";
        $attachments_stmt = $conn->prepare($attachments_query);
        $attachments_stmt->bind_param("i", $row['id']);
        $attachments_stmt->execute();
        $attachments_result = $attachments_stmt->get_result();
        $row['attachments'] = $attachments_result->fetch_all(MYSQLI_ASSOC);
        
        $announcements[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'announcements' => $announcements
    ]);
}

function trackView() {
    global $conn;
    
    // Enhanced error logging for production debugging
    error_log('TrackView called - Session: ' . json_encode($_SESSION));
    error_log('TrackView called - Input: ' . file_get_contents('php://input'));
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate JSON input
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON decode error: ' . json_last_error_msg());
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }
    
    $announcement_id = intval($input['announcement_id'] ?? 0);
    $user_id = $_SESSION['user_id'] ?? null;
    
    error_log('TrackView - announcement_id: ' . $announcement_id . ', user_id: ' . $user_id);
    
    if (!$announcement_id) {
        throw new Exception('Announcement ID is required');
    }
    
    // Get IP address
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    error_log('TrackView - IP address: ' . $ip_address);
    
    // Check database connection
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Validate user_id exists in jobseeker table if provided
    if ($user_id) {
        $user_check_stmt = $conn->prepare("SELECT user_id FROM jobseeker WHERE user_id = ?");
        if (!$user_check_stmt) {
            throw new Exception('Failed to prepare user check statement: ' . $conn->error);
        }
        
        $user_check_stmt->bind_param("i", $user_id);
        $user_check_stmt->execute();
        $user_check_result = $user_check_stmt->get_result();
        
        if ($user_check_result->num_rows === 0) {
            // User doesn't exist in jobseeker table, set user_id to null
            error_log('User ID ' . $user_id . ' not found in jobseeker table, using IP tracking');
            $user_id = null;
        }
    }
    
    // Check if user already viewed this announcement (by user_id or IP)
    if ($user_id) {
        $check_stmt = $conn->prepare("SELECT id FROM announcement_views WHERE announcement_id = ? AND user_id = ?");
        if (!$check_stmt) {
            throw new Exception('Failed to prepare duplicate check statement: ' . $conn->error);
        }
        $check_stmt->bind_param("ii", $announcement_id, $user_id);
    } else {
        $check_stmt = $conn->prepare("SELECT id FROM announcement_views WHERE announcement_id = ? AND ip_address = ? AND user_id IS NULL");
        if (!$check_stmt) {
            throw new Exception('Failed to prepare IP duplicate check statement: ' . $conn->error);
        }
        $check_stmt->bind_param("is", $announcement_id, $ip_address);
    }
    
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        // Insert view record
        $stmt = $conn->prepare("INSERT INTO announcement_views (announcement_id, user_id, ip_address) VALUES (?, ?, ?)");
        if (!$stmt) {
            throw new Exception('Failed to prepare insert statement: ' . $conn->error);
        }
        
        $stmt->bind_param("iis", $announcement_id, $user_id, $ip_address);
        
        if (!$stmt->execute()) {
            error_log('Insert failed: ' . $stmt->error);
            throw new Exception('Failed to track view: ' . $stmt->error);
        }
        
        error_log('View tracked successfully for announcement ' . $announcement_id);
    } else {
        error_log('View already exists for announcement ' . $announcement_id);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'View tracked successfully',
        'debug' => [
            'announcement_id' => $announcement_id,
            'user_id' => $user_id,
            'ip_address' => $ip_address
        ]
    ]);
}
?>
