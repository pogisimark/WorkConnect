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
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $announcement_id = intval($input['announcement_id'] ?? 0);
    $user_id = $_SESSION['user_id'] ?? null;
    
    if (!$announcement_id) {
        throw new Exception('Announcement ID is required');
    }
    
    // Get IP address
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    
    // Check if user already viewed this announcement
    $check_stmt = $conn->prepare("SELECT id FROM announcement_views WHERE announcement_id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $announcement_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        // Insert view record
        $stmt = $conn->prepare("INSERT INTO announcement_views (announcement_id, user_id, ip_address) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $announcement_id, $user_id, $ip_address);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to track view');
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'View tracked successfully'
    ]);
}
?>
