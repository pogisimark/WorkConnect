<?php
// Debug script for image upload
require_once 'session_check.php';
require_once 'db.php';

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

echo "<h2>Image Upload Debug</h2>";
echo "<p>User ID: " . $userId . "</p>";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['profile_image'])) {
    echo "<h3>POST Data:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h3>FILES Data:</h3>";
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";
    
    if ($_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        echo "<p style='color: green;'>✓ File upload successful</p>";
        
        $uploadDir = 'uploads/profile_images/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
            echo "<p>Created upload directory: " . $uploadDir . "</p>";
        }
        
        $fileExtension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $fileName = 'profile_' . $userId . '_' . time() . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        echo "<p>Target file path: " . $filePath . "</p>";
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $filePath)) {
            echo "<p style='color: green;'>✓ File moved successfully to: " . $filePath . "</p>";
            
            // Test database insert
            $stmt = $conn->prepare("INSERT INTO resumes (user_id, template_id, personal_info, work_experience, education, skills, certifications, resume_name, profile_image, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $testPersonalInfo = json_encode(['firstname' => 'Test', 'lastname' => 'User']);
            $testWorkExp = json_encode([]);
            $testEducation = json_encode([]);
            $testSkills = json_encode('Test Skills');
            $testCertifications = json_encode([]);
            $testResumeName = 'Test Resume';
            $testIsDefault = 0;
            $testTemplateId = 1;
            
            $stmt->bind_param("iisssssssi", $userId, $testTemplateId, $testPersonalInfo, $testWorkExp, $testEducation, $testSkills, $testCertifications, $testResumeName, $filePath, $testIsDefault);
            
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✓ Database insert successful</p>";
                echo "<p>Resume ID: " . $conn->insert_id . "</p>";
            } else {
                echo "<p style='color: red;'>✗ Database insert failed: " . $conn->error . "</p>";
            }
            $stmt->close();
            
        } else {
            echo "<p style='color: red;'>✗ Failed to move uploaded file</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ File upload error: " . $_FILES['profile_image']['error'] . "</p>";
        echo "<p>Error codes: UPLOAD_ERR_OK = " . UPLOAD_ERR_OK . "</p>";
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <h3>Test Image Upload</h3>
    <input type="file" name="profile_image" accept="image/*" required>
    <br><br>
    <button type="submit">Upload Test Image</button>
</form>

<?php
// Show existing images
echo "<h3>Existing Images:</h3>";
$uploadDir = 'uploads/profile_images/';
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<p>" . $file . " - <a href='" . $uploadDir . $file . "' target='_blank'>View</a></p>";
        }
    }
}

// Show recent resumes
echo "<h3>Recent Resumes:</h3>";
$stmt = $conn->prepare("SELECT id, resume_name, profile_image, created_at FROM resumes WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Profile Image</th><th>Created</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['resume_name']) . "</td>";
        echo "<td>" . ($row['profile_image'] ? htmlspecialchars($row['profile_image']) : 'NULL') . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No resumes found</p>";
}
$stmt->close();

$conn->close();
?>
