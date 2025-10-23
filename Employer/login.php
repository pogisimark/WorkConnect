<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');

if ($username === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Username and password required.']);
    exit;
}

// Check for main admin
if ($username === 'Admin' && $password === 'Password') {
    $_SESSION['username'] = 'Admin';
    echo json_encode(['success' => true, 'isMainAdmin' => true, 'redirect' => 'Dashboard.php']);
    exit;
}

// Check for other admins in DB
$stmt = $conn->prepare('SELECT id, password FROM admin_accounts WHERE username = ?');
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    // Check if password is hashed (from password reset) or plain text (from admin creation)
    if (password_verify($password, $user['password']) || $password === $user['password']) {
        $_SESSION['username'] = $username;
        echo json_encode(['success' => true, 'isMainAdmin' => false, 'redirect' => 'Dashboard.php']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
}
$stmt->close();
$conn->close();
