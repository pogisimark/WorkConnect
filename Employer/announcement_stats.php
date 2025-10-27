<?php
include 'session_protect.php';
include 'db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'overview':
            getOverviewStats();
            break;
            
        case 'views':
            getViewStats();
            break;
            
        case 'clicks':
            getClickStats();
            break;
            
        case 'most_viewed':
            getMostViewed();
            break;
            
        case 'recent_activity':
            getRecentActivity();
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function getOverviewStats() {
    global $conn;
    
    // Total announcements
    $total_stmt = $conn->query("SELECT COUNT(*) as total FROM announcements");
    $total = $total_stmt->fetch_assoc()['total'];
    
    // Published announcements
    $published_stmt = $conn->query("SELECT COUNT(*) as published FROM announcements WHERE status = 'published'");
    $published = $published_stmt->fetch_assoc()['published'];
    
    // Draft announcements
    $draft_stmt = $conn->query("SELECT COUNT(*) as draft FROM announcements WHERE status = 'draft'");
    $draft = $draft_stmt->fetch_assoc()['draft'];
    
    // Archived announcements
    $archived_stmt = $conn->query("SELECT COUNT(*) as archived FROM announcements WHERE status = 'archived'");
    $archived = $archived_stmt->fetch_assoc()['archived'];
    
    // Total views
    $views_stmt = $conn->query("SELECT COUNT(*) as total_views FROM announcement_views");
    $total_views = $views_stmt->fetch_assoc()['total_views'];
    
    // Total clicks
    $clicks_stmt = $conn->query("SELECT COUNT(*) as total_clicks FROM announcement_clicks");
    $total_clicks = $clicks_stmt->fetch_assoc()['total_clicks'];
    
    // Views this month
    $month_views_stmt = $conn->query("SELECT COUNT(*) as month_views FROM announcement_views WHERE MONTH(viewed_at) = MONTH(CURRENT_DATE()) AND YEAR(viewed_at) = YEAR(CURRENT_DATE())");
    $month_views = $month_views_stmt->fetch_assoc()['month_views'];
    
    // Clicks this month
    $month_clicks_stmt = $conn->query("SELECT COUNT(*) as month_clicks FROM announcement_clicks WHERE MONTH(clicked_at) = MONTH(CURRENT_DATE()) AND YEAR(clicked_at) = YEAR(CURRENT_DATE())");
    $month_clicks = $month_clicks_stmt->fetch_assoc()['month_clicks'];
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_announcements' => $total,
            'published' => $published,
            'draft' => $draft,
            'archived' => $archived,
            'total_views' => $total_views,
            'total_clicks' => $total_clicks,
            'month_views' => $month_views,
            'month_clicks' => $month_clicks
        ]
    ]);
}

function getViewStats() {
    global $conn;
    
    $announcement_id = intval($_GET['announcement_id'] ?? 0);
    $days = intval($_GET['days'] ?? 30);
    
    if ($announcement_id) {
        // Get views for specific announcement
        $stmt = $conn->prepare("
            SELECT 
                DATE(viewed_at) as date,
                COUNT(*) as views
            FROM announcement_views 
            WHERE announcement_id = ? 
            AND viewed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL ? DAY)
            GROUP BY DATE(viewed_at)
            ORDER BY date ASC
        ");
        $stmt->bind_param("ii", $announcement_id, $days);
    } else {
        // Get total views across all announcements
        $stmt = $conn->prepare("
            SELECT 
                DATE(viewed_at) as date,
                COUNT(*) as views
            FROM announcement_views 
            WHERE viewed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL ? DAY)
            GROUP BY DATE(viewed_at)
            ORDER BY date ASC
        ");
        $stmt->bind_param("i", $days);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $views = [];
    while ($row = $result->fetch_assoc()) {
        $views[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'views' => $views
    ]);
}

function getClickStats() {
    global $conn;
    
    $announcement_id = intval($_GET['announcement_id'] ?? 0);
    $days = intval($_GET['days'] ?? 30);
    
    if ($announcement_id) {
        // Get clicks for specific announcement
        $stmt = $conn->prepare("
            SELECT 
                DATE(clicked_at) as date,
                click_type,
                COUNT(*) as clicks
            FROM announcement_clicks 
            WHERE announcement_id = ? 
            AND clicked_at >= DATE_SUB(CURRENT_DATE(), INTERVAL ? DAY)
            GROUP BY DATE(clicked_at), click_type
            ORDER BY date ASC
        ");
        $stmt->bind_param("ii", $announcement_id, $days);
    } else {
        // Get total clicks across all announcements
        $stmt = $conn->prepare("
            SELECT 
                DATE(clicked_at) as date,
                click_type,
                COUNT(*) as clicks
            FROM announcement_clicks 
            WHERE clicked_at >= DATE_SUB(CURRENT_DATE(), INTERVAL ? DAY)
            GROUP BY DATE(clicked_at), click_type
            ORDER BY date ASC
        ");
        $stmt->bind_param("i", $days);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $clicks = [];
    while ($row = $result->fetch_assoc()) {
        $clicks[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'clicks' => $clicks
    ]);
}

function getMostViewed() {
    global $conn;
    
    $limit = intval($_GET['limit'] ?? 10);
    
    $stmt = $conn->prepare("
        SELECT 
            a.id,
            a.title,
            a.category,
            a.status,
            a.date_posted,
            COUNT(av.id) as view_count,
            COUNT(ac.id) as click_count
        FROM announcements a
        LEFT JOIN announcement_views av ON a.id = av.announcement_id
        LEFT JOIN announcement_clicks ac ON a.id = ac.announcement_id
        WHERE a.status = 'published'
        GROUP BY a.id
        ORDER BY view_count DESC
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $announcements = [];
    
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'announcements' => $announcements
    ]);
}

function getRecentActivity() {
    global $conn;
    
    $limit = intval($_GET['limit'] ?? 20);
    
    // Get recent views
    $views_stmt = $conn->prepare("
        SELECT 
            'view' as type,
            av.viewed_at as activity_date,
            a.title as announcement_title,
            a.id as announcement_id,
            av.user_id,
            av.ip_address
        FROM announcement_views av
        JOIN announcements a ON av.announcement_id = a.id
        ORDER BY av.viewed_at DESC
        LIMIT ?
    ");
    $views_stmt->bind_param("i", $limit);
    $views_stmt->execute();
    
    $views = $views_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get recent clicks
    $clicks_stmt = $conn->prepare("
        SELECT 
            'click' as type,
            ac.clicked_at as activity_date,
            a.title as announcement_title,
            a.id as announcement_id,
            ac.user_id,
            ac.click_type,
            NULL as ip_address
        FROM announcement_clicks ac
        JOIN announcements a ON ac.announcement_id = a.id
        ORDER BY ac.clicked_at DESC
        LIMIT ?
    ");
    $clicks_stmt->bind_param("i", $limit);
    $clicks_stmt->execute();
    
    $clicks = $clicks_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Combine and sort by date
    $activities = array_merge($views, $clicks);
    usort($activities, function($a, $b) {
        return strtotime($b['activity_date']) - strtotime($a['activity_date']);
    });
    
    // Limit to requested number
    $activities = array_slice($activities, 0, $limit);
    
    echo json_encode([
        'success' => true,
        'activities' => $activities
    ]);
}
?>
