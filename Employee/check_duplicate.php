<?php
header('Content-Type: application/json');

// Include database connection
require_once 'db.php';

// Get the form data
$surname = trim($_POST['surname'] ?? '');
$firstname = trim($_POST['firstname'] ?? '');
$middlename = trim($_POST['middlename'] ?? '');
$suffix = trim($_POST['suffix'] ?? '');

// Validate required fields
if (empty($surname) || empty($firstname)) {
    echo json_encode(['duplicate' => false, 'error' => 'Required fields missing']);
    exit;
}

try {
    // Prepare the SQL query to check for case-insensitive exact match
    // We need to check for exact combination of all four fields (case-insensitive)
    // Handle empty fields by using COALESCE to treat empty strings as NULL for comparison
    $sql = "SELECT surname, firstname, middlename, suffix 
            FROM jobseeker 
            WHERE LOWER(surname) = LOWER(?) 
            AND LOWER(firstname) = LOWER(?) 
            AND COALESCE(NULLIF(NULLIF(middlename, ''), 'n/a'), '') = COALESCE(NULLIF(NULLIF(?, ''), 'n/a'), '')
            AND COALESCE(NULLIF(NULLIF(suffix, ''), 'n/a'), '') = COALESCE(NULLIF(NULLIF(?, ''), 'n/a'), '')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $surname, $firstname, $middlename, $suffix);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $existingRecord = $result->fetch_assoc();
        
        // Format the existing name for display
        $existingName = $existingRecord['firstname'];
        if (!empty($existingRecord['middlename']) && $existingRecord['middlename'] !== 'n/a') {
            $existingName .= ' ' . $existingRecord['middlename'];
        }
        $existingName .= ' ' . $existingRecord['surname'];
        if (!empty($existingRecord['suffix']) && $existingRecord['suffix'] !== 'n/a') {
            $existingName .= ' ' . $existingRecord['suffix'];
        }
        
        echo json_encode([
            'duplicate' => true,
            'existingName' => $existingName
        ]);
    } else {
        echo json_encode(['duplicate' => false]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Duplicate check error: " . $e->getMessage());
    echo json_encode(['duplicate' => false, 'error' => 'Database error']);
}

$conn->close();
?>
