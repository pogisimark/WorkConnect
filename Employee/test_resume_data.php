<?php
// Test Resume Data Retrieval - Debug Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Resume Data Test ===\n";

// Test database connection
echo "1. Testing database connection...\n";
try {
    require_once 'db.php';
    if ($conn && $conn->ping()) {
        echo "   ✓ Database connection successful\n";
    } else {
        echo "   ✗ Database connection failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test session
echo "2. Testing session...\n";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "   ✓ User ID in session: " . $_SESSION['user_id'] . "\n";
} else {
    echo "   ✗ No user ID in session\n";
    echo "   Note: This test needs to be run from a logged-in session\n";
}

// Test resume retrieval for ID 6 (from the error)
echo "3. Testing resume retrieval for ID 6...\n";
$resumeId = 6;
$userId = $_SESSION['user_id'] ?? 1; // Use session user_id or default to 1 for testing

try {
    $stmt = $conn->prepare("SELECT * FROM resumes_new WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $resumeId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "   ✗ Resume ID $resumeId not found for user $userId\n";
        
        // Check if resume exists at all
        $stmt2 = $conn->prepare("SELECT * FROM resumes_new WHERE id = ?");
        $stmt2->bind_param("i", $resumeId);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        
        if ($result2->num_rows > 0) {
            $resume = $result2->fetch_assoc();
            echo "   ! Resume exists but belongs to user: " . $resume['user_id'] . "\n";
        } else {
            echo "   ✗ Resume ID $resumeId does not exist at all\n";
        }
        $stmt2->close();
    } else {
        $resume = $result->fetch_assoc();
        echo "   ✓ Resume found: " . $resume['resume_name'] . "\n";
        echo "   ✓ Template ID: " . $resume['template_id'] . "\n";
        echo "   ✓ First Name: " . $resume['firstname'] . "\n";
        echo "   ✓ Last Name: " . $resume['lastname'] . "\n";
    }
    $stmt->close();
} catch (Exception $e) {
    echo "   ✗ Resume retrieval failed: " . $e->getMessage() . "\n";
}

// Test related tables
echo "4. Testing related tables...\n";
try {
    // Test work experience
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM resume_work_experience WHERE resume_id = ?");
    $stmt->bind_param("i", $resumeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    echo "   ✓ Work experience records: $count\n";
    $stmt->close();
    
    // Test education
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM resume_education WHERE resume_id = ?");
    $stmt->bind_param("i", $resumeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    echo "   ✓ Education records: $count\n";
    $stmt->close();
    
    // Test certifications
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM resume_certifications WHERE resume_id = ?");
    $stmt->bind_param("i", $resumeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    echo "   ✓ Certification records: $count\n";
    $stmt->close();
    
} catch (Exception $e) {
    echo "   ✗ Related tables test failed: " . $e->getMessage() . "\n";
}

echo "\n=== Resume data test complete ===\n";
?>
