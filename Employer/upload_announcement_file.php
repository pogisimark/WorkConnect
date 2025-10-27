<?php
include 'session_protect.php';
include 'db.php';

header('Content-Type: application/json');

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['file'];
$announcement_id = intval($_POST['announcement_id'] ?? 0);

if (!$announcement_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Announcement ID is required']);
    exit;
}

try {
    // Validate file type
    $allowed_types = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        throw new Exception('Invalid file type. Allowed: PDF, JPG, PNG, DOC, DOCX');
    }
    
    // Validate file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('File size too large. Maximum 5MB allowed');
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
    $upload_path = '../uploads/announcements/' . $filename;
    
    // Create directory if it doesn't exist
    $upload_dir = '../uploads/announcements/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        throw new Exception('Failed to upload file');
    }
    
    // Save file info to database
    $file_path = 'uploads/announcements/' . $filename;
    $file_type = $file['type'];
    $file_size = $file['size'];
    
    $stmt = $conn->prepare("INSERT INTO announcement_attachments (announcement_id, file_name, file_path, file_type, file_size) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $announcement_id, $file['name'], $file_path, $file_type, $file_size);
    
    if (!$stmt->execute()) {
        // Delete uploaded file if database insert fails
        unlink($upload_path);
        throw new Exception('Failed to save file information');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully',
        'file' => [
            'id' => $conn->insert_id,
            'name' => $file['name'],
            'path' => $file_path,
            'type' => $file_type,
            'size' => $file_size,
            'size_formatted' => formatFileSize($file_size)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function formatFileSize($bytes) {
    if ($bytes >= 1024 * 1024) {
        return round($bytes / (1024 * 1024), 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>
