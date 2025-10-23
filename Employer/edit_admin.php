<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id'] ?? 0);
$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');
if ($id <= 0 || $username === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}
// Prevent editing main admin username
$stmt = $conn->prepare('SELECT username FROM admin_accounts WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($oldUsername);
$stmt->fetch();
$stmt->close();
if ($oldUsername === 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Cannot edit main admin.']);
    exit;
}
$stmt = $conn->prepare('UPDATE admin_accounts SET username = ?, password = ? WHERE id = ?');
$stmt->bind_param('ssi', $username, $password, $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed.']);
}
$stmt->close();
$conn->close();
