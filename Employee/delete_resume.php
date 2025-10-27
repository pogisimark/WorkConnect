<?php
// Delete Resume - NEW VERSION WITH SPECIFIC COLUMNS
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
    $resumeId = (int)($input['resume_id'] ?? 0);
    
    if ($resumeId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid resume ID']);
        exit();
    }
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Get profile image path before deletion
        $stmt = $conn->prepare("SELECT profile_image FROM resumes_new WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $resumeId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Resume not found');
        }
        
        $resume = $result->fetch_assoc();
        $stmt->close();
        
        // Delete related records (cascade should handle this, but being explicit)
        $conn->query("DELETE FROM resume_work_experience WHERE resume_id = $resumeId");
        $conn->query("DELETE FROM resume_education WHERE resume_id = $resumeId");
        $conn->query("DELETE FROM resume_certifications WHERE resume_id = $resumeId");
        
        // Delete main resume record
        $stmt = $conn->prepare("DELETE FROM resumes_new WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $resumeId, $userId);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete resume');
        }
        
        $stmt->close();
        
        // Delete profile image file if it exists
        if ($resume['profile_image'] && file_exists($resume['profile_image'])) {
            unlink($resume['profile_image']);
        }
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Resume deleted successfully']);
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error deleting resume: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>