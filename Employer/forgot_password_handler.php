<?php
header('Content-Type: application/json');
require_once 'db.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['username']) || empty(trim($input['username']))) {
    echo json_encode(['success' => false, 'message' => 'Username is required']);
    exit;
}

$username = trim($input['username']);

// Check if username exists in admin_accounts table
$stmt = $conn->prepare("SELECT id, username FROM admin_accounts WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    // Username exists
    echo json_encode([
        'success' => true, 
        'message' => 'Username found',
        'user_id' => $user['id'],
        'username' => $user['username']
    ]);
} else {
    // Username not found
    echo json_encode([
        'success' => false, 
        'message' => 'Username not found. Please check your username and try again.'
    ]);
}

$stmt->close();
$conn->close();
?>
