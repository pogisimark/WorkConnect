<?php
// Get User's Resumes for AI Generator
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
    $action = $input['action'] ?? '';
    
    if ($action === 'list_resumes') {
        try {
            // Get all resumes for the current user
            $stmt = $conn->prepare("SELECT id, resume_name, firstname, lastname, template_id, created_at, is_default FROM resumes_new WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $resumes = [];
            while ($row = $result->fetch_assoc()) {
                $resumes[] = [
                    'id' => $row['id'],
                    'resume_name' => $row['resume_name'],
                    'firstname' => $row['firstname'],
                    'lastname' => $row['lastname'],
                    'template_id' => $row['template_id'],
                    'created_at' => $row['created_at'],
                    'is_default' => $row['is_default'],
                    'display_name' => !empty($row['resume_name']) ? $row['resume_name'] : ($row['firstname'] . ' ' . $row['lastname'])
                ];
            }
            $stmt->close();
            
            echo json_encode([
                'success' => true, 
                'resumes' => $resumes,
                'count' => count($resumes)
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>