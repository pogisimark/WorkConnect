<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Get NRSP Form Data for Editing
require_once 'session_check.php';
require_once 'db.php';

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['action']) || $input['action'] !== 'get_nrsp_data') {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit();
    }
    
    try {
        // Get the most recent NRSP form data
        $stmt = $conn->prepare("SELECT * FROM jobseeker WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'No NRSP form found']);
            exit();
        }
        
        $nrspData = $result->fetch_assoc();
        $stmt->close();
        
        // Determine if form can be edited (accepted forms are read-only but data is still returned for pre-load/display)
        $status = $nrspData['application_status'] ?? null;
        $statusLower = strtolower($status ?? '');
        $canEdit = $statusLower === 'pending' || $statusLower === 'rejected' || $statusLower === '';
        
        // Return the NRSP form data for all statuses (pending, rejected, accepted) so form can be pre-loaded
        echo json_encode([
            'success' => true,
            'nrsp_data' => $nrspData,
            'can_edit' => $canEdit
        ]);
        
    } catch (Exception $e) {
        error_log("Error fetching NRSP data: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error fetching form data']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
