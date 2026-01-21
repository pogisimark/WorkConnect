<?php
session_start();
require_once 'db.php';

// Only allow if main admin is logged in
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');

if ($username === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Username and password required.']);
    exit;
}

// SECURITY: Prevent creating admin account with super admin username
if (strtolower($username) === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Cannot create account with super admin username.']);
    exit;
}

// Prevent duplicate usernames
$stmt = $conn->prepare('SELECT id FROM admin_accounts WHERE username = ?');
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username already exists.']);
    exit;
}
$stmt->close();

// Insert new admin (plain password for demo; use password_hash in production)
$stmt = $conn->prepare('INSERT INTO admin_accounts (username, password) VALUES (?, ?)');
$stmt->bind_param('ss', $username, $password);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Admin account created.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
$stmt->close();
$conn->close();
