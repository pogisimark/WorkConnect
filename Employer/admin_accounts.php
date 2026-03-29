<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

require_once 'db.php';

$result = $conn->query('SELECT id, username FROM admin_accounts ORDER BY id ASC');
$admins = [];
while ($row = $result->fetch_assoc()) {
    $admins[] = $row;
}
echo json_encode($admins);
$conn->close();
