<?php
// Get Resume Data for Editing - NEW VERSION WITH SPECIFIC COLUMNS
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
        // Get main resume data
        $stmt = $conn->prepare("SELECT * FROM resumes_new WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $resumeId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Resume not found']);
            exit();
        }
        
        $resume = $result->fetch_assoc();
        $stmt->close();
        
        // Get work experience
        $workExpStmt = $conn->prepare("SELECT * FROM resume_work_experience WHERE resume_id = ? ORDER BY sort_order");
        $workExpStmt->bind_param("i", $resumeId);
        $workExpStmt->execute();
        $workExpResult = $workExpStmt->get_result();
        $workExperience = [];
        while ($exp = $workExpResult->fetch_assoc()) {
            $workExperience[] = $exp;
        }
        $workExpStmt->close();
        
        // Get education
        $eduStmt = $conn->prepare("SELECT * FROM resume_education WHERE resume_id = ? ORDER BY sort_order");
        $eduStmt->bind_param("i", $resumeId);
        $eduStmt->execute();
        $eduResult = $eduStmt->get_result();
        $education = [];
        while ($edu = $eduResult->fetch_assoc()) {
            $education[] = $edu;
        }
        $eduStmt->close();
        
        // Get certifications
        $certStmt = $conn->prepare("SELECT * FROM resume_certifications WHERE resume_id = ? ORDER BY sort_order");
        $certStmt->bind_param("i", $resumeId);
        $certStmt->execute();
        $certResult = $certStmt->get_result();
        $certifications = [];
        while ($cert = $certResult->fetch_assoc()) {
            $certifications[] = $cert;
        }
        $certStmt->close();
        
        // Combine all data
        $resume['work_experience'] = $workExperience;
        $resume['education'] = $education;
        $resume['certifications'] = $certifications;
        
        echo json_encode(['success' => true, 'resume' => $resume]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>