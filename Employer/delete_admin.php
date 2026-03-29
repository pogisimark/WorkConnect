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
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}
// Prevent deleting main admin
$stmt = $conn->prepare('SELECT username FROM admin_accounts WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();
if ($username === 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Cannot delete main admin.']);
    exit;
}
$stmt = $conn->prepare('DELETE FROM admin_accounts WHERE id = ?');
$stmt->bind_param('i', $id);
if ($stmt->execute()) {
    // Optional: realign AUTO_INCREMENT (may fail without ALTER privilege; delete still succeeded)
    $result = $conn->query('SELECT MAX(id) as max_id FROM admin_accounts');
    if ($result) {
        $row = $result->fetch_assoc();
        $max_id = $row['max_id'] ?? null;
        $next_id = ($max_id ? (int) $max_id + 1 : 1);
        @$conn->query('ALTER TABLE admin_accounts AUTO_INCREMENT = ' . (int) $next_id);
    }
    echo json_encode(['success' => true, 'message' => 'Admin account removed.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Delete failed.']);
}
$stmt->close();
$conn->close();
